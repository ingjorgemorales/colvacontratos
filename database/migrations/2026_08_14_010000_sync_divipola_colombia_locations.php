<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DANE_MUNICIPIOS_URL = 'https://geoportal.dane.gov.co/mparcgis/rest/services/Divipola/Serv_DIVIPOLA_MGN_2025/FeatureServer/317/query?f=json&where=1%3D1&outFields=DPTO_CCDGO,DPTO_CNMBRE,MPIO_CDPMP,MPIO_CNMBRE&returnGeometry=false';

    public function up(): void
    {
        if (!Schema::hasTable('departments') || !Schema::hasTable('provider_cities')) {
            return;
        }

        $this->dropIndexIfExists('provider_cities', 'uk_provider_cities_name');
        $this->normalizeLegacyRows();

        $rows = $this->fetchMunicipalities();
        $departments = [];

        foreach ($rows as $row) {
            $departmentCode = $row['department_code'];
            $departments[$departmentCode] = [
                'code' => $departmentCode,
                'name' => $row['department'],
                'sort_order' => (int) $departmentCode,
                'active' => 1,
            ];
        }

        foreach ($departments as $department) {
            $this->upsertByCode('departments', $department);
        }

        foreach ($rows as $row) {
            $this->upsertByCode('provider_cities', [
                'code' => $row['municipality_code'],
                'name' => $row['municipality'],
                'department' => $row['department'],
                'sort_order' => (int) $row['municipality_code'],
                'active' => 1,
            ]);
        }

        $this->createUniqueIndexIfMissing('departments', 'departments_code_unique', 'code');
        $this->createUniqueIndexIfMissing('provider_cities', 'provider_cities_code_unique', 'code');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('departments', 'departments_code_unique');
        $this->dropIndexIfExists('provider_cities', 'provider_cities_code_unique');
    }

    private function fetchMunicipalities(): array
    {
        $json = $this->getRemoteJson(self::DANE_MUNICIPIOS_URL);
        if ($json === false || trim($json) === '') {
            throw new RuntimeException('No fue posible consultar DIVIPOLA 2025 del DANE.');
        }

        $payload = json_decode($json, true);
        $features = $payload['features'] ?? [];
        if (!is_array($features) || $features === []) {
            throw new RuntimeException('DIVIPOLA 2025 no devolvio municipios.');
        }

        $rows = [];
        foreach ($features as $feature) {
            $attributes = $feature['attributes'] ?? [];
            $departmentCode = trim((string)($attributes['DPTO_CCDGO'] ?? ''));
            $department = $this->titleName((string)($attributes['DPTO_CNMBRE'] ?? ''));
            $municipalityCode = trim((string)($attributes['MPIO_CDPMP'] ?? ''));
            $municipality = $this->titleName((string)($attributes['MPIO_CNMBRE'] ?? ''));

            if ($departmentCode === '' || $department === '' || $municipalityCode === '' || $municipality === '') {
                continue;
            }

            $rows[$municipalityCode] = compact('departmentCode', 'department', 'municipalityCode', 'municipality');
        }

        ksort($rows, SORT_STRING);

        return array_map(static fn (array $row): array => [
            'department_code' => $row['departmentCode'],
            'department' => $row['department'],
            'municipality_code' => $row['municipalityCode'],
            'municipality' => $row['municipality'],
        ], array_values($rows));
    }

    private function getRemoteJson(string $url): string|false
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'ColvaContratos DIVIPOLA sync',
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if (is_string($body) && $status >= 200 && $status < 300) {
                return $body;
            }
        }

        return @file_get_contents($url);
    }

    private function normalizeLegacyRows(): void
    {
        $legacyCities = [
            1 => ['code' => '11001', 'name' => 'Bogota D.C.', 'department' => 'Bogota D.C.', 'sort_order' => 11001],
            4 => ['code' => '05001', 'name' => 'Medellin', 'department' => 'Antioquia', 'sort_order' => 5001],
            5 => ['code' => '76001', 'name' => 'Cali', 'department' => 'Valle del Cauca', 'sort_order' => 76001],
            6 => ['code' => '08001', 'name' => 'Barranquilla', 'department' => 'Atlantico', 'sort_order' => 8001],
            7 => ['code' => '68001', 'name' => 'Bucaramanga', 'department' => 'Santander', 'sort_order' => 68001],
            8 => ['code' => '13001', 'name' => 'Cartagena de Indias', 'department' => 'Bolivar', 'sort_order' => 13001],
        ];

        foreach ($legacyCities as $id => $data) {
            DB::table('provider_cities')->where('id', $id)->update($data + ['active' => 1]);
        }

        $legacyDepartments = [
            1 => ['code' => '11', 'name' => 'Bogota D.C.', 'sort_order' => 11],
            2 => ['code' => '25', 'name' => 'Cundinamarca', 'sort_order' => 25],
            3 => ['code' => '05', 'name' => 'Antioquia', 'sort_order' => 5],
            4 => ['code' => '76', 'name' => 'Valle del Cauca', 'sort_order' => 76],
            5 => ['code' => '08', 'name' => 'Atlantico', 'sort_order' => 8],
        ];

        foreach ($legacyDepartments as $id => $data) {
            DB::table('departments')->where('id', $id)->update($data + ['active' => 1]);
        }
    }

    private function upsertByCode(string $table, array $data): void
    {
        $existing = DB::table($table)->where('code', $data['code'])->first();
        if ($existing) {
            DB::table($table)->where('id', (int) $existing->id)->update($data);
            return;
        }

        DB::table($table)->insert($data);
    }

    private function titleName(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', mb_convert_case($value, MB_CASE_TITLE, 'UTF-8')) ?? '');
        $value = preg_replace_callback('/\b(De|Del|La|Las|Los|Y|El)\b/u', static fn ($m) => mb_strtolower($m[1], 'UTF-8'), $value) ?? $value;
        $value = str_replace(['D.C.', 'D.c.'], 'D.C.', $value);

        return $value;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        $exists = DB::selectOne('SELECT COUNT(*) total FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$table, $index]);
        if ((int)($exists->total ?? 0) > 0) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function createUniqueIndexIfMissing(string $table, string $index, string $column): void
    {
        $exists = DB::selectOne('SELECT COUNT(*) total FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$table, $index]);
        if ((int)($exists->total ?? 0) === 0) {
            DB::statement("ALTER TABLE `{$table}` ADD UNIQUE `{$index}` (`{$column}`)");
        }
    }
};
