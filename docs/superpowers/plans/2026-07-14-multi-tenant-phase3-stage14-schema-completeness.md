# Phase 3 Stage 14 — Full Schema Completeness (No Data) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `school_saas` currently has 39 of the real app's 193 tables. Create the remaining 155 (minus 3 that don't belong in a shared multi-tenant schema — see Global Constraints) so `school_saas`'s schema is a complete, self-consistent superset of every real per-branch database's structure — even for tables with zero data for the pilot tenant today. This closes the gap where app code referencing an unmigrated table would hard-fail with "table doesn't exist" the moment more of the codebase starts querying `school_saas`, and prepares the ground for Phase 5 schools that may actually populate modules this pilot tenant doesn't use.

**Architecture:** A new, framework-agnostic, directly-testable `SchemaMirror` class (`tools/multitenant/SchemaMirror.php`) reads a source table's column definitions from `information_schema.columns` and generates a `tenant_id`-augmented `CREATE TABLE` statement for the target — no FKs yet. A second, separate pass reads each table's FK structure from `information_schema.key_column_usage` and adds `tenant_id → tenants(id)` plus every sibling FK as a follow-up `ALTER TABLE`, once all 194 tables already exist. Splitting table-creation from FK-linking into two passes deliberately avoids needing a topological sort over the FK dependency graph — by the time any FK is added, its target table is guaranteed to already exist.

**Tech Stack:** PHP 8.1, PDO, PHPUnit 10.5, MySQL `information_schema` (unchanged tooling, new tool).

## Global Constraints

