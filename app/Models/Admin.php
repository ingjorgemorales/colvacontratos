<?php
namespace App\Models;

use App\Core\Database;
use Throwable;

final class Admin {
    public static function roles(): array { return Database::pdo()->query("SELECT * FROM roles ORDER BY id")->fetchAll(); }
    public static function users(): array { return Database::pdo()->query("SELECT u.*, r.name role_name, r.description role_description FROM users u LEFT JOIN roles r ON r.id=u.role_id ORDER BY u.id DESC")->fetchAll(); }
    public static function createUser(array $d): ?string {
        $name=trim($d['name']??''); $email=trim($d['email']??''); $role=(int)($d['role_id']??1); $pass=trim($d['password']??'');
        if($name===''||$email==='') return null; if($pass==='') $pass=self::generateInitialPassword();
        Database::pdo()->prepare("INSERT INTO users(role_id,name,email,password_hash,active) VALUES(?,?,?,?,1)")->execute([$role,$name,$email,password_hash($pass,PASSWORD_BCRYPT)]);
        return $pass;
    }
    public static function updateUser(int $id, array $d): void {
        Database::pdo()->prepare("UPDATE users SET role_id=?, name=?, email=?, active=?, updated_at=NOW() WHERE id=?")->execute([(int)($d['role_id']??1), trim($d['name']??''), trim($d['email']??''), isset($d['active'])?1:0, $id]);
    }
    public static function providers(string $q=''): array {
        $pdo=Database::pdo();
        if($q!=='') { $like="%$q%"; $st=$pdo->prepare("SELECT * FROM providers WHERE name LIKE ? OR document_number LIKE ? OR email LIKE ? ORDER BY name"); $st->execute([$like,$like,$like]); return $st->fetchAll(); }
        return $pdo->query("SELECT * FROM providers ORDER BY name")->fetchAll();
    }
    public static function createProvider(array $d): void { Database::pdo()->prepare("INSERT INTO providers(document_number,verification_digit,name,address,phone,email,city,active) VALUES(?,?,?,?,?,?,?,1)")->execute([trim($d['document_number']??''),trim($d['verification_digit']??''),trim($d['name']??''),trim($d['address']??''),trim($d['phone']??''),trim($d['email']??''),trim($d['city']??'')]); }
    public static function updateProvider(int $id,array $d): void { Database::pdo()->prepare("UPDATE providers SET document_number=?, verification_digit=?, name=?, address=?, phone=?, email=?, city=?, active=? WHERE id=?")->execute([trim($d['document_number']??''),trim($d['verification_digit']??''),trim($d['name']??''),trim($d['address']??''),trim($d['phone']??''),trim($d['email']??''),trim($d['city']??''),isset($d['active'])?1:0,$id]); }
    public static function supervisors(): array { return Database::pdo()->query("SELECT s.*, u.name user_name FROM contract_supervisors s LEFT JOIN users u ON u.id=s.user_id ORDER BY s.full_name")->fetchAll(); }
    public static function createSupervisor(array $d): void { $uid=($d['user_id']??'')!==''?(int)$d['user_id']:null; Database::pdo()->prepare("INSERT INTO contract_supervisors(user_id,document_number,full_name,email,active) VALUES(?,?,?,?,1)")->execute([$uid,trim($d['document_number']??''),trim($d['full_name']??''),trim($d['email']??'')]); }
    public static function updateSupervisor(int $id,array $d): void { $uid=($d['user_id']??'')!==''?(int)$d['user_id']:null; Database::pdo()->prepare("UPDATE contract_supervisors SET user_id=?, document_number=?, full_name=?, email=?, active=? WHERE id=?")->execute([$uid,trim($d['document_number']??''),trim($d['full_name']??''),trim($d['email']??''),isset($d['active'])?1:0,$id]); }

