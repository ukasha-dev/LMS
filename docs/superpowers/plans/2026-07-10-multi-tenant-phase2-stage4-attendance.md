# Multi-Tenant Migration — Phase 2 Stage 4: Attendance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the pilot tenant's real student attendance records
(`attendence_type` lookup table + `student_attendences`, 1,124 real rows
in `al_hafeez_campus`) into `school_saas`, proving the tenant-scoping
mechanism extends to a table that references TWO different kinds of
already-migrated data: a lookup table migrated in the SAME run
(`attendence_type`, via `IdRemapper`, same pattern as Stages 1/2) and a
table migrated in a PRIOR stage with no persisted id mapping
(`student_session`, from Stage 3 — requiring a new resolver).

**Architecture:** Two new tenant-scoped tables in `school_saas`. A new
`StudentSessionIdResolver` (same shape as Stage 3's
`ClassSectionPairResolver`, with collision-detection built in from the
start this time, not bolted on after a real-data failure) reconnects
`student_attendences.student_session_id` to the already-migrated
`student_session` rows by matching a 3-column composite natural key
(`admission_no`, `class`, `section` — since `student_session` itself has
no single natural-key column of its own). A new `MergeAttendanceData`
(extends `AbstractTenantMerger`) combines `IdRemapper` (for
`attendence_type`, newly migrated in this run) with the new resolver
(for `student_session`, already migrated). A new `PilotAttendance`
controller proves it end-to-end.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, PDO.

## Global Constraints

- Do not modify `application/controllers/Site.php`, `application/libraries/Auth.php`,
  `application/libraries/Db_manager.php`, or any existing model.
- Do not modify `PilotStudents.php`, `PilotLogin.php`, `PilotClasses.php`,
  `PilotStudentSessions.php`, or `application/core/Tenant_Model.php` —
  this stage adds a NEW controller, reusing the `pilot_tenant_id` session
  convention.
- Do not modify `tools/multitenant/TenantScope.php`, `IdRemapper.php`,
  `AbstractTenantMerger.php`, `NaturalKeyIdResolver.php`, or
  `ClassSectionPairResolver.php`.
- Do not modify the existing `al_hafeez_campus` (or any other school)
  database — only read from it.
- **Every new natural-key/composite-key resolver in this stage must
  include collision detection (throw `RuntimeException` on genuine
  ambiguity) from its first version** — Stage 3 had to learn this lesson
  twice (once for `ClassSectionPairResolver`, once for the older sibling
  `NaturalKeyIdResolver`); this stage applies it upfront instead of
  waiting for a real-data failure to reveal the gap.
- All new PHP must run under PHP 8.1.
- Use `127.0.0.1` / `root` / empty password for local MySQL.
- Tenant id `25` is reserved for `al_hafeez_campus`.
- MySQL and Apache are already running — don't start/stop them.
- **Out of scope for this stage** (deferred): `staff_attendance` (0 rows
  currently in `al_hafeez_campus` — nothing real to prove by migrating an
  empty table), `student_subject_attendances` (0 rows, also depends on
  `subject_timetable`, not yet migrated), `student_attendence_schedules`
  and `staff_attendence_schedules` (attendance time-window configuration,
  not attendance records themselves; `staff_attendence_schedules` has 4
  rows but no enforced FKs and is a schedule-config table, not core
  data). Only `attendence_type` + `student_attendences` — the tables with
  real, substantial data (6 rows, 1,124 rows respectively) — are in scope.
- `student_attendences` columns `biometric_attendence`, `qrcode_attendance`,
  `biometric_device_data`, `user_agent` are excluded from this stage's
  minimal slice (same minimal-column-subset principle as every prior
  stage) — they're metadata about HOW attendance was recorded, not the
  attendance record itself.

---

### Task 1: Extend `school_saas` — `attendence_type`, `student_attendences`

**Files:**
- Create: `sql/multitenant/005_add_attendance_tables.sql`

**Interfaces:**
- Produces: `attendence_type` (tenant-scoped lookup table) and
  `student_attendences` (tenant-scoped, FK to `attendence_type(id)` and
  `student_session(id)`) in `school_saas`. Consumed by Task 3
  (`MergeAttendanceData`) and Task 4 (`PilotAttendance`).

- [ ] **Step 1: Write the schema SQL**

Create `sql/multitenant/005_add_attendance_tables.sql`:

