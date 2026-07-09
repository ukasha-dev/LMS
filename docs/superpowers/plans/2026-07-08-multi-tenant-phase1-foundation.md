# Multi-Tenant Migration — Phase 1: Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prove the core multi-tenant mechanism — schema, ID-remap/merge
tooling, and a tenant-scoping query wrapper — end-to-end for one vertical
slice (`students` + `users`) against one pilot school
(`al_hafeez_campus`), without modifying any of the existing 159 models,
20 controllers, or the live per-branch databases.

**Architecture:** New code lives in `tools/multitenant/` (framework-agnostic
PHP, PDO-based, fully unit-testable) and `application/core/Tenant_Model.php`
(a thin CodeIgniter 3 adapter around it). A new database, `school_saas`,
holds the merged pilot data with a `tenant_id` column on every table. A
one-off CLI migration script copies `al_hafeez_campus`'s `students` and
`users` rows into `school_saas`, remapping primary keys to avoid collision
and stamping `tenant_id`. A minimal pilot controller proves the wrapper
works through a real HTTP request.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5 (new dev dependency), PDO (`pdo_mysql`,
already enabled).

## Global Constraints

- Do not modify any file under `application/models/`, `application/controllers/`
  (except the new pilot controller), `application/libraries/Auth.php`, or
  `application/libraries/Db_manager.php` in this phase — the live system
  for all other schools must keep working exactly as it does today.
- Do not modify the existing `al_hafeez_campus`, `al_mateen_campus`,
  `nafay_campus`, `salam_boys_school`, `salam_girls_school`,
  `smart_school`, or `school_default` databases — only read from
  `al_hafeez_campus`, never write to it.
- All new PHP must run under PHP 8.1 (typed properties/params are fine,
  no PHP 8.2+-only syntax).
- Every tenant-scoped read/write must go through `TenantScope` — no new
  code in this phase issues a query without a `tenant_id` condition.
- Use `127.0.0.1` / `root` / empty password for all local MySQL
  connections, matching the existing `C:\xampp81` setup.
- Tenant id `25` is reserved for `al_hafeez_campus` in `school_saas`, to
  match its existing `multi_branch.id` in the old system (traceability,
  not a functional requirement).

---

