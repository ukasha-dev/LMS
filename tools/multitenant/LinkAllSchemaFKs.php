<?php

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;

    if (!$sourceDb) {
        fwrite(STDERR, "Usage: php LinkAllSchemaFKs.php <source_database_name>\n");
        exit(1);
    }

    $excluded = ['multi_branch', 'migrations', 'captcha'];

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $targetTables = $target->query(
        "SELECT table_name FROM information_schema.tables WHERE table_schema = 'school_saas'"
    )->fetchAll(PDO::FETCH_COLUMN);

    // Dedup by the actual (table, column, referenced_table) relationship, not by the
    // constraint name the script would generate. Two earlier revisions of this script
    // both got this wrong in different ways: first by fetching the wrong column from the
    // lookup query (collecting table names instead of constraint names, so the dedup
    // check never matched anything), then — after fixing that — by comparing generated
    // names ("fk_{table}_tenant") against *existing* constraint names, which still missed
    // every table whose pre-existing FK used an older/abbreviated naming convention (e.g.
    // "fk_egcbe_tenant" for exam_group_class_batch_exams). Against a database that already
    // had 39 tenant-scoped tables carrying FKs under such legacy names, both bugs caused
    // the script to add a second, redundant FK enforcing a relationship that already
    // existed — 54 duplicate constraints across 23 tables, confirmed and cleaned up twice
    // during Task 3's real run. See p3s14-task-3-report.md for the full incident writeup.
    $existingFkRelationships = [];
    foreach ($target->query(
        "SELECT table_name, column_name, referenced_table_name "
        . "FROM information_schema.key_column_usage "
        . "WHERE table_schema = 'school_saas' AND referenced_table_name IS NOT NULL"
    )->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existingFkRelationships["{$row['table_name']}|{$row['column_name']}|{$row['referenced_table_name']}"] = true;
    }

    $tenantFkAdded = 0;
    $tenantFkSkipped = 0;
    $siblingFkAdded = 0;
    $siblingFkSkippedExcludedTarget = 0;
    $siblingFkSkippedAlreadyExists = 0;

    foreach ($targetTables as $table) {
        if ($table === 'tenants') {
            continue;
        }

        $fkName = "fk_{$table}_tenant";
        if (!isset($existingFkRelationships["{$table}|tenant_id|tenants"])) {
            try {
                $target->exec(
                    "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`)"
                );
                $tenantFkAdded++;
            } catch (PDOException $e) {
                fwrite(STDERR, "tenant FK failed for {$table}: {$e->getMessage()}\n");
                $tenantFkSkipped++;
            }
        }

        $siblingFks = $source->query(
            "SELECT column_name, referenced_table_name, referenced_column_name, constraint_name "
            . "FROM information_schema.key_column_usage "
            . "WHERE table_schema = '{$sourceDb}' AND table_name = '{$table}' AND referenced_table_name IS NOT NULL"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($siblingFks as $fk) {
            if (in_array($fk['referenced_table_name'], $excluded, true)) {
                $siblingFkSkippedExcludedTarget++;
                continue;
            }
            if (!in_array($fk['referenced_table_name'], $targetTables, true)) {
                $siblingFkSkippedExcludedTarget++;
                continue;
            }

            $siblingFkName = "fk_{$table}_{$fk['column_name']}";
            $relationshipKey = "{$table}|{$fk['column_name']}|{$fk['referenced_table_name']}";
            if (isset($existingFkRelationships[$relationshipKey])) {
                $siblingFkSkippedAlreadyExists++;
                continue;
            }

            try {
                $target->exec(
                    "ALTER TABLE `{$table}` ADD CONSTRAINT `{$siblingFkName}` FOREIGN KEY (`{$fk['column_name']}`) "
                    . "REFERENCES `{$fk['referenced_table_name']}`(`{$fk['referenced_column_name']}`)"
                );
                $siblingFkAdded++;
            } catch (PDOException $e) {
                fwrite(STDERR, "sibling FK failed for {$table}.{$fk['column_name']}: {$e->getMessage()}\n");
            }
        }
    }

    echo "Tenant FKs added: {$tenantFkAdded} (skipped/failed: {$tenantFkSkipped})\n";
    echo "Sibling FKs added: {$siblingFkAdded}\n";
    echo "Sibling FKs skipped (target excluded/missing): {$siblingFkSkippedExcludedTarget}\n";
    echo "Sibling FKs skipped (already existed): {$siblingFkSkippedAlreadyExists}\n";
}
