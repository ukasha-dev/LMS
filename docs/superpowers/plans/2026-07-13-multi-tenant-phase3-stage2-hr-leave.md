# Multi-Tenant Migration — Phase 3 Stage 2: HR / Staff Leave Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the pilot tenant's real HR/staff-leave data — department
and designation catalogs, leave type definitions, and per-staff leave
allotments (51 real rows across 4 tables in `al_hafeez_campus`) — into
`school_saas`, proving `NaturalKeyIdResolver` generalizes to a THIRD
distinct table/column shape (`staff`/`email`, after `students`/`admission_no`
in Stage 3 and `sessions`/`session` in Phase 3 Stage 1) and that this
project's merge-tool pattern scales down cleanly to a small, simple
module, not just large deep-FK-chain ones.

**Architecture:** Four new tenant-scoped tables in `school_saas`. A
single new `MergeHrData` tool (extends `AbstractTenantMerger`) migrates
all four in one `run()`/one transaction: three tables use `IdRemapper`
only (fresh migration — `department`, `staff_designation`, `leave_types`,
none of which have any FK at all), and one table
(`staff_leave_details`) reconnects to `staff` — migrated in an EARLIER,
SEPARATE stage (Phase 2 Stage 1) — using the EXISTING
`NaturalKeyIdResolver` (unchanged), matching on `email` (verified unique
across all 18 real staff rows). A new `PilotHr` controller proves it end
to end.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, PDO.

## Global Constraints

- Do not modify `application/controllers/Site.php`, `application/libraries/Auth.php`,
  `application/libraries/Db_manager.php`, `application/core/MY_Controller.php`,
  or any existing model/controller — this stage adds new schema, a new
  merge tool, and a new standalone `Pilot*` proof controller only, same
  scope shape as every data-only stage before Stage 6's real-controller
  retrofit.
- Do not modify `tools/multitenant/TenantScope.php`, `IdRemapper.php`,
  `AbstractTenantMerger.php`, `NaturalKeyIdResolver.php`,
  `ClassSectionPairResolver.php`, `StudentSessionIdResolver.php`,
  `application/core/Tenant_Model.php`, or any existing merge tool
  (`MergeSchoolData`, `MergeStaffData`, `MergeClassData`,
  `MergeStudentSessionData`, `MergeAttendanceData`, `MergeExamData`,
  `MergeFeeData`). This stage's whole point is proving
  `NaturalKeyIdResolver` generalizes to a THIRD table/column shape
  without changes.
- **Do not modify the existing `school_saas.staff` table's schema.**
  The source `staff.department`/`staff.designation` columns (integer FKs
  into the tables this stage adds) are real, but linking them into the
  already-migrated, already-reviewed `staff` table is a separate,
  bigger decision (an `ALTER TABLE` on prior-stage schema) explicitly
  OUT OF SCOPE for this stage — see the Out-of-scope note below. This
  stage only reconnects the OTHER direction: `staff_leave_details.staff_id`
  looking up an already-migrated `staff` row, which requires no change
  to `staff` at all.
- Do not modify the existing `al_hafeez_campus` (or any other school)
  database — only read from it.
- `MergeHrData` must report `_source_total` and `_skipped` counts for
  every table where a row can be skipped due to an unresolved natural-key
  lookup, and the CLI must print a STDERR warning when any skip count is
  nonzero — established from Stage 5 onward.
- Every migrated row must get a FRESH target-appropriate id via
  `IdRemapper`, never reuse the source database's original id — the hard
  lesson from two prior real bugs in this project (Phase 2 Stage 5's
  Post-Task-3 fix, an `IdRemapper` created but never called; this
  project's own established checklist item for every new merge tool
  since). Check every `insertRow()` call site.
- All new PHP must run under PHP 8.1. Use `127.0.0.1`/`root`/empty
  password for local MySQL. Tenant id `25` is reserved for
  `al_hafeez_campus`. MySQL and Apache are already running.
