<?php

require_once __DIR__ . '/IdRemapper.php';

final class MergeSchoolData
{
    private PDO $source;
    private PDO $target;
    private int $tenantId;

    public function __construct(PDO $source, PDO $target, int $tenantId)
    {
        $this->source = $source;
        $this->target = $target;
        $this->tenantId = $tenantId;
    }

    public function run(): array
    {
        $studentRemap = new IdRemapper($this->nextId('students'));
        $userRemap = new IdRemapper($this->nextId('users'));

        $students = $this->fetchAll('SELECT * FROM students');
        $users = $this->fetchAll('SELECT * FROM users');

        foreach ($students as $row) {
            $studentRemap->remapId((int) $row['id']);
        }
        foreach ($users as $row) {
            $userRemap->remapId((int) $row['id']);
        }

        $this->target->beginTransaction();
        try {
            foreach ($students as $row) {
                $row['id'] = $studentRemap->getMapping((int) $row['id']);
                $row['parent_id'] = $userRemap->hasMapping((int) $row['parent_id'])
                    ? $userRemap->getMapping((int) $row['parent_id'])
                    : 0;
                $this->insertRow('students', $row);
            }
            foreach ($users as $row) {
                $row['id'] = $userRemap->getMapping((int) $row['id']);
                $row['user_id'] = $studentRemap->hasMapping((int) $row['user_id'])
                    ? $studentRemap->getMapping((int) $row['user_id'])
                    : 0;
                $this->insertRow('users', $row);
            }
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }

        return [
            'students_migrated' => count($students),
            'users_migrated' => count($users),
        ];
    }

    private function nextId(string $table): int
    {
        $stmt = $this->target->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `{$table}`");

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
    }

    private function fetchAll(string $sql): array
    {
        return $this->source->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function insertRow(string $table, array $row): void
    {
        $row['tenant_id'] = $this->tenantId;
        $columns = array_keys($row);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);

        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->target->prepare($sql);

        $params = [];
        foreach ($row as $column => $value) {
            $params[':' . $column] = $value;
        }
        $stmt->execute($params);
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeSchoolData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeSchoolData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['students_migrated']} students and {$result['users_migrated']} users for tenant {$tenantId}.\n";
}