```sql
USE school_saas;

CREATE TABLE attendence_type (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    type VARCHAR(50) DEFAULT NULL,
    key_value VARCHAR(50) NOT NULL,
    long_lang_name VARCHAR(250) DEFAULT NULL,
    long_name_style VARCHAR(250) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    for_qr_attendance INT NOT NULL DEFAULT 1,
    for_schedule INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_attendencetype_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_attendences (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_session_id INT NOT NULL,
    date DATE DEFAULT NULL,
    attendence_type_id INT NOT NULL,
    remark VARCHAR(200) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    in_time TIME DEFAULT NULL,
    out_time TIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_studentattendences_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_studentattendences_type FOREIGN KEY (attendence_type_id) REFERENCES attendence_type (id),
    CONSTRAINT fk_studentattendences_session FOREIGN KEY (student_session_id) REFERENCES student_session (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Apply the schema**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root < sql/multitenant/005_add_attendance_tables.sql`

- [ ] **Step 3: Verify**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SHOW TABLES;"`
Expected: 12 tables now, including `attendence_type` and `student_attendences`.

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM students; SELECT COUNT(*) FROM student_session;"`
Expected: 312 students, 484 student_session rows (unchanged from Stage 3).

- [ ] **Step 4: Commit**

```bash
git add sql/multitenant/005_add_attendance_tables.sql
git commit -m "feat: add attendence_type/student_attendences tables to school_saas"
```

---

### Task 2: `StudentSessionIdResolver` — reconnect attendance to already-migrated sessions

**Files:**
- Create: `tools/multitenant/StudentSessionIdResolver.php`
- Test: `tests/tools/multitenant/StudentSessionIdResolverTest.php`

**Interfaces:**
- Produces: `class StudentSessionIdResolver` with
  `resolve(PDO $source, PDO $target, int $tenantId): array` — returns
  `array<int old_student_session_id, int new_student_session_id>`.
  Consumed by Task 3's `MergeAttendanceData`.

`student_session` has no single natural-key column of its own (it's a
join table). This resolver matches on the composite 3-column key
`(students.admission_no, classes.class, sections.section)` — the same
students/classes/sections tables already migrated in prior stages,
joined the same way `Stuattendence_model.php`'s own queries join them.
Collision detection (throw `RuntimeException` on genuine ambiguity) is
included from the start, per this stage's Global Constraints.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/StudentSessionIdResolverTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class StudentSessionIdResolverTest extends TestCase
{
    private PDO $source;
    private PDO $target;
    private StudentSessionIdResolver $resolver;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS sessionresolver_test_source');
        $admin->exec('CREATE DATABASE sessionresolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS sessionresolver_test_target');
        $admin->exec('CREATE DATABASE sessionresolver_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=sessionresolver_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=sessionresolver_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL';
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL';

        $this->source->exec("CREATE TABLE students ({$studentSchema})");
        $this->source->exec("CREATE TABLE classes ({$classSchema})");
        $this->source->exec("CREATE TABLE sections ({$sectionSchema})");
        $this->source->exec("CREATE TABLE student_session ({$sessionSchema})");

        $this->target->exec("CREATE TABLE students ({$studentSchema}, tenant_id INT NOT NULL)");
        $this->target->exec("CREATE TABLE classes ({$classSchema}, tenant_id INT NOT NULL)");
        $this->target->exec("CREATE TABLE sections ({$sectionSchema}, tenant_id INT NOT NULL)");
        $this->target->exec("CREATE TABLE student_session ({$sessionSchema}, tenant_id INT NOT NULL)");

        $this->resolver = new StudentSessionIdResolver();
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS sessionresolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS sessionresolver_test_target');
    }

    public function testResolvesOldSessionIdToNewSessionIdByCompositeKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec('INSERT INTO student_session (id, student_id, class_id, section_id) VALUES (401, 101, 201, 301)');

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec('INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id) VALUES (4, 1, 2, 3, 25)');

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([401 => 4], $map);
    }

    public function testUnmatchedSourceSessionIsAbsentFromTheMap(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001'), (102, 'ADM-002')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec('INSERT INTO student_session (id, student_id, class_id, section_id) VALUES (401, 101, 201, 301), (402, 102, 201, 301)');

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec('INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id) VALUES (4, 1, 2, 3, 25)');

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([401 => 4], $map);
        $this->assertArrayNotHasKey(402, $map);
    }

    public function testThrowsWhenSourceHasAmbiguousDuplicateCompositeKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // Two different session rows for the exact same student/class/section
        // triple -- a genuine data-entry duplicate, must throw rather than
        // silently pick one.
        $this->source->exec('INSERT INTO student_session (id, student_id, class_id, section_id) VALUES (401, 101, 201, 301), (402, 101, 201, 301)');

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec('INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id) VALUES (4, 1, 2, 3, 25)');

        $this->expectException(RuntimeException::class);
        $this->resolver->resolve($this->source, $this->target, 25);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/StudentSessionIdResolverTest.php`
Expected: FAIL — `Class "StudentSessionIdResolver" not found`.

- [ ] **Step 3: Implement `StudentSessionIdResolver`**

Create `tools/multitenant/StudentSessionIdResolver.php`:

```php
<?php

