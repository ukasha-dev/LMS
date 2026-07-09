# Multi-Tenant Migration — Phase 2 Stage 3: student_session Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Two things. First, close the merge-tool triplication debt flagged
in Stage 2's final review by extracting a shared `AbstractTenantMerger`
base class from `MergeSchoolData`/`MergeStaffData`/`MergeClassData`,
purely a refactor — no behavior change, all existing tests keep passing
unmodified. Second, migrate `student_session` — the table that actually
links a student to a class/section (`students` itself has neither
column) — proving a real migrated student shows their real class/section,
via a new pilot controller.

**Architecture:** Task 1 is a pure internal refactor of three
already-shipped, already-tested tools. Tasks 2-6 build on it: a new
`student_session` table in `school_saas`; a new `NaturalKeyIdResolver`
class (since Phase 1's and Stage 2's `IdRemapper` old-id→new-id mappings
were never persisted — they're in-memory and discarded once each merge
script's process exits — so reconnecting `student_session`'s
`student_id`/`class_id`/`section_id` to the already-migrated rows in
`school_saas` requires re-deriving those mappings by matching on a stable
natural key: `students.admission_no`, `classes.class`, `sections.section`);
a fourth merge tool (`MergeStudentSessionData`, built on the new base
class from Task 1); and a new `PilotStudentSessions` controller.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, PDO.

## Global Constraints

- Task 1 is a pure refactor: `MergeSchoolData::run()`,
  `MergeStaffData::run()`, and `MergeClassData::run()` must produce
  IDENTICAL observable behavior after the refactor — same return array
  shapes, same remap logic, same transaction/rollback semantics. Do not
  change any of their public interfaces or CLI usage strings.
- Do not modify `application/controllers/Site.php`, `application/libraries/Auth.php`,
  `application/libraries/Db_manager.php`, or any existing model.
- Do not modify `PilotStudents.php`, `PilotLogin.php`, `PilotClasses.php`,
  or `application/core/Tenant_Model.php` — Task 5 adds a NEW controller,
  reusing the `pilot_tenant_id` session convention already established.
- Do not modify `tools/multitenant/TenantScope.php`'s or `IdRemapper.php`'s
  public interfaces.
- Do not modify the existing `al_hafeez_campus` (or any other school)
  database — only read from it.
- All new PHP must run under PHP 8.1.
- Use `127.0.0.1` / `root` / empty password for local MySQL.
- Tenant id `25` is reserved for `al_hafeez_campus`.
- MySQL and Apache are already running — don't start/stop them.
- `sessions` (the academic-year table `student_session.session_id`
  references) is NOT migrated — that column is omitted from this stage's
  minimal `student_session` slice, same as Stage 1 omitted
  `staff.department`/`designation`.

---

### Task 1: Extract `AbstractTenantMerger` — close the triplication debt

**Files:**
- Create: `tools/multitenant/AbstractTenantMerger.php`
- Modify: `tools/multitenant/MergeSchoolData.php`
- Modify: `tools/multitenant/MergeStaffData.php`
- Modify: `tools/multitenant/MergeClassData.php`

**Interfaces:**
- Produces: `abstract class AbstractTenantMerger` with
  `__construct(PDO $source, PDO $target, int $tenantId)`,
  `abstract public function run(): array`, and protected helpers
  `nextId(string $table): int`, `fetchAll(string $sql): array`,
  `insertRow(string $table, array $row): void`,
  `inTransaction(callable $work): void`. Consumed by the three existing
  merge tools (modified in this task) and by Task 4's
  `MergeStudentSessionData`.

This task touches no test files — it's a pure refactor of already-tested
code. Verification is: all 23 existing tests still pass, unmodified,
after the refactor.

- [ ] **Step 1: Create the base class**

Create `tools/multitenant/AbstractTenantMerger.php`:

