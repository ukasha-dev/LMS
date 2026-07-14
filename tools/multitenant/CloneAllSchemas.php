<?php

require_once __DIR__ . '/SchemaMirror.php';

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;

    if (!$sourceDb) {
        fwrite(STDERR, "Usage: php CloneAllSchemas.php <source_database_name>\n");
        exit(1);
    }

    $excluded = ['multi_branch', 'migrations', 'captcha'];

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $existingTargetTables = $target->query(
        "SELECT table_name FROM information_schema.tables WHERE table_schema = 'school_saas'"
    )->fetchAll(PDO::FETCH_COLUMN);

    $sourceTables = $source->query(
        "SELECT table_name FROM information_schema.tables WHERE table_schema = '{$sourceDb}' AND table_type = 'BASE TABLE'"
    )->fetchAll(PDO::FETCH_COLUMN);

    $mirror = new SchemaMirror($source);
    $created = [];
    $skippedExisting = [];
    $skippedExcluded = [];

    foreach ($sourceTables as $table) {
        if (in_array($table, $excluded, true)) {
            $skippedExcluded[] = $table;
            continue;
        }
        if (in_array($table, $existingTargetTables, true)) {
            $skippedExisting[] = $table;
            continue;
        }

        $sql = $mirror->generateCreateTableSql($sourceDb, $table);
        $target->exec($sql);
        $created[] = $table;
    }

    echo 'Created ' . count($created) . " tables.\n";
    echo 'Skipped (already existed): ' . count($skippedExisting) . "\n";
    echo 'Skipped (excluded, infrastructure-only): ' . implode(', ', $skippedExcluded) . "\n";
}