- **Out of scope for this stage** (verified via direct SQL against
  `al_hafeez_campus` before writing this plan): `staff_leave_request`
  (0 rows — the actual leave-request/approval workflow table, distinct
  from `staff_leave_details`'s leave-type ALLOTMENT records), `homework`,
  `subject_timetable`, and every table surveyed for `payroll` (`staff_payroll`,
  `staff_payslip`, `payslip_allowance`), `library` (`book_issues`,
  `visitors_book` — `books` has exactly 1 row, also excluded as
  not-meaningfully-real), `hostel` (`hostel`, `hostel_rooms`), and
  `transport` (`vehicles`, `vehicle_routes`, `transport_route`,
  `route_pickup_point`) — ALL 0 rows in `al_hafeez_campus`, nothing real
  to prove, same "verified empty, deferred" discipline as every prior
  stage's out-of-scope notes.
- `staff.department`/`staff.designation` (source columns, real data:
  every real staff row is in department 1, with designation 1 or 3) are
  NOT added to `school_saas.staff` by this stage — seeded but unlinked,
  per the Global Constraint above. `PilotHr`'s proof therefore shows
  department/designation catalogs and per-staff leave allotments
  side-by-side, not literally joined through a `staff.department_id`
  column, since that column doesn't exist on the migrated `staff` table
  yet.
- Verified before writing this plan: zero dangling FK references exist
  in `staff_leave_details` (`staff_id`/`leave_type_id` both fully
  resolve), and all 18 real `school_saas.staff` (tenant 25) email
  addresses are distinct — confirming `email` is a safe natural key for
  `NaturalKeyIdResolver` reuse. `leave_types.type` also has a `UNIQUE`
  constraint in the source schema itself (not just verified-clean data),
  so no natural-key ambiguity is structurally possible for it even in a
  future school.

---

### Task 1: Extend `school_saas` — 4 HR tables

**Files:**
- Create: `sql/multitenant/009_add_hr_tables.sql`

**Interfaces:**
- Produces: `department`, `staff_designation`, `leave_types`,
  `staff_leave_details` (all tenant-scoped) in `school_saas`. Consumed
  by Task 2 (`MergeHrData`) and Task 3 (`PilotHr`).

- [ ] **Step 1: Write the schema SQL**

Create `sql/multitenant/009_add_hr_tables.sql`:

```sql
USE school_saas;

CREATE TABLE department (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    department_name VARCHAR(200) NOT NULL,
    is_active VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_department_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_designation (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    designation VARCHAR(200) NOT NULL,
    is_active VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_staffdesignation_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE leave_types (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    type VARCHAR(200) NOT NULL,
    is_active VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    UNIQUE KEY uniq_tenant_type (tenant_id, type),
    CONSTRAINT fk_leavetypes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_leave_details (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    staff_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    alloted_leave VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sld_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sld_staff FOREIGN KEY (staff_id) REFERENCES staff (id),
    CONSTRAINT fk_sld_leavetype FOREIGN KEY (leave_type_id) REFERENCES leave_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

(`leave_types`'s `UNIQUE KEY uniq_tenant_type (tenant_id, type)` mirrors
the source schema's own `UNIQUE KEY (type)` constraint, scoped per
tenant instead of globally — the source's structural guarantee that
`type` names can't collide carries over correctly.)

- [ ] **Step 2: Apply the schema**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root < sql/multitenant/009_add_hr_tables.sql`

- [ ] **Step 3: Verify**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SHOW TABLES;"`
Expected: 38 tables now (34 existing + 4 new — the 34 baseline includes
the 5 settings/reference fixture tables added during Phase 2 Stage 6's
Post-Task-5 fix, `sch_settings`/`languages`/`currencies`/`email_config`/
`permission_group`, which this plan's earlier arithmetic omitted).

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM staff; SELECT COUNT(*) FROM student_fees_deposite;"`
Expected: 18 staff, 699 student_fees_deposite rows — both unchanged from
Phase 3 Stage 1.

- [ ] **Step 4: Commit**

```bash
git add sql/multitenant/009_add_hr_tables.sql
git commit -m "feat: add HR tables (department, staff_designation, leave_types, staff_leave_details) to school_saas"
```

---

### Task 2: `MergeHrData` — catalog tables + staff reconnection

**Files:**
- Create: `tools/multitenant/MergeHrData.php`
- Test: `tests/tools/multitenant/MergeHrDataTest.php`

**Interfaces:**
- Produces: `class MergeHrData extends AbstractTenantMerger` with
  `run(): array`, returning `department_migrated`,
  `staff_designation_migrated`, `leave_types_migrated` (all `int`, no
  skip keys — none of these three tables has any FK) and
  `staff_leave_details_migrated`/`_source_total`/`_skipped` (reconnects
  to `staff` and `leave_types`, both of which can fail to resolve).
- Consumes: `AbstractTenantMerger::nextId()/fetchAll()/insertRow()/inTransaction()`,
  `IdRemapper`, `NaturalKeyIdResolver::resolve(PDO $source, PDO $target, int $tenantId, string $table, string $naturalKeyColumn): array`
  (all already exist, unchanged).

Unlike every prior merge tool in this project, this one is small enough
to build in a SINGLE task rather than splitting into Parts A/B/C — three
independent catalog tables plus one reconnecting table, no multi-layer
FK chain.

- [ ] **Step 1: Write the failing test**

Create `tests/tools/multitenant/MergeHrDataTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class MergeHrDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_hr_test_source');
        $admin->exec('CREATE DATABASE merge_hr_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_hr_test_target');
        $admin->exec('CREATE DATABASE merge_hr_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_hr_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_hr_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $departmentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, department_name VARCHAR(200) NOT NULL, is_active VARCHAR(100) NOT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $designationSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, designation VARCHAR(200) NOT NULL, is_active VARCHAR(100) NOT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $leaveTypeSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(200) NOT NULL, is_active VARCHAR(50) NOT NULL';
        $staffSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(200) NOT NULL';
        $sldSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, leave_type_id INT NOT NULL, alloted_leave VARCHAR(100) NOT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE department ({$departmentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE staff_designation ({$designationSchema}{$tenantCol})");
            $db->exec("CREATE TABLE leave_types ({$leaveTypeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE staff ({$staffSchema}{$tenantCol})");
            $db->exec("CREATE TABLE staff_leave_details ({$sldSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_hr_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_hr_test_target');
    }

    public function testMergesCatalogTablesAndReconnectsStaffLeaveDetails(): void
    {
        $this->source->exec("INSERT INTO department (id, department_name, is_active) VALUES (3, 'Academics', '1')");
        $this->source->exec("INSERT INTO staff_designation (id, designation, is_active) VALUES (7, 'Teacher', '1')");
        $this->source->exec("INSERT INTO leave_types (id, type, is_active) VALUES (2, 'Sick Leave', '1')");

        // Staff old id (100) in source, already-migrated new id (5) in
        // target, deliberately non-overlapping so a bug using the wrong
        // id is obvious.
        $this->source->exec("INSERT INTO staff (id, email) VALUES (100, 'teacher@example.com')");
        $this->target->exec("INSERT INTO staff (id, email, tenant_id) VALUES (5, 'teacher@example.com', 25)");

        $this->source->exec(
            "INSERT INTO staff_leave_details (id, staff_id, leave_type_id, alloted_leave) VALUES (500, 100, 2, '10')"
        );

        $merger = new MergeHrData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['department_migrated']);
        $this->assertSame(1, $result['staff_designation_migrated']);
        $this->assertSame(1, $result['leave_types_migrated']);
        $this->assertSame(1, $result['staff_leave_details_migrated']);
        $this->assertSame(1, $result['staff_leave_details_source_total']);
        $this->assertSame(0, $result['staff_leave_details_skipped']);

        $department = $this->target->query('SELECT * FROM department')->fetch(PDO::FETCH_ASSOC);
        $designation = $this->target->query('SELECT * FROM staff_designation')->fetch(PDO::FETCH_ASSOC);
        $leaveType = $this->target->query('SELECT * FROM leave_types')->fetch(PDO::FETCH_ASSOC);
        $sld = $this->target->query('SELECT * FROM staff_leave_details')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Academics', $department['department_name']);
        $this->assertSame(25, (int) $department['tenant_id']);
        $this->assertSame('Teacher', $designation['designation']);
        $this->assertSame('Sick Leave', $leaveType['type']);
        $this->assertSame(5, (int) $sld['staff_id']);
        $this->assertSame((int) $leaveType['id'], (int) $sld['leave_type_id']);
        $this->assertSame('10', $sld['alloted_leave']);
        $this->assertSame(25, (int) $sld['tenant_id']);
    }

    public function testSkipsLeaveDetailsReferencingAnUnmigratedStaffMember(): void
    {
        $this->source->exec("INSERT INTO leave_types (id, type, is_active) VALUES (2, 'Sick Leave', '1')");
        // staff_id 999 has no corresponding row anywhere -- simulates a
        // dangling reference that must be skipped, not inserted broken.
        $this->source->exec(
            "INSERT INTO staff_leave_details (id, staff_id, leave_type_id, alloted_leave) VALUES (500, 999, 2, '10')"
        );

        $merger = new MergeHrData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['staff_leave_details_migrated']);
        $this->assertSame(1, $result['staff_leave_details_source_total']);
        $this->assertSame(1, $result['staff_leave_details_skipped']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeHrDataTest.php`
Expected: FAIL — `Class "MergeHrData" not found`.

- [ ] **Step 3: Implement `MergeHrData`**

Create `tools/multitenant/MergeHrData.php`:

```php
<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';

final class MergeHrData extends AbstractTenantMerger
{
    public function run(): array
    {
        $departmentRemap = new IdRemapper($this->nextId('department'));
        $departments = $this->fetchAll('SELECT id, department_name, is_active, created_at, updated_at FROM department');
        foreach ($departments as $row) {
            $departmentRemap->remapId((int) $row['id']);
        }

        $designationRemap = new IdRemapper($this->nextId('staff_designation'));
        $designations = $this->fetchAll('SELECT id, designation, is_active, created_at, updated_at FROM staff_designation');
        foreach ($designations as $row) {
            $designationRemap->remapId((int) $row['id']);
        }

        $leaveTypeRemap = new IdRemapper($this->nextId('leave_types'));
        $leaveTypes = $this->fetchAll('SELECT id, type, is_active FROM leave_types');
        foreach ($leaveTypes as $row) {
            $leaveTypeRemap->remapId((int) $row['id']);
        }

        $staffResolver = new NaturalKeyIdResolver();
        $staffMap = $staffResolver->resolve($this->source, $this->target, $this->tenantId, 'staff', 'email');

        $sldRemap = new IdRemapper($this->nextId('staff_leave_details'));
        $sldRows = $this->fetchAll(
            'SELECT id, staff_id, leave_type_id, alloted_leave, created_at, updated_at FROM staff_leave_details'
        );
        $sldSourceTotal = count($sldRows);
        $sldSkipped = 0;
        $sldRowsToInsert = [];
        foreach ($sldRows as $row) {
            $oldId = (int) $row['id'];
            $oldStaffId = (int) $row['staff_id'];
            $oldLeaveTypeId = (int) $row['leave_type_id'];
            if (!isset($staffMap[$oldStaffId]) || !$leaveTypeRemap->hasMapping($oldLeaveTypeId)) {
                $sldSkipped++;
                continue;
            }
            $sldRemap->remapId($oldId);
            $row['id'] = $sldRemap->getMapping($oldId);
            $row['staff_id'] = $staffMap[$oldStaffId];
            $row['leave_type_id'] = $leaveTypeRemap->getMapping($oldLeaveTypeId);
            $sldRowsToInsert[$oldId] = $row;
        }

        $this->inTransaction(function () use (
            $departments, $designations, $leaveTypes, $sldRowsToInsert,
            $departmentRemap, $designationRemap, $leaveTypeRemap
        ) {
            foreach ($departments as $row) {
                $row['id'] = $departmentRemap->getMapping((int) $row['id']);
                $this->insertRow('department', $row);
            }
            foreach ($designations as $row) {
                $row['id'] = $designationRemap->getMapping((int) $row['id']);
                $this->insertRow('staff_designation', $row);
            }
            foreach ($leaveTypes as $row) {
                $row['id'] = $leaveTypeRemap->getMapping((int) $row['id']);
                $this->insertRow('leave_types', $row);
            }
            foreach ($sldRowsToInsert as $row) {
                $this->insertRow('staff_leave_details', $row);
            }
        });

        return [
            'department_migrated' => count($departments),
            'staff_designation_migrated' => count($designations),
            'leave_types_migrated' => count($leaveTypes),
            'staff_leave_details_migrated' => count($sldRowsToInsert),
            'staff_leave_details_source_total' => $sldSourceTotal,
            'staff_leave_details_skipped' => $sldSkipped,
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeHrData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeHrData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['department_migrated']} departments, {$result['staff_designation_migrated']} designations,"
        . " {$result['leave_types_migrated']} leave types, and {$result['staff_leave_details_migrated']} staff leave allotments"
        . " for tenant {$tenantId}.\n";

    if ($result['staff_leave_details_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['staff_leave_details_skipped']} of {$result['staff_leave_details_source_total']}"
            . " staff leave allotments could not be resolved and were skipped."
            . " Investigate before trusting this migration.\n"
        );
    }
}
```

(`$leaveTypeRemap->hasMapping($oldLeaveTypeId)` — not a `$sessionMap`/
`isset()` check — is used for the skip condition on `leave_type_id`
because `leave_types` is migrated fresh via `IdRemapper` in this SAME
run, so `IdRemapper::hasMapping()` is the correct, direct way to check
"was this specific old id actually migrated" — matching the established
pattern from every prior merge tool's `_migrated`-in-this-run reconnection
checks, e.g. Stage 5's `fgfRowsToInsert`/`feetypeRowsToInsert`
`isset()` lookups against the in-memory insert arrays. `$staffMap` uses
`isset()` because `NaturalKeyIdResolver::resolve()` already returns a
plain `old_id => new_id` array with only successfully-resolved entries
present.)

- [ ] **Step 4: Run test to verify it passes**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeHrDataTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (57 tests, ...)` (55 prior + 2 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeHrData.php tests/tools/multitenant/MergeHrDataTest.php
git commit -m "feat: add MergeHrData — migrate department/designation/leave-type catalogs, reconnect staff leave details via email"
```

---

### Task 3: `PilotHr` controller — end-to-end proof

**Files:**
- Create: `application/controllers/PilotHr.php`
- Create: `application/views/pilot_hr.php`

**Interfaces:**
- Consumes: `Tenant_Model::tenantGetAll(string $table): array` (existing,
  unchanged), the `pilot_tenant_id` session convention set by
  `PilotStudents::login_as($tenantId)` (existing, unchanged).
- Produces: a `/pilothr/index` page listing every real staff leave
  allotment for the pilot tenant with staff name, leave type name, and
  alloted amount — proving the full reconnection-to-already-migrated-`staff`
  chain resolves correctly end to end.

- [ ] **Step 1: Create the controller**

Create `application/controllers/PilotHr.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotHr extends CI_Controller
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
        $leaveDetails = $this->tenant_model->tenantGetAll('staff_leave_details');
        $staff = $this->tenant_model->tenantGetAll('staff');
        $leaveTypes = $this->tenant_model->tenantGetAll('leave_types');
        $departments = $this->tenant_model->tenantGetAll('department');
        $designations = $this->tenant_model->tenantGetAll('staff_designation');

        $staffNameById = [];
        foreach ($staff as $member) {
            $staffNameById[$member['id']] = trim($member['name'] . ' ' . ($member['surname'] ?? ''));
        }

        $leaveTypeNameById = [];
        foreach ($leaveTypes as $leaveType) {
            $leaveTypeNameById[$leaveType['id']] = $leaveType['type'];
        }

        $rows = [];
        foreach ($leaveDetails as $detail) {
            $rows[] = [
                'staff' => $staffNameById[$detail['staff_id']] ?? 'Unknown',
                'leave_type' => $leaveTypeNameById[$detail['leave_type_id']] ?? 'Unknown',
                'alloted_leave' => $detail['alloted_leave'],
            ];
        }

        $this->load->view('pilot_hr', [
            'rows' => $rows,
            'department_count' => count($departments),
            'designation_count' => count($designations),
        ]);
    }
}
```

- [ ] **Step 2: Create the view**

Create `application/views/pilot_hr.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Pilot HR</title></head>
<body>
<h1>Staff Leave Allotments (<?php echo count($rows); ?> results, <?php echo $department_count; ?> departments, <?php echo $designation_count; ?> designations)</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li><?php echo htmlspecialchars($row['staff']); ?> — <?php echo htmlspecialchars($row['leave_type']); ?>: <?php echo htmlspecialchars((string) $row['alloted_leave']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

- [ ] **Step 3: Lint**

Run: `"C:\xampp81\php\php.exe" -l application/controllers/PilotHr.php` and `"C:\xampp81\php\php.exe" -l application/views/pilot_hr.php`.
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Run the full suite (regression check only — this task adds no new tests)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (57 tests, ...)` (unchanged from Task 2).

- [ ] **Step 5: Commit**

```bash
git add application/controllers/PilotHr.php application/views/pilot_hr.php
git commit -m "feat: add PilotHr controller showing tenant-scoped staff leave allotments end to end"
```

---

### Task 4: Migrate real HR data + verify end-to-end

**Files:** none created — this task runs the tool built in Task 2
against real data and verifies the result. If verification finds a real
data-shape problem, stop, diagnose, document a "Post-Task-N fix" section
in this plan doc, dispatch a fix, get it independently reviewed, and
only then retry this task — the same discipline every prior stage
followed, including two real bugs caught this exact way in Phase 3
Stage 1 alone.

- [ ] **Step 1: Run the real migration**

Run: `"C:\xampp81\php\php.exe" tools/multitenant/MergeHrData.php al_hafeez_campus 25`

Expected output (values from this plan's pre-flight data survey — if
actual output differs, STOP and investigate before continuing):
```
Migrated 5 departments, 12 designations, 2 leave types, and 32 staff leave allotments for tenant 25.
```
No STDERR warning lines expected (skip count should be 0, verified via
this plan's pre-flight dangling-reference survey).

- [ ] **Step 2: Row-count reconciliation**

Run against `al_hafeez_campus` and the matching `WHERE tenant_id = 25`
query against `school_saas`, for all four tables: `department`,
`staff_designation`, `leave_types`, `staff_leave_details`.
Expected: 5, 12, 2, 32 — identical on both sides for every table.

- [ ] **Step 3: Spot-check a handful of real rows**

Pick 3-5 real `staff_leave_details` rows (e.g. via `SELECT staff_id,
leave_type_id, alloted_leave FROM staff_leave_details LIMIT 5` against
source, then trace `staff_id` up to `staff.email`) and compare staff
email, leave type name, and alloted amount between `al_hafeez_campus`
and `school_saas` (joining through the migrated tables on both sides,
scoped to `tenant_id = 25`). All fields must match exactly.

- [ ] **Step 4: End-to-end verification via `PilotHr`**

```
curl http://localhost/web-app/pilotstudents/login_as/25
curl http://localhost/web-app/pilothr/index
```

Expected: HTTP 200, exactly 32 `<li>` entries (matching
`staff_leave_details`'s real row count), 0 occurrences of the string
`Unknown`, header correctly reading "5 departments, 12 designations".

- [ ] **Step 5: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`
to mark Phase 3 Stage 2 complete, following the exact style of every
prior stage entry (real row counts, what was proven, any bugs found and
fixed, and an honest note on what was surveyed-and-found-empty:
payroll, library, hostel, transport — all deferred with 0 real rows,
same as this plan's Global Constraints already documented).

- [ ] **Step 6: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 2 (HR/staff leave) complete"
```

---

### Final whole-stage review (after Task 4)

Once Task 4 succeeds, dispatch an Opus adversarial review of the whole
stage's final state, following the exact pattern used at the end of
every prior stage: tenant isolation reasoning, `NaturalKeyIdResolver`
reuse correctness for its THIRD table/column shape (`staff`/`email`),
whether the skip-count reporting actually works end to end against real
data, whether every `insertRow()` call correctly overwrites `id` via its
own remapper (check all four tables explicitly), and whether the
roadmap/plan docs accurately reflect what was built. Fix any
Critical/Important findings (with independent re-review) before
considering this stage done.