```php
<?php

abstract class AbstractTenantMerger
{
    protected PDO $source;
    protected PDO $target;
    protected int $tenantId;

    public function __construct(PDO $source, PDO $target, int $tenantId)
    {
        $this->source = $source;
        $this->target = $target;
        $this->tenantId = $tenantId;
    }

    abstract public function run(): array;

    protected function nextId(string $table): int
    {
        $stmt = $this->target->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `{$table}`");

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
    }

    protected function fetchAll(string $sql): array
    {
        return $this->source->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function insertRow(string $table, array $row): void
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

    protected function inTransaction(callable $work): void
    {
        $this->target->beginTransaction();
        try {
            $work();
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }
    }
}
```

- [ ] **Step 2: Refactor `MergeSchoolData` to extend it**

Replace the full contents of `tools/multitenant/MergeSchoolData.php` with:

```php
<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergeSchoolData extends AbstractTenantMerger
{
    public function run(): array
    {
        $studentRemap = new IdRemapper($this->nextId('students'));
        $userRemap = new IdRemapper($this->nextId('users'));

        // Explicit column lists, not SELECT *: the source school databases
        // have the full production schema (~55 columns on students alone,
        // including a `users.childs` column encoding parent->children as a
        // CSV of student ids). school_saas's students/users tables are a
        // deliberately minimal Phase 1 subset (see Task 4). Selecting only
        // the columns that subset actually has keeps this tool honest about
        // what it migrates — anything not listed here (childs included) is
        // explicitly deferred to Phase 2's full-parity schema, not silently
        // dropped or, worse, inserted unremapped into a column that doesn't
        // encode the same ids anymore.
        $students = $this->fetchAll(
            'SELECT id, parent_id, admission_no, firstname, middlename, lastname, is_active FROM students'
        );
        $users = $this->fetchAll(
            'SELECT id, user_id, username, password, role, is_active, created_at FROM users'
        );

        foreach ($students as $row) {
            $studentRemap->remapId((int) $row['id']);
        }
        foreach ($users as $row) {
            $userRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($students, $users, $studentRemap, $userRemap) {
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
        });

        return [
            'students_migrated' => count($students),
            'users_migrated' => count($users),
        ];
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
```

- [ ] **Step 3: Refactor `MergeStaffData` to extend it**

Replace the full contents of `tools/multitenant/MergeStaffData.php` with:

```php
<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergeStaffData extends AbstractTenantMerger
{
    public function run(): array
    {
        $staffRemap = new IdRemapper($this->nextId('staff'));
        $roleRemap = new IdRemapper($this->nextId('roles'));

        $staff = $this->fetchAll(
            'SELECT id, employee_id, name, surname, email, password, gender, image, is_active, verification_code, lang_id, currency_id, created_at, updated_at FROM staff'
        );
        $roles = $this->fetchAll(
            'SELECT id, name, slug, is_active, is_system, is_superadmin, created_at, updated_at FROM roles'
        );
        $staffRoles = $this->fetchAll('SELECT staff_id, role_id, is_active, created_at, updated_at FROM staff_roles');

        foreach ($staff as $row) {
            $staffRemap->remapId((int) $row['id']);
        }
        foreach ($roles as $row) {
            $roleRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($staff, $roles, $staffRoles, $staffRemap, $roleRemap) {
            foreach ($staff as $row) {
                $row['id'] = $staffRemap->getMapping((int) $row['id']);
                $this->insertRow('staff', $row);
            }
            foreach ($roles as $row) {
                $row['id'] = $roleRemap->getMapping((int) $row['id']);
                $this->insertRow('roles', $row);
            }
            foreach ($staffRoles as $row) {
                if (!$staffRemap->hasMapping((int) $row['staff_id']) || !$roleRemap->hasMapping((int) $row['role_id'])) {
                    continue;
                }
                $row['staff_id'] = $staffRemap->getMapping((int) $row['staff_id']);
                $row['role_id'] = $roleRemap->getMapping((int) $row['role_id']);
                $this->insertRow('staff_roles', $row);
            }
        });

        return [
            'staff_migrated' => count($staff),
            'roles_migrated' => count($roles),
            'staff_roles_migrated' => count($staffRoles),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeStaffData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeStaffData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['staff_migrated']} staff, {$result['roles_migrated']} roles, {$result['staff_roles_migrated']} staff_roles for tenant {$tenantId}.\n";
}
```

