# Multi-Tenant Migration — Phase 2 Stage 2: Classes/Sections Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the pilot tenant's class/section catalog (`classes`,
`sections`, `class_sections`) into `school_saas`, proving the merge and
tenant-scoping mechanism extends cleanly to a third table group, via a
new pilot controller that lists each class with its sections.

**Architecture:** Same shape as Stage 1 exactly. Three new tenant-scoped
tables in `school_saas`. A new `MergeClassData` CLI tool (same structure
as `MergeStaffData` — `classes`/`sections` have no dependency on each
other, `class_sections` depends one-directionally on both, no
circularity). A new `PilotClasses` controller reads via `Tenant_Model`,
reusing the `pilot_tenant_id` session key already established by Stage
1's `PilotLogin`/Phase 1's `PilotStudents` — no new login flow needed.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, PDO.

## Global Constraints

- Do not modify `application/controllers/Site.php`, `application/libraries/Auth.php`,
  `application/libraries/Db_manager.php`, or any existing model.
- Do not modify `application/controllers/PilotStudents.php`, `PilotLogin.php`,
  or `application/core/Tenant_Model.php` — this stage only ADDS a new
  controller, reusing what already exists.
- Do not modify `tools/multitenant/TenantScope.php`'s or `IdRemapper.php`'s
  public interfaces.
- Do not modify the existing `al_hafeez_campus` (or any other school)
  database — only read from it.
- All new PHP must run under PHP 8.1.
- Use `127.0.0.1` / `root` / empty password for local MySQL.
- Tenant id `25` is reserved for `al_hafeez_campus`.
- MySQL and Apache are already running — don't start/stop them.
- **`student_session` (the table that actually links a migrated student to
  a class/section) is explicitly OUT OF SCOPE for this stage** — see the
  bottom of this document. This stage migrates the class/section
  *catalog* only; connecting existing students to it is a later stage.

---

### Task 1: Extend `school_saas` — `classes`, `sections`, `class_sections`

**Files:**
- Create: `sql/multitenant/003_add_class_section_tables.sql`

**Interfaces:**
- Produces: three new tables in `school_saas` — `classes`, `sections`,
  `class_sections`, each with `tenant_id` and FK to `tenants(id)`, plus
  `class_sections.class_id → classes(id)` and
  `class_sections.section_id → sections(id)`. Consumed by Task 2
  (`MergeClassData`) and Task 3 (`PilotClasses`).

Column selection matches the real production schema exactly for these
three tables — they're already minimal (5-6 columns each in production),
so no slicing needed, unlike `staff`'s 47-column production table.

- [ ] **Step 1: Write the schema SQL**

Create `sql/multitenant/003_add_class_section_tables.sql`:

```sql
USE school_saas;

CREATE TABLE classes (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    class VARCHAR(60) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_classes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sections (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    section VARCHAR(60) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sections_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE class_sections (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    class_id INT NOT NULL,
    section_id INT NOT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_classsections_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_classsections_class FOREIGN KEY (class_id) REFERENCES classes (id),
    CONSTRAINT fk_classsections_section FOREIGN KEY (section_id) REFERENCES sections (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Apply the schema**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root < sql/multitenant/003_add_class_section_tables.sql`
Expected: no output on success.

- [ ] **Step 3: Verify the tables exist and prior data is untouched**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SHOW TABLES;"`
Expected: `class_sections, classes, roles, sections, staff, staff_roles, students, tenants, users` (9 tables).

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM students; SELECT COUNT(*) FROM staff;"`
Expected: 312 students, 18 staff (unchanged from prior stages).

- [ ] **Step 4: Commit**

```bash
git add sql/multitenant/003_add_class_section_tables.sql
git commit -m "feat: add classes/sections/class_sections tables to school_saas"
```

---

### Task 2: `MergeClassData` — migrate class/section catalog for one tenant

**Files:**
- Create: `tools/multitenant/MergeClassData.php`
- Test: `tests/tools/multitenant/MergeClassDataTest.php`

**Interfaces:**
- Consumes: `IdRemapper` (unchanged, `tools/multitenant/IdRemapper.php`).
- Produces: `class MergeClassData` with
  `__construct(PDO $source, PDO $target, int $tenantId)` and
  `run(): array` (returns
  `['classes_migrated' => int, 'sections_migrated' => int, 'class_sections_migrated' => int]`).
  CLI entry point: `php tools/multitenant/MergeClassData.php <source_database> <tenant_id>`.