final class StudentSessionIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId): array
    {
        $sourceRows = $source->query(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
        )->fetchAll(PDO::FETCH_ASSOC);

        $targetStmt = $target->prepare(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
            . ' WHERE student_session.tenant_id = :tenant_id'
        );
        $targetStmt->execute([':tenant_id' => $tenantId]);
        $targetRows = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

        $sourceMap = $this->buildKeyedMap($sourceRows, 'source');
        $targetMap = $this->buildKeyedMap($targetRows, 'target');

        $oldToNew = [];
        foreach ($sourceMap as $key => $oldId) {
            if (isset($targetMap[$key])) {
                $oldToNew[$oldId] = $targetMap[$key];
            }
        }

        return $oldToNew;
    }

    private function buildKeyedMap(array $rows, string $side): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = $row['admission_no'] . "\x00" . $row['class_name'] . "\x00" . $row['section_name'];
            $id = (int) $row['id'];
            if (isset($map[$key]) && $map[$key] !== $id) {
                throw new RuntimeException(
                    "Ambiguous student_session key: multiple distinct ids share"
                    . " admission_no/class/section \"{$row['admission_no']}\"/\"{$row['class_name']}\"/\"{$row['section_name']}\""
                    . " in {$side} data — cannot safely resolve. Manual investigation required."
                );
            }
            $map[$key] = $id;
        }

        return $map;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/StudentSessionIdResolverTest.php`
Expected: `OK (3 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (38 tests, ...)` (35 prior + 3 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/StudentSessionIdResolver.php tests/tools/multitenant/StudentSessionIdResolverTest.php
git commit -m "feat: add StudentSessionIdResolver to reconnect attendance to migrated sessions"
```

**Post-Task-2 fix (found while running Task 5 against real data):** the
collision-detection guard fired for real — running the real merge threw
`RuntimeException` for admission_no `10175`/`10122`. Investigation found
both students genuinely have TWO `student_session` rows for the same
class/section (ids 184/2438 and 1412/2476 respectively), differing only
in `session_id` (an academic-year/term column this migration doesn't
track — see this stage's Global Constraints). Critically, **both rows for
both students are marked `is_active='no'`, and neither is referenced by
a single one of the 1,124 `student_attendences` rows** — verified via a
direct join query. This isn't a data-completeness bug like Stage 3's
(nothing gets silently dropped or mis-linked); it's the resolver's
uniqueness domain being broader than it needs to be — it validates
ambiguity across the WHOLE `student_session` table, including dead
historical rows nothing in this migration actually needs.

**Fix:** restrict `StudentSessionIdResolver` to only consider ACTIVE
session rows (`student_session.is_active = 'yes'`) on both source and
target sides. Verified empirically before applying: filtering the real
`al_hafeez_campus` data to `is_active='yes'` and re-checking for
duplicate `(admission_no, class, section)` combinations returns **zero**
collisions — this isn't a fix scoped to just these two students, it
resolves the whole class of the problem, because inactive/historical
duplicate enrollment rows are exactly the kind of noise `is_active`
already exists in this schema to mark.

- [ ] **Fix Step 1: Update `StudentSessionIdResolver` to filter by `is_active`**

Replace the full contents of `tools/multitenant/StudentSessionIdResolver.php` with:

```php
<?php

final class StudentSessionIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId): array
    {
        $sourceRows = $source->query(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
            . " WHERE student_session.is_active = 'yes'"
        )->fetchAll(PDO::FETCH_ASSOC);

        $targetStmt = $target->prepare(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
            . " WHERE student_session.tenant_id = :tenant_id AND student_session.is_active = 'yes'"
        );
        $targetStmt->execute([':tenant_id' => $tenantId]);
        $targetRows = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

        $sourceMap = $this->buildKeyedMap($sourceRows, 'source');
        $targetMap = $this->buildKeyedMap($targetRows, 'target');

        $oldToNew = [];
        foreach ($sourceMap as $key => $oldId) {
            if (isset($targetMap[$key])) {
                $oldToNew[$oldId] = $targetMap[$key];
            }
        }

        return $oldToNew;
    }

    private function buildKeyedMap(array $rows, string $side): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = $row['admission_no'] . "\x00" . $row['class_name'] . "\x00" . $row['section_name'];
            $id = (int) $row['id'];
            if (isset($map[$key]) && $map[$key] !== $id) {
                throw new RuntimeException(
                    "Ambiguous student_session key: multiple distinct ids share"
                    . " admission_no/class/section \"{$row['admission_no']}\"/\"{$row['class_name']}\"/\"{$row['section_name']}\""
                    . " in {$side} data — cannot safely resolve. Manual investigation required."
                );
            }
            $map[$key] = $id;
        }

        return $map;
    }
}
```

(Only the two SQL strings changed — `class_name` and `is_active` addition
to the `WHERE` clauses. `buildKeyedMap()` is unchanged.)

- [ ] **Fix Step 2: Add `is_active` to both test files' `student_session` fixtures**

The resolver's query now references `student_session.is_active`, so both
`tests/tools/multitenant/StudentSessionIdResolverTest.php` and
`tests/tools/multitenant/MergeAttendanceDataTest.php` (which exercises
the resolver indirectly through `MergeAttendanceData`) need that column
added to their `student_session` schema, with existing rows set to
`'yes'` so they keep passing unmodified in behavior.

In `tests/tools/multitenant/StudentSessionIdResolverTest.php`, change:

```php
        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL';
```

to:

```php
        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'yes'";
```

The `DEFAULT 'yes'` means the three existing tests' `INSERT INTO
student_session (id, student_id, class_id, section_id) VALUES (...)`
statements (which don't mention `is_active`) automatically get `'yes'`
and keep passing unmodified — no changes needed to the test bodies
themselves, only the schema.

Apply the identical schema change in
`tests/tools/multitenant/MergeAttendanceDataTest.php`'s `$sessionSchema`
variable (same string, same `DEFAULT 'yes'` addition) — its two existing
tests' `student_session` inserts also omit `is_active` and will get the
same default.

- [ ] **Fix Step 3: Add a regression test proving the fix**

Add to `tests/tools/multitenant/StudentSessionIdResolverTest.php`:

```php
    public function testExcludesInactiveSessionsFromBothTheMapAndCollisionDetection(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // Two INACTIVE session rows for the exact same student/class/section
        // triple -- mirrors the real al_hafeez_campus collision (admission_no
        // 10175/10122, both duplicate rows is_active='no'). Must NOT throw,
        // and neither row should appear in the result map.
        $this->source->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, is_active) VALUES (401, 101, 201, 301, 'no'), (402, 101, 201, 301, 'no')"
        );

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([], $map);
    }
