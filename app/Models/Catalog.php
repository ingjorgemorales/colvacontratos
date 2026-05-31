<?php
namespace App\Models;
use App\Core\Database;

final class Catalog {
    private const TABLES = [
        'tipo_compromiso' => 'contract_tipo_compromiso',
        'modalidad_seleccion' => 'contract_modalidad_seleccion',
        'tema_gasto' => 'contract_tema_gasto',
        'unidad_plazo' => 'contract_unidad_plazo',
        'tipologia_especifica' => 'contract_tipologia_especifica',
        'regimen_contratacion' => 'contract_regimen_contratacion',
        'tipo_gasto' => 'contract_tipo_gasto',
        'origen_presupuesto' => 'contract_origen_presupuesto',
        'origen_recursos' => 'contract_origen_recursos',
        'tipo_moneda' => 'contract_tipo_moneda',
        'tipo_control' => 'contract_tipo_control',
        'procedimiento' => 'contract_procedimiento',
        'inicio_contrato' => 'contract_inicio_contrato',
        'prorroga_automatica' => 'contract_prorroga_automatica',
        'tipo_contrato' => 'contract_types',
    ];

    public static function areas(): array { return Database::pdo()->query('SELECT id,name FROM areas WHERE active=1 ORDER BY name')->fetchAll(); }
    public static function subAreas(): array { return Database::pdo()->query('SELECT id,area_id,name FROM area_subareas WHERE active=1 ORDER BY name')->fetchAll(); }
    public static function statuses(): array { return Database::pdo()->query('SELECT id,name FROM contract_statuses ORDER BY sort_order,id')->fetchAll(); }
    public static function providers(): array { return Database::pdo()->query('SELECT id,name FROM providers WHERE active=1 ORDER BY name')->fetchAll(); }
    public static function supervisors(): array { return Database::pdo()->query('SELECT id,full_name,document_number,verification_digit FROM contract_supervisors WHERE active=1 ORDER BY full_name')->fetchAll(); }
    public static function contractTypes(): array { return Database::pdo()->query('SELECT id,name FROM contract_types WHERE active=1 ORDER BY name')->fetchAll(); }

    public static function tableFor(string $code): ?string { return self::TABLES[$code] ?? null; }
    public static function paramTables(): array { return self::TABLES; }

    public static function group(string $code): array {
        $table = self::tableFor($code);
        if (!$table) { return []; }
        $sql = "SELECT id, code, name FROM {$table} WHERE active = 1 ORDER BY sort_order, name";
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function contractParams(): array {
        return [
            'commitmentTypes' => self::group('tipo_compromiso'),
            'selectionModalities' => self::group('modalidad_seleccion'),
            'expenseTopics' => self::group('tema_gasto'),
            'termUnits' => self::group('unidad_plazo'),
            'specificTypologies' => self::group('tipologia_especifica'),
            'contractingRegimes' => self::group('regimen_contratacion'),
            'expenseTypes' => self::group('tipo_gasto'),
            'budgetOrigins' => self::group('origen_presupuesto'),
            'resourceOrigins' => self::group('origen_recursos'),
            'currencyTypes' => self::group('tipo_moneda'),
            'controlTypes' => self::group('tipo_control'),
            'procedureTypes' => self::group('procedimiento'),
            'contractStartTypes' => self::group('inicio_contrato'),
            'autoExtensionOptions' => self::group('prorroga_automatica'),
            'contractTypes' => self::contractTypes(),
        ];
    }
}
