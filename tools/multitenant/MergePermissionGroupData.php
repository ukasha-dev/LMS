<?php

// permission_group is a GLOBAL reference table (no tenant_id column --
// confirmed via school_saas's real schema and LinkAllSchemaFKs.php's
// Stage 14 skip list). It is shared across all tenants, not per-tenant
// business data, so this tool does a direct copy with source ids
// preserved -- there is no tenant scoping and no IdRemapper here, unlike
// every other Merge*Data tool in this directory.

final class MergePermissionGroupData
{
    public function __construct(private PDO $source, private PDO $target)
    {
    }

    public function run(): array
    {
        $existing = (int) $this->target->query('SELECT COUNT(*) AS c FROM permission_group')->fetch(PDO::FETCH_ASSOC)['c'];
        if ($existing > 0) {
            throw new RuntimeException(
                "Refusing to run: permission_group already has {$existing} row(s). This is a shared global "
                . 'table with no tenant scoping -- re-running would duplicate data for every tenant, not just one.'
            );
        }

        $rows = $this->source->query(
            'SELECT id, name, short_code, is_active, system FROM permission_group'
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->target->beginTransaction();
        try {
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
                $sql = 'INSERT INTO permission_group (`' . implode('`, `', $columns) . '`) VALUES ('
                    . implode(', ', $placeholders) . ')';
                $stmt = $this->target->prepare($sql);
                $params = [];
                foreach ($row as $column => $value) {
                    $params[':' . $column] = $value;
                }
                $stmt->execute($params);
            }
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }

        return ['permission_group_migrated' => count($rows)];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;

    if (!$sourceDb) {
        fwrite(STDERR, "Usage: php MergePermissionGroupData.php <source_database_name>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergePermissionGroupData($source, $target);
    $result = $merger->run();

    echo "Migrated {$result['permission_group_migrated']} permission_group rows (global, no tenant scoping).\n";
}