    private static function tableExists(string $table): bool {
        static $cache=[]; if(isset($cache[$table])) return $cache[$table];
        try { $st=Database::pdo()->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?"); $st->execute([$table]); return $cache[$table]=((int)$st->fetchColumn()>0); } catch(Throwable $e) { return $cache[$table]=false; }
    }
    private static function columnExists(string $table,string $column): bool {
        static $cache=[]; $key=$table.'.'.$column; if(isset($cache[$key])) return $cache[$key];
        try { $st=Database::pdo()->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?"); $st->execute([$table,$column]); return $cache[$key]=((int)$st->fetchColumn()>0); } catch(Throwable $e) { return $cache[$key]=false; }
    }
    private static function catalogTables(): array {
        return [
            'contract_types'=>['name'=>'Tipos de contrato maestro','category'=>'Contratos','description'=>'Cliente, Proveedor, Subcontratista y futuras categorías maestras.','icon'=>'bi-diagram-3','priority'=>1],
            'contract_statuses'=>['name'=>'Estados de contrato','category'=>'Contratos','description'=>'Estados operativos del ciclo contractual.','icon'=>'bi-flag','priority'=>2],
            'contract_tipo_compromiso'=>['name'=>'Tipo compromiso','category'=>'Contratos','description'=>'Clasificación legal del compromiso contractual.','icon'=>'bi-file-earmark-text','priority'=>3],
            'contract_modalidad_seleccion'=>['name'=>'Modalidad de selección','category'=>'Contratos','description'=>'Modalidades de selección y contratación.','icon'=>'bi-ui-checks-grid','priority'=>4],
            'contract_tema_gasto'=>['name'=>'Tema del gasto','category'=>'Contratos','description'=>'Tema al que corresponde el gasto.','icon'=>'bi-tags','priority'=>5],
            'contract_tipologia_especifica'=>['name'=>'Tipología específica','category'=>'Contratos','description'=>'Tipologías específicas del contrato.','icon'=>'bi-list-stars','priority'=>6],
            'contract_regimen_contratacion'=>['name'=>'Régimen contratación','category'=>'Contratos','description'=>'Régimen aplicable a la contratación.','icon'=>'bi-bank','priority'=>7],
            'contract_inicio_contrato'=>['name'=>'Inicio contrato','category'=>'Contratos','description'=>'Opciones de inicio o activación del contrato.','icon'=>'bi-play-circle','priority'=>8],
            'contract_prorroga_automatica'=>['name'=>'Prórroga automática','category'=>'Contratos','description'=>'Opciones para prórroga automática.','icon'=>'bi-arrow-repeat','priority'=>9],
            'departments'=>['name'=>'Departamentos','category'=>'Organizacional','description'=>'Departamentos geográficos o administrativos.','icon'=>'bi-map','priority'=>1],
            'provider_cities'=>['name'=>'Ciudades','category'=>'Organizacional','description'=>'Ciudades usadas en proveedores y contratos.','icon'=>'bi-geo-alt','priority'=>2],
            'areas'=>['name'=>'Áreas','category'=>'Organizacional','description'=>'Áreas internas responsables.','icon'=>'bi-building','priority'=>3],
            'cost_centers'=>['name'=>'CECOS','category'=>'Organizacional','description'=>'Centros de costo para control interno.','icon'=>'bi-bullseye','priority'=>4],
            'provider_types'=>['name'=>'Tipos de proveedor','category'=>'Proveedores','description'=>'Naturaleza o categoría del proveedor.','icon'=>'bi-person-badge','priority'=>1],
            'catalog_tipo_contratista'=>['name'=>'Tipo contratista','category'=>'Proveedores','description'=>'Contratista, consorcio, unión temporal y demás tipos legales.','icon'=>'bi-diagram-3','priority'=>2],
            'catalog_tipo_persona'=>['name'=>'Tipo persona','category'=>'Proveedores','description'=>'Persona natural, jurídica o jurídica extranjera.','icon'=>'bi-person-vcard','priority'=>3],
            'catalog_naturaleza'=>['name'=>'Naturaleza','category'=>'Proveedores','description'=>'Naturaleza privada, pública o sin ánimo de lucro.','icon'=>'bi-bank','priority'=>4],
            'catalog_clasificacion'=>['name'=>'Clasificación','category'=>'Proveedores','description'=>'Clasificación legal del contratista.','icon'=>'bi-tags','priority'=>5],
            'catalog_nacionalidad_contratista'=>['name'=>'Nacionalidad del contratista','category'=>'Proveedores','description'=>'Nacional o extranjero.','icon'=>'bi-globe-americas','priority'=>6],
            'catalog_clase_contratista'=>['name'=>'Clase contratista','category'=>'Proveedores','description'=>'Clase del contratista dentro del proceso.','icon'=>'bi-shield-check','priority'=>7],
            'contract_supervisors'=>['name'=>'Supervisores','category'=>'Proveedores','description'=>'Supervisores responsables del seguimiento.','icon'=>'bi-person-check','priority'=>8],
            'contract_tipo_gasto'=>['name'=>'Tipo gasto','category'=>'Financiero','description'=>'Tipo de gasto presupuestal.','icon'=>'bi-cash-stack','priority'=>1],
            'contract_origen_presupuesto'=>['name'=>'Origen del presupuesto','category'=>'Financiero','description'=>'Origen presupuestal del contrato.','icon'=>'bi-wallet2','priority'=>2],
            'contract_origen_recursos'=>['name'=>'Origen de recursos','category'=>'Financiero','description'=>'Fuente de los recursos asociados.','icon'=>'bi-piggy-bank','priority'=>3],
            'contract_tipo_moneda'=>['name'=>'Tipo de moneda','category'=>'Financiero','description'=>'Monedas permitidas en contratos.','icon'=>'bi-currency-exchange','priority'=>4],
            'contract_unidad_plazo'=>['name'=>'Unidad plazo ejecución','category'=>'Ejecución','description'=>'Días, meses, años y demás unidades de plazo.','icon'=>'bi-calendar-range','priority'=>1],
            'contract_tipo_control'=>['name'=>'Tipo control a la ejecución','category'=>'Ejecución','description'=>'Formas de control y seguimiento.','icon'=>'bi-clipboard-check','priority'=>2],
            'contract_procedimiento'=>['name'=>'Procedimiento','category'=>'Ejecución','description'=>'Procedimientos asociados a la ejecución.','icon'=>'bi-diagram-2','priority'=>3],
            'contract_document_types'=>['name'=>'Tipos de documento','category'=>'Documental','description'=>'Tipos documentales requeridos.','icon'=>'bi-folder2-open','priority'=>1],
            'catalog_groups'=>['name'=>'Grupos heredados','category'=>'Sistema','description'=>'Grupos paramétricos existentes de versiones anteriores.','icon'=>'bi-collection','priority'=>1],
            'catalog_items'=>['name'=>'Ítems heredados','category'=>'Sistema','description'=>'Opciones antiguas migradas al panel general.','icon'=>'bi-list-ul','priority'=>2],
        ];
    }
    private static function availableCatalogTables(): array { $out=[]; foreach(self::catalogTables() as $table=>$cfg){ if(self::tableExists($table)) $out[$table]=$cfg; } return $out; }
    private static function safeCatalogTable(string $table): string { $tables=self::availableCatalogTables(); return isset($tables[$table])?$table:(array_key_first($tables)?:'contract_tipo_compromiso'); }
    public static function groups(): array { $out=[]; foreach(self::availableCatalogTables() as $table=>$cfg){ $out[]=['id'=>$table,'code'=>$table,'name'=>$cfg['name'],'category'=>$cfg['category'],'description'=>$cfg['description'],'icon'=>$cfg['icon'],'priority'=>$cfg['priority']]; } usort($out, fn($a,$b)=>[$a['category'],$a['priority'],$a['name']] <=> [$b['category'],$b['priority'],$b['name']]); return $out; }
    public static function categoryCards(): array { $meta=['Contratos'=>['icon'=>'bi-file-earmark-text','description'=>'Tipos, estados, modalidades, régimen y clasificación contractual.'],'Organizacional'=>['icon'=>'bi-building','description'=>'Ciudades, departamentos, áreas y centros de costo.'],'Proveedores'=>['icon'=>'bi-people','description'=>'Tipos de proveedor, supervisores y datos maestros relacionados.'],'Financiero'=>['icon'=>'bi-cash-coin','description'=>'Monedas, tipos de gasto, presupuesto y recursos.'],'Ejecución'=>['icon'=>'bi-calendar-check','description'=>'Plazos, controles, procedimientos y seguimiento.'],'Documental'=>['icon'=>'bi-folder2-open','description'=>'Tipos documentales y parametría documental.'],'Sistema'=>['icon'=>'bi-database','description'=>'Catálogos heredados y compatibilidad con versiones anteriores.']]; $cards=[]; foreach(self::groups() as $g){ $cat=$g['category']; if(!isset($cards[$cat])) $cards[$cat]=['name'=>$cat,'count'=>0,'icon'=>$meta[$cat]['icon']??'bi-grid','description'=>$meta[$cat]['description']??'Catálogos del sistema.']; $cards[$cat]['count']++; } return array_values($cards); }
    public static function catalogStats(): array { $groups=self::groups(); $active=0; $items=0; foreach($groups as $g){ $t=$g['id']; try{$items+=(int)Database::pdo()->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();}catch(Throwable $e){} try{ if(self::columnExists($t,'active')) $active+=(int)Database::pdo()->query("SELECT COUNT(*) FROM `{$t}` WHERE active=1")->fetchColumn(); else $active+=(int)Database::pdo()->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn(); }catch(Throwable $e){} } return ['catalogs'=>count($groups),'items'=>$items,'active'=>$active]; }
    public static function items(string $table='', string $q=''): array {
        $tables=self::availableCatalogTables(); if(!$tables) return [];
        $fetch=function(string $t,array $cfg) use($q): array { $hasCode=self::columnExists($t,'code'); $hasSort=self::columnExists($t,'sort_order'); $hasActive=self::columnExists($t,'active'); $nameCol=self::columnExists($t,'name')?'name':(self::columnExists($t,'full_name')?'full_name':'id'); $codeExpr=$hasCode?'code':'CAST(id AS CHAR)'; $sortExpr=$hasSort?'sort_order':'id'; $activeExpr=$hasActive?'active':'1'; $where=''; $params=[]; if($q!==''){ $where=" WHERE {$nameCol} LIKE ?"; $params[]="%$q%"; if($hasCode){$where.=" OR code LIKE ?"; $params[]="%$q%";} } $sql="SELECT id, {$codeExpr} AS code, {$nameCol} AS name, {$sortExpr} AS sort_order, {$activeExpr} AS active, '{$t}' AS group_id, '{$cfg['name']}' AS group_name, '{$cfg['category']}' AS category, '{$cfg['description']}' AS group_description FROM `{$t}`{$where} ORDER BY sort_order,name"; $st=Database::pdo()->prepare($sql); $st->execute($params); return $st->fetchAll(); };
        if($table!=='' && isset($tables[$table])) return $fetch($table,$tables[$table]); $all=[]; foreach($tables as $t=>$cfg){ try{$all=array_merge($all,$fetch($t,$cfg));}catch(Throwable $e){} } return $all;
    }
    public static function createGroup(array $d): void { }
    public static function createItem(array $d): void { $table=self::safeCatalogTable((string)($d['group_id']??'')); $cols=['name']; $vals=[trim($d['name']??'')]; if(self::columnExists($table,'code')){$cols[]='code';$vals[]=trim($d['code']??'');} if(self::columnExists($table,'sort_order')){$cols[]='sort_order';$vals[]=(int)($d['sort_order']??0);} if(self::columnExists($table,'active')){$cols[]='active';$vals[]=1;} $ph=implode(',',array_fill(0,count($cols),'?')); Database::pdo()->prepare("INSERT INTO `{$table}` (`".implode('`,`',$cols)."`) VALUES ({$ph})")->execute($vals); }
    public static function updateItem(int $id,array $d): void { $table=self::safeCatalogTable((string)($d['group_id']??'')); $set=['name=?']; $vals=[trim($d['name']??'')]; if(self::columnExists($table,'code')){$set[]='code=?';$vals[]=trim($d['code']??'');} if(self::columnExists($table,'sort_order')){$set[]='sort_order=?';$vals[]=(int)($d['sort_order']??0);} if(self::columnExists($table,'active')){$set[]='active=?';$vals[]=isset($d['active'])?1:0;} $vals[]=$id; Database::pdo()->prepare("UPDATE `{$table}` SET ".implode(', ',$set)." WHERE id=?")->execute($vals); }

    private static function generateInitialPassword(int $length = 14): string {
        $groups = ['ABCDEFGHJKLMNPQRSTUVWXYZ','abcdefghijkmnopqrstuvwxyz','23456789','!@#$%*?'];
        $all = implode('', $groups);
        $chars = [];
        foreach($groups as $group) $chars[] = $group[random_int(0, strlen($group)-1)];
        while(count($chars) < $length) $chars[] = $all[random_int(0, strlen($all)-1)];
        for($i=count($chars)-1; $i>0; $i--) { $j=random_int(0,$i); [$chars[$i],$chars[$j]]=[$chars[$j],$chars[$i]]; }
        return implode('', $chars);
    }
}