- [ ] **Step 4: Refactor `MergeClassData` to extend it**

Replace the full contents of `tools/multitenant/MergeClassData.php` with:

```php
<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergeClassData extends AbstractTenantMerger
{
    public function run(): array
    {
        $classRemap = new IdRemapper($this->nextId('classes'));
        $sectionRemap = new IdRemapper($this->nextId('sections'));

        $classes = $this->fetchAll('SELECT id, class, is_active, created_at, updated_at FROM classes');
        $sections = $this->fetchAll('SELECT id, section, is_active, created_at, updated_at FROM sections');
        $classSections = $this->fetchAll('SELECT class_id, section_id, is_active, created_at, updated_at FROM class_sections');

        foreach ($classes as $row) {
            $classRemap->remapId((int) $row['id']);
        }
        foreach ($sections as $row) {
            $sectionRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($classes, $sections, $classSections, $classRemap, $sectionRemap) {
            foreach ($classes as $row) {
                $row['id'] = $classRemap->getMapping((int) $row['id']);
                $this->insertRow('classes', $row);
            }
            foreach ($sections as $row) {
                $row['id'] = $sectionRemap->getMapping((int) $row['id']);
                $this->insertRow('sections', $row);
            }
            foreach ($classSections as $row) {
                if (!$classRemap->hasMapping((int) $row['class_id']) || !$sectionRemap->hasMapping((int) $row['section_id'])) {
                    continue;
                }
                $row['class_id'] = $classRemap->getMapping((int) $row['class_id']);
                $row['section_id'] = $sectionRemap->getMapping((int) $row['section_id']);
                $this->insertRow('class_sections', $row);
            }
        });

        return [
            'classes_migrated' => count($classes),
            'sections_migrated' => count($sections),
            'class_sections_migrated' => count($classSections),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeClassData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeClassData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['classes_migrated']} classes, {$result['sections_migrated']} sections, {$result['class_sections_migrated']} class_sections for tenant {$tenantId}.\n";
}
```

- [ ] **Step 5: Run the full suite — must show zero behavior change**

Run: `"C:\xampp81\php\php.exe" composer.phar dump-autoload` (refresh the
classmap so the new `AbstractTenantMerger` class is autoloadable), then:

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (23 tests, 64 assertions)` — the exact same count as before
this task. If any test fails or the count changes, the refactor changed
behavior and must be fixed before proceeding — do not adjust a test to
make it pass.

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/AbstractTenantMerger.php tools/multitenant/MergeSchoolData.php tools/multitenant/MergeStaffData.php tools/multitenant/MergeClassData.php
git commit -m "refactor: extract AbstractTenantMerger to close merge-tool triplication debt"
```

---

### Task 2: Extend `school_saas` — `student_session` table

**Files:**
- Create: `sql/multitenant/004_add_student_session_table.sql`

**Interfaces:**
- Produces: `student_session` in `school_saas` — `tenant_id`, `student_id`
  (FK to `students(id)`), `class_id` (FK to `classes(id)`), `section_id`
  (FK to `sections(id)`), `is_active`, `created_at`, `updated_at`.
  Consumed by Task 4 (`MergeStudentSessionData`) and Task 5
  (`PilotStudentSessions`).

- [ ] **Step 1: Write the schema SQL**

Create `sql/multitenant/004_add_student_session_table.sql`:

```sql
USE school_saas;

CREATE TABLE student_session (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    section_id INT NOT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_studentsession_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_studentsession_student FOREIGN KEY (student_id) REFERENCES students (id),
    CONSTRAINT fk_studentsession_class FOREIGN KEY (class_id) REFERENCES classes (id),
    CONSTRAINT fk_studentsession_section FOREIGN KEY (section_id) REFERENCES sections (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Apply the schema**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root < sql/multitenant/004_add_student_session_table.sql`