### Task 1: PHPUnit scaffolding

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml.dist`
- Create: `tests/bootstrap.php`

**Interfaces:**
- Produces: a working `vendor/bin/phpunit` runnable via
  `"/c/xampp81/php/php.exe" vendor/bin/phpunit`, autoloading everything
  under `tools/multitenant/` via Composer classmap.

- [ ] **Step 1: Check Composer is available**

Run: `composer --version`
Expected: prints a Composer version string. If it fails with "command not
found", download the installer from https://getcomposer.org/download/ and
follow its Windows instructions before continuing — every later step in
this plan depends on it.

- [ ] **Step 2: Create `composer.json`**

```json
{
    "name": "smart-school/multitenant-tools",
    "description": "Dev tooling and tests for the multi-tenant migration",
    "require-dev": {
        "phpunit/phpunit": "^10.5"
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        },
        "classmap": [
            "tools/multitenant/"
        ]
    }
}
```

- [ ] **Step 3: Create `phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="multitenant">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 4: Create `tests/bootstrap.php`**

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
```

- [ ] **Step 5: Install dependencies**

Run: `composer install`
Expected: creates `vendor/` with `phpunit/phpunit` installed, no errors.

- [ ] **Step 6: Verify PHPUnit runs with zero tests**

Run: `"/c/xampp81/php/php.exe" vendor/bin/phpunit`
Expected: `No tests executed!` (or similar) — proves the runner and
bootstrap work before any real test exists.

- [ ] **Step 7: Commit**

```bash
git add composer.json phpunit.xml.dist tests/bootstrap.php composer.lock
git commit -m "chore: scaffold PHPUnit for multi-tenant migration tooling"
```

---

### Task 2: `IdRemapper` — primary key remapping

**Files:**
- Create: `tools/multitenant/IdRemapper.php`
- Test: `tests/tools/multitenant/IdRemapperTest.php`

**Interfaces:**
- Produces: `class IdRemapper` with `__construct(int $startId)`,
  `remapId(int $oldId): int`, `hasMapping(int $oldId): bool`,
  `getMapping(int $oldId): ?int`, `count(): int`. Consumed by Task 5's
  `MergeSchoolData`.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/IdRemapperTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class IdRemapperTest extends TestCase
{
    public function testRemapsSequentiallyStartingFromGivenId(): void
    {
        $remapper = new IdRemapper(1000);
        $this->assertSame(1000, $remapper->remapId(5));
        $this->assertSame(1001, $remapper->remapId(6));
    }

    public function testSameOldIdAlwaysReturnsSameNewId(): void
    {
        $remapper = new IdRemapper(1000);
        $first = $remapper->remapId(42);
        $second = $remapper->remapId(42);
        $this->assertSame($first, $second);
    }

    public function testHasMappingReflectsWhetherIdWasRemapped(): void
    {
        $remapper = new IdRemapper(1000);
        $this->assertFalse($remapper->hasMapping(7));
        $remapper->remapId(7);
        $this->assertTrue($remapper->hasMapping(7));
    }

    public function testGetMappingReturnsNullForUnknownId(): void
    {
        $remapper = new IdRemapper(1000);
        $this->assertNull($remapper->getMapping(999));
    }

    public function testCountTracksNumberOfDistinctIdsRemapped(): void
    {
        $remapper = new IdRemapper(1000);
        $remapper->remapId(1);
        $remapper->remapId(2);
        $remapper->remapId(1);
        $this->assertSame(2, $remapper->count());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"/c/xampp81/php/php.exe" vendor/bin/phpunit tests/tools/multitenant/IdRemapperTest.php`
Expected: FAIL — `Class "IdRemapper" not found`.

- [ ] **Step 3: Implement `IdRemapper`**

Create `tools/multitenant/IdRemapper.php`:

```php
<?php

final class IdRemapper
{
    /** @var array<int, int> */
    private array $map = [];
    private int $nextId;

    public function __construct(int $startId)
    {
        $this->nextId = $startId;
    }

    public function remapId(int $oldId): int
    {
        if (!isset($this->map[$oldId])) {
            $this->map[$oldId] = $this->nextId;
            $this->nextId++;
        }

        return $this->map[$oldId];
    }

    public function hasMapping(int $oldId): bool
    {
        return isset($this->map[$oldId]);
    }

    public function getMapping(int $oldId): ?int
    {
        return $this->map[$oldId] ?? null;
    }

    public function count(): int
    {
        return count($this->map);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"/c/xampp81/php/php.exe" vendor/bin/phpunit tests/tools/multitenant/IdRemapperTest.php`
Expected: `OK (5 tests, 5 assertions)`.

- [ ] **Step 5: Commit**

```bash
git add tools/multitenant/IdRemapper.php tests/tools/multitenant/IdRemapperTest.php
git commit -m "feat: add IdRemapper for multi-tenant primary key remapping"
```

---

### Task 3: `TenantScope` — the query-scoping wrapper

**Files:**
- Create: `tools/multitenant/TenantScope.php`
- Test: `tests/tools/multitenant/TenantScopeTest.php`

**Interfaces:**
- Consumes: a `PDO` connection (constructed by the caller).
- Produces: `class TenantScope` with `__construct(PDO $pdo)`,
  `selectAll(string $table, array $where, int $tenantId): array`,
  `insert(string $table, array $data, int $tenantId): int`,
  `update(string $table, array $data, array $where, int $tenantId): int`,
  `delete(string $table, array $where, int $tenantId): int`,
  `count(string $table, array $where, int $tenantId): int`. Consumed by
  Task 6's `Tenant_Model`.