```

- [ ] **Fix Step 4: Run tests**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/StudentSessionIdResolverTest.php tests/tools/multitenant/MergeAttendanceDataTest.php`
Expected: `OK (6 tests, ...)` (4 in `StudentSessionIdResolverTest` — 3
existing + 1 new — plus 2 unmodified in `MergeAttendanceDataTest`).

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (41 tests, ...)` (40 prior + 1 new).

- [ ] **Fix Step 5: Commit**

```bash
git add tools/multitenant/StudentSessionIdResolver.php tests/tools/multitenant/StudentSessionIdResolverTest.php tests/tools/multitenant/MergeAttendanceDataTest.php
git commit -m "fix: restrict StudentSessionIdResolver to active sessions to avoid dead-row collisions"
```

**Post-Task-2 fix, round 2 (adversarial review caught round 1 was wrong,
commit `19ff67509a01f804585c671de0c4edffb80d975e` superseded):** the
independent reviewer re-derived the underlying data directly instead of
trusting the round-1 analysis, and found the `is_active='yes'` filter is
a **vacuous-truth bug**: every single one of `al_hafeez_campus`'s 484
`student_session` rows is `is_active='no'` — there are zero `'yes'` rows
on either the source or the (Stage-3-migrated) target side. So "zero
collisions among active rows" was trivially true because the active-row
set is empty. Live-probing `resolve()` against the real databases
confirmed the round-1 code returns an **empty map** (0 entries), which
made `MergeAttendanceData` report `student_attendences_migrated => 0`
while claiming success — silent 100% data loss, strictly worse than
round 1's honest, loud `RuntimeException`. `is_active` in this schema
apparently marks "currently enrolled this term," not "was active when
attendance happened" — it carries no signal relevant to this migration
at all and must not be used to filter attendance-bearing rows.

**Root-cause re-diagnosis:** direct SQL against both databases (see
below) showed the two colliding students' duplicate rows differ by
`session_id` (an academic-term column present on the source
`student_session` table but never migrated to the target schema by
Stage 3 — `school_saas.student_session` has no `session_id` column) —
but **both source and target duplicate rows have byte-identical
`created_at`/`updated_at` timestamps preserved 1:1 across the Stage 3
merge** (verified: `10122` → source ids 1412/2476 with `created_at`
`2025-07-18 11:21:38`/`2026-02-10 10:42:37`, target ids 518/643 with the
*exact same two timestamps*; `10175` → source 184/2438 vs target
340/606, same pairing). Re-running the collision-group query with
`created_at` added to the `GROUP BY` on both `al_hafeez_campus` and
`school_saas` (tenant 25) returns **zero** remaining collision groups on
either side, with both tables still showing their full 484 rows and all
213 distinct attendance-referenced session ids present with no dangling
references. `created_at` is a real, already-preserved column on both
sides (unlike `session_id`, which only exists on the source) — extending
the composite key to include it is the narrowest fix that actually
matches the data.

**Fix:** extend `StudentSessionIdResolver`'s composite natural key from
3 columns (`admission_no`, `class`, `section`) to 4
(`admission_no`, `class`, `section`, `created_at`) — no `is_active`
filtering at all. Revert the round-1 `WHERE`/`AND is_active = 'yes'`
clauses entirely.

- [ ] **Fix Round 2, Step 1: Replace `StudentSessionIdResolver` with the `created_at`-keyed version**

Replace the full contents of `tools/multitenant/StudentSessionIdResolver.php` with:

```php
<?php