- **This stage creates SCHEMA ONLY — zero data population.** Every new table starts empty (0 rows for every tenant, including 25). Populating a specific table's real data remains a separate, later decision per module, exactly as done for every module so far (fees, HR, exams, grades, etc.).
- **Three source tables are deliberately excluded, not cloned**: `multi_branch` (per-branch connection config — makes no sense in a single shared database; `school_saas` doesn't need to know about per-branch hosts/credentials), `migrations` (CI3's internal schema-migration bookkeeping — this project already tracks `school_saas`'s own migrations differently, via `sql/multitenant/*.sql` files), `captcha` (transient captcha-challenge state, not business data). Confirmed via live schema inspection before this plan was written — no other source table is infrastructure-only.
- **No `ENUM` columns exist anywhere in the source schema** (confirmed live via `information_schema.columns WHERE data_type='enum'`, returned zero rows) — `SchemaMirror` does not need to handle that type, but must throw a clear, loud exception if it ever encounters one rather than silently mis-translating it, since this plan's testing didn't cover that case.
- **Column definitions are reproduced from `information_schema.columns.column_type`** verbatim (already includes precision/length, e.g. `varchar(60)`, `float(10,1)`) — never hand-guessed or abbreviated.
- **Every new table gets `tenant_id INT NOT NULL` plus a `tenant_id → tenants(id)` FK**, matching every existing migration file's convention.
- **Every sibling FK the source table has is replicated**, non-composite (`<table>(id)`, not `(tenant_id, id)`) — this is consistent with, not a new instance of, the already-documented, already-corrected "non-composite tenant FK" debt item (Phase 3 Stage 10's roadmap correction: 36 FKs across the 9 existing migration files). This stage will substantially grow that debt item's real scope, and Task 4 must update the roadmap's count accordingly — do not silently leave the old "36 FKs / 9 files" figure standing once this stage adds many more.
- **If a source FK references one of the 3 excluded tables**, skip adding that specific FK (log it, don't fail the whole run) — there is no sensible tenant-scoped target for it.
- **Two-phase execution is mandatory**: ALL 152 in-scope tables' `CREATE TABLE` statements must be applied (Task 2) before ANY `ALTER TABLE ADD FOREIGN KEY` statement is attempted (Task 3). Do not interleave per-table create-then-link — that reintroduces the dependency-ordering problem this design exists to avoid.
- **Known test credential** (unchanged): tenant_id=25, email `rabiachauhan923@gmail.com`, password `TestVerify123!`. This stage's own work needs no HTTP verification (it's schema-only, no controller changes), but Task 4's regression check should confirm the live app is still unaffected.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: `SchemaMirror` — column-definition cloning, proven against real MySQL

**Files:**
- Create: `tools/multitenant/SchemaMirror.php`
- Test: `tests/tools/multitenant/SchemaMirrorTest.php`

**Interfaces:**
- Produces: `SchemaMirror::__construct(PDO $source)`, `SchemaMirror::generateCreateTableSql(string $sourceSchema, string $table): string` — returns a complete `CREATE TABLE` statement (backtick-quoted table/column names, `tenant_id INT NOT NULL` appended after the source's own columns, `PRIMARY KEY` on whichever column has `column_key = 'PRI'` in the source, no FK clauses at all).
- Consumes: nothing from other tasks — this is the first task.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/SchemaMirrorTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class SchemaMirrorTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS schema_mirror_test_source');
        $admin->exec('CREATE DATABASE schema_mirror_test_source');
        $admin->exec('DROP DATABASE IF EXISTS schema_mirror_test_target');
        $admin->exec('CREATE DATABASE schema_mirror_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=schema_mirror_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=schema_mirror_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->source->exec(
            'CREATE TABLE widgets ('
            . 'id INT AUTO_INCREMENT PRIMARY KEY, '
            . 'name VARCHAR(60) NOT NULL, '
            . 'notes TEXT DEFAULT NULL, '
            . 'price FLOAT(10,2) DEFAULT NULL, '
            . "status VARCHAR(20) NOT NULL DEFAULT 'active', "
            . 'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, '
            . 'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            . ')'
        );
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS schema_mirror_test_source');
        $admin->exec('DROP DATABASE IF EXISTS schema_mirror_test_target');
    }

    public function testGeneratedDdlCreatesAWorkingTargetTableWithTenantIdAppended(): void
    {
        $mirror = new SchemaMirror($this->source);
        $sql = $mirror->generateCreateTableSql('schema_mirror_test_source', 'widgets');

        $this->target->exec($sql);

        $columns = $this->target->query('DESCRIBE widgets')->fetchAll(PDO::FETCH_ASSOC);
        $byName = [];
        foreach ($columns as $col) {
            $byName[$col['Field']] = $col;
        }

        $this->assertArrayHasKey('tenant_id', $byName, 'tenant_id column must be appended');
        $this->assertSame('NO', $byName['tenant_id']['Null']);
        $this->assertSame('int(11)', $byName['tenant_id']['Type']);

        $this->assertSame('PRI', $byName['id']['Key']);
        $this->assertSame('auto_increment', $byName['id']['Extra']);

        $this->assertSame('varchar(60)', $byName['name']['Type']);
        $this->assertSame('NO', $byName['name']['Null']);

        $this->assertSame('text', $byName['notes']['Type']);
        $this->assertSame('YES', $byName['notes']['Null']);

        $this->assertSame('float(10,2)', $byName['price']['Type']);

        $this->assertSame("active", trim($byName['status']['Default'], "'"));

        $this->assertSame('current_timestamp()', $byName['created_at']['Default']);

        $this->assertStringContainsString('on update current_timestamp()', $byName['updated_at']['Extra']);
    }

    public function testInsertingARealRowIntoTheGeneratedTableWorks(): void
    {
        $mirror = new SchemaMirror($this->source);
        $sql = $mirror->generateCreateTableSql('schema_mirror_test_source', 'widgets');
        $this->target->exec($sql);

        $this->target->exec("INSERT INTO widgets (name, tenant_id) VALUES ('Widget A', 25)");

        $row = $this->target->query('SELECT * FROM widgets')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Widget A', $row['name']);
        $this->assertSame(25, (int) $row['tenant_id']);
        $this->assertSame('active', $row['status']);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/SchemaMirrorTest.php`
Expected: FAIL — `Class "SchemaMirror" not found`.

- [ ] **Step 3: Write the implementation**

Create `tools/multitenant/SchemaMirror.php`:

```php
<?php

final class SchemaMirror
{
    public function __construct(private PDO $source)
    {
    }

    public function generateCreateTableSql(string $sourceSchema, string $table): string
    {
        $stmt = $this->source->prepare(
            'SELECT column_name, column_type, is_nullable, column_default, extra, column_key, data_type '
            . 'FROM information_schema.columns '
            . 'WHERE table_schema = :schema AND table_name = :table '
            . 'ORDER BY ordinal_position'
        );
        $stmt->execute(['schema' => $sourceSchema, 'table' => $table]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lines = [];
        $primaryKey = null;

        foreach ($columns as $col) {
            if ($col['data_type'] === 'enum') {
                throw new RuntimeException(
                    "SchemaMirror does not support ENUM columns (found `{$table}`.`{$col['column_name']}`) -- this was never encountered during this tool's development; extend it deliberately before trusting it here."
                );
            }

            $line = "`{$col['column_name']}` {$col['column_type']}";

            if ($col['is_nullable'] === 'NO') {
                $line .= ' NOT NULL';
            }

            if ($col['column_default'] !== null) {
                if (stripos((string) $col['extra'], 'auto_increment') !== false) {
                    // no default clause for auto_increment columns
                } elseif (strtolower((string) $col['column_default']) === 'current_timestamp()') {
                    $line .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $line .= ' DEFAULT ' . $col['column_default'];
                }
            } elseif ($col['is_nullable'] === 'YES') {
                $line .= ' DEFAULT NULL';
            }

            if (stripos((string) $col['extra'], 'auto_increment') !== false) {
                $line .= ' AUTO_INCREMENT';
            }
            if (stripos((string) $col['extra'], 'on update current_timestamp') !== false) {
                $line .= ' ON UPDATE CURRENT_TIMESTAMP';
            }

            $lines[] = $line;

            if ($col['column_key'] === 'PRI') {
                $primaryKey = $col['column_name'];
            }
        }

        $lines[] = '`tenant_id` INT NOT NULL';

        if ($primaryKey !== null) {
            $lines[] = "PRIMARY KEY (`{$primaryKey}`)";
        }

        return "CREATE TABLE `{$table}` (\n    " . implode(",\n    ", $lines) . "\n)";
    }
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/SchemaMirrorTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" composer.phar dump-autoload
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: prior total (87) + 2 new = 89, all passing.

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/SchemaMirror.php tests/tools/multitenant/SchemaMirrorTest.php
git commit -m "feat: add SchemaMirror, generates tenant-scoped CREATE TABLE DDL from a source table's real schema"
```

---

### Task 2: Create all 152 in-scope tables in `school_saas` (no FKs yet)

**Files:**
- Create: `tools/multitenant/CloneAllSchemas.php` (CLI driver script, not a class needing its own unit test — it is a thin orchestration script over `SchemaMirror`, which is already tested)

**Interfaces:**
- Consumes: `SchemaMirror::generateCreateTableSql()` from Task 1.
- Produces: 152 new tables in `school_saas`, no FKs.

- [ ] **Step 1: Write the driver script**

Create `tools/multitenant/CloneAllSchemas.php`:

```php
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
```

- [ ] **Step 2: Lint**

```bash
"C:\xampp81\php\php.exe" -l tools/multitenant/CloneAllSchemas.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Run it for real against the actual source and target databases**

```bash
"C:\xampp81\php\php.exe" tools/multitenant/CloneAllSchemas.php al_hafeez_campus
```

Expected: "Created 152 tables." (adjust to whatever the real number is if it differs slightly from this plan's count — re-verify against live `information_schema` counts taken when this plan was written: 155 missing minus 3 excluded = 152 expected; if the actual number differs, STOP and understand why before proceeding, don't just accept a different number silently).

- [ ] **Step 4: Verify the table count**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='school_saas';"
```

Expected: 39 (prior) + 152 (new) = 191.

- [ ] **Step 5: Spot-check 3 tables of your choice for schema correctness**

Pick 3 newly-created tables spanning different complexity (e.g. one simple, one with many columns, one with a `TEXT`/`FLOAT` mix) and run `DESCRIBE <table>` against both `school_saas` and `al_hafeez_campus`, confirming every source column is present in the target with the same type/nullability, plus the added `tenant_id`.

- [ ] **Step 6: Confirm zero rows in every new table (schema-only, no data)**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema='school_saas' AND table_rows > 0;"
```

Expected: only the tables that already had real migrated data before this stage (students, staff, classes, sections, exams, fees, HR, subjects, grades, etc.) — none of the 152 new tables should show any rows.

- [ ] **Step 7: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 89/89, no regressions (this task adds no new tests of its own, and touches no application code).

- [ ] **Step 8: Commit**

```bash
git add tools/multitenant/CloneAllSchemas.php
git commit -m "feat: create all 152 remaining table schemas in school_saas (no data, no FKs yet)"
```

---

### Task 3: Link every table's foreign keys (tenant_id + sibling FKs)

**Files:**
- Create: `tools/multitenant/LinkAllSchemaFKs.php` (CLI driver script)

**Interfaces:**
- Consumes: all 191 tables from Task 2, already committed and applied to the live `school_saas` database.
- Produces: FK constraints on every table.

- [ ] **Step 1: Write the driver script**

Create `tools/multitenant/LinkAllSchemaFKs.php`:

```php
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

    $alreadyHasFk = $target->query(
        "SELECT DISTINCT table_name, constraint_name FROM information_schema.table_constraints "
        . "WHERE table_schema = 'school_saas' AND constraint_type = 'FOREIGN KEY'"
    )->fetchAll(PDO::FETCH_COLUMN, 0);

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
        if (!in_array($fkName, $alreadyHasFk, true)) {
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
            if (in_array($siblingFkName, $alreadyHasFk, true)) {
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
```

- [ ] **Step 2: Lint**

```bash
"C:\xampp81\php\php.exe" -l tools/multitenant/LinkAllSchemaFKs.php
```

- [ ] **Step 3: Run it for real**

```bash
"C:\xampp81\php\php.exe" tools/multitenant/LinkAllSchemaFKs.php al_hafeez_campus
```

Expected: `Tenant FKs added: 152` (skipped: 0 — the 39 pre-existing tables already have theirs from earlier stages), some nonzero `Sibling FKs added` count, and any `sibling FK failed` STDERR lines investigated individually before proceeding (a failure here most likely means a genuine data-type mismatch or a source FK referencing a column that isn't actually a unique/indexed key in the target — do not silently ignore any STDERR output, read and understand each one).

- [ ] **Step 4: Verify FK count**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema='school_saas' AND constraint_type='FOREIGN KEY';"
```

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 89/89, no regressions.

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/LinkAllSchemaFKs.php
git commit -m "feat: link tenant_id and sibling foreign keys for all 152 newly-created table schemas"
```

---

### Task 4: Live regression proof + roadmap update

**Files:**
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`

**Interfaces:**
- Consumes: Tasks 1-3, already committed and applied to the live database.
- Produces: nothing — this is the closing task.

- [ ] **Step 1: Confirm the app is completely unaffected**

This stage touches only `school_saas`'s schema (new empty tables + new FKs) — it must not change behavior for anyone. Live-verify the 13 already-retrofitted routes still work exactly as before:

```bash
CJ=/tmp/p3s14_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -o /dev/null -w "staff: %{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/grade/tenantGradeList -w "\ngrade: %{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/site/login -o /dev/null -w "site-login: %{http_code}\n"
```

Expected: staff 200/18 rows, grade 200/14 rows, site-login 200 (unaffected real login path).

- [ ] **Step 2: Confirm final table/FK counts**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "
SELECT (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='school_saas') AS tables,
       (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema='school_saas' AND constraint_type='FOREIGN KEY') AS fks;"
```

- [ ] **Step 3: Run the full suite one more time**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 89/89.

- [ ] **Step 4: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`. Add a Phase 3 Stage 14 entry: what was built (`SchemaMirror`, the two-phase clone-then-link driver scripts), the real final table/FK counts from Step 2, the 3 explicitly-excluded infrastructure tables and why, and explicit confirmation this is schema-only (zero data population). **Critically, update the "Carried-forward technical debt" non-composite-FK item** (last touched in Phase 3 Stage 10, which corrected it to "69 total FKs / 36 non-composite in-scope across 9 files") — this stage adds many more non-composite sibling FKs across 152 more tables, so that debt item's true scope is now dramatically larger. State the new real total (from Step 2's FK count, minus the tenant FKs, which are a different, non-debt category) plainly, without estimating — pull the real number live.

- [ ] **Step 5: Commit and push**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 14 (full schema completeness) complete"
git push github master
```

---

## Final whole-stage review (after Task 4)

Dispatch an adversarial reviewer (same rigor as every prior stage's final review) to independently:
- **READ-ONLY ONLY**: never `git checkout -- <path>`, `git restore`, `git reset`, `git clean`, `git stash apply/pop`. Use only `git show`/`git diff`/`git log`/`git status`. Do not dispatch any subagent of its own.
- Re-read the full diff across all 4 tasks.
- Independently spot-check `SchemaMirror`'s DDL generation against at least 3 tables of the reviewer's own choosing (not the same 3 already checked in Task 2), comparing `DESCRIBE` output column-by-column between source and target.
- Confirm the excluded-table list (`multi_branch`, `migrations`, `captcha`) is still exactly 3 tables and no in-scope table was accidentally skipped or double-created.
- Confirm zero rows exist in any of the 152 new tables.
- Independently re-verify the live app is unaffected (staff/grade routes, `site/login`).
- Confirm the full suite passes (89/89).
- Confirm the roadmap's FK-debt-item scope correction uses a real, live-queried number, not an estimate.
- Confirm git hygiene — the single long-standing pre-existing omnipay vendor-file deletion should be the only uncommitted item, if anything; do not touch it.
- Report Ready to merge (Yes/With fixes/No) plus Critical/Important/Minor findings, same format as every prior stage.