This is the highest-risk file in the whole migration — every future
call site's tenant-safety depends on it. The tests below assert the
security property directly: an update/delete for tenant A's row id,
issued under tenant B's id, must affect zero rows.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/TenantScopeTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class TenantScopeTest extends TestCase
{
    private PDO $pdo;
    private TenantScope $scope;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS tenant_scope_test');
        $admin->exec('CREATE DATABASE tenant_scope_test');

        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=tenant_scope_test;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE widgets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            name VARCHAR(100) NOT NULL
        )');

        $this->scope = new TenantScope($this->pdo);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS tenant_scope_test');
    }

    public function testInsertStampsTenantId(): void
    {
        $id = $this->scope->insert('widgets', ['name' => 'Widget A'], 7);
        $rows = $this->scope->selectAll('widgets', [], 7);

        $this->assertCount(1, $rows);
        $this->assertSame('7', $rows[0]['tenant_id']);
        $this->assertSame($id, (int) $rows[0]['id']);
    }

    public function testSelectAllOnlyReturnsMatchingTenant(): void
    {
        $this->scope->insert('widgets', ['name' => 'Tenant 1 Widget'], 1);
        $this->scope->insert('widgets', ['name' => 'Tenant 2 Widget'], 2);

        $tenant1Rows = $this->scope->selectAll('widgets', [], 1);

        $this->assertCount(1, $tenant1Rows);
        $this->assertSame('Tenant 1 Widget', $tenant1Rows[0]['name']);
    }

    public function testUpdateOnlyAffectsMatchingTenant(): void
    {
        $idTenant1 = $this->scope->insert('widgets', ['name' => 'Original'], 1);

        $affected = $this->scope->update('widgets', ['name' => 'Renamed'], ['id' => $idTenant1], 1);

        $this->assertSame(1, $affected);
        $rows = $this->scope->selectAll('widgets', [], 1);
        $this->assertSame('Renamed', $rows[0]['name']);
    }

    public function testUpdateDoesNotAffectOtherTenantsRowEvenWithMatchingId(): void
    {
        $idTenant1 = $this->scope->insert('widgets', ['name' => 'Original'], 1);

        $affected = $this->scope->update('widgets', ['name' => 'Hacked'], ['id' => $idTenant1], 2);

        $this->assertSame(0, $affected);
        $rows = $this->scope->selectAll('widgets', [], 1);
        $this->assertSame('Original', $rows[0]['name']);
    }

    public function testDeleteOnlyAffectsMatchingTenant(): void
    {
        $idTenant1 = $this->scope->insert('widgets', ['name' => 'ToDelete'], 1);

        $deletedByWrongTenant = $this->scope->delete('widgets', ['id' => $idTenant1], 2);
        $this->assertSame(0, $deletedByWrongTenant);

        $deletedByRightTenant = $this->scope->delete('widgets', ['id' => $idTenant1], 1);
        $this->assertSame(1, $deletedByRightTenant);
    }

    public function testCountOnlyCountsMatchingTenant(): void
    {
        $this->scope->insert('widgets', ['name' => 'A'], 5);
        $this->scope->insert('widgets', ['name' => 'B'], 5);
        $this->scope->insert('widgets', ['name' => 'C'], 6);

        $this->assertSame(2, $this->scope->count('widgets', [], 5));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"/c/xampp81/php/php.exe" vendor/bin/phpunit tests/tools/multitenant/TenantScopeTest.php`
Expected: FAIL — `Class "TenantScope" not found`.

- [ ] **Step 3: Implement `TenantScope`**

Create `tools/multitenant/TenantScope.php`:

```php
<?php

final class TenantScope
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function selectAll(string $table, array $where, int $tenantId): array
    {
        [$whereSql, $params] = $this->buildWhere($where, $tenantId);
        $stmt = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE {$whereSql}");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(string $table, array $data, int $tenantId): int
    {
        $data['tenant_id'] = $tenantId;
        $columns = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);

        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);

        $params = [];
        foreach ($data as $column => $value) {
            $params[':' . $column] = $value;
        }
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where, int $tenantId): int
    {
        $setParts = [];
        $setParams = [];
        foreach ($data as $column => $value) {
            $placeholder = ':set_' . $column;
            $setParts[] = "`{$column}` = {$placeholder}";
            $setParams[$placeholder] = $value;
        }

        [$whereSql, $whereParams] = $this->buildWhere($where, $tenantId);
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($setParams + $whereParams);

        return $stmt->rowCount();
    }

    public function delete(string $table, array $where, int $tenantId): int
    {
        [$whereSql, $params] = $this->buildWhere($where, $tenantId);
        $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE {$whereSql}");
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function count(string $table, array $where, int $tenantId): int
    {
        [$whereSql, $params] = $this->buildWhere($where, $tenantId);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE {$whereSql}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function buildWhere(array $where, int $tenantId): array
    {
        $conditions = ['`tenant_id` = :tenant_id'];
        $params = [':tenant_id' => $tenantId];

        foreach ($where as $column => $value) {
            $placeholder = ':where_' . $column;
            $conditions[] = "`{$column}` = {$placeholder}";
            $params[$placeholder] = $value;
        }

        return [implode(' AND ', $conditions), $params];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"/c/xampp81/php/php.exe" vendor/bin/phpunit tests/tools/multitenant/TenantScopeTest.php`
Expected: `OK (6 tests, ...)`. `testUpdateDoesNotAffectOtherTenantsRowEvenWithMatchingId`
passing is the single most important assertion in this entire phase —
it's the cross-tenant-leak regression test.

- [ ] **Step 5: Commit**

```bash
git add tools/multitenant/TenantScope.php tests/tools/multitenant/TenantScopeTest.php
git commit -m "feat: add TenantScope query wrapper enforcing tenant isolation"
```

**Post-Phase-1 hardening (applied after the final whole-branch review, before
Phase 2 began):** the review found two latent gaps in this class — neither
reachable by any code Phase 1 actually shipped, but both worth closing
while `TenantScope` has exactly one consumer:
1. `update()` didn't strip a caller-supplied `tenant_id` from `$data`,
   so a future call site passing `tenant_id` in the update payload could
   reassign a row to a different tenant via the SET clause even though the
   WHERE clause correctly scoped the row being updated.
2. Table/column identifiers were interpolated into SQL with no format
   validation (only values were bound) — safe while every identifier is
   developer-hardcoded, but this class is the security boundary for the
   whole migration.

Both were fixed with a small addendum commit (strip `tenant_id` from
`update()`'s `$data` before building the SET clause; validate every
table/column identifier against `/^[A-Za-z0-9_]+$/` before interpolation),
with new tests for each, reviewed and approved separately from the
original Task 3 commit.

**Second hardening round:** re-review of the first fix found the
`tenant_id` strip in `update()` was exact-match only (`unset($data['tenant_id'])`),
missing that MySQL column names are case-insensitive — a caller passing
`TENANT_ID`/`Tenant_Id`/any case variant survived the strip and could
still reassign a row's tenant via the SET clause. Fixed by stripping any
key matching `tenant_id` via `strcasecmp()` rather than an exact key
lookup, with a regression test covering a case-variant key. Re-review
independently probed the fix across 6 case permutations plus the related
`insert()`/`buildWhere()` code paths (confirmed both fail safe / can only
narrow results, not bypass) before approving. One residual Minor item
(not a security bug): `insert()`'s own `tenant_id` stamp uses exact-key
assignment, so a cased `tenant_id` key surfaces a raw `PDOException`
("Column specified twice") instead of a clean validation error — left
as a follow-up, not blocking.

---

### Task 4: `school_saas` schema

**Files:**
- Create: `sql/multitenant/001_create_school_saas.sql`

**Interfaces:**
- Produces: database `school_saas` with tables `tenants`, `students`,
  `users` (minimal pilot column set — full column parity is Phase 2+).
  Consumed by Task 5 (target of the merge) and Task 6 (pilot controller
  reads from it).

- [ ] **Step 1: Write the schema SQL**

Create `sql/multitenant/001_create_school_saas.sql`:

```sql
CREATE DATABASE IF NOT EXISTS school_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE school_saas;

CREATE TABLE tenants (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    source_database VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    parent_id INT NOT NULL DEFAULT 0,
    admission_no VARCHAR(100) DEFAULT NULL,
    firstname VARCHAR(100) DEFAULT NULL,
    middlename VARCHAR(255) DEFAULT NULL,
    lastname VARCHAR(100) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'yes',
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_students_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    user_id INT NOT NULL DEFAULT 0,
    username VARCHAR(50) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    role VARCHAR(30) NOT NULL,
    is_active VARCHAR(255) DEFAULT 'yes',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tenants (id, name, source_database, is_active)
VALUES (25, 'Al-Hafeez Campus', 'al_hafeez_campus', 1);
```

Note: `students`/`users` here are a deliberately minimal column subset
(enough to prove the merge + tenant-scoping mechanism). Phase 2 extends
these to full parity with the production schema (~55 columns on
`students` alone) before any other module is migrated.

- [ ] **Step 2: Apply the schema**

Run: `"/c/xampp81/mysql/bin/mysql.exe" -u root < sql/multitenant/001_create_school_saas.sql`
Expected: no output on success.

- [ ] **Step 3: Verify the tables exist**

Run: `"/c/xampp81/mysql/bin/mysql.exe" -u root school_saas -e "SHOW TABLES;"`
Expected: lists `students`, `tenants`, `users`.

- [ ] **Step 4: Commit**

```bash
git add sql/multitenant/001_create_school_saas.sql
git commit -m "feat: add school_saas schema for multi-tenant pilot"
```

---

### Task 5: `MergeSchoolData` — the ID-remap merge script

**Files:**
- Create: `tools/multitenant/MergeSchoolData.php`
- Test: `tests/tools/multitenant/MergeSchoolDataTest.php`

**Interfaces:**
- Consumes: `IdRemapper` (Task 2).
- Produces: `class MergeSchoolData` with
  `__construct(PDO $source, PDO $target, int $tenantId)` and
  `run(): array` (returns `['students_migrated' => int, 'users_migrated' => int]`).
  Also a CLI entry point: `php tools/multitenant/MergeSchoolData.php <source_database> <tenant_id>`.

`students.parent_id` references `users.id`, and `users.user_id`
references `students.id` — a circular foreign key. The tests below exist
specifically to catch a broken remap of this relationship.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/MergeSchoolDataTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class MergeSchoolDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('CREATE DATABASE merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
        $admin->exec('CREATE DATABASE merge_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Matches the exact column set MergeSchoolData::run() selects (see
        // its whitelist SELECT below) and school_saas's real schema (Task
        // 4) — not just the two columns the assertions happen to check.
        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, parent_id INT NOT NULL DEFAULT 0,'
            . ' admission_no VARCHAR(100) DEFAULT NULL, firstname VARCHAR(100) NOT NULL,'
            . ' middlename VARCHAR(255) DEFAULT NULL, lastname VARCHAR(100) DEFAULT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'yes'";
        $this->source->exec("CREATE TABLE students ({$studentSchema})");
        $this->target->exec("CREATE TABLE students ({$studentSchema}, tenant_id INT NOT NULL)");

        $userSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL DEFAULT 0,'
            . ' username VARCHAR(50) NOT NULL, password VARCHAR(255) DEFAULT NULL,'
            . " role VARCHAR(30) NOT NULL DEFAULT 'parent', is_active VARCHAR(255) DEFAULT 'yes',"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE users ({$userSchema})");
        $this->target->exec("CREATE TABLE users ({$userSchema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
    }

    public function testMergesStudentsAndUsersPreservingCircularReference(): void
    {
        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, firstname) VALUES (1, 1, 'Alice')");
        $this->source->exec('UPDATE users SET user_id = 1 WHERE id = 1');

        $merger = new MergeSchoolData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['students_migrated']);
        $this->assertSame(1, $result['users_migrated']);

        $student = $this->target->query('SELECT * FROM students')->fetch(PDO::FETCH_ASSOC);
        $user = $this->target->query('SELECT * FROM users')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Alice', $student['firstname']);
        $this->assertSame(25, (int) $student['tenant_id']);
        $this->assertSame(25, (int) $user['tenant_id']);
        $this->assertSame((int) $user['id'], (int) $student['parent_id']);
        $this->assertSame((int) $student['id'], (int) $user['user_id']);
    }

    public function testStartsIdsAfterExistingTargetRowsToAvoidCollision(): void
    {
        $this->target->exec("INSERT INTO students (id, parent_id, firstname, tenant_id) VALUES (500, 0, 'Existing', 1)");
        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, firstname) VALUES (1, 1, 'Bob')");

        $merger = new MergeSchoolData($this->source, $this->target, 2);
        $merger->run();

        $newStudent = $this->target->query("SELECT * FROM students WHERE firstname = 'Bob'")->fetch(PDO::FETCH_ASSOC);
        $this->assertGreaterThan(500, (int) $newStudent['id']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"/c/xampp81/php/php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeSchoolDataTest.php`
Expected: FAIL — `Class "MergeSchoolData" not found`.

- [ ] **Step 3: Implement `MergeSchoolData`**

Create `tools/multitenant/MergeSchoolData.php`:

```php
<?php

require_once __DIR__ . '/IdRemapper.php';

final class MergeSchoolData
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

        $this->target->beginTransaction();
        try {
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
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }

        return [
            'students_migrated' => count($students),
            'users_migrated' => count($users),
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

- [ ] **Step 4: Run tests to verify they pass**

Run: `"/c/xampp81/php/php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeSchoolDataTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Commit**

```bash
git add tools/multitenant/MergeSchoolData.php tests/tools/multitenant/MergeSchoolDataTest.php
git commit -m "feat: add MergeSchoolData CLI tool for tenant data migration"
```

---

### Task 6: `Tenant_Model` adapter + pilot controller

**Files:**
- Create: `application/core/Tenant_Model.php`
- Create: `application/controllers/PilotStudents.php`
- Create: `application/views/pilot_students.php`

**Interfaces:**
- Consumes: `TenantScope` (Task 3).
- Produces: `class Tenant_Model extends MY_Model` with
  `tenantGetAll(string $table, array $where = []): array`,
  `tenantInsert(string $table, array $data): int`,
  `tenantUpdate(string $table, array $data, array $where): int`,
  `tenantDelete(string $table, array $where): int`,
  `tenantCount(string $table, array $where = []): int`.

This task has no PHPUnit coverage — bootstrapping the full CodeIgniter 3
framework inside PHPUnit is out of scope for this phase (CI3 relies on
`get_instance()` singletons that assume a full HTTP request lifecycle).
Instead, Step 5 is a manual verification through a real HTTP request,
which is what actually matters: proving the wrapper works when driven by
the framework, not just in isolation.

The pilot controller sets `tenant_id` via a debug-only route parameter
rather than wiring real login — that wiring is genuinely part of Phase 2
(it touches `Site.php`, which is out of scope here per Global
Constraints).

- [ ] **Step 1: Implement `Tenant_Model`**

Create `application/core/Tenant_Model.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . '../tools/multitenant/TenantScope.php';

class Tenant_Model extends MY_Model
{
    protected TenantScope $tenantScope;

    public function __construct()
    {
        parent::__construct();

        $pdo = new PDO(
            'mysql:host=' . $this->db->hostname . ';dbname=' . $this->db->database . ';charset=utf8mb4',
            $this->db->username,
            $this->db->password
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->tenantScope = new TenantScope($pdo);
    }

    protected function currentTenantId(): int
    {
        $tenantId = $this->session->userdata('pilot_tenant_id');
        if (empty($tenantId)) {
            throw new RuntimeException('Tenant_Model: no pilot_tenant_id in session');
        }

        return (int) $tenantId;
    }

    public function tenantGetAll(string $table, array $where = []): array
    {
        return $this->tenantScope->selectAll($table, $where, $this->currentTenantId());
    }

    public function tenantInsert(string $table, array $data): int
    {
        return $this->tenantScope->insert($table, $data, $this->currentTenantId());
    }

    public function tenantUpdate(string $table, array $data, array $where): int
    {
        return $this->tenantScope->update($table, $data, $where, $this->currentTenantId());
    }

    public function tenantDelete(string $table, array $where): int
    {
        return $this->tenantScope->delete($table, $where, $this->currentTenantId());
    }

    public function tenantCount(string $table, array $where = []): int
    {
        return $this->tenantScope->count($table, $where, $this->currentTenantId());
    }
}
```

- [ ] **Step 2: Implement the pilot controller**

Create `application/controllers/PilotStudents.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotStudents extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // No `true` return flag here on purpose: that would return the
        // connection object without assigning it to $this->db, and
        // Tenant_Model reads $this->db->hostname/username/password/database
        // to build its own PDO connection. This form assigns the
        // school_saas_pilot connection to the shared $this->db that both
        // this controller and Tenant_Model reference.
        $this->load->database('school_saas_pilot');
        $this->load->model('Tenant_Model', 'tenant_model');
    }

    public function login_as($tenantId)
    {
        $this->session->set_userdata('pilot_tenant_id', (int) $tenantId);
        echo "Pilot session set to tenant_id={$tenantId}. Now visit /web-app/pilotstudents/index\n";
    }

    public function index()
    {
        $students = $this->tenant_model->tenantGetAll('students');
        $this->load->view('pilot_students', ['students' => $students]);
    }

    public function add($firstname)
    {
        $newId = $this->tenant_model->tenantInsert('students', [
            'firstname' => $firstname,
            'parent_id' => 0,
        ]);
        echo "Inserted student id={$newId}\n";
    }
}
```

- [ ] **Step 3: Add a `school_saas_pilot` connection group**

Modify `application/config/database.php` — add this array entry after the
existing `$db['default']` block (do not change `$db['default']` itself):

```php
$db['school_saas_pilot'] = array(
    'dsn'          => '',
    'hostname'     => '127.0.0.1',
    'username'     => 'root',
    'password'     => '',
    'database'     => 'school_saas',
    'dbdriver'     => 'mysqli',
    'dbprefix'     => '',
    'pconnect'     => false,
    'db_debug'     => (ENVIRONMENT !== 'production'),
    'cache_on'     => false,
    'cachedir'     => '',
    'char_set'     => 'utf8',
    'dbcollat'     => 'utf8_general_ci',
    'swap_pre'     => '',
    'encrypt'      => false,
    'compress'     => false,
    'stricton'     => false,
    'failover'     => array(),
    'save_queries' => true,
    'multi_branch' => false,
);
```

- [ ] **Step 4: Implement the pilot view**

Create `application/views/pilot_students.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Students (tenant_id = <?php echo $this->session->userdata('pilot_tenant_id'); ?>)</h1>
<ul>
<?php foreach ($students as $student): ?>
    <li><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname'], ENT_QUOTES); ?></li>
<?php endforeach; ?>
</ul>
```

- [ ] **Step 5: Manual verification**

Restart Apache (Control Panel → Stop → Start on `C:\xampp81`), then:

1. Visit `http://localhost/web-app/pilotstudents/login_as/25` — sets the
   pilot session to tenant 25.
2. Visit `http://localhost/web-app/pilotstudents/index` — should load
   with an empty list (no data merged yet — that's Task 7).
3. Visit `http://localhost/web-app/pilotstudents/add/TestStudent` — should
   print `Inserted student id=1`.
4. Re-visit `http://localhost/web-app/pilotstudents/index` — should show
   "TestStudent".
5. Visit `http://localhost/web-app/pilotstudents/login_as/99` then
   `.../pilotstudents/index` — should show an **empty list**, proving
   tenant 99 cannot see tenant 25's data through the same code path.

- [ ] **Step 6: Commit**

```bash
git add application/core/Tenant_Model.php application/controllers/PilotStudents.php application/views/pilot_students.php application/config/database.php
git commit -m "feat: add Tenant_Model adapter and pilot controller for manual verification"
```

---

### Task 7: Migrate the pilot tenant's real data + close out Phase 1

**Files:**
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`

**Interfaces:** none (this task runs existing tools against real data).

- [ ] **Step 1: Clear the test data from Task 6's manual verification**

```bash
"/c/xampp81/mysql/bin/mysql.exe" -u root school_saas -e "DELETE FROM students; DELETE FROM users;"
```

- [ ] **Step 2: Run the real merge for `al_hafeez_campus`**

```bash
"/c/xampp81/php/php.exe" tools/multitenant/MergeSchoolData.php al_hafeez_campus 25
```

Expected: `Migrated <N> students and <M> users for tenant 25.` where N/M
are non-zero and match the row counts already seen for that school
(recall: `al_hafeez_campus.users` had rows starting at id 225+ from
earlier exploration — N should be in that neighborhood).

- [ ] **Step 3: Verify row counts match source**

```bash
"/c/xampp81/mysql/bin/mysql.exe" -u root al_hafeez_campus -e "SELECT COUNT(*) FROM students; SELECT COUNT(*) FROM users;"
"/c/xampp81/mysql/bin/mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM students WHERE tenant_id=25; SELECT COUNT(*) FROM users WHERE tenant_id=25;"
```

Expected: matching counts between source and target.

- [ ] **Step 4: Manual smoke test against real data**

Visit `http://localhost/web-app/pilotstudents/login_as/25` then
`http://localhost/web-app/pilotstudents/index` — should list real
`al_hafeez_campus` student names.

- [ ] **Step 5: Update the roadmap doc**

In `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`,
change the Phase 1 line from:

```markdown
1. **Phase 1 — Foundation** (plan: `2026-07-08-multi-tenant-phase1-foundation.md`)
```

to:

```markdown
1. **Phase 1 — Foundation** — ✅ complete (plan: `2026-07-08-multi-tenant-phase1-foundation.md`)
```

- [ ] **Step 6: Commit**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 1 foundation complete"
```

---

## Explicitly out of scope for Phase 1 (deferred to later phases)

- Real login integration (`Site.php`) — Phase 2.
- Any table/module beyond `students`/`users` — Phase 2/3.
- The API layer (`api/`) — Phase 4.
- Migrating any school other than `al_hafeez_campus` — Phase 5.
- Retiring `multi_branch`/`Db_manager` or any existing per-branch
  database — Phase 5, only after all schools are confirmed stable.
- Every `students`/`users` column beyond the Phase 1 minimal subset,
  including `users.childs` (the CSV-of-student-ids column that today
  encodes which children a parent account can see) — `MergeSchoolData`
  explicitly does not select or migrate it (see Task 5's `run()`); Phase 2's
  full-parity schema must decide how `childs` gets remapped to the new
  merged ids before any parent-login flow can be trusted on `school_saas`.
- Reusing `TenantScope::insert()` inside `MergeSchoolData::insertRow()` —
  considered during Task 5's review and deliberately rejected: `TenantScope`
  is shaped for the live app's per-request, single-connection, tenant-id-
  auto-stamped queries, while `MergeSchoolData` is a one-off batch tool
  juggling two PDO connections and pre-remapped explicit ids. The SQL-
  building code looks similar but the two have different invariants —
  forcing a shared abstraction here would be premature coupling.