final class StudentSessionIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId): array
    {
        $sourceRows = $source->query(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name,'
            . ' student_session.created_at AS created_at'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
        )->fetchAll(PDO::FETCH_ASSOC);

        $targetStmt = $target->prepare(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name,'
            . ' student_session.created_at AS created_at'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
            . ' WHERE student_session.tenant_id = :tenant_id'
        );
        $targetStmt->execute([':tenant_id' => $tenantId]);
        $targetRows = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

        $sourceMap = $this->buildKeyedMap($sourceRows, 'source');
        $targetMap = $this->buildKeyedMap($targetRows, 'target');

        $oldToNew = [];
        foreach ($sourceMap as $key => $oldId) {
            if (isset($targetMap[$key])) {
                $oldToNew[$oldId] = $targetMap[$key];
            }
        }

        return $oldToNew;
    }

    private function buildKeyedMap(array $rows, string $side): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = $row['admission_no'] . "\x00" . $row['class_name'] . "\x00" . $row['section_name'] . "\x00" . $row['created_at'];
            $id = (int) $row['id'];
            if (isset($map[$key]) && $map[$key] !== $id) {
                throw new RuntimeException(
                    "Ambiguous student_session key: multiple distinct ids share"
                    . " admission_no/class/section/created_at \"{$row['admission_no']}\"/\"{$row['class_name']}\"/\"{$row['section_name']}\"/\"{$row['created_at']}\""
                    . " in {$side} data — cannot safely resolve. Manual investigation required."
                );
            }
            $map[$key] = $id;
        }

        return $map;
    }
}
```

- [ ] **Fix Round 2, Step 2: Revert the `is_active` schema/test changes from round 1, add `created_at`**

In both `tests/tools/multitenant/StudentSessionIdResolverTest.php` and
`tests/tools/multitenant/MergeAttendanceDataTest.php`, change
`$sessionSchema` back to omit `is_active` (round 1's addition is no
longer read by the resolver) and instead add a `created_at` column:

```php
        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
```

Existing tests that insert exactly one row per natural key are
unaffected by the default. The existing
`testThrowsWhenSourceHasAmbiguousDuplicateCompositeKey` test inserts two
rows sharing the same natural key in the same statement with no explicit
`created_at` — both get the same default timestamp (same INSERT
statement, same instant), so the collision remains genuine and the test
still correctly expects a `RuntimeException`. No changes needed to that
test's body.

Delete round 1's `testExcludesInactiveSessionsFromBothTheMapAndCollisionDetection`
test entirely (it encoded the disproven `is_active` theory) and replace
it with:

```php
    public function testDistinctCreatedAtDisambiguatesSameCompositeKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // Two session rows for the exact same student/class/section triple,
        // at two different points in time -- mirrors the real
        // al_hafeez_campus collision (admission_no 10175/10122: two
        // enrollments in the same class/section a school year apart, same
        // admission_no/class/section, distinct created_at). Must NOT throw,
        // and each old id must resolve to its own matching target id.
        $this->source->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES"
            . " (401, 101, 201, 301, '2025-07-18 11:21:38'), (402, 101, 201, 301, '2026-02-10 10:42:37')"
        );

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES"
            . " (4, 1, 2, 3, 25, '2025-07-18 11:21:38'), (5, 1, 2, 3, 25, '2026-02-10 10:42:37')"
        );

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([401 => 4, 402 => 5], $map);
    }
```

- [ ] **Fix Round 2, Step 3: Run tests**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (41 tests, ...)` (same total as round 1 — one test removed,
one added).