- [ ] **Step 3: Verify**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SHOW TABLES;"`
Expected: 10 tables now, including `student_session`.

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM students; SELECT COUNT(*) FROM classes;"`
Expected: 312 students, 7 classes (unchanged from prior stages).

- [ ] **Step 4: Commit**

```bash
git add sql/multitenant/004_add_student_session_table.sql
git commit -m "feat: add student_session table to school_saas"
```

---

### Task 3: `NaturalKeyIdResolver` — reconnect already-migrated rows by natural key

**Files:**
- Create: `tools/multitenant/NaturalKeyIdResolver.php`
- Test: `tests/tools/multitenant/NaturalKeyIdResolverTest.php`

**Interfaces:**
- Produces: `class NaturalKeyIdResolver` with one method:
  `resolve(PDO $source, PDO $target, int $tenantId, string $table, string $naturalKeyColumn): array`
  — returns `array<int old_id, int new_id>`. Consumed by Task 4's
  `MergeStudentSessionData` (called three times: for `students` via
  `admission_no`, `classes` via `class`, `sections` via `section`).

**Why this exists:** Phase 1's `MergeSchoolData` and Stage 2's
`MergeClassData` each built an `IdRemapper` mapping old-id→new-id
in-memory while they ran, then that process exited — the mapping was
never written anywhere. To link `student_session` rows (which reference
old `student_id`/`class_id`/`section_id` values from the source database)
to the already-migrated rows in `school_saas` (which have different,
already-assigned new ids), this resolver rebuilds the mapping by matching
on a stable natural key present in both databases: source row's
`{naturalKeyColumn}` value → source `id`, and separately target row's
(tenant-scoped) `{naturalKeyColumn}` value → target `id`. Joining those
two maps on the shared natural key value gives old-id→new-id.

Rows with a `null` or empty-string natural key are excluded from both
sides (an empty `admission_no` can't be trusted to uniquely identify a
student) — callers must handle an old id having no entry in the returned
map (this is intentional, not a bug: `MergeStudentSessionData` skips
`student_session` rows that reference such an id, same skip-on-
missing-mapping pattern already used for `class_sections`/`staff_roles`).

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/NaturalKeyIdResolverTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class NaturalKeyIdResolverTest extends TestCase
{
    private PDO $source;
    private PDO $target;
    private NaturalKeyIdResolver $resolver;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS resolver_test_source');
        $admin->exec('CREATE DATABASE resolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS resolver_test_target');
        $admin->exec('CREATE DATABASE resolver_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=resolver_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=resolver_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->source->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL)');
        $this->target->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, tenant_id INT NOT NULL)');

        $this->resolver = new NaturalKeyIdResolver();
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS resolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS resolver_test_target');
    }

    public function testResolvesOldIdToNewIdByMatchingNaturalKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, 'ADM-001', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');

        $this->assertSame([101 => 7], $map);
    }

    public function testExcludesRowsWithNullOrEmptyNaturalKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, NULL), (102, '')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, NULL, 25), (8, '', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');

        $this->assertSame([], $map);
    }

    public function testOnlyMatchesWithinTheGivenTenant(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, 'ADM-001', 99)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');

        $this->assertSame([], $map);
    }

    public function testUnmatchedSourceRowIsSimplyAbsentFromTheMap(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001'), (102, 'ADM-002')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, 'ADM-001', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');

        $this->assertSame([101 => 7], $map);
        $this->assertArrayNotHasKey(102, $map);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/NaturalKeyIdResolverTest.php`
Expected: FAIL — `Class "NaturalKeyIdResolver" not found`.

- [ ] **Step 3: Implement `NaturalKeyIdResolver`**

Create `tools/multitenant/NaturalKeyIdResolver.php`:

```php
<?php