Same shape as `MergeStaffData`: `classes` and `sections` are independent
of each other and of `class_sections`, so remap-and-insert both first,
then `class_sections` last. `class_sections.id` itself is never
referenced by anything in this stage's scope (production tables that DO
reference it — `contents.cls_sec_id`, `online_admissions.class_section_id`,
etc. — are all out of scope), so the target's `AUTO_INCREMENT` assigns it
fresh, same as `staff_roles.id` in Stage 1.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/MergeClassDataTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class MergeClassDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_class_test_source');
        $admin->exec('CREATE DATABASE merge_class_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_class_test_target');
        $admin->exec('CREATE DATABASE merge_class_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_class_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_class_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'no',"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE classes ({$classSchema})");
        $this->target->exec("CREATE TABLE classes ({$classSchema}, tenant_id INT NOT NULL)");

        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'no',"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE sections ({$sectionSchema})");
        $this->target->exec("CREATE TABLE sections ({$sectionSchema}, tenant_id INT NOT NULL)");

        $classSectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'no',"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE class_sections ({$classSectionSchema})");
        $this->target->exec("CREATE TABLE class_sections ({$classSectionSchema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_class_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_class_test_target');
    }

    public function testMergesClassesSectionsAndClassSectionsWithRemappedForeignKeys(): void
    {
        $this->source->exec("INSERT INTO classes (id, class) VALUES (1, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (1, 'A')");
        $this->source->exec('INSERT INTO class_sections (id, class_id, section_id) VALUES (1, 1, 1)');

        $merger = new MergeClassData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['classes_migrated']);
        $this->assertSame(1, $result['sections_migrated']);
        $this->assertSame(1, $result['class_sections_migrated']);

        $class = $this->target->query('SELECT * FROM classes')->fetch(PDO::FETCH_ASSOC);
        $section = $this->target->query('SELECT * FROM sections')->fetch(PDO::FETCH_ASSOC);
        $classSection = $this->target->query('SELECT * FROM class_sections')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Class 1', $class['class']);
        $this->assertSame(25, (int) $class['tenant_id']);
        $this->assertSame(25, (int) $section['tenant_id']);
        $this->assertSame(25, (int) $classSection['tenant_id']);
        $this->assertSame((int) $class['id'], (int) $classSection['class_id']);
        $this->assertSame((int) $section['id'], (int) $classSection['section_id']);
    }

    public function testStartsClassAndSectionIdsAfterExistingTargetRowsToAvoidCollision(): void
    {
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (500, 'Existing', 1)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (700, 'Existing', 1)");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (1, 'Class 2')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (1, 'B')");
        $this->source->exec('INSERT INTO class_sections (id, class_id, section_id) VALUES (1, 1, 1)');

        $merger = new MergeClassData($this->source, $this->target, 2);
        $merger->run();

        $newClass = $this->target->query("SELECT * FROM classes WHERE class = 'Class 2'")->fetch(PDO::FETCH_ASSOC);
        $newSection = $this->target->query("SELECT * FROM sections WHERE section = 'B'")->fetch(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(500, (int) $newClass['id']);
        $this->assertGreaterThan(700, (int) $newSection['id']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeClassDataTest.php`
Expected: FAIL — `Class "MergeClassData" not found`.

- [ ] **Step 3: Implement `MergeClassData`**

Create `tools/multitenant/MergeClassData.php`:

```php
<?php

require_once __DIR__ . '/IdRemapper.php';

final class MergeClassData
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

        $this->target->beginTransaction();
        try {
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
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }

        return [
            'classes_migrated' => count($classes),
            'sections_migrated' => count($sections),
            'class_sections_migrated' => count($classSections),
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

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeClassDataTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Run the full suite to check for regressions**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: all 21 prior tests plus these 2 new ones pass (23 tests total).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeClassData.php tests/tools/multitenant/MergeClassDataTest.php
git commit -m "feat: add MergeClassData CLI tool for class/section tenant migration"
```

---

### Task 3: `PilotClasses` controller — list classes with their sections

**Files:**
- Create: `application/controllers/PilotClasses.php`
- Create: `application/views/pilot_classes.php`

**Interfaces:**
- Consumes: `Tenant_Model::tenantGetAll(string $table, array $where = []): array`
  (unchanged, from Phase 1). Reuses the `pilot_tenant_id` session key
  already set by `PilotStudents::login_as()` or `PilotLogin::login()` —
  this controller does NOT set it itself, so a pilot session must already
  exist from a prior visit to one of those (documented in Task 4's manual
  verification step).
- Produces: `http://localhost/web-app/pilotclasses/index` — lists each
  class with its sections, tenant-scoped.

`class_sections` links classes to sections, but `TenantScope` has no JOIN
support (by design, per Phase 1/Stage 1's YAGNI decision) — so this
controller does three sequential tenant-scoped lookups: all classes, all
sections, all class_sections, then joins them in PHP.

- [ ] **Step 1: Implement the controller**

Create `application/controllers/PilotClasses.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotClasses extends CI_Controller
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
        $classes = $this->tenant_model->tenantGetAll('classes');
        $sections = $this->tenant_model->tenantGetAll('sections');
        $classSections = $this->tenant_model->tenantGetAll('class_sections');

        $sectionsById = [];
        foreach ($sections as $section) {
            $sectionsById[$section['id']] = $section['section'];
        }

        $classSectionsByClassId = [];
        foreach ($classSections as $link) {
            $classSectionsByClassId[$link['class_id']][] = $sectionsById[$link['section_id']] ?? 'Unknown';
        }

        $rows = [];
        foreach ($classes as $class) {
            $rows[] = [
                'class' => $class['class'],
                'sections' => $classSectionsByClassId[$class['id']] ?? [],
            ];
        }

        $this->load->view('pilot_classes', ['rows' => $rows]);
    }
}
```

- [ ] **Step 2: Implement the view**

Create `application/views/pilot_classes.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Classes</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li>
        <?php echo htmlspecialchars($row['class'], ENT_QUOTES); ?>:
        <?php echo htmlspecialchars(implode(', ', $row['sections']), ENT_QUOTES); ?>
    </li>
<?php endforeach; ?>
</ul>
```

- [ ] **Step 3: Commit**

```bash
git add application/controllers/PilotClasses.php application/views/pilot_classes.php
git commit -m "feat: add PilotClasses controller listing tenant-scoped class/section catalog"
```

---

### Task 4: Migrate pilot tenant's real class/section data + verify end-to-end

**Files:**
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (mark complete)

**Interfaces:** none (runs existing tools against real data).

- [ ] **Step 1: Run the real merge for `al_hafeez_campus`**

```bash
"C:\xampp81\php\php.exe" tools/multitenant/MergeClassData.php al_hafeez_campus 25
```

Expected: `Migrated 7 classes, 8 sections, 13 class_sections for tenant 25.`
(counts per the row counts confirmed earlier: 7 classes, 8 sections, 13
class_sections in `al_hafeez_campus`.)

- [ ] **Step 2: Verify row counts match source**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root al_hafeez_campus -e "SELECT COUNT(*) FROM classes; SELECT COUNT(*) FROM sections; SELECT COUNT(*) FROM class_sections;"
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM classes WHERE tenant_id=25; SELECT COUNT(*) FROM sections WHERE tenant_id=25; SELECT COUNT(*) FROM class_sections WHERE tenant_id=25;"
```

Expected: matching counts (7/8/13 both sides).

- [ ] **Step 3: Manual end-to-end verification**

First establish a pilot session (if one doesn't already exist from a
prior visit this session) via either existing pilot controller, e.g.:

```bash
curl -s -c /tmp/pilotclasses_cookies.txt -b /tmp/pilotclasses_cookies.txt "http://localhost/web-app/pilotstudents/login_as/25"
```

Then visit the new controller with the same cookie jar:

```bash
curl -s -c /tmp/pilotclasses_cookies.txt -b /tmp/pilotclasses_cookies.txt "http://localhost/web-app/pilotclasses/index"
```

Expected: HTML listing each of the 7 real class names from
`al_hafeez_campus` (e.g. "Class 1", "Class 2", ...) each followed by
its real section names (e.g. "A, B") — not "Unknown", confirming the
`class_sections` join resolved correctly.

- [ ] **Step 4: Mark this plan complete in the roadmap**

Add a line under Phase 2 → Stage 2 in
`docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`
noting completion, matching the style of the Stage 1 entry already there.

- [ ] **Step 5: Commit**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 2 Stage 2 (classes/sections) complete"
```

---

## Explicitly out of scope for this stage (deferred to later stages)

- **`student_session`** — the table that actually links a migrated
  student to a class/section (`students` itself has no `class_id`/
  `section_id` — that link lives on `student_session`, keyed by
  `session_id`/`student_id`/`class_id`/`section_id`). Migrating it
  requires reconstructing the Phase 1 `students` old-id→new-id mapping
  (not persisted anywhere after that migration ran — `IdRemapper` is
  in-memory only) by joining on a stable shared field like
  `admission_no`. This is real, separate complexity — its own stage.
- The `sessions` (academic year) table itself — not migrated; if/when
  `student_session` is tackled, its `session_id` column will need a
  decision (migrate `sessions` too, or drop the column for now).
- `class_teacher`, `class_section_times`, `subject_group_class_sections`,
  and every other table with an FK to `classes`/`sections`/
  `class_sections` (`contents`, `homework`, `questions`,
  `subject_timetable`, `feemasters`, `userlog`, `online_admissions`,
  `alumni_events`, `enquiry`, `share_content_for`,
  `video_tutorial_class_sections`) — all later stages/phases.
- Real admin panel screens for managing classes/sections — this stage
  only proves read access via a pilot controller.
- Migrating class/section data for any school other than `al_hafeez_campus`.