- [ ] **Fix Round 2, Step 4: Verify against real data before re-attempting Task 5**

Write a throwaway probe script (not committed) that calls
`StudentSessionIdResolver::resolve()` against real PDO connections to
`al_hafeez_campus` (source) and `school_saas` tenant 25 (target).
Expected: no exception, map size 484 (all source rows resolve — the
round-1 probe returned 0; this must not repeat), and specifically that
old ids 184→340, 2438→606, 1412→518, 2476→643 (the four previously
colliding rows) all appear correctly in the map.

- [ ] **Fix Round 2, Step 5: Commit**

```bash
git add tools/multitenant/StudentSessionIdResolver.php tests/tools/multitenant/StudentSessionIdResolverTest.php tests/tools/multitenant/MergeAttendanceDataTest.php
git commit -m "fix: key StudentSessionIdResolver on created_at instead of is_active, which carries no real signal here"
```

---

### Task 3: `MergeAttendanceData` — migrate attendance types + records

**Files:**
- Create: `tools/multitenant/MergeAttendanceData.php`
- Test: `tests/tools/multitenant/MergeAttendanceDataTest.php`

**Interfaces:**
- Consumes: `AbstractTenantMerger` (Task 1 of Stage 3), `IdRemapper`
  (Phase 1), `StudentSessionIdResolver` (Task 2 of this stage).
- Produces: `class MergeAttendanceData extends AbstractTenantMerger` with
  `run(): array` (returns
  `['attendence_types_migrated' => int, 'student_attendences_migrated' => int]`).
  CLI: `php tools/multitenant/MergeAttendanceData.php <source_database> <tenant_id>`.

This tool combines both migration patterns established so far:
`attendence_type` is newly migrated IN THIS RUN (so it uses `IdRemapper`,
same as every table in Stages 1/2), while `student_session` was migrated
in a PRIOR stage with no persisted mapping (so it uses the new
natural-key resolver, same principle as Stage 3).

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/MergeAttendanceDataTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class MergeAttendanceDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_attendance_test_source');
        $admin->exec('CREATE DATABASE merge_attendance_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_attendance_test_target');
        $admin->exec('CREATE DATABASE merge_attendance_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_attendance_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_attendance_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL';
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL';
        $typeSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(50) DEFAULT NULL, key_value VARCHAR(50) NOT NULL,'
            . ' long_lang_name VARCHAR(250) DEFAULT NULL, long_name_style VARCHAR(250) DEFAULT NULL,'
            . " is_active VARCHAR(255) DEFAULT NULL, for_qr_attendance INT NOT NULL DEFAULT 1, for_schedule INT NOT NULL DEFAULT 0,"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $attendanceSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_session_id INT NOT NULL, date DATE DEFAULT NULL,'
            . ' attendence_type_id INT NOT NULL, remark VARCHAR(200) DEFAULT NULL, is_active VARCHAR(255) DEFAULT NULL,'
            . ' in_time TIME DEFAULT NULL, out_time TIME DEFAULT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE classes ({$classSchema}{$tenantCol})");
            $db->exec("CREATE TABLE sections ({$sectionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_session ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE attendence_type ({$typeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_attendences ({$attendanceSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_attendance_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_attendance_test_target');
    }

    public function testMergesAttendanceTypesAndRecordsWithResolvedSessionAndType(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec('INSERT INTO student_session (id, student_id, class_id, section_id) VALUES (401, 101, 201, 301)');
        $this->source->exec("INSERT INTO attendence_type (id, type, key_value) VALUES (1, 'Present', 'P')");
        $this->source->exec("INSERT INTO student_attendences (student_session_id, date, attendence_type_id, remark) VALUES (401, '2026-01-15', 1, '')");

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec('INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id) VALUES (4, 1, 2, 3, 25)');

        $merger = new MergeAttendanceData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['attendence_types_migrated']);
        $this->assertSame(1, $result['student_attendences_migrated']);

        $type = $this->target->query('SELECT * FROM attendence_type')->fetch(PDO::FETCH_ASSOC);
        $attendance = $this->target->query('SELECT * FROM student_attendences')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Present', $type['type']);
        $this->assertSame(25, (int) $type['tenant_id']);
        $this->assertSame(4, (int) $attendance['student_session_id']);
        $this->assertSame((int) $type['id'], (int) $attendance['attendence_type_id']);
        $this->assertSame(25, (int) $attendance['tenant_id']);
        $this->assertSame('2026-01-15', $attendance['date']);
    }

    public function testSkipsAttendanceRowsReferencingAnUnmigratedStudentSession(): void
    {
        $this->source->exec("INSERT INTO attendence_type (id, type, key_value) VALUES (1, 'Present', 'P')");
        // student_session_id 999 has no corresponding row anywhere -- a
        // dangling reference that must be skipped, not inserted broken.
        $this->source->exec("INSERT INTO student_attendences (student_session_id, date, attendence_type_id, remark) VALUES (999, '2026-01-15', 1, '')");

        $merger = new MergeAttendanceData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['attendence_types_migrated']);
        $this->assertSame(0, $result['student_attendences_migrated']);
        $count = (int) $this->target->query('SELECT COUNT(*) FROM student_attendences')->fetchColumn();
        $this->assertSame(0, $count);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeAttendanceDataTest.php`
Expected: FAIL — `Class "MergeAttendanceData" not found`.

- [ ] **Step 3: Implement `MergeAttendanceData`**

Create `tools/multitenant/MergeAttendanceData.php`:

```php
<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/StudentSessionIdResolver.php';