final class NaturalKeyIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId, string $table, string $naturalKeyColumn): array
    {
        $sourceMap = [];
        $sourceStmt = $source->query("SELECT id, `{$naturalKeyColumn}` AS natural_key FROM `{$table}`");
        foreach ($sourceStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['natural_key'] !== null && $row['natural_key'] !== '') {
                $sourceMap[$row['natural_key']] = (int) $row['id'];
            }
        }

        $targetStmt = $target->prepare(
            "SELECT id, `{$naturalKeyColumn}` AS natural_key FROM `{$table}` WHERE tenant_id = :tenant_id"
        );
        $targetStmt->execute([':tenant_id' => $tenantId]);

        $oldToNew = [];
        foreach ($targetStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = $row['natural_key'];
            if ($key !== null && $key !== '' && isset($sourceMap[$key])) {
                $oldToNew[$sourceMap[$key]] = (int) $row['id'];
            }
        }

        return $oldToNew;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/NaturalKeyIdResolverTest.php`
Expected: `OK (4 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (27 tests, ...)` (23 prior + 4 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/NaturalKeyIdResolver.php tests/tools/multitenant/NaturalKeyIdResolverTest.php
git commit -m "feat: add NaturalKeyIdResolver to reconnect already-migrated rows by natural key"
```

---

### Task 4: `MergeStudentSessionData` — migrate the student↔class/section link

**Files:**
- Create: `tools/multitenant/MergeStudentSessionData.php`
- Test: `tests/tools/multitenant/MergeStudentSessionDataTest.php`

**Interfaces:**
- Consumes: `AbstractTenantMerger` (Task 1), `NaturalKeyIdResolver`
  (Task 3).
- Produces: `class MergeStudentSessionData extends AbstractTenantMerger`
  with `run(): array` (returns
  `['student_session_migrated' => int]`). CLI entry point:
  `php tools/multitenant/MergeStudentSessionData.php <source_database> <tenant_id>`.

Unlike the prior three merge tools, this one does NOT use `IdRemapper` —
`students`/`classes`/`sections` were already migrated in earlier
stages/tasks, so there's nothing new to remap; instead it uses
`NaturalKeyIdResolver` three times to reconnect to what's already there.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/MergeStudentSessionDataTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class MergeStudentSessionDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_session_test_source');
        $admin->exec('CREATE DATABASE merge_session_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_session_test_target');
        $admin->exec('CREATE DATABASE merge_session_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_session_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_session_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Source: the "old" school database shape, unremapped ids.
        $this->source->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL)');
        $this->source->exec('CREATE TABLE classes (id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL)');
        $this->source->exec('CREATE TABLE sections (id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL)');
        $this->source->exec('CREATE TABLE student_session (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL, is_active VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)');

        // Target: school_saas shape, already has these rows under DIFFERENT
        // (already-migrated) ids, matched only by natural key.
        $this->target->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, tenant_id INT NOT NULL)');
        $this->target->exec('CREATE TABLE classes (id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL, tenant_id INT NOT NULL)');
        $this->target->exec('CREATE TABLE sections (id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL, tenant_id INT NOT NULL)');
        $this->target->exec('CREATE TABLE student_session (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL, is_active VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, tenant_id INT NOT NULL)');
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_session_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_session_test_target');
    }

    public function testReconnectsStudentSessionToAlreadyMigratedRowsByNaturalKey(): void
    {
        // Old ids in source (100s), already-migrated NEW ids in target (1s) —
        // deliberately non-overlapping ranges so a bug that used the wrong
        // id would be obvious.
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (student_id, class_id, section_id, is_active) VALUES (101, 201, 301, 'yes')");

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");

        $merger = new MergeStudentSessionData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['student_session_migrated']);

        $row = $this->target->query('SELECT * FROM student_session')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $row['student_id']);
        $this->assertSame(2, (int) $row['class_id']);
        $this->assertSame(3, (int) $row['section_id']);
        $this->assertSame(25, (int) $row['tenant_id']);
    }

    public function testSkipsRowsThatReferenceAStudentNotYetMigrated(): void
    {
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // student_id 999 has no corresponding students row at all (and
        // definitely no match in target) — simulates a dangling reference.
        $this->source->exec("INSERT INTO student_session (student_id, class_id, section_id, is_active) VALUES (999, 201, 301, 'yes')");

        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");

        $merger = new MergeStudentSessionData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['student_session_migrated']);
        $count = (int) $this->target->query('SELECT COUNT(*) FROM student_session')->fetchColumn();
        $this->assertSame(0, $count);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeStudentSessionDataTest.php`
Expected: FAIL — `Class "MergeStudentSessionData" not found`.

- [ ] **Step 3: Implement `MergeStudentSessionData`**

Create `tools/multitenant/MergeStudentSessionData.php`:

```php
<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';

