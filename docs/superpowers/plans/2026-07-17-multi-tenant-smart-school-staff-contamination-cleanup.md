# Smart School Staff Contamination Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. **This plan contains a mandatory human checkpoint between Task 2 and Task 3 — see Global Constraints. Do not dispatch Task 3 without it, regardless of "continuous execution" defaults.**

**Goal:** Neutralize the login capability of the 92 confirmed-inactive rows in `smart_school.staff` that are byte-identical password-hash copies of real staff from the other 5 real schools, without deleting any row, without touching any other column or table, and without affecting smart_school's own genuine staff or the known shared vendor account.

**Architecture:** One new, standalone tool, `tools/multitenant/SmartSchoolStaffNeutralizer.php`, operating entirely within `smart_school`'s own database (reading the other 5 schools' `staff` tables read-only for comparison, writing only to `smart_school.staff`). Unlike every prior `tools/multitenant/*.php` tool (which only ever `INSERT`s into fresh target tables), this is the first tool in the project that `UPDATE`s existing rows in a live table — it ships with a dry-run-only mode first, a separate explicit live-execution mode second, and a mandatory human review of the dry-run's real-data output between the two.

**Tech Stack:** PHP 8.1, PDO/MySQL, PHPUnit 10.5 (existing project stack, unchanged).

## Context

This addresses a finding first surfaced in Phase 4 Stage 1 (2026-07-17): `smart_school` (tenant 30, database `smart_school`) contains 172 rows in its `staff` table, but 93 of them are byte-identical password-hash copies of real staff belonging to the other 5 real schools — not independent data (bcrypt embeds a random salt; identical hashes cannot occur by coincidence, the same reasoning this entire migration has relied on throughout). A dedicated investigation (2026-07-17, read-only, no data touched) fully characterized this finding:

- **95% of every other school's entire staff roster is duplicated inside `smart_school`**: al_hafeez_campus 17/18, al_mateen_campus 17/17, nafay_campus 27/27, salam_boys_school 20/23, salam_girls_school 17/18 — 98 of 103 total rows across the other 5 schools have a matching hash somewhere in `smart_school`.
- Of `smart_school`'s 93 colliding rows, **92 have `is_active = 0`** and are therefore invisible in the real admin staff-list page (`application/models/Staff_model.php:628`, `searchFullText()`, filters `WHERE staff.is_active = '$active'` with `active=1`). This is not an actively-exposed, ongoing UI leak — it's real, dormant data hygiene debt.
- The **1 remaining colliding row with `is_active = 1`** is fully identified and understood: `hamza.ali@kics.edu.pk`, `employee_id = 9000`, `id = 177` — identical across **all 6** real school databases including `smart_school`. This is the same known, deliberate, shared vendor/support account already documented in Phase 4 Stage 1's roadmap entry (the `@kics.edu.pk` domain, the non-sequential employee id, and its presence with matching `id` in every database all point to intentional provisioning, not contamination). **This row must never be touched by this plan** — the selection criteria below exclude it automatically via its `is_active = 1` status, not via a hardcoded exception list.
- `smart_school` itself has real operational scale comparable to other genuine schools (8 classes / 10 sections / 1,462 fee deposits vs. `al_mateen_campus`'s 7 / 7 / 959) — it is very likely a real, actively-operating school, not a template or demo database. The remaining 79 non-colliding staff rows (no internal duplication among them either) are presumed to be `smart_school`'s own genuine staff and must not be touched.
- A separate, smaller, differently-shaped issue was also found on the student side (4 collision pairs, all specifically between tenant 26/`al_mateen_campus` and tenant 30/`smart_school`, all `is_active = 'yes'` on both sides — unlike the dormant staff-side collisions) — **explicitly out of scope for this plan**, scoped as its own future dedicated investigation given its different (active, not dormant) risk shape.

## Why not go further (explicitly out of scope, and why)

- **Not the 79 genuine `smart_school` staff rows.** Untouched by this plan's selection criteria by construction (they have no cross-school hash collision).
- **Not the `hamza.ali@kics.edu.pk` vendor row.** Excluded by construction (`is_active = 1`).
- **Not any other school's own `staff` table.** This tool only ever writes to `smart_school.staff`; the other 5 databases are read-only comparison sources.
- **Not the student-side `al_mateen_campus`/`smart_school` collisions.** Different shape (active, not dormant; only one school pair; counts don't cleanly resolve to clean pairs) — deserves its own dedicated investigation, not a bundled fix.
- **Not row deletion.** Every affected row's name, employee_id, email, documents, leave history, and every other column stay completely intact — only the `password` column is overwritten, so login capability is removed without destroying whatever audit-trail value the row represents.
- **Not touching Phase 4's login-gate code.** `Site.php`, `RealLoginGate.php`, and `RealUserLoginGate.php` are all untouched by this plan — this is data remediation, not a login-routing change. (Tenant 30 has no login gate at all today, and this plan doesn't add one.)

## Components (reference — full detail in each task below)

- **`tools/multitenant/SmartSchoolStaffNeutralizer.php`** (new) — `__construct(PDO $smartSchoolPdo, array $otherSchoolPdos)`, `dryRun(): array` (read-only, returns the candidate list), `executeLive(): array` (performs the mutation, returns the result), plus a CLI entry point.

## Global Constraints

- **The selection criteria are re-derived live at execution time, every run — never a hardcoded row-id list.** A `smart_school.staff` row qualifies for neutralization if and only if (a) `is_active = 0`, AND (b) its `password` column value is byte-identical to at least one row's `password` column in ANY of the other 5 real schools' own `staff` tables, queried directly against each per-branch database (not via `school_saas`, which may not have exact 1:1 parity with each per-branch database's current live state).
- **Neutralization mechanism**: `UPDATE` the qualifying row's `password` column to the literal sentinel value `'NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE'` (46 characters — confirmed live this session that `smart_school.staff.password` is `varchar(250) NOT NULL`, comfortably wide enough). This is not a valid bcrypt hash, so any password verification against it fails deterministically without even needing to run bcrypt. No other column on the row is ever modified. No row is ever deleted.
- **No cleartext password or bcrypt hash value is ever printed to console output, logged, or written to any report file** — only metadata (row id, employee_id, which school(s) it collides with, counts).
- **All new testing (Task 1, Task 2's unit tests) uses throwaway PHPUnit-managed databases mirroring the real schema shape — never live data.**
- **MANDATORY HUMAN CHECKPOINT.** Task 2 ends with the dry-run having been run against real live data and its full output written to a durable file. Task 2's own completion does NOT include running the live mutation. **Task 3 (the live mutation) must not be dispatched until the user has explicitly reviewed Task 2's dry-run output file and confirmed proceeding.** This overrides subagent-driven-development's normal "continuous execution without check-ins" default — that default assumes purely additive/reversible work; this plan's Task 3 is the first real-data-mutating step in the entire project and requires an explicit, deliberate human go-ahead, not an automatic continuation.
- **Task 3 re-derives the candidate set fresh, immediately before the live `UPDATE`** (not reusing Task 2's dry-run result set), and cross-checks its row count against a fresh re-run of the same dry-run query taken at that same moment, to detect any drift since Task 2.
- **Post-run verification (Task 3) must confirm, live**: every previously-qualifying row now has the sentinel password value; the 79 non-colliding rows and the `hamza.ali@kics.edu.pk` row are byte-for-byte unchanged (every column, via a full-row comparison against a pre-run snapshot, not just password); all 5 other real schools' own `staff` tables are completely unchanged; the existing full PHPUnit suite still passes.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: `SmartSchoolStaffNeutralizer` — dry-run capability + full unit test suite

**Files:**
- Create: `tools/multitenant/SmartSchoolStaffNeutralizer.php`
- Test: `tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php`

**Interfaces:**
- Produces: `SmartSchoolStaffNeutralizer::__construct(PDO $smartSchoolPdo, array $otherSchoolPdos)` (`$otherSchoolPdos` is an associative array of `[schoolName => PDO]` for the 5 comparison databases), `SmartSchoolStaffNeutralizer::dryRun(): array` returning `['candidates' => [['id' => int, 'employee_id' => string, 'colliding_with' => string[]], ...], 'count' => int]`. `colliding_with` lists which of the 5 school names (by array key) the row's password matched against — never the password/hash value itself.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class SmartSchoolStaffNeutralizerTest extends TestCase
{
    private PDO $smartSchoolPdo;
    private PDO $schoolAPdo;
    private PDO $schoolBPdo;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach (['neutralizer_test_smart', 'neutralizer_test_a', 'neutralizer_test_b'] as $db) {
            $admin->exec("DROP DATABASE IF EXISTS {$db}");
            $admin->exec("CREATE DATABASE {$db}");
        }

        $this->smartSchoolPdo = new PDO('mysql:host=127.0.0.1;dbname=neutralizer_test_smart;charset=utf8mb4', 'root', '');
        $this->smartSchoolPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->schoolAPdo = new PDO('mysql:host=127.0.0.1;dbname=neutralizer_test_a;charset=utf8mb4', 'root', '');
        $this->schoolAPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->schoolBPdo = new PDO('mysql:host=127.0.0.1;dbname=neutralizer_test_b;charset=utf8mb4', 'root', '');
        $this->schoolBPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $createStaff = 'CREATE TABLE staff (id INT AUTO_INCREMENT PRIMARY KEY, employee_id VARCHAR(50), password VARCHAR(250) NOT NULL, is_active TINYINT(1) NOT NULL)';
        $this->smartSchoolPdo->exec($createStaff);
        $this->schoolAPdo->exec($createStaff);
        $this->schoolBPdo->exec($createStaff);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        foreach (['neutralizer_test_smart', 'neutralizer_test_a', 'neutralizer_test_b'] as $db) {
            $admin->exec("DROP DATABASE IF EXISTS {$db}");
        }
    }

    private function neutralizer(): SmartSchoolStaffNeutralizer
    {
        return new SmartSchoolStaffNeutralizer($this->smartSchoolPdo, [
            'school_a' => $this->schoolAPdo,
            'school_b' => $this->schoolBPdo,
        ]);
    }

    public function testInactiveRowWithRealCollisionIsSelected(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E1', 'shared-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E1', 'shared-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(1, $result['count']);
        $this->assertSame('E1', $result['candidates'][0]['employee_id']);
        $this->assertSame(['school_a'], $result['candidates'][0]['colliding_with']);
    }

    public function testInactiveRowWithNoCollisionIsExcluded(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E2', 'unique-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E3', 'different-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(0, $result['count']);
    }

    public function testActiveRowWithCollisionIsExcluded(): void
    {
        // Mirrors the real hamza.ali@kics.edu.pk shape: is_active = 1 must
        // exclude the row from selection regardless of collision.
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E4', 'vendor-hash', 1)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E4', 'vendor-hash', 1)");
        $this->schoolBPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E4', 'vendor-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(0, $result['count']);
    }

    public function testActiveRowWithNoCollisionIsExcluded(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E5', 'own-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(0, $result['count']);
    }

    public function testCollisionAcrossMultipleSchoolsListsAll(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E6', 'multi-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E6', 'multi-hash', 1)");
        $this->schoolBPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E6', 'multi-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(1, $result['count']);
        sort($result['candidates'][0]['colliding_with']);
        $this->assertSame(['school_a', 'school_b'], $result['candidates'][0]['colliding_with']);
    }

    public function testDryRunPerformsZeroWrites(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E7', 'shared-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E7', 'shared-hash', 1)");

        $this->neutralizer()->dryRun();

        $row = $this->smartSchoolPdo->query("SELECT password FROM staff WHERE employee_id = 'E7'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('shared-hash', $row['password']);
    }

    public function testSentinelValueFitsWithinRealColumnWidth(): void
    {
        // Confirmed live this session: smart_school.staff.password is
        // varchar(250) NOT NULL -- the sentinel must fit comfortably.
        $sentinel = 'NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE';
        $this->assertLessThanOrEqual(250, strlen($sentinel));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php`
Expected: FAIL with `Class "SmartSchoolStaffNeutralizer" not found`

- [ ] **Step 3: Write the implementation**

Create `tools/multitenant/SmartSchoolStaffNeutralizer.php`:

```php
<?php

final class SmartSchoolStaffNeutralizer
{
    private const SENTINEL_PASSWORD = 'NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE';

    /** @var array<string, PDO> */
    private array $otherSchoolPdos;

    public function __construct(private PDO $smartSchoolPdo, array $otherSchoolPdos)
    {
        $this->otherSchoolPdos = $otherSchoolPdos;
    }

    public function dryRun(): array
    {
        $candidates = [];

        $stmt = $this->smartSchoolPdo->query('SELECT id, employee_id, password FROM staff WHERE is_active = 0');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $collidingWith = $this->findCollisions($row['password']);
            if (!empty($collidingWith)) {
                $candidates[] = [
                    'id' => (int) $row['id'],
                    'employee_id' => $row['employee_id'],
                    'colliding_with' => $collidingWith,
                ];
            }
        }

        return ['candidates' => $candidates, 'count' => count($candidates)];
    }

    private function findCollisions(string $password): array
    {
        $collidingWith = [];
        foreach ($this->otherSchoolPdos as $schoolName => $pdo) {
            $stmt = $pdo->prepare('SELECT 1 FROM staff WHERE password = :password LIMIT 1');
            $stmt->execute(['password' => $password]);
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                $collidingWith[] = $schoolName;
            }
        }

        return $collidingWith;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php`
Expected: `OK (7 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add tools/multitenant/SmartSchoolStaffNeutralizer.php tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php
git commit -m "feat: add SmartSchoolStaffNeutralizer dry-run capability, unit tested against synthetic fixtures"
```

---

### Task 2: Live-execution capability + CLI entry point + dry-run against real data

**Files:**
- Modify: `tools/multitenant/SmartSchoolStaffNeutralizer.php` (add the `executeLive()` method and the CLI entry point — Task 1's version of this file has no method that can write anything; this task is what introduces mutation capability, deliberately kept out of Task 1)
- Test: `tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php` (add live-execution tests)

**Interfaces:**
- Produces: `SmartSchoolStaffNeutralizer::executeLive(): array` returning `['updated' => int, 'candidates' => array]`. Reuses `dryRun()` (from Task 1) internally to derive the candidate set immediately before writing.

- [ ] **Step 1: Write the failing live-execution tests**

Add to `tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php`:

```php
    public function testExecuteLiveMutatesOnlyQualifyingRows(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E8', 'shared-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E8', 'shared-hash', 1)");
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E9', 'own-hash', 0)");

        $result = $this->neutralizer()->executeLive();

        $this->assertSame(1, $result['updated']);

        $mutated = $this->smartSchoolPdo->query("SELECT password FROM staff WHERE employee_id = 'E8'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE', $mutated['password']);

        $untouched = $this->smartSchoolPdo->query("SELECT password FROM staff WHERE employee_id = 'E9'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('own-hash', $untouched['password']);
    }

    public function testExecuteLiveNeverTouchesOtherSchoolTables(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E10', 'shared-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E10', 'shared-hash', 1)");

        $this->neutralizer()->executeLive();

        $schoolARow = $this->schoolAPdo->query("SELECT password FROM staff WHERE employee_id = 'E10'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('shared-hash', $schoolARow['password']);
    }

    public function testExecuteLiveOnZeroCandidatesUpdatesNothing(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E11', 'own-hash', 0)");

        $result = $this->neutralizer()->executeLive();

        $this->assertSame(0, $result['updated']);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php`
Expected: FAIL with `Call to undefined method SmartSchoolStaffNeutralizer::executeLive()` on the 3 new tests (the 7 Task 1 tests still pass).

- [ ] **Step 3: Implement `executeLive()`**

Add this method to the `SmartSchoolStaffNeutralizer` class in `tools/multitenant/SmartSchoolStaffNeutralizer.php`, immediately after `dryRun()`:

```php
    public function executeLive(): array
    {
        $preCheck = $this->dryRun();

        $updated = 0;
        $this->smartSchoolPdo->beginTransaction();
        try {
            $updateStmt = $this->smartSchoolPdo->prepare('UPDATE staff SET password = :sentinel WHERE id = :id');
            foreach ($preCheck['candidates'] as $candidate) {
                $updateStmt->execute(['sentinel' => self::SENTINEL_PASSWORD, 'id' => $candidate['id']]);
                $updated++;
            }
            $this->smartSchoolPdo->commit();
        } catch (\Throwable $e) {
            $this->smartSchoolPdo->rollBack();
            throw $e;
        }

        return ['updated' => $updated, 'candidates' => $preCheck['candidates']];
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php`
Expected: `OK (10 tests, ...)`

- [ ] **Step 5: Add the CLI entry point**

Append to `tools/multitenant/SmartSchoolStaffNeutralizer.php`, after the closing `}` of the class:

```php

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $live = in_array('--live', $argv, true);

    $smartSchoolPdo = new PDO('mysql:host=127.0.0.1;dbname=smart_school;charset=utf8mb4', 'root', '');
    $smartSchoolPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $otherSchoolNames = ['al_hafeez_campus', 'al_mateen_campus', 'nafay_campus', 'salam_boys_school', 'salam_girls_school'];
    $otherSchoolPdos = [];
    foreach ($otherSchoolNames as $name) {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname={$name};charset=utf8mb4", 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $otherSchoolPdos[$name] = $pdo;
    }

    $neutralizer = new SmartSchoolStaffNeutralizer($smartSchoolPdo, $otherSchoolPdos);

    if ($live) {
        $result = $neutralizer->executeLive();
        echo "LIVE RUN: neutralized {$result['updated']} row(s).\n";
        foreach ($result['candidates'] as $c) {
            echo "  id={$c['id']} employee_id={$c['employee_id']} colliding_with=" . implode(',', $c['colliding_with']) . "\n";
        }
    } else {
        $result = $neutralizer->dryRun();
        echo "DRY RUN (no changes made): {$result['count']} row(s) would be neutralized.\n";
        foreach ($result['candidates'] as $c) {
            echo "  id={$c['id']} employee_id={$c['employee_id']} colliding_with=" . implode(',', $c['colliding_with']) . "\n";
        }
        echo "\nRe-run with --live to actually apply this change.\n";
    }
}
```

- [ ] **Step 6: Run the dry-run against real live data**

Run:
```bash
/c/xampp81/php/php.exe tools/multitenant/SmartSchoolStaffNeutralizer.php > .superpowers/sdd/smart-school-neutralizer-dry-run-output.txt
cat .superpowers/sdd/smart-school-neutralizer-dry-run-output.txt
```
Expected: `DRY RUN (no changes made): 92 row(s) would be neutralized.` followed by 92 lines, each showing an `id`/`employee_id`/`colliding_with` triple — no password or hash value anywhere in the output. If the count differs from 92, do not treat this as an error to silently reconcile — report the actual count and stop; this task's job is to produce and preserve accurate real output, not to make it match a number written before the tool existed.

- [ ] **Step 7: Confirm zero writes occurred**

Run:
```bash
/c/xampp81/mysql/bin/mysql.exe -u root smart_school -e "SELECT COUNT(*) FROM staff WHERE password = 'NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE';"
```
Expected: `0` — the dry-run must not have written anything.

- [ ] **Step 8: Commit**

```bash
git add tools/multitenant/SmartSchoolStaffNeutralizer.php tests/tools/multitenant/SmartSchoolStaffNeutralizerTest.php .superpowers/sdd/smart-school-neutralizer-dry-run-output.txt
git commit -m "feat: add SmartSchoolStaffNeutralizer CLI entry point, run dry-run against real data"
```

**This task ends here. Do not proceed to Task 3 automatically.** Report DONE with the dry-run output file path and its exact candidate count, and explicitly state that the live run requires the user's review and go-ahead before Task 3 is dispatched.

---

### Task 3 (BLOCKED on explicit user confirmation — do not dispatch until the user has reviewed Task 2's dry-run output and explicitly said to proceed)

**Files:**
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (new top-level section, see Step 6)

**Interfaces:**
- Consumes: `SmartSchoolStaffNeutralizer::executeLive(): array` (from Task 1/2, unchanged).

- [ ] **Step 1: Re-confirm the candidate count fresh, immediately before the live run**

Run:
```bash
/c/xampp81/php/php.exe tools/multitenant/SmartSchoolStaffNeutralizer.php
```
Expected: same candidate count as Task 2's saved dry-run output (`.superpowers/sdd/smart-school-neutralizer-dry-run-output.txt`). If the count has drifted since Task 2, STOP and report the discrepancy — do not proceed with a live run against a set that no longer matches what the user reviewed.

- [ ] **Step 2: Take a pre-run snapshot for post-run comparison**

Run:
```bash
/c/xampp81/mysql/bin/mysql.exe -u root smart_school -e "SELECT * FROM staff WHERE is_active = 1 OR employee_id NOT IN (SELECT employee_id FROM staff WHERE is_active = 0);" > .superpowers/sdd/smart-school-pre-run-snapshot.txt
```
(This captures every row NOT expected to change — the active rows including `hamza.ali@kics.edu.pk`, plus any inactive row that turns out not to collide — for exact byte-for-byte comparison after the run. This file will contain real staff data including names/emails — do not commit it; add it to a cleanup step at the end of this task.)

- [ ] **Step 3: Execute the live run**

Run:
```bash
/c/xampp81/php/php.exe tools/multitenant/SmartSchoolStaffNeutralizer.php --live > .superpowers/sdd/smart-school-neutralizer-live-run-output.txt
cat .superpowers/sdd/smart-school-neutralizer-live-run-output.txt
```
Expected: `LIVE RUN: neutralized N row(s).` where N matches Step 1's re-confirmed count.

- [ ] **Step 4: Post-run verification**

Run each of these and confirm the stated expectation:

```bash
# Every neutralized row now has the sentinel value
/c/xampp81/mysql/bin/mysql.exe -u root smart_school -e "SELECT COUNT(*) FROM staff WHERE password = 'NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE';"
# Expected: matches the count from Step 3

# The snapshot rows (active rows including hamza.ali, and any non-colliding inactive rows) are byte-for-byte unchanged
/c/xampp81/mysql/bin/mysql.exe -u root smart_school -e "SELECT * FROM staff WHERE is_active = 1 OR employee_id NOT IN (SELECT employee_id FROM staff WHERE password = 'NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE');" > .superpowers/sdd/smart-school-post-run-snapshot.txt
diff .superpowers/sdd/smart-school-pre-run-snapshot.txt .superpowers/sdd/smart-school-post-run-snapshot.txt
# Expected: no differences

# hamza.ali@kics.edu.pk specifically is untouched
/c/xampp81/mysql/bin/mysql.exe -u root smart_school -e "SELECT id, employee_id, is_active, password FROM staff WHERE employee_id = '9000';"
# Expected: is_active=1, password is the real bcrypt hash, NOT the sentinel

# All 5 other schools' own staff tables are completely unchanged (row count check as a fast sanity signal)
for db in al_hafeez_campus al_mateen_campus nafay_campus salam_boys_school salam_girls_school; do
  /c/xampp81/mysql/bin/mysql.exe -u root "$db" -e "SELECT COUNT(*) FROM staff;"
done
# Expected: 18, 17, 27, 23, 18 respectively (unchanged from this plan's Context section)
```

- [ ] **Step 5: Run the full existing PHPUnit suite**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit`
Expected: no new failures beyond the already-documented, pre-existing connection-exhaustion flake in `AdminControllerTenantGateTest` (unrelated file).

- [ ] **Step 6: Update the roadmap doc**

Read `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`'s structure first (it has `## Phases`, `## Non-negotiables across every phase`, and `## Carried-forward technical debt` as its top-level sections). Add a new top-level section, `## Data-hygiene remediation efforts`, immediately after `## Phases` and before `## Non-negotiables across every phase` — this is remediation work fixing a found data issue, not a forward migration step, so it doesn't belong under a Phase/Stage number. Add a "Smart School staff contamination cleanup" entry covering: the finding (cross-reference Phase 4 Stage 1's original discovery and this effort's own dedicated investigation), the exact numbers (172 total, 93 colliding, 92 neutralized this run + confirmation of the exact live count, 79 untouched genuine rows, 1 untouched vendor row), the neutralization mechanism and why (sentinel overwrite, not deletion), the mandatory-human-checkpoint process this plan followed, and explicitly flag the student-side `al_mateen_campus`/`smart_school` collisions as a separate, not-yet-addressed follow-up.

- [ ] **Step 7: Clean up local-only artifacts and commit**

```bash
rm -f .superpowers/sdd/smart-school-pre-run-snapshot.txt .superpowers/sdd/smart-school-post-run-snapshot.txt
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md .superpowers/sdd/smart-school-neutralizer-live-run-output.txt
git commit -m "fix: neutralize 92 contaminated staff rows in smart_school, update roadmap"
```

(The pre/post-run snapshot files contain real staff PII and must never be committed — delete them, do not add them. The live-run output file contains only id/employee_id/school-name metadata, safe to commit as an audit record, matching this task's own "no password/hash ever written to a report file" constraint.)

## Final whole-effort review

After Task 3 is complete, dispatch a final adversarial review (most capable available model, given this is the first real-data-mutating change in the entire project) covering the full range from before Task 1's first commit through Task 3's last commit. Explicitly ask it to:

- Independently re-verify the selection criteria are correct and match exactly what was approved (is_active=0 AND cross-school collision, nothing else).
- Independently re-verify, via live read-only queries, that all 79 genuine rows and the `hamza.ali@kics.edu.pk` row are unchanged, and that all 5 other schools' staff tables are unchanged.
- Confirm no cleartext password or hash value appears in any committed file, including the live-run output.
- Confirm the mandatory human checkpoint was actually honored (Task 3 was not dispatched until explicit user go-ahead) by checking the conversation record, not just assuming.
- Confirm the roadmap update is accurate and honestly documents what was and wasn't done (including the student-side follow-up still being open).

If the review finds a Critical or Important issue, stop and present it to the user before considering this effort closed.
