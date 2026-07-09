# Multi-Tenant Migration — Phase 2 Stage 1: Staff + Real Login Proof Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prove real staff credential verification, role resolution, and
tenant-scoped session construction work end-to-end for the pilot tenant
(`al_hafeez_campus`, tenant_id 25) — via a new parallel pilot login
controller, without modifying `Site.php`'s production `login()` method
(used by all 6 live schools right now) and without attempting to render
the real admin dashboard yet (that depends on many more unmigrated
tables — settings, notices, fee summaries — and is explicitly out of
scope here).

**Architecture:** Extends Phase 1's pattern exactly. `school_saas` gains
three more tenant-scoped tables (`staff`, `staff_roles`, `roles`) via a
new SQL migration. A new `MergeStaffData` CLI tool (mirroring Phase 1's
`MergeSchoolData`, but simpler — `staff_roles` depends one-directionally
on `staff` and `roles`, no circular reference) migrates the pilot
tenant's real staff data. A new `PilotLogin` controller does real
`email`/`password` verification against `school_saas.staff` via the
existing `Tenant_Model`, resolves the staff member's role via two
sequential tenant-scoped lookups (no JOIN support added to `TenantScope`
— YAGNI, two round trips is simpler), and redirects to a new minimal
pilot-dashboard view showing the resolved session — not the real
`admin/admin/dashboard`.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5 (already set up from Phase 1), PDO.

## Global Constraints

- Do not modify `application/controllers/Site.php`, `application/libraries/Auth.php`,
  `application/libraries/Db_manager.php`, or any existing model in this
  stage — the live login path for all 6 schools must keep working exactly
  as it does today.
- Do not modify the existing `al_hafeez_campus` (or any other school)
  database — only read from it.
- Do not modify `tools/multitenant/TenantScope.php`'s public interface —
  reuse `Tenant_Model`'s existing `tenantGetAll`/`tenantInsert` methods as
  they are (built in Phase 1). If a join is needed, do it as two
  sequential calls in the consuming code, not by extending `TenantScope`.
- Reuse `application/libraries/Enc_lib.php`'s existing `passHashDyc()` for
  password verification — do not reimplement password hashing/verification.
- All new PHP must run under PHP 8.1.
- Use `127.0.0.1` / `root` / empty password for all local MySQL
  connections.
- Tenant id `25` is reserved for `al_hafeez_campus` (same as Phase 1).
- MySQL and Apache are already running (started during Phase 1) — don't
  start/stop them; if either seems down, say so rather than managing them.

---

### Task 1: Extend `school_saas` — `staff`, `staff_roles`, `roles` tables

**Files:**
- Create: `sql/multitenant/002_add_staff_tables.sql`

