# Multi-Tenant Migration — Phase 2 Stage 5: Exams Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the pilot tenant's real exam data — exam groups, batch
exams, exam subjects, per-student exam enrollment, and marks/results
(3,863 real rows across 7 tables in `al_hafeez_campus`) — into
`school_saas`, proving the tenant-scoping mechanism extends to the
deepest FK chain yet: two brand-new catalog tables (`sessions`,
`subjects`), three brand-new tables migrated fresh within this same run
(`exam_groups`, `exam_group_class_batch_exams`,
`exam_group_class_batch_exam_subjects`), and two tables that reconnect to
data migrated in EARLIER, SEPARATE stages (`students` from Stage 1,
`student_session` from Stage 3) using the two natural-key resolvers
already built and hardened for that purpose.

**Architecture:** Seven new tenant-scoped tables in `school_saas`. A
single new `MergeExamData` tool (extends `AbstractTenantMerger`) migrates
all seven in one `run()`/one transaction: the first five tables use
`IdRemapper` only (fresh migration, both ends of every FK are being
created in this same run, so an in-memory old→new map is sufficient — the
same pattern as Stage 4's `attendence_type`); the last two
(`exam_group_class_batch_exam_students`, `exam_group_exam_results`)
additionally reconnect to already-migrated `students` and
`student_session` rows via the EXISTING `NaturalKeyIdResolver` (Stage 3)
and `StudentSessionIdResolver` (Stage 4) — no new resolver is needed for
this stage. A new `PilotExam` controller proves it end-to-end by
rendering real students' marks per subject per exam.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, PDO.

## Global Constraints

- Do not modify `application/controllers/Site.php`, `application/libraries/Auth.php`,
  `application/libraries/Db_manager.php`, or any existing model.
- Do not modify `PilotStudents.php`, `PilotLogin.php`, `PilotClasses.php`,
  `PilotStudentSessions.php`, `PilotAttendance.php`, or
  `application/core/Tenant_Model.php` — this stage adds a NEW controller,
  reusing the `pilot_tenant_id` session convention.
- Do not modify `tools/multitenant/TenantScope.php`, `IdRemapper.php`,
  `AbstractTenantMerger.php`, `NaturalKeyIdResolver.php`,
  `ClassSectionPairResolver.php`, `StudentSessionIdResolver.php`, or any
  existing merge tool (`MergeSchoolData`, `MergeStaffData`,
  `MergeClassData`, `MergeStudentSessionData`, `MergeAttendanceData`).
  This stage's whole point is proving the two existing natural-key
  resolvers generalize to a THIRD consumer without changes.
- Do not touch the large set of pre-existing uncommitted changes to
  `application/controllers/admin/Exam_schedule.php`,
  `application/controllers/admin/Examgroup.php`,
  `application/controllers/admin/Examresult.php`,
  `application/models/Examgroup_model.php`,
  `application/models/Examgroupstudent_model.php`,
  `application/models/Examstudent_model.php`, or their views — this is
  unrelated pre-existing work on the legacy per-branch exam module, not
  part of the multi-tenant migration. Leave it exactly as found.
- Do not modify the existing `al_hafeez_campus` (or any other school)
  database — only read from it.
- **`MergeExamData` must report `_source_total` and `_skipped` counts for
  every table where a row can be skipped due to an unresolved natural-key
  lookup**, and the CLI must print a STDERR warning when any skip count
  is nonzero. This is not optional hardening added after the fact — it's
  the direct lesson from Stage 4's final review, which found
  `MergeAttendanceData` originally had no such reporting and could have
  silently under-migrated a future school with no alarm. Apply it from
  the start this time (Task 3).
- All new PHP must run under PHP 8.1.
- Use `127.0.0.1` / `root` / empty password for local MySQL.
- Tenant id `25` is reserved for `al_hafeez_campus`.
- MySQL and Apache are already running — don't start/stop them.
- **Out of scope for this stage** (deferred, verified empty or
  near-empty in `al_hafeez_campus`): `exams` (0 rows), `exam_schedules`
  (0 rows), `exam_group_exam_connections` (0 rows), `exam_group_students`
  (0 rows — and its FK from `exam_group_exam_results.exam_group_student_id`
  is dropped from this stage's target schema entirely, since that
  column is verified NULL on all 2,785 real rows), `onlineexam`/
  `onlineexam_attempts`/`onlineexam_questions`/`onlineexam_students`/
  `onlineexam_student_results` (a separate online-quiz feature with at
  most 2 real rows — nothing substantial to prove).
- Verified via direct SQL against `al_hafeez_campus` before writing this
  plan: `exam_group_class_batch_exams.session_id` only ever references
  session ids 20 (`"2024-25"`) and 22 (`"2026-27"`) — the `sessions`
  table does contain a real duplicate-name pair (`"2025-26"` appears as
  both id 21 and id 26), but no real exam data touches it, and this
  stage's migration doesn't need to resolve `sessions` by name at all
  (fresh `IdRemapper` migration, not natural-key reconnection), so the
  duplicate is a non-issue here. `subjects.code` is blank (empty string)
  on all 38 rows and is NOT used as a key for the same reason. Zero
  dangling FK references exist anywhere in the 7-table chain (verified
  via `LEFT JOIN ... WHERE ... IS NULL` on every FK).

---

### Task 1: Extend `school_saas` — 7 exam tables

**Files:**
- Create: `sql/multitenant/006_add_exam_tables.sql`

**Interfaces:**
- Produces: `sessions`, `subjects`, `exam_groups`,
  `exam_group_class_batch_exams`, `exam_group_class_batch_exam_subjects`,
  `exam_group_class_batch_exam_students`, `exam_group_exam_results` (all
  tenant-scoped) in `school_saas`. Consumed by Task 2, Task 3, and Task 4.

- [ ] **Step 1: Write the schema SQL**

Create `sql/multitenant/006_add_exam_tables.sql`:

```sql
USE school_saas;

CREATE TABLE sessions (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    session VARCHAR(60) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sessions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subjects (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    code VARCHAR(100) NOT NULL,
    type VARCHAR(100) NOT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_subjects_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_groups (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    name VARCHAR(250) DEFAULT NULL,
    exam_type VARCHAR(250) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active INT DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_examgroups_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_group_class_batch_exams (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    exam VARCHAR(250) DEFAULT NULL,
    passing_percentage FLOAT(10,2) DEFAULT NULL,
    session_id INT NOT NULL,
    date_from DATE DEFAULT NULL,
    date_to DATE DEFAULT NULL,
    exam_group_id INT DEFAULT NULL,
    use_exam_roll_no INT NOT NULL DEFAULT 1,
    is_publish INT DEFAULT 0,
    is_rank_generated INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_egcbe_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_egcbe_examgroup FOREIGN KEY (exam_group_id) REFERENCES exam_groups (id),
    CONSTRAINT fk_egcbe_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_group_class_batch_exam_subjects (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    exam_group_class_batch_exams_id INT DEFAULT NULL,
    subject_id INT NOT NULL,
    date_from DATE NOT NULL,
    time_from TIME NOT NULL,
    duration VARCHAR(50) NOT NULL,
    room_no VARCHAR(100) DEFAULT NULL,
    max_marks FLOAT(10,2) DEFAULT NULL,
    min_marks FLOAT(10,2) DEFAULT NULL,
    credit_hours FLOAT(10,2) DEFAULT 0.00,
    date_to DATETIME DEFAULT NULL,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_egcbes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_egcbes_exam FOREIGN KEY (exam_group_class_batch_exams_id) REFERENCES exam_group_class_batch_exams (id),
    CONSTRAINT fk_egcbes_subject FOREIGN KEY (subject_id) REFERENCES subjects (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_group_class_batch_exam_students (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    exam_group_class_batch_exam_id INT NOT NULL,
    student_id INT NOT NULL,
    student_session_id INT NOT NULL,
    roll_no INT DEFAULT NULL,
    teacher_remark TEXT DEFAULT NULL,
    `rank` INT NOT NULL DEFAULT 0,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_egcbest_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_egcbest_exam FOREIGN KEY (exam_group_class_batch_exam_id) REFERENCES exam_group_class_batch_exams (id),
    CONSTRAINT fk_egcbest_student FOREIGN KEY (student_id) REFERENCES students (id),
    CONSTRAINT fk_egcbest_session FOREIGN KEY (student_session_id) REFERENCES student_session (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_group_exam_results (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    exam_group_class_batch_exam_student_id INT NOT NULL,
    exam_group_class_batch_exam_subject_id INT DEFAULT NULL,
    attendence VARCHAR(10) DEFAULT NULL,
    get_marks FLOAT(10,2) DEFAULT 0.00,
    note TEXT DEFAULT NULL,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_result_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_result_student FOREIGN KEY (exam_group_class_batch_exam_student_id) REFERENCES exam_group_class_batch_exam_students (id),
    CONSTRAINT fk_result_subject FOREIGN KEY (exam_group_class_batch_exam_subject_id) REFERENCES exam_group_class_batch_exam_subjects (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

(`exam_group_exam_results.exam_group_student_id` from the source schema
is intentionally omitted — verified `0` non-NULL out of 2,785 real rows,
and its source-side FK target `exam_group_students` is out of scope.)

- [ ] **Step 2: Apply the schema**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root < sql/multitenant/006_add_exam_tables.sql`

- [ ] **Step 3: Verify**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SHOW TABLES;"`
Expected: 19 tables now (12 existing + 7 new).

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT COUNT(*) FROM students; SELECT COUNT(*) FROM student_session; SELECT COUNT(*) FROM student_attendences;"`
Expected: 312 students, 484 student_session rows, 1124 student_attendences rows — all unchanged from Stage 4.

- [ ] **Step 4: Commit**

```bash
git add sql/multitenant/006_add_exam_tables.sql
git commit -m "feat: add exam tables (sessions, subjects, exam_groups, batch exams/subjects/students, results) to school_saas"
```

---

### Task 2: `MergeExamData` Part A — catalog + schedule tables (fresh `IdRemapper` only)

**Files:**
- Create: `tools/multitenant/MergeExamData.php`
- Test: `tests/tools/multitenant/MergeExamDataTest.php`

**Interfaces:**
- Produces: `class MergeExamData extends AbstractTenantMerger` with
  `run(): array`, returning at minimum `sessions_migrated`,
  `subjects_migrated`, `exam_groups_migrated`,
  `exam_group_class_batch_exams_migrated`,
  `exam_group_class_batch_exam_subjects_migrated` (all `int`). Task 3
  extends this same class's `run()` to add the remaining two tables and
  their skip-count keys.
- Consumes: `AbstractTenantMerger::nextId()/fetchAll()/insertRow()/inTransaction()`,
  `IdRemapper` (both already exist, unchanged).

This task covers the five tables where BOTH ends of every FK are created
fresh within this same migration run (`sessions`, `subjects`,
`exam_groups` have no FKs into other stages' data at all;
`exam_group_class_batch_exams` FKs to `sessions`/`exam_groups` from this
same run; `exam_group_class_batch_exam_subjects` FKs to
`exam_group_class_batch_exams`/`subjects` from this same run) — so a
plain `IdRemapper` per table is sufficient, the same pattern Stage 4 used
for `attendence_type`. No natural-key resolution, no collision risk.

- [ ] **Step 1: Write the failing test**

Create `tests/tools/multitenant/MergeExamDataTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class MergeExamDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_exam_test_source');
        $admin->exec('CREATE DATABASE merge_exam_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_exam_test_target');
        $admin->exec('CREATE DATABASE merge_exam_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_exam_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_exam_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, session VARCHAR(60) DEFAULT NULL, is_active VARCHAR(255) DEFAULT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $subjectSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) DEFAULT NULL, code VARCHAR(100) NOT NULL,'
            . ' type VARCHAR(100) NOT NULL, is_active VARCHAR(255) DEFAULT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $examGroupSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(250) DEFAULT NULL, exam_type VARCHAR(250) DEFAULT NULL,'
            . ' description TEXT DEFAULT NULL, is_active INT DEFAULT 1,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $batchExamSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam VARCHAR(250) DEFAULT NULL, passing_percentage FLOAT(10,2) DEFAULT NULL,'
            . ' session_id INT NOT NULL, date_from DATE DEFAULT NULL, date_to DATE DEFAULT NULL, exam_group_id INT DEFAULT NULL,'
            . ' use_exam_roll_no INT NOT NULL DEFAULT 1, is_publish INT DEFAULT 0, is_rank_generated INT NOT NULL DEFAULT 0,'
            . ' description TEXT DEFAULT NULL, is_active INT DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $batchExamSubjectSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam_group_class_batch_exams_id INT DEFAULT NULL, subject_id INT NOT NULL,'
            . ' date_from DATE NOT NULL, time_from TIME NOT NULL, duration VARCHAR(50) NOT NULL, room_no VARCHAR(100) DEFAULT NULL,'
            . ' max_marks FLOAT(10,2) DEFAULT NULL, min_marks FLOAT(10,2) DEFAULT NULL, credit_hours FLOAT(10,2) DEFAULT 0.00,'
            . ' date_to DATETIME DEFAULT NULL, is_active INT DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE subjects ({$subjectSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_groups ({$examGroupSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exams ({$batchExamSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exam_subjects ({$batchExamSubjectSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_exam_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_exam_test_target');
    }

    public function testMergesCatalogAndScheduleTablesWithRemappedIds(): void
    {
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->source->exec("INSERT INTO subjects (id, name, code, type) VALUES (5, 'Mathematics', '', 'theory')");
        $this->source->exec("INSERT INTO exam_groups (id, name, exam_type) VALUES (8, 'Annual Terminal Examination', 'school_grade_system')");
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exams (id, exam, session_id, exam_group_id) VALUES (30, '9th Annual', 20, 8)"
        );
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_subjects (id, exam_group_class_batch_exams_id, subject_id, date_from, time_from, duration)"
            . " VALUES (100, 30, 5, '2026-03-01', '09:00:00', '2 hours')"
        );

        $merger = new MergeExamData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['sessions_migrated']);
        $this->assertSame(1, $result['subjects_migrated']);
        $this->assertSame(1, $result['exam_groups_migrated']);
        $this->assertSame(1, $result['exam_group_class_batch_exams_migrated']);
        $this->assertSame(1, $result['exam_group_class_batch_exam_subjects_migrated']);

        $session = $this->target->query('SELECT * FROM sessions')->fetch(PDO::FETCH_ASSOC);
        $subject = $this->target->query('SELECT * FROM subjects')->fetch(PDO::FETCH_ASSOC);
        $examGroup = $this->target->query('SELECT * FROM exam_groups')->fetch(PDO::FETCH_ASSOC);
        $batchExam = $this->target->query('SELECT * FROM exam_group_class_batch_exams')->fetch(PDO::FETCH_ASSOC);
        $batchExamSubject = $this->target->query('SELECT * FROM exam_group_class_batch_exam_subjects')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('2024-25', $session['session']);
        $this->assertSame(25, (int) $session['tenant_id']);
        $this->assertSame('Mathematics', $subject['name']);
        $this->assertSame('Annual Terminal Examination', $examGroup['name']);
        $this->assertSame((int) $session['id'], (int) $batchExam['session_id']);
        $this->assertSame((int) $examGroup['id'], (int) $batchExam['exam_group_id']);
        $this->assertSame(25, (int) $batchExam['tenant_id']);
        $this->assertSame((int) $batchExam['id'], (int) $batchExamSubject['exam_group_class_batch_exams_id']);
        $this->assertSame((int) $subject['id'], (int) $batchExamSubject['subject_id']);
        $this->assertSame(25, (int) $batchExamSubject['tenant_id']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeExamDataTest.php`
Expected: FAIL — `Class "MergeExamData" not found`.

- [ ] **Step 3: Implement `MergeExamData` (Part A)**

Create `tools/multitenant/MergeExamData.php`:

```php
<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';
require_once __DIR__ . '/StudentSessionIdResolver.php';

final class MergeExamData extends AbstractTenantMerger
{
    public function run(): array
    {
        $sessionRemap = new IdRemapper($this->nextId('sessions'));
        $sessions = $this->fetchAll('SELECT id, session, is_active, created_at, updated_at FROM sessions');
        foreach ($sessions as $row) {
            $sessionRemap->remapId((int) $row['id']);
        }

        $subjectRemap = new IdRemapper($this->nextId('subjects'));
        $subjects = $this->fetchAll('SELECT id, name, code, type, is_active, created_at, updated_at FROM subjects');
        foreach ($subjects as $row) {
            $subjectRemap->remapId((int) $row['id']);
        }

        $examGroupRemap = new IdRemapper($this->nextId('exam_groups'));
        $examGroups = $this->fetchAll('SELECT id, name, exam_type, description, is_active, created_at, updated_at FROM exam_groups');
        foreach ($examGroups as $row) {
            $examGroupRemap->remapId((int) $row['id']);
        }

        $batchExamRemap = new IdRemapper($this->nextId('exam_group_class_batch_exams'));
        $batchExams = $this->fetchAll(
            'SELECT id, exam, passing_percentage, session_id, date_from, date_to, exam_group_id,'
            . ' use_exam_roll_no, is_publish, is_rank_generated, description, is_active, created_at, updated_at'
            . ' FROM exam_group_class_batch_exams'
        );
        foreach ($batchExams as $row) {
            $batchExamRemap->remapId((int) $row['id']);
        }

        $batchExamSubjectRemap = new IdRemapper($this->nextId('exam_group_class_batch_exam_subjects'));
        $batchExamSubjects = $this->fetchAll(
            'SELECT id, exam_group_class_batch_exams_id, subject_id, date_from, time_from, duration, room_no,'
            . ' max_marks, min_marks, credit_hours, date_to, is_active, created_at, updated_at'
            . ' FROM exam_group_class_batch_exam_subjects'
        );
        foreach ($batchExamSubjects as $row) {
            $batchExamSubjectRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use (
            $sessions, $subjects, $examGroups, $batchExams, $batchExamSubjects,
            $sessionRemap, $subjectRemap, $examGroupRemap, $batchExamRemap, $batchExamSubjectRemap
        ) {
            foreach ($sessions as $row) {
                $row['id'] = $sessionRemap->getMapping((int) $row['id']);
                $this->insertRow('sessions', $row);
            }
            foreach ($subjects as $row) {
                $row['id'] = $subjectRemap->getMapping((int) $row['id']);
                $this->insertRow('subjects', $row);
            }
            foreach ($examGroups as $row) {
                $row['id'] = $examGroupRemap->getMapping((int) $row['id']);
                $this->insertRow('exam_groups', $row);
            }
            foreach ($batchExams as $row) {
                $oldId = (int) $row['id'];
                $row['id'] = $batchExamRemap->getMapping($oldId);
                $row['session_id'] = $sessionRemap->getMapping((int) $row['session_id']);
                $row['exam_group_id'] = $examGroupRemap->getMapping((int) $row['exam_group_id']);
                $this->insertRow('exam_group_class_batch_exams', $row);
            }
            foreach ($batchExamSubjects as $row) {
                $oldId = (int) $row['id'];
                $row['id'] = $batchExamSubjectRemap->getMapping($oldId);
                $row['exam_group_class_batch_exams_id'] = $batchExamRemap->getMapping((int) $row['exam_group_class_batch_exams_id']);
                $row['subject_id'] = $subjectRemap->getMapping((int) $row['subject_id']);
                $this->insertRow('exam_group_class_batch_exam_subjects', $row);
            }
        });

        return [
            'sessions_migrated' => count($sessions),
            'subjects_migrated' => count($subjects),
            'exam_groups_migrated' => count($examGroups),
            'exam_group_class_batch_exams_migrated' => count($batchExams),
            'exam_group_class_batch_exam_subjects_migrated' => count($batchExamSubjects),
        ];
    }
}
```

(The `require_once` for `NaturalKeyIdResolver.php` and
`StudentSessionIdResolver.php` are included now even though Part A
doesn't use them yet, so Task 3's diff is additive-only inside `run()`
and doesn't need to touch the top of the file.)

- [ ] **Step 4: Run test to verify it passes**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeExamDataTest.php`
Expected: `OK (1 test, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (42 tests, ...)` (41 prior + 1 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeExamData.php tests/tools/multitenant/MergeExamDataTest.php
git commit -m "feat: add MergeExamData (Part A) — migrate exam catalog/schedule tables via IdRemapper"
```

---

### Task 3: `MergeExamData` Part B — reconnect students/results to already-migrated data

**Files:**
- Modify: `tools/multitenant/MergeExamData.php` (extends `run()` from Task 2)
- Modify: `tests/tools/multitenant/MergeExamDataTest.php`

**Interfaces:**
- Extends `MergeExamData::run()`'s return array with:
  `exam_group_class_batch_exam_students_migrated`,
  `exam_group_class_batch_exam_students_source_total`,
  `exam_group_class_batch_exam_students_skipped`,
  `exam_group_exam_results_migrated`,
  `exam_group_exam_results_source_total`,
  `exam_group_exam_results_skipped` (all `int`).
- Consumes: `NaturalKeyIdResolver::resolve(PDO $source, PDO $target, int $tenantId, string $table, string $naturalKeyColumn): array`
  (existing, unchanged — resolves `students` by `admission_no`) and
  `StudentSessionIdResolver::resolve(PDO $source, PDO $target, int $tenantId): array`
  (existing, unchanged — resolves `student_session` by its 4-column
  composite key).

This task adds the two tables that reconnect to data migrated in
EARLIER, SEPARATE runs (Stage 1's `students`, Stage 3's
`student_session`) — the same shape of problem `MergeStudentSessionData`
and `MergeAttendanceData` solved, reusing their exact resolvers
unchanged. Rows referencing a student or student_session that can't be
resolved are skipped (not inserted, not erroring) — same as
`MergeStudentSessionData`'s and `MergeAttendanceData`'s precedent — but
per this stage's Global Constraints, the skip count is surfaced in the
return value and the CLI output from the start.

- [ ] **Step 1: Extend the test file's schema and add the failing test**

In `tests/tools/multitenant/MergeExamDataTest.php`, add these two schema
strings inside `setUp()` (after `$batchExamSubjectSchema`, before the
`foreach` loop):

```php
        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL';
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $studentSessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $batchExamStudentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam_group_class_batch_exam_id INT NOT NULL, student_id INT NOT NULL,'
            . ' student_session_id INT NOT NULL, roll_no INT DEFAULT NULL, teacher_remark TEXT DEFAULT NULL, `rank` INT NOT NULL DEFAULT 0,'
            . ' is_active INT DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $resultSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam_group_class_batch_exam_student_id INT NOT NULL,'
            . ' exam_group_class_batch_exam_subject_id INT DEFAULT NULL, attendence VARCHAR(10) DEFAULT NULL, get_marks FLOAT(10,2) DEFAULT 0.00,'
            . ' note TEXT DEFAULT NULL, is_active INT DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
```

Replace the entire `foreach ([$this->source, $this->target] as $db) {
... }` loop body from Task 2's version (still inside `setUp()`) — keep
the five original `CREATE TABLE` lines and add six more — so the whole
loop reads exactly as:

```php
        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE subjects ({$subjectSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_groups ({$examGroupSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exams ({$batchExamSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exam_subjects ({$batchExamSubjectSchema}{$tenantCol})");
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE classes ({$classSchema}{$tenantCol})");
            $db->exec("CREATE TABLE sections ({$sectionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_session ({$studentSessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exam_students ({$batchExamStudentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_exam_results ({$resultSchema}{$tenantCol})");
        }
```

(This replaces the ENTIRE `foreach` loop body from Task 2's version —
the five original `CREATE TABLE` lines are kept, six more are added.)

Then add this test method to the class (after
`testMergesCatalogAndScheduleTablesWithRemappedIds`):

```php
    public function testReconnectsExamStudentsAndResultsToAlreadyMigratedData(): void
    {
        // Catalog + schedule chain (same shape as the Part A test).
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->source->exec("INSERT INTO exam_groups (id, name) VALUES (8, 'Annual Terminal Examination')");
        $this->source->exec("INSERT INTO exam_group_class_batch_exams (id, exam, session_id, exam_group_id) VALUES (30, '9th Annual', 20, 8)");
        $this->source->exec("INSERT INTO subjects (id, name, code, type) VALUES (5, 'Mathematics', '', 'theory')");
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_subjects (id, exam_group_class_batch_exams_id, subject_id, date_from, time_from, duration)"
            . " VALUES (100, 30, 5, '2026-03-01', '09:00:00', '2 hours')"
        );

        // Student/session chain -- old ids in source (100s/400s), already
        // migrated NEW ids in target (1s/4s), deliberately non-overlapping
        // so a bug using the wrong id is obvious.
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES (401, 101, 201, 301, '2025-01-01 00:00:00')");

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");

        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_students (id, exam_group_class_batch_exam_id, student_id, student_session_id, roll_no)"
            . " VALUES (500, 30, 101, 401, 7)"
        );
        $this->source->exec(
            "INSERT INTO exam_group_exam_results (id, exam_group_class_batch_exam_student_id, exam_group_class_batch_exam_subject_id, attendence, get_marks)"
            . " VALUES (900, 500, 100, 'present', 88.5)"
        );

        $merger = new MergeExamData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['exam_group_class_batch_exam_students_migrated']);
        $this->assertSame(1, $result['exam_group_class_batch_exam_students_source_total']);
        $this->assertSame(0, $result['exam_group_class_batch_exam_students_skipped']);
        $this->assertSame(1, $result['exam_group_exam_results_migrated']);
        $this->assertSame(1, $result['exam_group_exam_results_source_total']);
        $this->assertSame(0, $result['exam_group_exam_results_skipped']);

        $examStudent = $this->target->query('SELECT * FROM exam_group_class_batch_exam_students')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $examStudent['student_id']);
        $this->assertSame(4, (int) $examStudent['student_session_id']);
        $this->assertSame(7, (int) $examStudent['roll_no']);
        $this->assertSame(25, (int) $examStudent['tenant_id']);

        $result_row = $this->target->query('SELECT * FROM exam_group_exam_results')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame((int) $examStudent['id'], (int) $result_row['exam_group_class_batch_exam_student_id']);
        $this->assertSame(88.5, (float) $result_row['get_marks']);
        $this->assertSame(25, (int) $result_row['tenant_id']);
    }

    public function testSkipsExamStudentRowsReferencingAnUnmigratedStudent(): void
    {
        $this->source->exec("INSERT INTO exam_groups (id, name) VALUES (8, 'Annual Terminal Examination')");
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->source->exec("INSERT INTO exam_group_class_batch_exams (id, exam, session_id, exam_group_id) VALUES (30, '9th Annual', 20, 8)");
        // student_id 999 / student_session_id 999 have no corresponding rows
        // anywhere -- simulates a dangling reference that must be skipped,
        // not inserted broken, and must be counted.
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_students (id, exam_group_class_batch_exam_id, student_id, student_session_id, roll_no)"
            . " VALUES (500, 30, 999, 999, 1)"
        );
        $this->source->exec(
            "INSERT INTO exam_group_exam_results (id, exam_group_class_batch_exam_student_id, attendence, get_marks) VALUES (900, 500, 'present', 50)"
        );

        $merger = new MergeExamData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['exam_group_class_batch_exam_students_migrated']);
        $this->assertSame(1, $result['exam_group_class_batch_exam_students_source_total']);
        $this->assertSame(1, $result['exam_group_class_batch_exam_students_skipped']);
        // The result row's own parent link never got migrated, so it must
        // cascade-skip too, not orphan-insert.
        $this->assertSame(0, $result['exam_group_exam_results_migrated']);
        $this->assertSame(1, $result['exam_group_exam_results_source_total']);
        $this->assertSame(1, $result['exam_group_exam_results_skipped']);
    }
}
```

- [ ] **Step 2: Run tests to verify the new ones fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeExamDataTest.php`
Expected: the two new tests FAIL (undefined array key), the Part A test
still passes.

- [ ] **Step 3: Extend `MergeExamData::run()` (Part B)**

In `tools/multitenant/MergeExamData.php`, insert the following code
inside `run()`, immediately after the `foreach ($batchExamSubjects as
$row) { $batchExamSubjectRemap->remapId(...); }` block and BEFORE the
`$this->inTransaction(function () use (...` line:

```php
        $studentResolver = new NaturalKeyIdResolver();
        $studentMap = $studentResolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');

        $sessionResolver = new StudentSessionIdResolver();
        $studentSessionMap = $sessionResolver->resolve($this->source, $this->target, $this->tenantId);

        $batchExamStudentRemap = new IdRemapper($this->nextId('exam_group_class_batch_exam_students'));
        $batchExamStudents = $this->fetchAll(
            'SELECT id, exam_group_class_batch_exam_id, student_id, student_session_id, roll_no, teacher_remark,'
            . ' `rank`, is_active, created_at, updated_at FROM exam_group_class_batch_exam_students'
        );
        $batchExamStudentSourceTotal = count($batchExamStudents);
        $batchExamStudentSkipped = 0;
        $batchExamStudentRowsToInsert = [];
        foreach ($batchExamStudents as $row) {
            $oldId = (int) $row['id'];
            $oldStudentId = (int) $row['student_id'];
            $oldStudentSessionId = (int) $row['student_session_id'];
            if (!isset($studentMap[$oldStudentId]) || !isset($studentSessionMap[$oldStudentSessionId])) {
                $batchExamStudentSkipped++;
                continue;
            }
            $batchExamStudentRemap->remapId($oldId);
            $row['id'] = $batchExamStudentRemap->getMapping($oldId);
            $row['exam_group_class_batch_exam_id'] = $batchExamRemap->getMapping((int) $row['exam_group_class_batch_exam_id']);
            $row['student_id'] = $studentMap[$oldStudentId];
            $row['student_session_id'] = $studentSessionMap[$oldStudentSessionId];
            $batchExamStudentRowsToInsert[$oldId] = $row;
        }

        $resultRemap = new IdRemapper($this->nextId('exam_group_exam_results'));
        $results = $this->fetchAll(
            'SELECT id, exam_group_class_batch_exam_student_id, exam_group_class_batch_exam_subject_id, attendence,'
            . ' get_marks, note, is_active, created_at, updated_at FROM exam_group_exam_results'
        );
        $resultSourceTotal = count($results);
        $resultSkipped = 0;
        $resultRowsToInsert = [];
        foreach ($results as $row) {
            $oldStudentLinkId = (int) $row['exam_group_class_batch_exam_student_id'];
            if (!isset($batchExamStudentRowsToInsert[$oldStudentLinkId])) {
                $resultSkipped++;
                continue;
            }
            $row['exam_group_class_batch_exam_student_id'] = $batchExamStudentRemap->getMapping($oldStudentLinkId);
            if ($row['exam_group_class_batch_exam_subject_id'] !== null) {
                $row['exam_group_class_batch_exam_subject_id'] = $batchExamSubjectRemap->getMapping((int) $row['exam_group_class_batch_exam_subject_id']);
            }
            $resultRowsToInsert[] = $row;
        }
```

Then change the `$this->inTransaction(function () use (...` line's `use`
clause to also capture `$batchExamStudentRowsToInsert,
$resultRowsToInsert`, and add these two `foreach` loops inside the
closure body, after the existing `exam_group_class_batch_exam_subjects`
loop:

```php
            foreach ($batchExamStudentRowsToInsert as $row) {
                $this->insertRow('exam_group_class_batch_exam_students', $row);
            }
            foreach ($resultRowsToInsert as $row) {
                $this->insertRow('exam_group_exam_results', $row);
            }
```

Finally, extend the `return [...]` array (still inside `run()`) to add:

```php
            'exam_group_class_batch_exam_students_migrated' => count($batchExamStudentRowsToInsert),
            'exam_group_class_batch_exam_students_source_total' => $batchExamStudentSourceTotal,
            'exam_group_class_batch_exam_students_skipped' => $batchExamStudentSkipped,
            'exam_group_exam_results_migrated' => count($resultRowsToInsert),
            'exam_group_exam_results_source_total' => $resultSourceTotal,
            'exam_group_exam_results_skipped' => $resultSkipped,
```

Add a CLI entry point at the bottom of the file (this is the tool's
first CLI bootstrap — Part A had none since it wasn't runnable
standalone yet):

```php
if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeExamData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeExamData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['sessions_migrated']} sessions, {$result['subjects_migrated']} subjects,"
        . " {$result['exam_groups_migrated']} exam groups, {$result['exam_group_class_batch_exams_migrated']} batch exams,"
        . " {$result['exam_group_class_batch_exam_subjects_migrated']} exam subjects,"
        . " {$result['exam_group_class_batch_exam_students_migrated']} exam-student enrollments, and"
        . " {$result['exam_group_exam_results_migrated']} exam results for tenant {$tenantId}.\n";

    if ($result['exam_group_class_batch_exam_students_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['exam_group_class_batch_exam_students_skipped']} of"
            . " {$result['exam_group_class_batch_exam_students_source_total']} exam-student enrollments could not be"
            . " resolved and were skipped. Investigate before trusting this migration.\n"
        );
    }
    if ($result['exam_group_exam_results_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['exam_group_exam_results_skipped']} of {$result['exam_group_exam_results_source_total']}"
            . " exam results could not be resolved and were skipped. Investigate before trusting this migration.\n"
        );
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeExamDataTest.php`
Expected: `OK (3 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (44 tests, ...)` (42 prior + 2 new).

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeExamData.php tests/tools/multitenant/MergeExamDataTest.php
git commit -m "feat: extend MergeExamData (Part B) — reconnect exam students/results via existing resolvers"
```

---

### Task 4: `PilotExam` controller — end-to-end proof

**Files:**
- Create: `application/controllers/PilotExam.php`
- Create: `application/views/pilot_exam.php`

**Interfaces:**
- Consumes: `Tenant_Model::tenantGetAll(string $table): array` (existing,
  unchanged), the `pilot_tenant_id` session convention set by
  `PilotStudents::login_as($tenantId)` (existing, unchanged).
- Produces: a `/pilotexam/index` page listing every real exam result for
  the pilot tenant with student name, exam group name, batch exam name,
  subject name, and marks obtained/max — proving the full 7-table chain
  resolves correctly end to end.

- [ ] **Step 1: Create the controller**

Create `application/controllers/PilotExam.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotExam extends CI_Controller
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
        $results = $this->tenant_model->tenantGetAll('exam_group_exam_results');
        $examStudents = $this->tenant_model->tenantGetAll('exam_group_class_batch_exam_students');
        $students = $this->tenant_model->tenantGetAll('students');
        $subjects = $this->tenant_model->tenantGetAll('subjects');
        $examSubjectLinks = $this->tenant_model->tenantGetAll('exam_group_class_batch_exam_subjects');
        $batchExams = $this->tenant_model->tenantGetAll('exam_group_class_batch_exams');
        $examGroups = $this->tenant_model->tenantGetAll('exam_groups');

        $studentIdByExamStudentId = [];
        foreach ($examStudents as $examStudent) {
            $studentIdByExamStudentId[$examStudent['id']] = $examStudent['student_id'];
        }

        $studentNameById = [];
        foreach ($students as $student) {
            $studentNameById[$student['id']] = trim($student['firstname'] . ' ' . ($student['lastname'] ?? ''));
        }

        $subjectIdByExamSubjectLinkId = [];
        foreach ($examSubjectLinks as $link) {
            $subjectIdByExamSubjectLinkId[$link['id']] = $link['subject_id'];
        }

        $subjectNameById = [];
        foreach ($subjects as $subject) {
            $subjectNameById[$subject['id']] = $subject['name'];
        }

        $examNameById = [];
        foreach ($batchExams as $batchExam) {
            $examNameById[$batchExam['id']] = $batchExam['exam'];
        }

        $rows = [];
        foreach ($results as $result) {
            $studentId = $studentIdByExamStudentId[$result['exam_group_class_batch_exam_student_id']] ?? null;
            $subjectLinkId = $result['exam_group_class_batch_exam_subject_id'];
            $subjectId = $subjectLinkId !== null ? ($subjectIdByExamSubjectLinkId[$subjectLinkId] ?? null) : null;

            $rows[] = [
                'student' => $studentId !== null ? ($studentNameById[$studentId] ?? 'Unknown') : 'Unknown',
                'subject' => $subjectId !== null ? ($subjectNameById[$subjectId] ?? 'Unknown') : 'Unknown',
                'marks' => $result['get_marks'],
                'attendence' => $result['attendence'],
            ];
        }

        $this->load->view('pilot_exam', ['rows' => $rows, 'exam_group_count' => count($examGroups)]);
    }
}
```

- [ ] **Step 2: Create the view**

Create `application/views/pilot_exam.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Pilot Exam Results</title></head>
<body>
<h1>Exam Results (<?php echo count($rows); ?> results, <?php echo $exam_group_count; ?> exam groups)</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li><?php echo htmlspecialchars($row['student']); ?> — <?php echo htmlspecialchars($row['subject']); ?>: <?php echo htmlspecialchars((string) $row['marks']); ?> (<?php echo htmlspecialchars((string) $row['attendence']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

- [ ] **Step 3: Manual smoke test (no automated test — this controller has no business logic beyond what Task 2/3's unit tests already cover; it's the end-to-end proof, verified against real data in Task 5)**

Confirm the file lints cleanly: `"C:\xampp81\php\php.exe" -l application/controllers/PilotExam.php` and `"C:\xampp81\php\php.exe" -l application/views/pilot_exam.php`.
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Run the full suite (regression check only — this task adds no new tests)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (44 tests, ...)` (unchanged from Task 3).

- [ ] **Step 5: Commit**

```bash
git add application/controllers/PilotExam.php application/views/pilot_exam.php
git commit -m "feat: add PilotExam controller showing tenant-scoped exam results end to end"
```

---

### Task 5: Migrate real exam data + verify end-to-end

**Files:** none created — this task runs the tool built in Tasks 2-3
against real data and verifies the result. If verification finds a real
data-shape problem (mirroring Stage 4's two `StudentSessionIdResolver`
rounds), stop, diagnose, document a "Post-Task-3 fix" section in this
plan doc, dispatch a fix, get it independently reviewed, and only then
retry this task — do not force through or weaken a safety check.

- [ ] **Step 1: Run the real migration**

Run: `"C:\xampp81\php\php.exe" tools/multitenant/MergeExamData.php al_hafeez_campus 25`

Expected output (values from this plan's pre-flight data survey — if
actual output differs, STOP and investigate before continuing):
```
Migrated 15 sessions, 38 subjects, 8 exam groups, 32 batch exams, 266 exam subjects, 719 exam-student enrollments, and 2785 exam results for tenant 25.
```
No STDERR warning lines expected (both skip counts should be 0 — verified
via the dangling-reference and natural-key coverage checks done before
writing this plan; if a warning DOES appear, stop and investigate, don't
proceed to Step 2 with an unexplained skip).

- [ ] **Step 2: Row-count reconciliation**

Run: `"C:\xampp81\mysql\bin\mysql.exe" -u root al_hafeez_campus -e "SELECT COUNT(*) FROM sessions; SELECT COUNT(*) FROM subjects; SELECT COUNT(*) FROM exam_groups; SELECT COUNT(*) FROM exam_group_class_batch_exams; SELECT COUNT(*) FROM exam_group_class_batch_exam_subjects; SELECT COUNT(*) FROM exam_group_class_batch_exam_students; SELECT COUNT(*) FROM exam_group_exam_results;"`
Expected: 15, 38, 8, 32, 266, 719, 2785.

Run the same seven `SELECT COUNT(*)` queries against `school_saas`, each
scoped `WHERE tenant_id = 25`.
Expected: identical counts to the source.

- [ ] **Step 3: Spot-check a handful of real rows**

Pick 3-5 real students with exam results (e.g. via `SELECT student_id,
COUNT(*) FROM exam_group_class_batch_exam_students GROUP BY student_id
LIMIT 5` against source) and compare their admission_no, subject names,
and `get_marks` values between `al_hafeez_campus` and `school_saas`
(joining through the migrated tables on both sides). All fields must
match exactly.

- [ ] **Step 4: End-to-end verification via `PilotExam`**

```
curl http://localhost/web-app/pilotstudents/login_as/25
curl http://localhost/web-app/pilotexam/index
```
(Same base URL/session-cookie handling used for `PilotAttendance` in
Stage 4's Task 5 — reuse the same curl cookie-jar flags if that's how it
was invoked there.)

Expected: HTTP 200, exactly 2785 `<li>` entries, 0 occurrences of the
string `Unknown`, and the header line showing `8 exam groups`.

- [ ] **Step 5: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`
to mark Stage 5 complete, following the exact style of the Stage 1-4
entries (real row counts, what was proven, any bugs found and fixed).

- [ ] **Step 6: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 2 Stage 5 (exams) complete"
```

---

### Final whole-stage review (after Task 5)

Once Task 5 succeeds, dispatch an Opus adversarial review of the whole
stage's final state (all commits from Task 1 through Task 5 together),
following the exact pattern used at the end of Stages 1-4: tenant
isolation reasoning, natural-key/resolver-reuse correctness, whether the
skip-count reporting actually works end to end against real data, and
whether the roadmap/plan docs accurately reflect what was built. Fix any
Critical/Important findings (with independent re-review) before
considering Stage 5 done.