final class MergeStudentSessionData extends AbstractTenantMerger
{
    public function run(): array
    {
        $resolver = new NaturalKeyIdResolver();
        $studentMap = $resolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');
        $classMap = $resolver->resolve($this->source, $this->target, $this->tenantId, 'classes', 'class');
        $sectionMap = $resolver->resolve($this->source, $this->target, $this->tenantId, 'sections', 'section');

        $sourceRows = $this->fetchAll(
            'SELECT student_id, class_id, section_id, is_active, created_at, updated_at FROM student_session'
        );

        $rowsToInsert = [];
        foreach ($sourceRows as $row) {
            $oldStudentId = (int) $row['student_id'];
            $oldClassId = (int) $row['class_id'];
            $oldSectionId = (int) $row['section_id'];
            if (!isset($studentMap[$oldStudentId]) || !isset($classMap[$oldClassId]) || !isset($sectionMap[$oldSectionId])) {
                continue;
            }
            $row['student_id'] = $studentMap[$oldStudentId];
            $row['class_id'] = $classMap[$oldClassId];
            $row['section_id'] = $sectionMap[$oldSectionId];
            $rowsToInsert[] = $row;
        }

        $this->inTransaction(function () use ($rowsToInsert) {
            foreach ($rowsToInsert as $row) {
                $this->insertRow('student_session', $row);
            }
        });

        return ['student_session_migrated' => count($rowsToInsert)];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeStudentSessionData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeStudentSessionData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['student_session_migrated']} student_session rows for tenant {$tenantId}.\n";
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeStudentSessionDataTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (29 tests, ...)` (27 prior + 2 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeStudentSessionData.php tests/tools/multitenant/MergeStudentSessionDataTest.php
git commit -m "feat: add MergeStudentSessionData to reconnect migrated students to their class/section"
```

---

### Task 5: `PilotStudentSessions` controller — show each student's class/section

**Files:**
- Create: `application/controllers/PilotStudentSessions.php`
- Create: `application/views/pilot_student_sessions.php`

**Interfaces:**
- Consumes: `Tenant_Model::tenantGetAll(string $table, array $where = []): array`
  (unchanged, from Phase 1). Reuses `pilot_tenant_id` from session,
  same convention as `PilotClasses`.
- Produces: `http://localhost/web-app/pilotstudentsessions/index` — lists
  each `student_session` row's student name alongside their class/section.

Four sequential tenant-scoped lookups (`student_session`, `students`,
`classes`, `sections`), joined in PHP — same no-JOIN-in-TenantScope
constraint as `PilotClasses`.

- [ ] **Step 1: Implement the controller**

Create `application/controllers/PilotStudentSessions.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotStudentSessions extends CI_Controller
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
        $sessions = $this->tenant_model->tenantGetAll('student_session');
        $students = $this->tenant_model->tenantGetAll('students');
        $classes = $this->tenant_model->tenantGetAll('classes');
        $sections = $this->tenant_model->tenantGetAll('sections');

        $studentsById = [];
        foreach ($students as $student) {
            $studentsById[$student['id']] = trim($student['firstname'] . ' ' . ($student['lastname'] ?? ''));
        }

        $classesById = [];
        foreach ($classes as $class) {
            $classesById[$class['id']] = $class['class'];
        }

        $sectionsById = [];
        foreach ($sections as $section) {
            $sectionsById[$section['id']] = $section['section'];
        }

        $rows = [];
        foreach ($sessions as $session) {
            $rows[] = [
                'student' => $studentsById[$session['student_id']] ?? 'Unknown',
                'class' => $classesById[$session['class_id']] ?? 'Unknown',
                'section' => $sectionsById[$session['section_id']] ?? 'Unknown',
            ];
        }

        $this->load->view('pilot_student_sessions', ['rows' => $rows]);
    }
}
```

- [ ] **Step 2: Implement the view**

Create `application/views/pilot_student_sessions.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Student Sessions</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li>
        <?php echo htmlspecialchars($row['student'], ENT_QUOTES); ?>:
        <?php echo htmlspecialchars($row['class'], ENT_QUOTES); ?>
        <?php echo htmlspecialchars($row['section'], ENT_QUOTES); ?>
    </li>
<?php endforeach; ?>
</ul>
```

- [ ] **Step 3: Commit**

```bash
git add application/controllers/PilotStudentSessions.php application/views/pilot_student_sessions.php
git commit -m "feat: add PilotStudentSessions controller showing tenant-scoped student class/section links"
```

---

### Task 6: Migrate pilot tenant's real student_session data + verify end-to-end

**Files:**
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (mark complete)

**Interfaces:** none (runs existing tools against real data).

- [ ] **Step 1: Run the real merge for `al_hafeez_campus`**

```bash
"C:\xampp81\php\php.exe" tools/multitenant/MergeStudentSessionData.php al_hafeez_campus 25
```

Expected: `Migrated N student_session rows for tenant 25.` where N should
be close to (possibly less than) the row count in `al_hafeez_campus.student_session`
— check first: `"C:\xampp81\mysql\bin\mysql.exe" -u root al_hafeez_campus -e "SELECT COUNT(*) FROM student_session;"`.
A count lower than the source is expected/fine ONLY if some rows
reference students/classes/sections that weren't migrated (e.g. inactive/
alumni records outside the 312-student count) — if the count is
dramatically lower than expected, stop and report BLOCKED rather than
assuming it's fine.