**Interfaces:**
- Produces: three new tables in `school_saas` — `staff`, `staff_roles`,
  `roles`, each with a `tenant_id` column and FK to `tenants(id)`, plus
  `staff_roles.staff_id → staff(id)` and `staff_roles.role_id → roles(id)`
  FKs (both one-directional, safe to declare — no circularity like
  Phase 1's `students`/`users`). Consumed by Task 2 (`MergeStaffData`) and
  Task 3 (`PilotLogin`).

Column selection here matches exactly what `Site.php::login()` and
`Staff_model::checkLogin()`/`getStaffRoles()` need — not full production
parity (same minimal-slice principle as Phase 1's `students`/`users`).
Columns like `department`, `designation` (which FK to tables that don't
exist in `school_saas` yet) are deliberately excluded.

- [ ] **Step 1: Write the schema SQL**

Create `sql/multitenant/002_add_staff_tables.sql`:

```sql
USE school_saas;

CREATE TABLE staff (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    employee_id VARCHAR(200) NOT NULL,
    name VARCHAR(200) NOT NULL,
    surname VARCHAR(200) DEFAULT NULL,
    email VARCHAR(200) NOT NULL,
    password VARCHAR(250) NOT NULL,
    gender VARCHAR(50) DEFAULT NULL,
    image VARCHAR(200) DEFAULT NULL,
    is_active INT NOT NULL DEFAULT 1,
    verification_code VARCHAR(100) DEFAULT NULL,
    lang_id INT NOT NULL DEFAULT 0,
    currency_id INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_staff_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE roles (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    slug VARCHAR(150) DEFAULT NULL,
    is_active INT NOT NULL DEFAULT 1,
    is_system INT NOT NULL DEFAULT 0,
    is_superadmin INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_roles_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_roles (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    staff_id INT NOT NULL,
    role_id INT NOT NULL,
    is_active INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_staffroles_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_staffroles_staff FOREIGN KEY (staff_id) REFERENCES staff (id),
    CONSTRAINT fk_staffroles_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Apply the schema**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root < sql/multitenant/002_add_staff_tables.sql`
Expected: no output on success.

- [ ] **Step 3: Verify the tables exist**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SHOW TABLES;"`
Expected: now lists `roles`, `staff`, `staff_roles`, `students`, `tenants`, `users`.

- [ ] **Step 4: Commit**

```bash
git add sql/multitenant/002_add_staff_tables.sql
git commit -m "feat: add staff/staff_roles/roles tables to school_saas"
```

---

### Task 2: `MergeStaffData` — migrate staff/roles for one tenant

**Files:**
- Create: `tools/multitenant/MergeStaffData.php`
- Test: `tests/tools/multitenant/MergeStaffDataTest.php`

**Interfaces:**
- Consumes: `IdRemapper` (from Phase 1, `tools/multitenant/IdRemapper.php`
  — unchanged, same class, `remapId`/`hasMapping`/`getMapping`/`count`).
- Produces: `class MergeStaffData` with
  `__construct(PDO $source, PDO $target, int $tenantId)` and
  `run(): array` (returns
  `['staff_migrated' => int, 'roles_migrated' => int, 'staff_roles_migrated' => int]`).
  CLI entry point: `php tools/multitenant/MergeStaffData.php <source_database> <tenant_id>`.

Unlike Phase 1's `MergeSchoolData` (which had to solve a *circular*
`students.parent_id ↔ users.id` reference), this is simpler:
`staff_roles.staff_id → staff.id` and `staff_roles.role_id → roles.id`
are one-directional — `staff` and `roles` have no dependency on
`staff_roles`, so they can be remapped and inserted first, independently
of each other, then `staff_roles` last. `staff_roles.id` itself is never
referenced by anything else, so the target simply lets `AUTO_INCREMENT`
assign it — no remapping needed for that one column.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/MergeStaffDataTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class MergeStaffDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_staff_test_source');
        $admin->exec('CREATE DATABASE merge_staff_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_staff_test_target');
        $admin->exec('CREATE DATABASE merge_staff_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_staff_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_staff_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Matches exactly what MergeStaffData::run() selects below, which in
        // turn matches school_saas's real schema for these tables (see
        // sql/multitenant/002_add_staff_tables.sql from Task 1) — not just
        // the handful of columns the assertions happen to check.
        $staffSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, employee_id VARCHAR(200) NOT NULL,'
            . ' name VARCHAR(200) NOT NULL, surname VARCHAR(200) DEFAULT NULL,'
            . ' email VARCHAR(200) NOT NULL, password VARCHAR(250) NOT NULL,'
            . ' gender VARCHAR(50) DEFAULT NULL, image VARCHAR(200) DEFAULT NULL,'
            . ' is_active INT NOT NULL DEFAULT 1, verification_code VARCHAR(100) DEFAULT NULL,'
            . ' lang_id INT NOT NULL DEFAULT 0, currency_id INT NOT NULL DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE staff ({$staffSchema})");
        $this->target->exec("CREATE TABLE staff ({$staffSchema}, tenant_id INT NOT NULL)");

        $rolesSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) DEFAULT NULL,'
            . ' slug VARCHAR(150) DEFAULT NULL, is_active INT NOT NULL DEFAULT 1,'
            . ' is_system INT NOT NULL DEFAULT 0, is_superadmin INT NOT NULL DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE roles ({$rolesSchema})");
        $this->target->exec("CREATE TABLE roles ({$rolesSchema}, tenant_id INT NOT NULL)");

        $staffRolesSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, role_id INT NOT NULL,'
            . ' is_active INT NOT NULL DEFAULT 1,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE staff_roles ({$staffRolesSchema})");
        $this->target->exec("CREATE TABLE staff_roles ({$staffRolesSchema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_staff_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_staff_test_target');
    }

    public function testMergesStaffRolesAndStaffRolesWithRemappedForeignKeys(): void
    {
        $this->source->exec("INSERT INTO staff (id, employee_id, name, email, password) VALUES (1, 'EMP-1', 'Alice', 'alice@example.com', 'hash1')");
        $this->source->exec("INSERT INTO roles (id, name) VALUES (1, 'Coordinator')");
        $this->source->exec('INSERT INTO staff_roles (id, staff_id, role_id) VALUES (1, 1, 1)');

        $merger = new MergeStaffData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['staff_migrated']);
        $this->assertSame(1, $result['roles_migrated']);
        $this->assertSame(1, $result['staff_roles_migrated']);

        $staff = $this->target->query('SELECT * FROM staff')->fetch(PDO::FETCH_ASSOC);
        $role = $this->target->query('SELECT * FROM roles')->fetch(PDO::FETCH_ASSOC);
        $staffRole = $this->target->query('SELECT * FROM staff_roles')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Alice', $staff['name']);
        $this->assertSame(25, (int) $staff['tenant_id']);
        $this->assertSame(25, (int) $role['tenant_id']);
        $this->assertSame(25, (int) $staffRole['tenant_id']);
        $this->assertSame((int) $staff['id'], (int) $staffRole['staff_id']);
        $this->assertSame((int) $role['id'], (int) $staffRole['role_id']);
    }

    public function testStartsStaffAndRoleIdsAfterExistingTargetRowsToAvoidCollision(): void
    {
        $this->target->exec("INSERT INTO staff (id, employee_id, name, email, password, tenant_id) VALUES (500, 'EMP-EXIST', 'Existing', 'e@x.com', 'h', 1)");
        $this->target->exec("INSERT INTO roles (id, name, tenant_id) VALUES (700, 'ExistingRole', 1)");
        $this->source->exec("INSERT INTO staff (id, employee_id, name, email, password) VALUES (1, 'EMP-1', 'Bob', 'bob@example.com', 'hash2')");
        $this->source->exec("INSERT INTO roles (id, name) VALUES (1, 'Teacher')");
        $this->source->exec('INSERT INTO staff_roles (id, staff_id, role_id) VALUES (1, 1, 1)');

        $merger = new MergeStaffData($this->source, $this->target, 2);
        $merger->run();

        $newStaff = $this->target->query("SELECT * FROM staff WHERE name = 'Bob'")->fetch(PDO::FETCH_ASSOC);
        $newRole = $this->target->query("SELECT * FROM roles WHERE name = 'Teacher'")->fetch(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(500, (int) $newStaff['id']);
        $this->assertGreaterThan(700, (int) $newRole['id']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeStaffDataTest.php`
Expected: FAIL — `Class "MergeStaffData" not found`.

- [ ] **Step 3: Implement `MergeStaffData`**

Create `tools/multitenant/MergeStaffData.php`:

```php
<?php

require_once __DIR__ . '/IdRemapper.php';

final class MergeStaffData
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

        $this->target->beginTransaction();
        try {
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
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }

        return [
            'staff_migrated' => count($staff),
            'roles_migrated' => count($roles),
            'staff_roles_migrated' => count($staffRoles),
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

Note: the `staff_roles` remap loop skips (`continue`) any row whose
`staff_id`/`role_id` wasn't found in the corresponding remapper. This
can only happen if source data has a dangling reference (a `staff_roles`
row pointing at a deleted staff/role) — skip rather than fail the whole
migration for one orphaned link row.

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeStaffDataTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Run the full suite to check for regressions**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: all prior Phase 1 tests plus these 2 new ones pass (21 tests total).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeStaffData.php tests/tools/multitenant/MergeStaffDataTest.php
git commit -m "feat: add MergeStaffData CLI tool for staff/roles tenant migration"
```

---

### Task 3: `PilotLogin` controller — real credential verification

**Files:**
- Create: `application/controllers/PilotLogin.php`
- Create: `application/views/pilot_dashboard.php`

**Interfaces:**
- Consumes: `Tenant_Model::tenantGetAll(string $table, array $where = []): array`
  (from Phase 1, `application/core/Tenant_Model.php` — unchanged),
  `Enc_lib::passHashDyc(string $password, string $hash): bool` (existing
  library, `application/libraries/Enc_lib.php`).
- Produces: a `login` action reachable at
  `http://localhost/web-app/pilotlogin/login` (GET shows a bare HTML
  form, POST verifies credentials) and sets
  `$this->session->set_userdata('pilot_admin', $sessionData)` on success
  — deliberately a **different** session key than the real system's
  `'admin'` key, so this can never collide with or be mistaken for a real
  logged-in admin session.

This controller does NOT touch `$this->session->userdata('admin')` or
redirect to `admin/admin/dashboard` — it proves the verification and
session-construction logic works, then shows its own minimal dashboard
view. Making the *real* admin panel render against `school_saas` is a
later stage's job, once settings/notices/dashboard-widget tables are
migrated too.

- [ ] **Step 1: Implement the controller**

Create `application/controllers/PilotLogin.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Tenant_Model.php';

class PilotLogin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database('school_saas_pilot');
        $this->load->model('Tenant_Model', 'tenant_model');
        $this->load->library('enc_lib');
    }

    public function login()
    {
        if ($this->input->method() !== 'post') {
            echo '<form method="post" action="' . site_url('pilotlogin/login') . '">'
                . '<input type="hidden" name="tenant_id" value="25">'
                . 'Email: <input type="text" name="email"><br>'
                . 'Password: <input type="password" name="password"><br>'
                . '<button type="submit">Login</button>'
                . '</form>';

            return;
        }

        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $tenantId = (int) $this->input->post('tenant_id');

        $this->session->set_userdata('pilot_tenant_id', $tenantId);

        $staffRows = $this->tenant_model->tenantGetAll('staff', ['email' => $email]);
        if (count($staffRows) !== 1) {
            echo 'Invalid email or password.';

            return;
        }

        $staff = $staffRows[0];
        if (!$this->enc_lib->passHashDyc($password, $staff['password'])) {
            echo 'Invalid email or password.';

            return;
        }

        if ((int) $staff['is_active'] !== 1) {
            echo 'Account disabled.';

            return;
        }

        $staffRoleRows = $this->tenant_model->tenantGetAll('staff_roles', ['staff_id' => $staff['id']]);
        $roleName = 'Unknown';
        if (count($staffRoleRows) === 1) {
            $roleRows = $this->tenant_model->tenantGetAll('roles', ['id' => $staffRoleRows[0]['role_id']]);
            if (count($roleRows) === 1) {
                $roleName = $roleRows[0]['name'];
            }
        }

        $sessionData = [
            'id' => $staff['id'],
            'username' => trim($staff['name'] . ' ' . ($staff['surname'] ?? '')),
            'email' => $staff['email'],
            'role' => $roleName,
            'tenant_id' => $tenantId,
        ];

        $this->session->set_userdata('pilot_admin', $sessionData);
        redirect('pilotlogin/dashboard');
    }

    public function dashboard()
    {
        $sessionData = $this->session->userdata('pilot_admin');
        if (!$sessionData) {
            redirect('pilotlogin/login');

            return;
        }

        $this->load->view('pilot_dashboard', ['session' => $sessionData]);
    }
}
```

- [ ] **Step 2: Implement the dashboard view**

Create `application/views/pilot_dashboard.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Login Successful</h1>
<ul>
    <li>Staff ID: <?php echo htmlspecialchars((string) $session['id'], ENT_QUOTES); ?></li>
    <li>Name: <?php echo htmlspecialchars($session['username'], ENT_QUOTES); ?></li>
    <li>Email: <?php echo htmlspecialchars($session['email'], ENT_QUOTES); ?></li>
    <li>Role: <?php echo htmlspecialchars($session['role'], ENT_QUOTES); ?></li>
    <li>Tenant ID: <?php echo htmlspecialchars((string) $session['tenant_id'], ENT_QUOTES); ?></li>
</ul>
```

- [ ] **Step 3: Commit**

```bash
git add application/controllers/PilotLogin.php application/views/pilot_dashboard.php
git commit -m "feat: add PilotLogin controller proving real staff credential verification"
```

---

### Task 4: Migrate pilot tenant's real staff data + verify end-to-end

**Files:**
- Modify: `docs/superpowers/plans/2026-07-09-multi-tenant-phase2-staff-login.md` (mark complete)

**Interfaces:** none (this task runs existing tools against real data and
does one local-only password reset for testing, same pattern as Phase 1's
Task 7 verification).

- [ ] **Step 1: Run the real merge for `al_hafeez_campus`**

```bash
"C:\xampp81\php\php.exe" tools/multitenant/MergeStaffData.php al_hafeez_campus 25
```

Expected: `Migrated 18 staff, 8 roles, 18 staff_roles for tenant 25.`
(counts per the row counts already confirmed in `al_hafeez_campus`: 18
staff, 8 roles, 18 staff_roles.)

- [ ] **Step 2: Verify row counts match source**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root al_hafeez_campus -e "SELECT COUNT(*) FROM staff; SELECT COUNT(*) FROM staff_roles; SELECT COUNT(*) FROM roles;"
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM staff WHERE tenant_id=25; SELECT COUNT(*) FROM staff_roles WHERE tenant_id=25; SELECT COUNT(*) FROM roles WHERE tenant_id=25;"
```

Expected: matching counts (18/18/8/8/18/18) between source and target.

- [ ] **Step 3: Reset one staff member's password in the LOCAL `school_saas` copy for testing**

This only touches your local `school_saas` test database, not
`al_hafeez_campus` — same pattern used earlier in this project to get a
known test login.

```bash
"C:\xampp81\php\php.exe" -r "echo password_hash('Test@1234', PASSWORD_DEFAULT);"
```

Take the printed hash and run (substituting it in):

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "UPDATE staff SET password = '<PASTE_HASH_HERE>' WHERE tenant_id = 25 LIMIT 1;"
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT id, email FROM staff WHERE tenant_id = 25 LIMIT 1;"
```

Note the printed email — that's the login to test with in Step 4.

- [ ] **Step 4: Manual end-to-end verification**

Using a cookie jar to preserve the PHP session (same pattern as Phase 1):

```bash
curl -s -c /tmp/pilotlogin_cookies.txt -b /tmp/pilotlogin_cookies.txt \
  -X POST "http://localhost/web-app/pilotlogin/login" \
  --data-urlencode "email=<EMAIL_FROM_STEP_3>" \
  --data-urlencode "password=Test@1234" \
  --data-urlencode "tenant_id=25" -L
```

Expected: the response body is the pilot dashboard HTML, showing the
correct staff name, email, resolved role name (one of the real roles
migrated from `al_hafeez_campus`, e.g. "Coordinator"), and `Tenant ID: 25`.

Also verify a wrong password is rejected:

```bash
curl -s -c /tmp/pilotlogin_wrong.txt -b /tmp/pilotlogin_wrong.txt \
  -X POST "http://localhost/web-app/pilotlogin/login" \
  --data-urlencode "email=<EMAIL_FROM_STEP_3>" \
  --data-urlencode "password=WrongPassword" \
  --data-urlencode "tenant_id=25"
```

Expected: response body is `Invalid email or password.`, not the
dashboard.

- [ ] **Step 5: Mark this plan complete**

In `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`,
add a line under Phase 2 noting Stage 1 (staff + login proof) is
complete, and that Stage 2 (classes/sections, exams, attendance) is not
yet planned.

- [ ] **Step 6: Commit**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 2 Stage 1 (staff + login proof) complete"
```

---

## Explicitly out of scope for this stage (deferred to later stages)

- Modifying `Site.php`'s real `login()` — a later stage's job, once more
  of the admin panel is proven tenant-safe (see the plan's Architecture
  note on why this was deliberately deferred).
- Making the real `admin/admin/dashboard` (or any other real controller)
  render against `school_saas` — depends on `settings`, notices, and
  dashboard-widget tables not yet migrated.
- Department/designation tables (`staff.department`/`staff.designation`
  FKs) — excluded from this stage's minimal `staff` schema.
- Language/currency joins (`getByEmail()`'s `languages`/`currencies` LEFT
  JOINs) — `PilotLogin` omits these; `lang_id`/`currency_id` are stored
  but unused, since nothing in this stage's session data depends on them.
- Migrating staff data for any school other than `al_hafeez_campus`.
- Any classes/sections, exams, or attendance data — later Phase 2 stages.
