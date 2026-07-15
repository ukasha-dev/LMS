<?php

// Generates (and can execute) ALTER TABLE ADD COLUMN statements for columns
// that exist in a real legacy per-school table but are missing from the
// same-named table in the shared school_saas schema. Built for students/
// student_session specifically -- both were created minimally in Phase 1
// (just enough to prove student+login migration worked) and were never
// extended to the legacy app's real column set when the rest of the schema
// was completed in Stage 14 (Stage 14 explicitly skipped tables that
// "already existed").
//
// Every generated column is deliberately NULLable with no default, REGARDLESS
// of the source column's own NOT NULL/DEFAULT -- this is an ALTER against
// tables that already hold real migrated rows (312 students for tenant 25
// alone), so a column that can't be backfilled for existing rows must never
// be added as NOT NULL. ENUM and composite-key concerns don't apply here
// (ADD COLUMN never touches keys), so this intentionally does not reuse
// SchemaMirror's CREATE TABLE generator.
final class AddMissingTableColumns
{
    public function __construct(private PDO $source, private PDO $target)
    {
    }

    public function generateAlterStatements(string $sourceSchema, string $targetSchema, string $table): array
    {
        $sourceColumns = $this->columnTypesFor($this->source, $sourceSchema, $table);
        $targetColumns = $this->columnTypesFor($this->target, $targetSchema, $table);

        $statements = [];
        foreach ($sourceColumns as $name => $type) {
            if ($name === 'tenant_id' || isset($targetColumns[$name])) {
                continue;
            }
            $statements[] = "ALTER TABLE `{$table}` ADD COLUMN `{$name}` {$type} NULL DEFAULT NULL";
        }

        return $statements;
    }

    public function apply(string $sourceSchema, string $targetSchema, string $table): int
    {
        $statements = $this->generateAlterStatements($sourceSchema, $targetSchema, $table);
        foreach ($statements as $sql) {
            $this->target->exec($sql);
        }

        return count($statements);
    }

    private function columnTypesFor(PDO $pdo, string $schema, string $table): array
    {
        $stmt = $pdo->prepare(
            'SELECT column_name, column_type FROM information_schema.columns '
            . 'WHERE table_schema = :schema AND table_name = :table'
        );
        $stmt->execute(['schema' => $schema, 'table' => $table]);

        $types = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $types[$row['column_name']] = $row['column_type'];
        }

        return $types;
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $table    = $argv[2] ?? null;

    if (!$sourceDb || !$table) {
        fwrite(STDERR, "Usage: php AddMissingTableColumns.php <source_database_name> <table>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tool  = new AddMissingTableColumns($source, $target);
    $added = $tool->apply($sourceDb, 'school_saas', $table);
    echo "{$table}: added {$added} column(s)\n";
}