- [ ] **Step 2: Spot-check the migration**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM student_session WHERE tenant_id=25;"
```

Pick 2-3 real students from `al_hafeez_campus` and confirm their class/
section in the source matches what's in `school_saas` for the
same-admission_no student:

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root al_hafeez_campus -e "SELECT students.admission_no, classes.class, sections.section FROM student_session JOIN students ON students.id=student_session.student_id JOIN classes ON classes.id=student_session.class_id JOIN sections ON sections.id=student_session.section_id LIMIT 3;"
```

Then find those same `admission_no` values in `school_saas` (join
`student_session`→`students`→`classes`→`sections`, all filtered
`tenant_id=25`) and confirm the class/section names match.

- [ ] **Step 3: Manual end-to-end verification**

```bash
curl -s -c /tmp/pilotsessions_cookies.txt -b /tmp/pilotsessions_cookies.txt "http://localhost/web-app/pilotstudents/login_as/25"
curl -s -c /tmp/pilotsessions_cookies.txt -b /tmp/pilotsessions_cookies.txt "http://localhost/web-app/pilotstudentsessions/index"
```

Expected: HTML listing real student names each with a real class and
section — no "Unknown" anywhere (if "Unknown" appears, stop and report
BLOCKED rather than treating it as acceptable).

- [ ] **Step 4: Mark this plan complete in the roadmap**

Add a line under Phase 2 → Stage 3 in
`docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`,
matching the style of the Stage 1/Stage 2 entries.

- [ ] **Step 5: Commit**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 2 Stage 3 (student_session) complete"
```

---

## Explicitly out of scope for this stage (deferred to later stages)

- The `sessions` (academic year) table — `student_session.session_id` is
  not migrated/mapped; the target table doesn't even have that column.
- `class_teacher`, `subject_group_class_sections`, and every other table
  with an FK to `student_session`/`classes`/`sections`/`class_sections`
  not already covered — later stages/phases.
- Exams and attendance — Stage 4+.
- Real admin panel screens — this stage only proves read access via a
  pilot controller.
- Migrating `student_session` data for any school other than `al_hafeez_campus`.