final class MergeAttendanceData extends AbstractTenantMerger
{
    public function run(): array
    {
        $typeRemap = new IdRemapper($this->nextId('attendence_type'));

        $types = $this->fetchAll(
            'SELECT id, type, key_value, long_lang_name, long_name_style, is_active, for_qr_attendance, for_schedule, created_at, updated_at FROM attendence_type'
        );
        foreach ($types as $row) {
            $typeRemap->remapId((int) $row['id']);
        }

        $sessionResolver = new StudentSessionIdResolver();
        $sessionMap = $sessionResolver->resolve($this->source, $this->target, $this->tenantId);

        $attendances = $this->fetchAll(
            'SELECT student_session_id, date, attendence_type_id, remark, is_active, in_time, out_time, created_at, updated_at FROM student_attendences'
        );

        $rowsToInsert = [];
        foreach ($attendances as $row) {
            $oldSessionId = (int) $row['student_session_id'];
            $oldTypeId = (int) $row['attendence_type_id'];
            if (!isset($sessionMap[$oldSessionId]) || !$typeRemap->hasMapping($oldTypeId)) {
                continue;
            }
            $row['student_session_id'] = $sessionMap[$oldSessionId];
            $row['attendence_type_id'] = $typeRemap->getMapping($oldTypeId);
            $rowsToInsert[] = $row;
        }

        $this->inTransaction(function () use ($types, $typeRemap, $rowsToInsert) {
            foreach ($types as $row) {
                $row['id'] = $typeRemap->getMapping((int) $row['id']);
                $this->insertRow('attendence_type', $row);
            }
            foreach ($rowsToInsert as $row) {
                $this->insertRow('student_attendences', $row);
            }
        });

        return [
            'attendence_types_migrated' => count($types),
            'student_attendences_migrated' => count($rowsToInsert),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeAttendanceData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeAttendanceData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['attendence_types_migrated']} attendance types and {$result['student_attendences_migrated']} student attendance records for tenant {$tenantId}.\n";
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeAttendanceDataTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (40 tests, ...)` (38 prior + 2 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeAttendanceData.php tests/tools/multitenant/MergeAttendanceDataTest.php
git commit -m "feat: add MergeAttendanceData CLI tool for attendance tenant migration"
```

---

### Task 4: `PilotAttendance` controller — show each student's attendance

**Files:**
- Create: `application/controllers/PilotAttendance.php`
- Create: `application/views/pilot_attendance.php`

**Interfaces:**
- Consumes: `Tenant_Model::tenantGetAll()`. Reuses `pilot_tenant_id` from
  session (set by a prior visit to `PilotStudents::login_as()` or
  `PilotLogin::login()`), same convention as `PilotClasses`/
  `PilotStudentSessions`.
- Produces: `http://localhost/web-app/pilotattendance/index` — lists
  each attendance record's student name, date, and attendance type.

Four sequential tenant-scoped lookups (`student_attendences`,
`student_session`, `students`, `attendence_type`), joined in PHP.

- [ ] **Step 1: Implement the controller**

Create `application/controllers/PilotAttendance.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotAttendance extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database('school_saas_pilot');
        require_once APPPATH . 'core/Tenant_Model.php';
        $this->load->model('Tenant_Model', 'tenant_model');
    }

    public function index()
    {
        $attendances = $this->tenant_model->tenantGetAll('student_attendences');
        $sessions = $this->tenant_model->tenantGetAll('student_session');
        $students = $this->tenant_model->tenantGetAll('students');
        $types = $this->tenant_model->tenantGetAll('attendence_type');

        $studentIdBySessionId = [];
        foreach ($sessions as $session) {
            $studentIdBySessionId[$session['id']] = $session['student_id'];
        }

        $studentNameById = [];
        foreach ($students as $student) {
            $studentNameById[$student['id']] = trim($student['firstname'] . ' ' . ($student['lastname'] ?? ''));
        }

        $typeNameById = [];
        foreach ($types as $type) {
            $typeNameById[$type['id']] = $type['type'];
        }

        $rows = [];
        foreach ($attendances as $attendance) {
            $studentId = $studentIdBySessionId[$attendance['student_session_id']] ?? null;
            $rows[] = [
                'student' => $studentId !== null ? ($studentNameById[$studentId] ?? 'Unknown') : 'Unknown',
                'date' => $attendance['date'],
                'type' => $typeNameById[$attendance['attendence_type_id']] ?? 'Unknown',
            ];
        }

        $this->load->view('pilot_attendance', ['rows' => $rows]);
    }
}
```

- [ ] **Step 2: Implement the view**

Create `application/views/pilot_attendance.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Attendance</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li>
        <?php echo htmlspecialchars($row['student'], ENT_QUOTES); ?> —
        <?php echo htmlspecialchars((string) $row['date'], ENT_QUOTES); ?>:
        <?php echo htmlspecialchars($row['type'], ENT_QUOTES); ?>
    </li>
<?php endforeach; ?>
</ul>
```

- [ ] **Step 3: Commit**

```bash
git add application/controllers/PilotAttendance.php application/views/pilot_attendance.php
git commit -m "feat: add PilotAttendance controller showing tenant-scoped attendance records"
```

---

### Task 5: Migrate pilot tenant's real attendance data + verify end-to-end

**Files:**
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (mark complete)

**Interfaces:** none (runs existing tools against real data).

- [ ] **Step 1: Run the real merge for `al_hafeez_campus`**

```bash
"C:\xampp81\php\php.exe" tools/multitenant/MergeAttendanceData.php al_hafeez_campus 25
```

Expected: `Migrated 6 attendance types and 1124 student attendance records for tenant 25.`
(counts per the row counts confirmed earlier: 6 attendence_type rows,
1124 student_attendences rows in `al_hafeez_campus`.) If the migrated
attendance-records count is meaningfully lower than 1124, investigate
before proceeding — don't assume it's fine (unlike Stage 3, there's no
known reason for a gap here, since `student_attendences` only depends on
`student_session` — already fully migrated with all 484 rows in Stage
3 — and `attendence_type`, migrated fresh in this same run).

- [ ] **Step 2: Verify row counts match source**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root al_hafeez_campus -e "SELECT COUNT(*) FROM attendence_type; SELECT COUNT(*) FROM student_attendences;"
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM attendence_type WHERE tenant_id=25; SELECT COUNT(*) FROM student_attendences WHERE tenant_id=25;"
```

Expected: matching counts (6/1124 both sides).

- [ ] **Step 3: Spot-check 2-3 real attendance records**

Pick a few rows from `al_hafeez_campus.student_attendences`, join through
to get the real student admission_no, date, and attendance type name, and
confirm the same admission_no/date/type combination appears in
`school_saas` for tenant 25.

- [ ] **Step 4: Manual end-to-end verification**

```bash
curl -s -c /tmp/pilotattendance_cookies.txt -b /tmp/pilotattendance_cookies.txt "http://localhost/web-app/pilotstudents/login_as/25"
curl -s -c /tmp/pilotattendance_cookies.txt -b /tmp/pilotattendance_cookies.txt "http://localhost/web-app/pilotattendance/index"
```

Expected: HTML listing real student names with real dates and real
attendance type names (Present/Absent/Late/etc.) — no "Unknown"
anywhere. Count the `<li>` entries — should be 1124.

- [ ] **Step 5: Mark this plan complete in the roadmap**

Add a line under Phase 2 → Stage 4 in
`docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`,
matching the style of the Stage 1-3 entries.

- [ ] **Step 6: Commit**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 2 Stage 4 (attendance) complete"
```

---

## Explicitly out of scope for this stage (deferred to later stages)

- `staff_attendance`, `staff_attendance_type` — 0 rows currently, nothing
  real to prove; a later stage once staff attendance data exists.
- `student_subject_attendances` — 0 rows, depends on `subject_timetable`
  (not yet migrated).
- `student_attendence_schedules`, `staff_attendence_schedules` —
  attendance time-window configuration, not attendance records.
- `biometric_attendence`, `qrcode_attendance`, `biometric_device_data`,
  `user_agent` columns on `student_attendences` — metadata about how
  attendance was recorded, excluded from this stage's minimal slice.
- Real admin panel attendance screens — this stage only proves read
  access via a pilot controller.
- Migrating attendance data for any school other than `al_hafeez_campus`.
- Exams — a separate module, not yet planned.
