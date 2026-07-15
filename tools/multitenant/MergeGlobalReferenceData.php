<?php

// Generic tool for GLOBAL reference tables -- tables with no tenant_id
// column, shared identically across every school (confirmed live for each
// table this is used against: identical row content byte-for-byte across
// multiple real per-branch databases before ever being added here).
// Direct copy, source ids preserved, no tenant scoping, no IdRemapper --
// unlike every tenant-scoped Merge*Data tool in this directory. Do not use
// this for any table that varies per school; verify identical content
// across at least two real source databases first, the way every table
// this tool has been run against already was.

final class MergeGlobalReferenceData
{
    public function __construct(private PDO $source, private PDO $target, private string $table)
    {
    }

    public function run(): array
    {
        $existing = (int) $this->target
            ->query("SELECT COUNT(*) AS c FROM `{$this->table}`")
            ->fetch(PDO::FETCH_ASSOC)['c'];
        if ($existing > 0) {
            throw new RuntimeException(
                "Refusing to run: `{$this->table}` already has {$existing} row(s). This is a shared global "
                . 'table with no tenant scoping -- re-running would duplicate data for every tenant, not just one.'
            );
        }

        $columns = $this->source->query(
            "SELECT column_name FROM information_schema.columns "
            . "WHERE table_schema = DATABASE() AND table_name = '{$this->table}' ORDER BY ordinal_position"
        )->fetchAll(PDO::FETCH_COLUMN);

        $rows = $this->source->query('SELECT * FROM `' . $this->table . '`')->fetchAll(PDO::FETCH_ASSOC);

        $this->target->beginTransaction();
        try {
            foreach ($rows as $row) {
                $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
                $sql = 'INSERT INTO `' . $this->table . '` (`' . implode('`, `', $columns) . '`) VALUES ('
                    . implode(', ', $placeholders) . ')';
                $stmt = $this->target->prepare($sql);
                $params = [];
                foreach ($columns as $column) {
                    $params[':' . $column] = $row[$column];
                }
                $stmt->execute($params);
            }
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }

        return ['migrated' => count($rows)];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $table = $argv[2] ?? null;

    if (!$sourceDb || !$table) {
        fwrite(STDERR, "Usage: php MergeGlobalReferenceData.php <source_database_name> <table_name>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeGlobalReferenceData($source, $target, $table);
    $result = $merger->run();

    echo "Migrated {$result['migrated']} rows into `{$table}` (global, no tenant scoping).\n";
}
