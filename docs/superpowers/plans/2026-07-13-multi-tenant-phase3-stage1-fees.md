# Multi-Tenant Migration — Phase 3 Stage 1: Fees Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the pilot tenant's real fee data — fee catalog (types,
groups, session-scoped pricing), discount definitions, and per-student
fee assignment/collection/discount records (1,793 real rows across 10
tables in `al_hafeez_campus`) — into `school_saas`, proving the
tenant-scoping mechanism extends to Phase 3's first module and to the
deepest FK chain migrated yet (six layers: catalog → session-scoped
pricing → per-student assignment → per-student deposit/collection →
per-student applied discount).

**Architecture:** Ten new tenant-scoped tables in `school_saas`. A
single new `MergeFeeData` tool (extends `AbstractTenantMerger`) migrates
all ten in one `run()`/one transaction, following the exact layering
Stage 5 established: six tables use `IdRemapper` only (fresh migration —
`feetype`, `fee_groups`, `fees_discounts`, `fee_session_groups`,
`fee_groups_feetype`, `fees_reminder`), and four tables reconnect to
data migrated in EARLIER, SEPARATE stages
(`student_session` from Stage 3) or to this same run's own freshly
remapped catalog rows (`student_fees_master`, `student_fees_deposite`,
`student_fees_discounts`, `student_applied_discounts`) — reusing the
EXISTING `StudentSessionIdResolver` (Stage 4) completely unchanged, no
new resolver needed, proving it generalizes to a fourth consumer. A new
`PilotFees` controller proves it end to end.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, PDO.

## Global Constraints

- Do not modify `application/controllers/Site.php`, `application/libraries/Auth.php`,
  `application/libraries/Db_manager.php`, `application/core/MY_Controller.php`,
  or any existing model/controller — this stage adds new schema, a new
  merge tool, and a new standalone `Pilot*` proof controller only. It
  does NOT retrofit a real controller (that pattern, proven in Phase 2
  Stage 6, is reused only once Phase 3's data layer is proven first —
  matching how Phase 2 proved data for 5 stages before Stage 6 touched
  any real controller).
- Do not modify `tools/multitenant/TenantScope.php`, `IdRemapper.php`,
  `AbstractTenantMerger.php`, `NaturalKeyIdResolver.php`,
  `ClassSectionPairResolver.php`, `StudentSessionIdResolver.php`,
  `application/core/Tenant_Model.php`, or any existing merge tool
  (`MergeSchoolData`, `MergeStaffData`, `MergeClassData`,
  `MergeStudentSessionData`, `MergeAttendanceData`, `MergeExamData`).
  This stage's whole point is proving `StudentSessionIdResolver`
  generalizes to a fourth consumer without changes (after
  `MergeStudentSessionData` created it conceptually, `MergeAttendanceData`
  hardened it, `MergeExamData` proved it reusable).
- Do not modify the existing `al_hafeez_campus` (or any other school)
  database — only read from it.
- `MergeFeeData` must report `_source_total` and `_skipped` counts for
  every table where a row can be skipped due to an unresolved natural-key
  lookup, and the CLI must print a STDERR warning when any skip count is
  nonzero — the hard lesson from Phase 2 Stage 4's final review, applied
  from the start in every merge tool since Stage 5.
- Every migrated row must get a FRESH target-appropriate id via
  `IdRemapper`, never reuse the source database's original id — the hard
  lesson from Phase 2 Stage 5's Post-Task-3 fix (an `IdRemapper` created
  but never actually called on `exam_group_exam_results`). Double-check
  every single `insertRow()` call site in this stage's merge tool
  overwrites `id` via its remapper before insertion — this is exactly
  the class of bug that slipped through review once already.
- All new PHP must run under PHP 8.1. Use `127.0.0.1`/`root`/empty
  password for local MySQL. Tenant id `25` is reserved for
  `al_hafeez_campus`. MySQL and Apache are already running.
