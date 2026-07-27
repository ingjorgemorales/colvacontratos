<?php
namespace App\Models;
use App\Core\Database;

final class Provider {
    /** Total de proveedores que cumplen la búsqueda (para la paginación). */
    public static function countAll(string $q=''): int {
        $base = 'SELECT COUNT(*) FROM providers p
                 LEFT JOIN provider_cities pc ON pc.id=p.city_id
                 LEFT JOIN catalog_tipo_contratista tcon ON tcon.id=p.tipo_contratista_id
                 LEFT JOIN catalog_tipo_persona tper ON tper.id=p.tipo_persona_id
                 LEFT JOIN catalog_clasificacion cla ON cla.id=p.clasificacion_id';
        if ($q !== '') {
            $like='%'.$q.'%';
            $st=Database::pdo()->prepare($base.' WHERE (p.document_number LIKE ? OR p.name LIKE ? OR p.email LIKE ? OR p.city LIKE ? OR pc.name LIKE ? OR tcon.name LIKE ? OR tper.name LIKE ? OR cla.name LIKE ?)');
            $st->execute([$like,$like,$like,$like,$like,$like,$like,$like]);
            return (int)$st->fetchColumn();
        }
        return (int)Database::pdo()->query($base)->fetchColumn();
    }

    public static function all(string $q='', int $limit=0, int $offset=0): array {
        $lim = $limit > 0 ? " LIMIT {$limit} OFFSET {$offset}" : '';
        $sql = 'SELECT p.*, pc.name AS city_name, pt.name AS type_name,
                       tcon.name AS tipo_contratista_name,
                       tper.name AS tipo_persona_name,
                       nat.name AS naturaleza_name,
                       cla.name AS clasificacion_name,
                       nac.name AS nacionalidad_contratista_name,
                       ccl.name AS clase_contratista_name
                FROM providers p
                LEFT JOIN provider_cities pc ON pc.id=p.city_id
                LEFT JOIN provider_types pt ON pt.id=p.provider_type_id
                LEFT JOIN catalog_tipo_contratista tcon ON tcon.id=p.tipo_contratista_id
                LEFT JOIN catalog_tipo_persona tper ON tper.id=p.tipo_persona_id
                LEFT JOIN catalog_naturaleza nat ON nat.id=p.naturaleza_id
                LEFT JOIN catalog_clasificacion cla ON cla.id=p.clasificacion_id
                LEFT JOIN catalog_nacionalidad_contratista nac ON nac.id=p.nacionalidad_contratista_id
                LEFT JOIN catalog_clase_contratista ccl ON ccl.id=p.clase_contratista_id';
        if ($q !== '') {
            $like='%'.$q.'%';
            $st=Database::pdo()->prepare($sql.' WHERE (p.document_number LIKE ? OR p.name LIKE ? OR p.email LIKE ? OR p.city LIKE ? OR pc.name LIKE ? OR tcon.name LIKE ? OR tper.name LIKE ? OR cla.name LIKE ?) ORDER BY p.name'.$lim);
            $st->execute([$like,$like,$like,$like,$like,$like,$like,$like]); return $st->fetchAll();
        }
        return Database::pdo()->query($sql.' ORDER BY p.name'.$lim)->fetchAll();
    }
    public static function exportRows(string $q=""): array { return self::all($q); }
    public static function find(int $id): ?array { $st=Database::pdo()->prepare('SELECT * FROM providers WHERE id=?'); $st->execute([$id]); return $st->fetch() ?: null; }
    public static function cities(): array { return Database::pdo()->query('SELECT id,name,department FROM provider_cities WHERE active=1 ORDER BY name')->fetchAll(); }
    public static function types(): array { return Database::pdo()->query('SELECT id,name FROM provider_types WHERE active=1 ORDER BY name')->fetchAll(); }
    private static function catalog(string $table): array { return Database::pdo()->query("SELECT id,code,name FROM `{$table}` WHERE active=1 ORDER BY sort_order,name")->fetchAll(); }
    public static function tipoContratista(): array { return self::catalog('catalog_tipo_contratista'); }
    public static function tipoPersona(): array { return self::catalog('catalog_tipo_persona'); }
    public static function naturaleza(): array { return self::catalog('catalog_naturaleza'); }
    public static function clasificacion(): array { return self::catalog('catalog_clasificacion'); }
    public static function nacionalidadContratista(): array { return self::catalog('catalog_nacionalidad_contratista'); }
    public static function claseContratista(): array { return self::catalog('catalog_clase_contratista'); }
    public static function catalogsForForm(): array {
        return [
            'cities'=>self::cities(),
            'types'=>self::types(),
            'tipoContratista'=>self::tipoContratista(),
            'tipoPersona'=>self::tipoPersona(),
            'naturaleza'=>self::naturaleza(),
            'clasificacion'=>self::clasificacion(),
            'nacionalidadContratista'=>self::nacionalidadContratista(),
            'claseContratista'=>self::claseContratista(),
        ];
    }
    public static function create(array $d): int {
        $pdo=Database::pdo();
        $sql='INSERT INTO providers (document_number,verification_digit,name,address,phone,email,city,city_id,provider_type_id,tipo_contratista_id,tipo_persona_id,naturaleza_id,clasificacion_id,nacionalidad_contratista_id,clase_contratista_id,consortium_members_json,contact_name,notes,active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $pdo->prepare($sql)->execute(self::payload($d));
        return (int)$pdo->lastInsertId();
    }
    public static function update(int $id, array $d): void {
        $sql='UPDATE providers SET document_number=?,verification_digit=?,name=?,address=?,phone=?,email=?,city=?,city_id=?,provider_type_id=?,tipo_contratista_id=?,tipo_persona_id=?,naturaleza_id=?,clasificacion_id=?,nacionalidad_contratista_id=?,clase_contratista_id=?,consortium_members_json=?,contact_name=?,notes=?,active=? WHERE id=?';
        $p=self::payload($d); $p[]=$id; Database::pdo()->prepare($sql)->execute($p);
    }
    private static function intOrNull(array $d, string $key): ?int { $v=(int)($d[$key]??0); return $v>0?$v:null; }
    private static function membersJson(array $d): string {
        $names=$d['ut_member_name'] ?? []; $perc=$d['ut_member_percent'] ?? []; $out=[];
        if (is_array($names)) { foreach($names as $i=>$name){ $name=trim((string)$name); $p=trim((string)($perc[$i] ?? '')); if($name!=='' || $p!=='') $out[]=['name'=>$name,'percent'=>$p]; } }
        return json_encode($out, JSON_UNESCAPED_UNICODE);
    }
    private static function payload(array $d): array {
        return [
            trim($d['document_number']??''),
            trim($d['verification_digit']??''),
            trim($d['name']??''),
            trim($d['address']??''),
            trim($d['phone']??''),
            trim($d['email']??''),
            trim($d['city']??''),
            self::intOrNull($d,'city_id'),
            self::intOrNull($d,'provider_type_id'),
            self::intOrNull($d,'tipo_contratista_id'),
            self::intOrNull($d,'tipo_persona_id'),
            self::intOrNull($d,'naturaleza_id'),
            self::intOrNull($d,'clasificacion_id'),
            self::intOrNull($d,'nacionalidad_contratista_id'),
            self::intOrNull($d,'clase_contratista_id'),
            self::membersJson($d),
            trim($d['contact_name']??''),
            trim($d['notes']??''),
            isset($d['active'])?1:0
        ];
    }
}
