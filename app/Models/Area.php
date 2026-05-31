<?php
namespace App\Models;
use App\Core\Database;

final class Area {
    public static function all(): array { return Database::pdo()->query('SELECT * FROM areas ORDER BY name')->fetchAll(); }
    public static function create(string $name): void { Database::pdo()->prepare('INSERT INTO areas (name,active) VALUES (?,1)')->execute([trim($name)]); }
    public static function update(int $id, string $name, bool $active): void { Database::pdo()->prepare('UPDATE areas SET name=?, active=? WHERE id=?')->execute([trim($name), $active?1:0, $id]); }
}