- **Out of scope for this stage** (verified via direct SQL against
  `al_hafeez_campus` before writing this plan): `feemasters` (0 rows),
  `fee_receipt_no` (0 rows), `offline_fees_payments` (0 rows),
  `student_fees` (0 rows), `student_fees_processing` (0 rows),
  `student_transport_fees` (0 rows), `transport_feemaster` (0 rows —
  and `student_fees_deposite.student_transport_fee_id` is confirmed
  `NULL` on all 699 real rows, so the target schema's equivalent column
  is included for shape-completeness but never populated by this
  stage's migration). `feetype.feecategory_id` is `NULL` on all 6 real
  rows (no `feecategory` table exists in the source at all) — excluded
  from the target schema entirely, same minimal-column-subset principle
  as every prior stage.
- Verified before writing this plan: zero dangling FK references exist
  anywhere in the 10-table chain (checked every FK via `LEFT JOIN ... 
  WHERE ... IS NULL`), so no natural-key collision or ambiguity is
  expected — unlike Stages 3 and 4, which each hit a real one. This
  stage's `MergeFeeData` still implements full skip-counting and reuses
  `StudentSessionIdResolver`'s existing collision detection unchanged,
  because "verified clean today" is not a guarantee for a future school
  in Phase 5 — the whole reason collision detection exists at all.

---

### Task 1: Extend `school_saas` — 10 fee tables

**Files:**
- Create: `sql/multitenant/008_add_fee_tables.sql`

**Interfaces:**
- Produces: `feetype`, `fee_groups`, `fees_discounts`,
  `fee_session_groups`, `fee_groups_feetype`, `fees_reminder`,
  `student_fees_master`, `student_fees_deposite`,
  `student_fees_discounts`, `student_applied_discounts` (all
  tenant-scoped except none — every fee table IS genuinely per-school
  data, unlike Stage 6's `currencies`/`languages`) in `school_saas`.
  Consumed by Task 2-4 (`MergeFeeData`) and Task 5 (`PilotFees`).

- [ ] **Step 1: Write the schema SQL**

Create `sql/multitenant/008_add_fee_tables.sql`:

```sql
USE school_saas;

CREATE TABLE feetype (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    is_system INT NOT NULL DEFAULT 0,
    type VARCHAR(50) DEFAULT NULL,
    code VARCHAR(100) NOT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    description TEXT DEFAULT NULL,
    session_id INT DEFAULT NULL,
    nature VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_feetype_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_feetype_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fee_groups (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    name VARCHAR(200) DEFAULT NULL,
    is_system INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    nature VARCHAR(255) NOT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_feegroups_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fees_discounts (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    session_id INT DEFAULT NULL,
    name VARCHAR(100) DEFAULT NULL,
    code VARCHAR(100) DEFAULT NULL,
    type VARCHAR(20) DEFAULT NULL,
    percentage FLOAT(10,2) DEFAULT NULL,
    amount FLOAT(10,2) DEFAULT NULL,
    discount_limit INT DEFAULT NULL,
    expire_date DATE DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_feesdiscounts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_feesdiscounts_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fee_session_groups (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    fee_groups_id INT DEFAULT NULL,
    session_id INT DEFAULT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_fsg_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_fsg_feegroups FOREIGN KEY (fee_groups_id) REFERENCES fee_groups (id),
    CONSTRAINT fk_fsg_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fee_groups_feetype (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    fee_session_group_id INT DEFAULT NULL,
    fee_groups_id INT DEFAULT NULL,
    feetype_id INT DEFAULT NULL,
    session_id INT DEFAULT NULL,
    amount DECIMAL(10,2) DEFAULT NULL,
    fine_type VARCHAR(50) NOT NULL DEFAULT 'none',
    due_date DATE DEFAULT NULL,
    fine_percentage FLOAT(10,2) NOT NULL DEFAULT 0.00,
    fine_amount FLOAT(10,2) NOT NULL DEFAULT 0.00,
    fine_per_day INT NOT NULL DEFAULT 0,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_fgf_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_fgf_fsg FOREIGN KEY (fee_session_group_id) REFERENCES fee_session_groups (id),
    CONSTRAINT fk_fgf_feegroups FOREIGN KEY (fee_groups_id) REFERENCES fee_groups (id),
    CONSTRAINT fk_fgf_feetype FOREIGN KEY (feetype_id) REFERENCES feetype (id),
    CONSTRAINT fk_fgf_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fees_reminder (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    reminder_type VARCHAR(10) DEFAULT NULL,
    day INT DEFAULT NULL,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_feesreminder_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_fees_master (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    is_system INT NOT NULL DEFAULT 0,
    student_session_id INT DEFAULT NULL,
    fee_session_group_id INT DEFAULT NULL,
    amount FLOAT(10,2) DEFAULT 0.00,
    pre_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sfm_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sfm_session FOREIGN KEY (fee_session_group_id) REFERENCES fee_session_groups (id),
    CONSTRAINT fk_sfm_studentsession FOREIGN KEY (student_session_id) REFERENCES student_session (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_fees_deposite (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_fees_master_id INT DEFAULT NULL,
    fee_groups_feetype_id INT DEFAULT NULL,
    student_transport_fee_id INT DEFAULT NULL,
    amount_detail TEXT DEFAULT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sfd_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sfd_master FOREIGN KEY (student_fees_master_id) REFERENCES student_fees_master (id),
    CONSTRAINT fk_sfd_feegroupsfeetype FOREIGN KEY (fee_groups_feetype_id) REFERENCES fee_groups_feetype (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_fees_discounts (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_session_id INT DEFAULT NULL,
    fees_discount_id INT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'assigned',
    payment_id VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sfdisc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sfdisc_feesdiscount FOREIGN KEY (fees_discount_id) REFERENCES fees_discounts (id),
    CONSTRAINT fk_sfdisc_studentsession FOREIGN KEY (student_session_id) REFERENCES student_session (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_applied_discounts (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_fees_deposite_id INT DEFAULT NULL,
    student_fees_discount_id INT DEFAULT NULL,
    date DATE DEFAULT NULL,
    invoice_id INT DEFAULT NULL,
    sub_invoice_id INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sad_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sad_deposite FOREIGN KEY (student_fees_deposite_id) REFERENCES student_fees_deposite (id),
    CONSTRAINT fk_sad_discount FOREIGN KEY (student_fees_discount_id) REFERENCES student_fees_discounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

(`feetype.feecategory_id` and `feetype.student_session_id` from the
source schema are intentionally omitted — verified `feecategory_id` is
`NULL` on all 6 real rows with no source `feecategory` table to
reference, and `student_session_id` is a vestigial column on the
catalog-level `feetype` table unrelated to this migration's per-student
tables. `student_fees_deposite.student_transport_fee_id` IS included,
for shape-completeness with the real column, but no data ever populates
it — verified `NULL` on all 699 real rows.)

- [ ] **Step 2: Apply the schema**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root < sql/multitenant/008_add_fee_tables.sql`

- [ ] **Step 3: Verify**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SHOW TABLES;"`
Expected: 29 tables now (19 existing + 10 new).

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM students; SELECT COUNT(*) FROM student_session; SELECT COUNT(*) FROM exam_group_exam_results;"`
Expected: 312 students, 484 student_session rows, 2785
exam_group_exam_results rows — all unchanged from Stage 5.

- [ ] **Step 4: Commit**

```bash
git add sql/multitenant/008_add_fee_tables.sql
git commit -m "feat: add fee tables (feetype/fee_groups/discounts/session-pricing, student fee assignment/deposit/discount) to school_saas"
```

---

### Task 2: `MergeFeeData` Part A — catalog tables (fresh `IdRemapper` only)

**Files:**
- Create: `tools/multitenant/MergeFeeData.php`
- Test: `tests/tools/multitenant/MergeFeeDataTest.php`

**Interfaces:**
- Produces: `class MergeFeeData extends AbstractTenantMerger` with
  `run(): array`, returning at minimum `feetype_migrated`/`_source_total`/`_skipped`,
  `fee_groups_migrated` (no skip keys — no FKs), `fees_discounts_migrated`/`_source_total`/`_skipped`,
  `fee_session_groups_migrated`/`_source_total`/`_skipped`,
  `fee_groups_feetype_migrated`/`_source_total`/`_skipped`,
  `fees_reminder_migrated` (no skip keys — no FKs) (all `int`). Four of
  these six tables can skip a row (any FK pointing at `sessions` via the
  reused `NaturalKeyIdResolver`, or at another table remapped earlier in
  this same run) and so report skip counts per this stage's Global
  Constraints; `fee_groups` and `fees_reminder` have no FK columns at
  all in the source schema and correctly have no skip keys. Tasks 3-4
  extend this same class's `run()` to add the remaining four tables and
  their skip-count keys.
- Consumes: `AbstractTenantMerger::nextId()/fetchAll()/insertRow()/inTransaction()`,
  `IdRemapper` (both already exist, unchanged).

This task covers the six tables where every FK points to something ALSO
created fresh within this same migration run (`sessions`, already
migrated in Stage 5, is the only pre-existing dependency, and it's
referenced by id lookup through `IdRemapper`-style fresh remapping —
NOT natural-key reconnection, since these six tables are being migrated
for the first time in this run). Plain `IdRemapper` per table is
sufficient — same pattern as every catalog-layer table in Stage 5's
`MergeExamData` Part A.

**Migration order matters** (each table's FKs must resolve against a
map already built earlier in `run()`): `feetype` and `fee_groups` have
no fee-table dependencies (build first, in either order); `fees_discounts`
depends only on `sessions` (fresh IdRemapper doesn't need sessions
remapped — `sessions` was migrated in Stage 5, so a `session_id` on ANY
of these tables needs to look up Stage 5's already-established mapping.
BUT Stage 5's `MergeExamData` never persisted an old→new sessions map
anywhere reusable — its `IdRemapper` was local to that one run. So this
task must independently resolve `session_id` values via a fresh natural-key
lookup on `sessions.session` (the session NAME, e.g. `"2026-27"`), the
same pattern `NaturalKeyIdResolver` already implements generically for
exactly this shape of problem — reuse it here for `sessions`, matching
column `session`, instead of writing new resolution logic.); `fee_session_groups`
depends on `fee_groups` (this run) + `sessions` (via the same
`NaturalKeyIdResolver` reuse); `fee_groups_feetype` depends on
`fee_session_groups` + `fee_groups` + `feetype` (this run) + `sessions`
(via `NaturalKeyIdResolver` reuse); `fees_reminder` has no FKs at all.

- [ ] **Step 1: Write the failing test**

Create `tests/tools/multitenant/MergeFeeDataTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class MergeFeeDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_fee_test_source');
        $admin->exec('CREATE DATABASE merge_fee_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_fee_test_target');
        $admin->exec('CREATE DATABASE merge_fee_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_fee_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_fee_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, session VARCHAR(60) DEFAULT NULL';
        $feetypeSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, is_system INT NOT NULL DEFAULT 0, type VARCHAR(50) DEFAULT NULL,'
            . ' code VARCHAR(100) NOT NULL, is_active VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL,'
            . ' session_id INT DEFAULT NULL, nature VARCHAR(255) NOT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $feeGroupsSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) DEFAULT NULL, is_system INT NOT NULL DEFAULT 0,'
            . ' description TEXT DEFAULT NULL, nature VARCHAR(255) NOT NULL, is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $feesDiscountsSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, session_id INT DEFAULT NULL, name VARCHAR(100) DEFAULT NULL,'
            . ' code VARCHAR(100) DEFAULT NULL, type VARCHAR(20) DEFAULT NULL, percentage FLOAT(10,2) DEFAULT NULL,'
            . ' amount FLOAT(10,2) DEFAULT NULL, discount_limit INT DEFAULT NULL, expire_date DATE DEFAULT NULL,'
            . ' description TEXT DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $fsgSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, fee_groups_id INT DEFAULT NULL, session_id INT DEFAULT NULL,'
            . ' is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $fgfSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, fee_session_group_id INT DEFAULT NULL, fee_groups_id INT DEFAULT NULL,'
            . ' feetype_id INT DEFAULT NULL, session_id INT DEFAULT NULL, amount DECIMAL(10,2) DEFAULT NULL,'
            . ' fine_type VARCHAR(50) NOT NULL DEFAULT \'none\', due_date DATE DEFAULT NULL,'
            . ' fine_percentage FLOAT(10,2) NOT NULL DEFAULT 0.00, fine_amount FLOAT(10,2) NOT NULL DEFAULT 0.00,'
            . ' fine_per_day INT NOT NULL DEFAULT 0, is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $reminderSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, reminder_type VARCHAR(10) DEFAULT NULL, day INT DEFAULT NULL,'
            . ' is_active INT DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE feetype ({$feetypeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups ({$feeGroupsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_discounts ({$feesDiscountsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_session_groups ({$fsgSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups_feetype ({$fgfSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_reminder ({$reminderSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_fee_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_fee_test_target');
    }

    public function testMergesCatalogTablesWithRemappedIdsAndReconnectedSessions(): void
    {
        // Source session id (20) deliberately differs from the target's
        // already-migrated id for the SAME session name (2), to prove
        // the merge resolves by name, not by copying the source id.
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25)");

        $this->source->exec("INSERT INTO feetype (id, type, code, nature, session_id) VALUES (5, 'Tuition Fee', 'TUI', 'monthly', 20)");
        $this->source->exec("INSERT INTO fee_groups (id, name, nature) VALUES (8, 'General Fee', 'monthly')");
        $this->source->exec("INSERT INTO fees_discounts (id, session_id, name, code, type, percentage) VALUES (12, 20, 'Sibling Discount', 'SIB', 'percentage', 10.00)");
        $this->source->exec("INSERT INTO fee_session_groups (id, fee_groups_id, session_id) VALUES (30, 8, 20)");
        $this->source->exec(
            "INSERT INTO fee_groups_feetype (id, fee_session_group_id, fee_groups_id, feetype_id, session_id, amount)"
            . " VALUES (100, 30, 8, 5, 20, 1500.00)"
        );
        $this->source->exec("INSERT INTO fees_reminder (id, reminder_type, day) VALUES (1, 'due', 3)");

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['feetype_migrated']);
        $this->assertSame(0, $result['feetype_skipped']);
        $this->assertSame(1, $result['fee_groups_migrated']);
        $this->assertSame(1, $result['fees_discounts_migrated']);
        $this->assertSame(0, $result['fees_discounts_skipped']);
        $this->assertSame(1, $result['fee_session_groups_migrated']);
        $this->assertSame(0, $result['fee_session_groups_skipped']);
        $this->assertSame(1, $result['fee_groups_feetype_migrated']);
        $this->assertSame(0, $result['fee_groups_feetype_skipped']);
        $this->assertSame(1, $result['fees_reminder_migrated']);

        $session = $this->target->query("SELECT * FROM sessions WHERE session='2024-25'")->fetch(PDO::FETCH_ASSOC);
        $feetype = $this->target->query('SELECT * FROM feetype')->fetch(PDO::FETCH_ASSOC);
        $feeGroup = $this->target->query('SELECT * FROM fee_groups')->fetch(PDO::FETCH_ASSOC);
        $discount = $this->target->query('SELECT * FROM fees_discounts')->fetch(PDO::FETCH_ASSOC);
        $fsg = $this->target->query('SELECT * FROM fee_session_groups')->fetch(PDO::FETCH_ASSOC);
        $fgf = $this->target->query('SELECT * FROM fee_groups_feetype')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(2, (int) $session['id']);
        $this->assertSame((int) $session['id'], (int) $feetype['session_id']);
        $this->assertSame(25, (int) $feetype['tenant_id']);
        $this->assertSame((int) $session['id'], (int) $discount['session_id']);
        $this->assertSame((int) $feeGroup['id'], (int) $fsg['fee_groups_id']);
        $this->assertSame((int) $session['id'], (int) $fsg['session_id']);
        $this->assertSame((int) $fsg['id'], (int) $fgf['fee_session_group_id']);
        $this->assertSame((int) $feeGroup['id'], (int) $fgf['fee_groups_id']);
        $this->assertSame((int) $feetype['id'], (int) $fgf['feetype_id']);
        $this->assertSame((int) $session['id'], (int) $fgf['session_id']);
        $this->assertSame('1500.00', $fgf['amount']);
        $this->assertSame(25, (int) $fgf['tenant_id']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeFeeDataTest.php`
Expected: FAIL — `Class "MergeFeeData" not found`.

- [ ] **Step 3: Implement `MergeFeeData` (Part A)**

Create `tools/multitenant/MergeFeeData.php`:

```php
<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';
require_once __DIR__ . '/StudentSessionIdResolver.php';

final class MergeFeeData extends AbstractTenantMerger
{
    public function run(): array
    {
        $sessionResolver = new NaturalKeyIdResolver();
        $sessionMap = $sessionResolver->resolve($this->source, $this->target, $this->tenantId, 'sessions', 'session');

        $feetypeRemap = new IdRemapper($this->nextId('feetype'));
        $feetypes = $this->fetchAll(
            'SELECT id, is_system, type, code, is_active, description, session_id, nature, created_at, updated_at FROM feetype'
        );
        $feetypeSourceTotal = count($feetypes);
        $feetypeSkipped = 0;
        $feetypeRowsToInsert = [];
        foreach ($feetypes as $row) {
            $oldId = (int) $row['id'];
            $oldSessionId = $row['session_id'] !== null ? (int) $row['session_id'] : null;
            if ($oldSessionId !== null && !isset($sessionMap[$oldSessionId])) {
                $feetypeSkipped++;
                continue;
            }
            $feetypeRemap->remapId($oldId);
            $row['id'] = $feetypeRemap->getMapping($oldId);
            $row['session_id'] = $oldSessionId !== null ? $sessionMap[$oldSessionId] : null;
            $feetypeRowsToInsert[$oldId] = $row;
        }

        $feeGroupRemap = new IdRemapper($this->nextId('fee_groups'));
        $feeGroups = $this->fetchAll('SELECT id, name, is_system, description, nature, is_active, created_at, updated_at FROM fee_groups');
        foreach ($feeGroups as $row) {
            $feeGroupRemap->remapId((int) $row['id']);
        }

        $feesDiscountRemap = new IdRemapper($this->nextId('fees_discounts'));
        $feesDiscounts = $this->fetchAll(
            'SELECT id, session_id, name, code, type, percentage, amount, discount_limit, expire_date, description, is_active, created_at, updated_at'
            . ' FROM fees_discounts'
        );
        $feesDiscountSourceTotal = count($feesDiscounts);
        $feesDiscountSkipped = 0;
        $feesDiscountRowsToInsert = [];
        foreach ($feesDiscounts as $row) {
            $oldId = (int) $row['id'];
            $oldSessionId = $row['session_id'] !== null ? (int) $row['session_id'] : null;
            if ($oldSessionId !== null && !isset($sessionMap[$oldSessionId])) {
                $feesDiscountSkipped++;
                continue;
            }
            $feesDiscountRemap->remapId($oldId);
            $row['id'] = $feesDiscountRemap->getMapping($oldId);
            $row['session_id'] = $oldSessionId !== null ? $sessionMap[$oldSessionId] : null;
            $feesDiscountRowsToInsert[$oldId] = $row;
        }

        $fsgRemap = new IdRemapper($this->nextId('fee_session_groups'));
        $fsgRows = $this->fetchAll('SELECT id, fee_groups_id, session_id, is_active, created_at, updated_at FROM fee_session_groups');
        $fsgSourceTotal = count($fsgRows);
        $fsgSkipped = 0;
        $fsgRowsToInsert = [];
        foreach ($fsgRows as $row) {
            $oldId = (int) $row['id'];
            $oldSessionId = $row['session_id'] !== null ? (int) $row['session_id'] : null;
            $oldFeeGroupId = $row['fee_groups_id'] !== null ? (int) $row['fee_groups_id'] : null;
            if ($oldSessionId !== null && !isset($sessionMap[$oldSessionId])) {
                $fsgSkipped++;
                continue;
            }
            $fsgRemap->remapId($oldId);
            $row['id'] = $fsgRemap->getMapping($oldId);
            $row['fee_groups_id'] = $oldFeeGroupId !== null ? $feeGroupRemap->getMapping($oldFeeGroupId) : null;
            $row['session_id'] = $oldSessionId !== null ? $sessionMap[$oldSessionId] : null;
            $fsgRowsToInsert[$oldId] = $row;
        }

        $fgfRemap = new IdRemapper($this->nextId('fee_groups_feetype'));
        $fgfRows = $this->fetchAll(
            'SELECT id, fee_session_group_id, fee_groups_id, feetype_id, session_id, amount, fine_type, due_date,'
            . ' fine_percentage, fine_amount, fine_per_day, is_active, created_at, updated_at FROM fee_groups_feetype'
        );
        $fgfSourceTotal = count($fgfRows);
        $fgfSkipped = 0;
        $fgfRowsToInsert = [];
        foreach ($fgfRows as $row) {
            $oldId = (int) $row['id'];
            $oldFsgId = $row['fee_session_group_id'] !== null ? (int) $row['fee_session_group_id'] : null;
            $oldFeeGroupId = $row['fee_groups_id'] !== null ? (int) $row['fee_groups_id'] : null;
            $oldFeetypeId = $row['feetype_id'] !== null ? (int) $row['feetype_id'] : null;
            $oldSessionId = $row['session_id'] !== null ? (int) $row['session_id'] : null;
            if (($oldFsgId !== null && !isset($fsgRowsToInsert[$oldFsgId]))
                || ($oldFeetypeId !== null && !isset($feetypeRowsToInsert[$oldFeetypeId]))
                || ($oldSessionId !== null && !isset($sessionMap[$oldSessionId]))
            ) {
                $fgfSkipped++;
                continue;
            }
            $fgfRemap->remapId($oldId);
            $row['id'] = $fgfRemap->getMapping($oldId);
            $row['fee_session_group_id'] = $oldFsgId !== null ? $fsgRemap->getMapping($oldFsgId) : null;
            $row['fee_groups_id'] = $oldFeeGroupId !== null ? $feeGroupRemap->getMapping($oldFeeGroupId) : null;
            $row['feetype_id'] = $oldFeetypeId !== null ? $feetypeRemap->getMapping($oldFeetypeId) : null;
            $row['session_id'] = $oldSessionId !== null ? $sessionMap[$oldSessionId] : null;
            $fgfRowsToInsert[$oldId] = $row;
        }

        $reminderRemap = new IdRemapper($this->nextId('fees_reminder'));
        $reminders = $this->fetchAll('SELECT id, reminder_type, day, is_active, created_at, updated_at FROM fees_reminder');
        foreach ($reminders as $row) {
            $reminderRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use (
            $feetypeRowsToInsert, $feeGroups, $feesDiscountRowsToInsert, $fsgRowsToInsert, $fgfRowsToInsert, $reminders,
            $feeGroupRemap, $reminderRemap
        ) {
            foreach ($feetypeRowsToInsert as $row) {
                $this->insertRow('feetype', $row);
            }
            foreach ($feeGroups as $row) {
                $row['id'] = $feeGroupRemap->getMapping((int) $row['id']);
                $this->insertRow('fee_groups', $row);
            }
            foreach ($feesDiscountRowsToInsert as $row) {
                $this->insertRow('fees_discounts', $row);
            }
            foreach ($fsgRowsToInsert as $row) {
                $this->insertRow('fee_session_groups', $row);
            }
            foreach ($fgfRowsToInsert as $row) {
                $this->insertRow('fee_groups_feetype', $row);
            }
            foreach ($reminders as $row) {
                $row['id'] = $reminderRemap->getMapping((int) $row['id']);
                $this->insertRow('fees_reminder', $row);
            }
        });

        return [
            'feetype_migrated' => count($feetypeRowsToInsert),
            'feetype_source_total' => $feetypeSourceTotal,
            'feetype_skipped' => $feetypeSkipped,
            'fee_groups_migrated' => count($feeGroups),
            'fees_discounts_migrated' => count($feesDiscountRowsToInsert),
            'fees_discounts_source_total' => $feesDiscountSourceTotal,
            'fees_discounts_skipped' => $feesDiscountSkipped,
            'fee_session_groups_migrated' => count($fsgRowsToInsert),
            'fee_session_groups_source_total' => $fsgSourceTotal,
            'fee_session_groups_skipped' => $fsgSkipped,
            'fee_groups_feetype_migrated' => count($fgfRowsToInsert),
            'fee_groups_feetype_source_total' => $fgfSourceTotal,
            'fee_groups_feetype_skipped' => $fgfSkipped,
            'fees_reminder_migrated' => count($reminders),
        ];
    }
}
```

(`fee_groups` and `fees_reminder` have no FKs at all in the source
schema — confirmed during planning — so they can never skip a row and
correctly have no `_source_total`/`_skipped` keys, matching the Global
Constraint's "every table where a row CAN be skipped" scope, not every
table unconditionally.)

(`require_once` for `StudentSessionIdResolver.php` is included now even
though Part A doesn't use it yet, so Task 3's diff stays additive-only
inside `run()`. Note `fee_groups` and `fees_reminder` use plain
`IdRemapper` with no skip logic — their source data has zero dangling
refs by construction (no FKs at all) — while `feetype`,
`fees_discounts`, `fee_session_groups`, and `fee_groups_feetype` DO skip
on an unresolvable `session_id`/parent reference, matching this stage's
Global Constraints on skip-counting. Task 2's test only exercises the
happy path; skip-path tests are deferred to Task 3/4 where the
established pattern of a dedicated skip test already lives, to avoid
duplicating fixture setup across two tasks for the same underlying
mechanism.)

- [ ] **Step 4: Run test to verify it passes**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeFeeDataTest.php`
Expected: `OK (1 test, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (50 tests, ...)` (49 prior + 1 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeFeeData.php tests/tools/multitenant/MergeFeeDataTest.php
git commit -m "feat: add MergeFeeData (Part A) — migrate fee catalog/pricing tables via IdRemapper + session reconnection"
```

---

### Task 3: `MergeFeeData` Part B — student_fees_master + student_fees_discounts (reconnect via `StudentSessionIdResolver`)

**Files:**
- Modify: `tools/multitenant/MergeFeeData.php` (extends `run()` from Task 2)
- Modify: `tests/tools/multitenant/MergeFeeDataTest.php`

**Interfaces:**
- Extends `MergeFeeData::run()`'s return array with:
  `student_fees_master_migrated`, `student_fees_master_source_total`,
  `student_fees_master_skipped`, `student_fees_discounts_migrated`,
  `student_fees_discounts_source_total`,
  `student_fees_discounts_skipped` (all `int`).
- Consumes: `StudentSessionIdResolver::resolve(PDO $source, PDO $target, int $tenantId): array`
  (existing, unchanged — the exact same resolver Stage 4's attendance
  migration and Stage 5's exam migration both already reuse unchanged).

Both tables reconnect to `student_session` (Stage 3, via the existing
resolver) plus a table remapped earlier in THIS run
(`fee_session_groups` for `student_fees_master`, `fees_discounts` for
`student_fees_discounts`).

- [ ] **Step 1: Extend the test file's schema and add the failing tests**

In `tests/tools/multitenant/MergeFeeDataTest.php`, add these schema
strings inside `setUp()` (after `$reminderSchema`, before the `foreach`
loop):

```php
        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL';
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $studentSessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $sfmSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, is_system INT NOT NULL DEFAULT 0, student_session_id INT DEFAULT NULL,'
            . ' fee_session_group_id INT DEFAULT NULL, amount FLOAT(10,2) DEFAULT 0.00, pre_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,'
            . ' is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $sfDiscSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_session_id INT DEFAULT NULL, fees_discount_id INT DEFAULT NULL,'
            . ' status VARCHAR(20) DEFAULT \'assigned\', payment_id VARCHAR(50) DEFAULT NULL, description TEXT DEFAULT NULL,'
            . ' is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
```

Replace the entire `foreach ([$this->source, $this->target] as $db) {
... }` loop body from Task 2's version (still inside `setUp()`) — keep
the seven original `CREATE TABLE` lines and add six more — so the whole
loop reads exactly as:

```php
        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE feetype ({$feetypeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups ({$feeGroupsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_discounts ({$feesDiscountsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_session_groups ({$fsgSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups_feetype ({$fgfSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_reminder ({$reminderSchema}{$tenantCol})");
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE classes ({$classSchema}{$tenantCol})");
            $db->exec("CREATE TABLE sections ({$sectionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_session ({$studentSessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_fees_master ({$sfmSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_fees_discounts ({$sfDiscSchema}{$tenantCol})");
        }
```

Then add this test method to the class (after
`testMergesCatalogTablesWithRemappedIdsAndReconnectedSessions`):

```php
    public function testReconnectsStudentFeesMasterAndDiscountsToAlreadyMigratedData(): void
    {
        // Catalog chain (same shape as the Part A test).
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25)");
        $this->source->exec("INSERT INTO fee_groups (id, name, nature) VALUES (8, 'General Fee', 'monthly')");
        $this->source->exec("INSERT INTO fee_session_groups (id, fee_groups_id, session_id) VALUES (30, 8, 20)");
        $this->source->exec("INSERT INTO fees_discounts (id, session_id, name, code, type, percentage) VALUES (12, 20, 'Sibling Discount', 'SIB', 'percentage', 10.00)");

        // Student/session chain -- old ids in source (100s/400s), already
        // migrated NEW ids in target (1s/4s), deliberately non-overlapping.
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES (401, 101, 201, 301, '2025-01-01 00:00:00')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");

        $this->source->exec(
            "INSERT INTO student_fees_master (id, student_session_id, fee_session_group_id, amount) VALUES (500, 401, 30, 1500.00)"
        );
        $this->source->exec(
            "INSERT INTO student_fees_discounts (id, student_session_id, fees_discount_id, status) VALUES (600, 401, 12, 'assigned')"
        );

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['student_fees_master_migrated']);
        $this->assertSame(1, $result['student_fees_master_source_total']);
        $this->assertSame(0, $result['student_fees_master_skipped']);
        $this->assertSame(1, $result['student_fees_discounts_migrated']);
        $this->assertSame(1, $result['student_fees_discounts_source_total']);
        $this->assertSame(0, $result['student_fees_discounts_skipped']);

        $sfm = $this->target->query('SELECT * FROM student_fees_master')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(4, (int) $sfm['student_session_id']);
        $this->assertSame(25, (int) $sfm['tenant_id']);

        $sfDisc = $this->target->query('SELECT * FROM student_fees_discounts')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(4, (int) $sfDisc['student_session_id']);
        $this->assertSame(25, (int) $sfDisc['tenant_id']);
    }

    public function testSkipsStudentFeesRowsReferencingAnUnmigratedStudentSession(): void
    {
        $this->source->exec("INSERT INTO fee_groups (id, name, nature) VALUES (8, 'General Fee', 'monthly')");
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25)");
        $this->source->exec("INSERT INTO fee_session_groups (id, fee_groups_id, session_id) VALUES (30, 8, 20)");
        // student_session_id 999 has no corresponding row anywhere --
        // simulates a dangling reference that must be skipped, not
        // inserted broken, and must be counted.
        $this->source->exec(
            "INSERT INTO student_fees_master (id, student_session_id, fee_session_group_id, amount) VALUES (500, 999, 30, 1500.00)"
        );

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['student_fees_master_migrated']);
        $this->assertSame(1, $result['student_fees_master_source_total']);
        $this->assertSame(1, $result['student_fees_master_skipped']);
    }
}
```

- [ ] **Step 2: Run tests to verify the new ones fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeFeeDataTest.php`
Expected: the two new tests FAIL (undefined array key), the Part A test
still passes.

- [ ] **Step 3: Extend `MergeFeeData::run()` (Part B)**

In `tools/multitenant/MergeFeeData.php`, insert the following code
inside `run()`, immediately after the `foreach ($reminders as $row) {
$reminderRemap->remapId(...); }` block and BEFORE the
`$this->inTransaction(function () use (...` line:

```php
        $studentResolver = new NaturalKeyIdResolver();
        $studentMap = $studentResolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');

        $studentSessionResolver = new StudentSessionIdResolver();
        $studentSessionMap = $studentSessionResolver->resolve($this->source, $this->target, $this->tenantId);

        $sfmRemap = new IdRemapper($this->nextId('student_fees_master'));
        $sfmRows = $this->fetchAll(
            'SELECT id, is_system, student_session_id, fee_session_group_id, amount, pre_discount, is_active, created_at, updated_at'
            . ' FROM student_fees_master'
        );
        $sfmSourceTotal = count($sfmRows);
        $sfmSkipped = 0;
        $sfmRowsToInsert = [];
        foreach ($sfmRows as $row) {
            $oldId = (int) $row['id'];
            $oldStudentSessionId = $row['student_session_id'] !== null ? (int) $row['student_session_id'] : null;
            $oldFsgId = $row['fee_session_group_id'] !== null ? (int) $row['fee_session_group_id'] : null;
            if (($oldStudentSessionId !== null && !isset($studentSessionMap[$oldStudentSessionId]))
                || ($oldFsgId !== null && !isset($fsgRowsToInsert[$oldFsgId]))
            ) {
                $sfmSkipped++;
                continue;
            }
            $sfmRemap->remapId($oldId);
            $row['id'] = $sfmRemap->getMapping($oldId);
            $row['student_session_id'] = $oldStudentSessionId !== null ? $studentSessionMap[$oldStudentSessionId] : null;
            $row['fee_session_group_id'] = $oldFsgId !== null ? $fsgRemap->getMapping($oldFsgId) : null;
            $sfmRowsToInsert[$oldId] = $row;
        }

        $sfDiscRemap = new IdRemapper($this->nextId('student_fees_discounts'));
        $sfDiscRows = $this->fetchAll(
            'SELECT id, student_session_id, fees_discount_id, status, payment_id, description, is_active, created_at, updated_at'
            . ' FROM student_fees_discounts'
        );
        $sfDiscSourceTotal = count($sfDiscRows);
        $sfDiscSkipped = 0;
        $sfDiscRowsToInsert = [];
        foreach ($sfDiscRows as $row) {
            $oldId = (int) $row['id'];
            $oldStudentSessionId = $row['student_session_id'] !== null ? (int) $row['student_session_id'] : null;
            $oldFeesDiscountId = $row['fees_discount_id'] !== null ? (int) $row['fees_discount_id'] : null;
            if (($oldStudentSessionId !== null && !isset($studentSessionMap[$oldStudentSessionId]))
                || ($oldFeesDiscountId !== null && !isset($feesDiscountRowsToInsert[$oldFeesDiscountId]))
            ) {
                $sfDiscSkipped++;
                continue;
            }
            $sfDiscRemap->remapId($oldId);
            $row['id'] = $sfDiscRemap->getMapping($oldId);
            $row['student_session_id'] = $oldStudentSessionId !== null ? $studentSessionMap[$oldStudentSessionId] : null;
            $row['fees_discount_id'] = $oldFeesDiscountId !== null ? $feesDiscountRemap->getMapping($oldFeesDiscountId) : null;
            $sfDiscRowsToInsert[$oldId] = $row;
        }
```

Then change the `$this->inTransaction(function () use (...` line's `use`
clause to also capture `$sfmRowsToInsert, $sfDiscRowsToInsert`, and add
these two `foreach` loops inside the closure body, after the existing
`fees_reminder` loop:

```php
            foreach ($sfmRowsToInsert as $row) {
                $this->insertRow('student_fees_master', $row);
            }
            foreach ($sfDiscRowsToInsert as $row) {
                $this->insertRow('student_fees_discounts', $row);
            }
```

Finally, extend the `return [...]` array (still inside `run()`) to add:

```php
            'student_fees_master_migrated' => count($sfmRowsToInsert),
            'student_fees_master_source_total' => $sfmSourceTotal,
            'student_fees_master_skipped' => $sfmSkipped,
            'student_fees_discounts_migrated' => count($sfDiscRowsToInsert),
            'student_fees_discounts_source_total' => $sfDiscSourceTotal,
            'student_fees_discounts_skipped' => $sfDiscSkipped,
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeFeeDataTest.php`
Expected: `OK (3 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (52 tests, ...)` (50 prior + 2 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeFeeData.php tests/tools/multitenant/MergeFeeDataTest.php
git commit -m "feat: extend MergeFeeData (Part B) — reconnect student_fees_master/discounts via StudentSessionIdResolver"
```

**Post-Task-3 fix (found by the implementer's own self-review):** this
plan's own Step 3 code (above) included

```php
        $studentResolver = new NaturalKeyIdResolver();
        $studentMap = $studentResolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');
```

copy-pasted from Stage 5's `MergeExamData`, where a direct `student_id`
FK genuinely needed this resolver. Neither `student_fees_master` nor
`student_fees_discounts` has a `student_id` column at all — both only
reference `student_session_id`, already fully resolved by
`StudentSessionIdResolver` two lines below. `$studentMap` is computed
and never read anywhere in the file: dead code, plus one wasted
full-table `NaturalKeyIdResolver::resolve()` query (itself two SELECTs,
source and target) on every real run for no purpose. Not a correctness
bug — nothing depends on the unused value — but worth removing before
Task 4 builds more code around this file.

- [ ] **Fix Step 1: Remove the unused resolver call**

In `tools/multitenant/MergeFeeData.php`, delete these two lines (and the
blank line separating them from the next block) entirely:

```php
        $studentResolver = new NaturalKeyIdResolver();
        $studentMap = $studentResolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');

```

Nothing else in the file changes — `$studentSessionResolver`/
`$studentSessionMap` and everything after remain untouched.

- [ ] **Fix Step 2: Run tests**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (52 tests, ...)` (unchanged — this removes only dead code,
no behavior changes).

- [ ] **Fix Step 3: Commit**

```bash
git add tools/multitenant/MergeFeeData.php
git commit -m "fix: remove unused NaturalKeyIdResolver(students) call from MergeFeeData — student_fees_master/discounts only need StudentSessionIdResolver"
```

---

### Task 4: `MergeFeeData` Part C — student_fees_deposite + student_applied_discounts (reconnect to this run's own remapped rows)

**Files:**
- Modify: `tools/multitenant/MergeFeeData.php` (extends `run()` from Task 3)
- Modify: `tests/tools/multitenant/MergeFeeDataTest.php`

**Interfaces:**
- Extends `MergeFeeData::run()`'s return array with:
  `student_fees_deposite_migrated`, `student_fees_deposite_source_total`,
  `student_fees_deposite_skipped`, `student_applied_discounts_migrated`,
  `student_applied_discounts_source_total`,
  `student_applied_discounts_skipped` (all `int`). This is the FINAL
  extension to `run()` — after this task, the method is complete.

Unlike every other reconnection in this stage, these two tables don't
need `StudentSessionIdResolver` or `NaturalKeyIdResolver` at all — every
FK they have points to a table remapped EARLIER in this SAME run
(`student_fees_master`, `fee_groups_feetype`, `student_fees_discounts`,
`student_fees_deposite`), so plain lookups against the in-memory
`*RowsToInsert`/`*Remap` maps already built by Tasks 2-3 are sufficient.
`student_fees_deposite.student_transport_fee_id` is always `NULL` in
real data (verified during planning) and is not resolved by this task —
it passes through as `NULL` unchanged, matching the Global Constraints'
scope note.

- [ ] **Step 1: Extend the test file's schema and add the failing tests**

In `tests/tools/multitenant/MergeFeeDataTest.php`, add these two schema
strings inside `setUp()` (after `$sfDiscSchema`, before the `foreach`
loop):

```php
        $sfDepositeSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_fees_master_id INT DEFAULT NULL, fee_groups_feetype_id INT DEFAULT NULL,'
            . ' student_transport_fee_id INT DEFAULT NULL, amount_detail TEXT DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $sadSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_fees_deposite_id INT DEFAULT NULL, student_fees_discount_id INT DEFAULT NULL,'
            . ' date DATE DEFAULT NULL, invoice_id INT DEFAULT NULL, sub_invoice_id INT DEFAULT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
```

Replace the entire `foreach ([$this->source, $this->target] as $db) {
... }` loop body from Task 3's version (still inside `setUp()`) — keep
the thirteen original `CREATE TABLE` lines and add two more — so the
whole loop reads exactly as:

```php
        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE feetype ({$feetypeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups ({$feeGroupsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_discounts ({$feesDiscountsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_session_groups ({$fsgSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups_feetype ({$fgfSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_reminder ({$reminderSchema}{$tenantCol})");
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE classes ({$classSchema}{$tenantCol})");
            $db->exec("CREATE TABLE sections ({$sectionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_session ({$studentSessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_fees_master ({$sfmSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_fees_discounts ({$sfDiscSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_fees_deposite ({$sfDepositeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_applied_discounts ({$sadSchema}{$tenantCol})");
        }
```

Then add this test method to the class (after
`testSkipsStudentFeesRowsReferencingAnUnmigratedStudentSession`):

```php
    public function testReconnectsDepositeAndAppliedDiscountsToThisRunsOwnRemappedRows(): void
    {
        // Full chain: catalog -> student -> master -> deposite -> applied discount.
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25)");
        $this->source->exec("INSERT INTO fee_groups (id, name, nature) VALUES (8, 'General Fee', 'monthly')");
        $this->source->exec("INSERT INTO fee_session_groups (id, fee_groups_id, session_id) VALUES (30, 8, 20)");
        $this->source->exec("INSERT INTO feetype (id, type, code, nature, session_id) VALUES (5, 'Tuition Fee', 'TUI', 'monthly', 20)");
        $this->source->exec(
            "INSERT INTO fee_groups_feetype (id, fee_session_group_id, fee_groups_id, feetype_id, session_id, amount)"
            . " VALUES (100, 30, 8, 5, 20, 1500.00)"
        );
        $this->source->exec("INSERT INTO fees_discounts (id, session_id, name, code, type, percentage) VALUES (12, 20, 'Sibling Discount', 'SIB', 'percentage', 10.00)");

        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES (401, 101, 201, 301, '2025-01-01 00:00:00')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");

        $this->source->exec(
            "INSERT INTO student_fees_master (id, student_session_id, fee_session_group_id, amount) VALUES (500, 401, 30, 1500.00)"
        );
        $this->source->exec(
            "INSERT INTO student_fees_discounts (id, student_session_id, fees_discount_id, status) VALUES (600, 401, 12, 'assigned')"
        );
        $this->source->exec(
            "INSERT INTO student_fees_deposite (id, student_fees_master_id, fee_groups_feetype_id, amount_detail) VALUES (700, 500, 100, '{\"paid\":1500}')"
        );
        $this->source->exec(
            "INSERT INTO student_applied_discounts (id, student_fees_deposite_id, student_fees_discount_id, date, invoice_id) VALUES (800, 700, 600, '2026-01-15', 42)"
        );

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['student_fees_deposite_migrated']);
        $this->assertSame(1, $result['student_fees_deposite_source_total']);
        $this->assertSame(0, $result['student_fees_deposite_skipped']);
        $this->assertSame(1, $result['student_applied_discounts_migrated']);
        $this->assertSame(1, $result['student_applied_discounts_source_total']);
        $this->assertSame(0, $result['student_applied_discounts_skipped']);

        $deposite = $this->target->query('SELECT * FROM student_fees_deposite')->fetch(PDO::FETCH_ASSOC);
        $master = $this->target->query('SELECT * FROM student_fees_master')->fetch(PDO::FETCH_ASSOC);
        $fgf = $this->target->query('SELECT * FROM fee_groups_feetype')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame((int) $master['id'], (int) $deposite['student_fees_master_id']);
        $this->assertSame((int) $fgf['id'], (int) $deposite['fee_groups_feetype_id']);
        $this->assertNull($deposite['student_transport_fee_id']);
        $this->assertSame(25, (int) $deposite['tenant_id']);

        $applied = $this->target->query('SELECT * FROM student_applied_discounts')->fetch(PDO::FETCH_ASSOC);
        $sfDisc = $this->target->query('SELECT * FROM student_fees_discounts')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame((int) $deposite['id'], (int) $applied['student_fees_deposite_id']);
        $this->assertSame((int) $sfDisc['id'], (int) $applied['student_fees_discount_id']);
        $this->assertSame(42, (int) $applied['invoice_id']);
        $this->assertSame(25, (int) $applied['tenant_id']);
    }
}
```

- [ ] **Step 2: Run tests to verify the new one fails**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeFeeDataTest.php`
Expected: the new test FAILS (undefined array key), the three prior
tests still pass.

- [ ] **Step 3: Extend `MergeFeeData::run()` (Part C)**

In `tools/multitenant/MergeFeeData.php`, insert the following code
inside `run()`, immediately after the `foreach ($sfDiscRows as $row) {
... }` block from Part B and BEFORE the `$this->inTransaction(function
() use (...` line:

```php
        $sfDepositeRemap = new IdRemapper($this->nextId('student_fees_deposite'));
        $sfDepositeRows = $this->fetchAll(
            'SELECT id, student_fees_master_id, fee_groups_feetype_id, student_transport_fee_id, amount_detail, is_active, created_at, updated_at'
            . ' FROM student_fees_deposite'
        );
        $sfDepositeSourceTotal = count($sfDepositeRows);
        $sfDepositeSkipped = 0;
        $sfDepositeRowsToInsert = [];
        foreach ($sfDepositeRows as $row) {
            $oldId = (int) $row['id'];
            $oldMasterId = $row['student_fees_master_id'] !== null ? (int) $row['student_fees_master_id'] : null;
            $oldFgfId = $row['fee_groups_feetype_id'] !== null ? (int) $row['fee_groups_feetype_id'] : null;
            if (($oldMasterId !== null && !isset($sfmRowsToInsert[$oldMasterId]))
                || ($oldFgfId !== null && !isset($fgfRowsToInsert[$oldFgfId]))
            ) {
                $sfDepositeSkipped++;
                continue;
            }
            $sfDepositeRemap->remapId($oldId);
            $row['id'] = $sfDepositeRemap->getMapping($oldId);
            $row['student_fees_master_id'] = $oldMasterId !== null ? $sfmRemap->getMapping($oldMasterId) : null;
            $row['fee_groups_feetype_id'] = $oldFgfId !== null ? $fgfRemap->getMapping($oldFgfId) : null;
            // student_transport_fee_id intentionally passed through unresolved --
            // always NULL in real data (verified during planning); transport
            // fees are out of this stage's scope.
            $sfDepositeRowsToInsert[$oldId] = $row;
        }

        $sadRemap = new IdRemapper($this->nextId('student_applied_discounts'));
        $sadRows = $this->fetchAll(
            'SELECT id, student_fees_deposite_id, student_fees_discount_id, date, invoice_id, sub_invoice_id, created_at, updated_at'
            . ' FROM student_applied_discounts'
        );
        $sadSourceTotal = count($sadRows);
        $sadSkipped = 0;
        $sadRowsToInsert = [];
        foreach ($sadRows as $row) {
            $oldId = (int) $row['id'];
            $oldDepositeId = $row['student_fees_deposite_id'] !== null ? (int) $row['student_fees_deposite_id'] : null;
            $oldDiscountId = $row['student_fees_discount_id'] !== null ? (int) $row['student_fees_discount_id'] : null;
            if (($oldDepositeId !== null && !isset($sfDepositeRowsToInsert[$oldDepositeId]))
                || ($oldDiscountId !== null && !isset($sfDiscRowsToInsert[$oldDiscountId]))
            ) {
                $sadSkipped++;
                continue;
            }
            $sadRemap->remapId($oldId);
            $row['id'] = $sadRemap->getMapping($oldId);
            $row['student_fees_deposite_id'] = $oldDepositeId !== null ? $sfDepositeRemap->getMapping($oldDepositeId) : null;
            $row['student_fees_discount_id'] = $oldDiscountId !== null ? $sfDiscRemap->getMapping($oldDiscountId) : null;
            $sadRowsToInsert[$oldId] = $row;
        }
```

Then change the `$this->inTransaction(function () use (...` line's `use`
clause to also capture `$sfDepositeRowsToInsert, $sadRowsToInsert`, and
add these two `foreach` loops inside the closure body, after the
existing `student_fees_discounts` loop:

```php
            foreach ($sfDepositeRowsToInsert as $row) {
                $this->insertRow('student_fees_deposite', $row);
            }
            foreach ($sadRowsToInsert as $row) {
                $this->insertRow('student_applied_discounts', $row);
            }
```

Finally, extend the `return [...]` array (still inside `run()`) to add:

```php
            'student_fees_deposite_migrated' => count($sfDepositeRowsToInsert),
            'student_fees_deposite_source_total' => $sfDepositeSourceTotal,
            'student_fees_deposite_skipped' => $sfDepositeSkipped,
            'student_applied_discounts_migrated' => count($sadRowsToInsert),
            'student_applied_discounts_source_total' => $sadSourceTotal,
            'student_applied_discounts_skipped' => $sadSkipped,
```

Add a CLI entry point at the bottom of the file (this is the tool's
first CLI bootstrap):

```php
if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeFeeData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeFeeData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['feetype_migrated']} fee types, {$result['fee_groups_migrated']} fee groups,"
        . " {$result['fees_discounts_migrated']} fee discounts, {$result['fee_session_groups_migrated']} fee session groups,"
        . " {$result['fee_groups_feetype_migrated']} fee group pricing rows, {$result['fees_reminder_migrated']} fee reminders,"
        . " {$result['student_fees_master_migrated']} student fee assignments, {$result['student_fees_deposite_migrated']} student fee deposits,"
        . " {$result['student_fees_discounts_migrated']} student fee discounts, and {$result['student_applied_discounts_migrated']} applied discounts"
        . " for tenant {$tenantId}.\n";

    $skipChecks = [
        'feetype' => [$result['feetype_skipped'], $result['feetype_source_total']],
        'fees_discounts' => [$result['fees_discounts_skipped'], $result['fees_discounts_source_total']],
        'fee_session_groups' => [$result['fee_session_groups_skipped'], $result['fee_session_groups_source_total']],
        'fee_groups_feetype' => [$result['fee_groups_feetype_skipped'], $result['fee_groups_feetype_source_total']],
        'student_fees_master' => [$result['student_fees_master_skipped'], $result['student_fees_master_source_total']],
        'student_fees_discounts' => [$result['student_fees_discounts_skipped'], $result['student_fees_discounts_source_total']],
        'student_fees_deposite' => [$result['student_fees_deposite_skipped'], $result['student_fees_deposite_source_total']],
        'student_applied_discounts' => [$result['student_applied_discounts_skipped'], $result['student_applied_discounts_source_total']],
    ];
    foreach ($skipChecks as $label => [$skipped, $sourceTotal]) {
        if ($skipped > 0) {
            fwrite(
                STDERR,
                "WARNING: {$skipped} of {$sourceTotal} {$label} rows could not be resolved and were skipped."
                . " Investigate before trusting this migration.\n"
            );
        }
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeFeeDataTest.php`
Expected: `OK (4 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (53 tests, ...)` (52 prior + 1 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeFeeData.php tests/tools/multitenant/MergeFeeDataTest.php
git commit -m "feat: extend MergeFeeData (Part C) — reconnect student_fees_deposite/applied_discounts within this run"
```

---

### Task 5: `PilotFees` controller — end-to-end proof

**Files:**
- Create: `application/controllers/PilotFees.php`
- Create: `application/views/pilot_fees.php`

**Interfaces:**
- Consumes: `Tenant_Model::tenantGetAll(string $table): array` (existing,
  unchanged), the `pilot_tenant_id` session convention set by
  `PilotStudents::login_as($tenantId)` (existing, unchanged).
- Produces: a `/pilotfees/index` page listing every real student fee
  deposit for the pilot tenant with student name, fee type name, fee
  group name, amount, and any applied discount — proving the full
  10-table chain resolves correctly end to end.

- [ ] **Step 1: Create the controller**

Create `application/controllers/PilotFees.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotFees extends CI_Controller
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
        $deposites = $this->tenant_model->tenantGetAll('student_fees_deposite');
        $masters = $this->tenant_model->tenantGetAll('student_fees_master');
        $studentSessions = $this->tenant_model->tenantGetAll('student_session');
        $students = $this->tenant_model->tenantGetAll('students');
        $fgfRows = $this->tenant_model->tenantGetAll('fee_groups_feetype');
        $feetypes = $this->tenant_model->tenantGetAll('feetype');
        $feeGroups = $this->tenant_model->tenantGetAll('fee_groups');
        $appliedDiscounts = $this->tenant_model->tenantGetAll('student_applied_discounts');

        $studentSessionIdByMasterId = [];
        foreach ($masters as $master) {
            $studentSessionIdByMasterId[$master['id']] = $master['student_session_id'];
        }

        $studentIdBySessionId = [];
        foreach ($studentSessions as $session) {
            $studentIdBySessionId[$session['id']] = $session['student_id'];
        }

        $studentNameById = [];
        foreach ($students as $student) {
            $studentNameById[$student['id']] = trim($student['firstname'] . ' ' . ($student['lastname'] ?? ''));
        }

        $feetypeIdByFgfId = [];
        $feeGroupIdByFgfId = [];
        foreach ($fgfRows as $fgf) {
            $feetypeIdByFgfId[$fgf['id']] = $fgf['feetype_id'];
            $feeGroupIdByFgfId[$fgf['id']] = $fgf['fee_groups_id'];
        }

        $feetypeNameById = [];
        foreach ($feetypes as $feetype) {
            $feetypeNameById[$feetype['id']] = $feetype['type'];
        }

        $feeGroupNameById = [];
        foreach ($feeGroups as $feeGroup) {
            $feeGroupNameById[$feeGroup['id']] = $feeGroup['name'];
        }

        $discountCountByDepositeId = [];
        foreach ($appliedDiscounts as $discount) {
            $depositeId = $discount['student_fees_deposite_id'];
            $discountCountByDepositeId[$depositeId] = ($discountCountByDepositeId[$depositeId] ?? 0) + 1;
        }

        $rows = [];
        foreach ($deposites as $deposite) {
            $masterId = $deposite['student_fees_master_id'];
            $sessionId = $studentSessionIdByMasterId[$masterId] ?? null;
            $studentId = $sessionId !== null ? ($studentIdBySessionId[$sessionId] ?? null) : null;

            $fgfId = $deposite['fee_groups_feetype_id'];
            $feetypeId = $feetypeIdByFgfId[$fgfId] ?? null;
            $feeGroupId = $feeGroupIdByFgfId[$fgfId] ?? null;

            $rows[] = [
                'student' => $studentId !== null ? ($studentNameById[$studentId] ?? 'Unknown') : 'Unknown',
                'fee_type' => $feetypeId !== null ? ($feetypeNameById[$feetypeId] ?? 'Unknown') : 'Unknown',
                'fee_group' => $feeGroupId !== null ? ($feeGroupNameById[$feeGroupId] ?? 'Unknown') : 'Unknown',
                'discount_count' => $discountCountByDepositeId[$deposite['id']] ?? 0,
            ];
        }

        $this->load->view('pilot_fees', ['rows' => $rows]);
    }
}
```

- [ ] **Step 2: Create the view**

Create `application/views/pilot_fees.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Pilot Fees</title></head>
<body>
<h1>Student Fee Deposits (<?php echo count($rows); ?> results)</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li><?php echo htmlspecialchars($row['student']); ?> — <?php echo htmlspecialchars($row['fee_group']); ?> / <?php echo htmlspecialchars($row['fee_type']); ?> (<?php echo (int) $row['discount_count']; ?> discount(s) applied)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

- [ ] **Step 3: Lint**

Run: `"C:\xampp81\php\php.exe" -l application/controllers/PilotFees.php` and `"C:\xampp81\php\php.exe" -l application/views/pilot_fees.php`.
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Run the full suite (regression check only — this task adds no new tests)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (53 tests, ...)` (unchanged from Task 4).

- [ ] **Step 5: Commit**

```bash
git add application/controllers/PilotFees.php application/views/pilot_fees.php
git commit -m "feat: add PilotFees controller showing tenant-scoped student fee deposits end to end"
```

---

### Task 6: Migrate real fee data + verify end-to-end

**Files:** none created — this task runs the tool built in Tasks 2-4
against real data and verifies the result. If verification finds a real
data-shape problem (this plan's pre-flight survey found zero dangling
references and zero natural-key ambiguity, unlike Stages 3 and 4 — but
"verified clean today" is not a guarantee, the same discipline applies),
stop, diagnose, document a "Post-Task-N fix" section in this plan doc,
dispatch a fix, get it independently reviewed, and only then retry this
task.

- [ ] **Step 1: Run the real migration**

Run: `"C:\xampp81\php\php.exe" tools/multitenant/MergeFeeData.php al_hafeez_campus 25`

Expected output (values from this plan's pre-flight data survey — if
actual output differs, STOP and investigate before continuing):
```
Migrated 6 fee types, 26 fee groups, 35 fee discounts, 25 fee session groups, 46 fee group pricing rows, 4 fee reminders, 626 student fee assignments, 699 student fee deposits, 215 student fee discounts, and 111 applied discounts for tenant 25.
```
No STDERR warning lines expected (all four skip counts should be 0,
verified via this plan's pre-flight dangling-reference survey; if a
warning DOES appear, stop and investigate).

- [ ] **Step 2: Row-count reconciliation**

Run against `al_hafeez_campus` and the matching `WHERE tenant_id = 25`
query against `school_saas`, for all ten tables:
`feetype`, `fee_groups`, `fees_discounts`, `fee_session_groups`,
`fee_groups_feetype`, `fees_reminder`, `student_fees_master`,
`student_fees_deposite`, `student_fees_discounts`,
`student_applied_discounts`.
Expected: 6, 26, 35, 25, 46, 4, 626, 699, 215, 111 — identical on both
sides for every table.

- [ ] **Step 3: Spot-check a handful of real rows**

Pick 3-5 real students with fee deposits (e.g. via `SELECT
student_fees_master_id, COUNT(*) FROM student_fees_deposite GROUP BY
student_fees_master_id LIMIT 5` against source, then trace up to
`student_session`/`students`) and compare their admission_no, fee type
names, fee group names, and deposit amounts between `al_hafeez_campus`
and `school_saas` (joining through the migrated tables on both sides).
All fields must match exactly.

- [ ] **Step 4: End-to-end verification via `PilotFees`**

```
curl http://localhost/web-app/pilotstudents/login_as/25
curl http://localhost/web-app/pilotfees/index
```

Expected: HTTP 200, exactly 699 `<li>` entries (matching
`student_fees_deposite`'s real row count), 0 occurrences of the string
`Unknown`.

- [ ] **Step 5: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`
to mark Phase 3 Stage 1 complete, following the exact style of Phase 2's
stage entries (real row counts, what was proven, any bugs found and
fixed) — and update the `Phase 3` heading itself from "not yet planned"
to "in progress, staged" (matching how Phase 2's heading was updated
once Stage 1 began), noting the same per-module sub-plan structure the
roadmap already anticipated.

- [ ] **Step 6: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 1 (fees) complete"
```

---

### Final whole-stage review (after Task 6)

Once Task 6 succeeds, dispatch an Opus adversarial review of the whole
stage's final state, following the exact pattern used at the end of
every prior stage: tenant isolation reasoning, resolver-reuse
correctness (`StudentSessionIdResolver` used unchanged for a fourth
time), whether the skip-count reporting actually works end to end
against real data, whether every `insertRow()` call correctly overwrites
`id` via its own remapper (the exact class of bug Stage 5's Post-Task-3
fix caught — re-check this explicitly, table by table, for all ten
tables in this stage), and whether the roadmap/plan docs accurately
reflect what was built. Fix any Critical/Important findings (with
independent re-review) before considering this stage done.
