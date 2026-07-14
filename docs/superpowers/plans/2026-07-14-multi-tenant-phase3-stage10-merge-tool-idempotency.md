# Phase 3 Stage 10 — Merge-Tool Idempotency Guard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close a real, already-triggered-once data-integrity bug: none of the 8 `Merge*Data` tools in `tools/multitenant/` check whether a tenant already has rows before inserting more. A manual re-run of `MergeAttendanceData` on 2026-07-10 silently duplicated tenant 25's attendance rows (1124→2248, 6→12) with no error. Add a re-run guard to the shared base class and wire it into all 8 tools so any future accidental re-run refuses immediately instead of duplicating data.

**Architecture:** One new method on `tools/multitenant/AbstractTenantMerger.php` — `guardAgainstExistingData(string ...$tables): void` — runs a `SELECT COUNT(*) WHERE tenant_id = ?` against the target database for each named table and throws a `RuntimeException` with an actionable message if any of them already has rows for that tenant. Each of the 8 concrete `Merge*Data::run()` methods calls it as their first statement, listing every table that tool populates. This mirrors the project's established "fix the shared base class once, verify it propagates to every concrete subclass" pattern (used successfully for the `Pilot_Controller` environment gate in Phase 3 Stage 8).

**Tech Stack:** PHP 8.1, PDO, PHPUnit 10.5 (unchanged).

## Global Constraints

- **The guard must be table-scoped and tenant-scoped**, not global — `SELECT COUNT(*) FROM {table} WHERE tenant_id = :tenant_id`, using `$this->target` and `$this->tenantId` (both already available as protected properties on `AbstractTenantMerger`). No raw string interpolation of `$this->tenantId` into SQL — always a bound parameter. The table name itself is backtick-quoted exactly like `insertRow()`'s existing pattern (`AbstractTenantMerger.php:36`), never bound as a parameter (table names can't be bound placeholders in PDO) and never taken from external/untrusted input — every call site passes a literal string constant.
- **Every one of the 8 concrete tools calls the guard exactly once, listing every table it populates**, as the literal first statement inside `run()` — before any `fetchAll`, `nextId`, or `IdRemapper` construction. This must run BEFORE any work happens, not just before the transaction, so a doomed re-run does zero work (no wasted `fetchAll` against the source, no partial remapping) before refusing.
- **This guard only ever fires on a genuine re-run against a tenant that already has data** — it must never produce a false positive against a fresh tenant's first run (the existing test suite's fresh-tenant tests must continue to pass unmodified for all 8 tools).
- **No existing method on `AbstractTenantMerger` or any of the 8 concrete tools is modified or reformatted** — this is a pure addition (one new method + one new call site per tool).
- **This does not touch `tools/multitenant/TenantScope.php`** — that class has its own, structurally similar `count()` method but is entirely unused by the merge-tool hierarchy today (confirmed via grep — it only self-references) and is out of scope; do not wire it in or refactor `AbstractTenantMerger` to use it. This is a deliberate, minimal, self-contained addition, not a consolidation.
- **The 8 tools, in the order they'll be touched, and every table each one must guard**:
  1. `MergeSchoolData` — `students`, `users`
  2. `MergeStaffData` — `staff`, `roles`, `staff_roles`
  3. `MergeClassData` — `classes`, `sections`, `class_sections`
  4. `MergeStudentSessionData` — `student_session`
  5. `MergeAttendanceData` — `attendence_type`, `student_attendences`
  6. `MergeExamData` — `sessions`, `subjects`, `exam_groups`, `exam_group_class_batch_exams`, `exam_group_class_batch_exam_subjects`, `exam_group_class_batch_exam_students`, `exam_group_exam_results`
  7. `MergeFeeData` — `feetype`, `fee_groups`, `fees_discounts`, `fee_session_groups`, `fee_groups_feetype`, `fees_reminder`, `student_fees_master`, `student_fees_deposite`, `student_fees_discounts`, `student_applied_discounts`
  8. `MergeHrData` — `department`, `staff_designation`, `leave_types`, `staff_leave_details`
- **Real production state to be aware of**: tenant 25 (`al_hafeez_campus`) already has real, migrated data in every one of the tables above. Once this guard is wired in, none of these 8 tools can ever be run again against tenant 25 (that is the entire point — verified as a real, live proof in Task 3, not just a unit test). This is intentional and correct: migration for the pilot tenant is complete, and these tools have no legitimate reason to run against it again.
- **Known test credential** (for any live HTTP checks, unchanged): tenant_id=25, email `rabiachauhan923@gmail.com`, password `TestVerify123!`. This stage's own work is CLI/database-only and doesn't need HTTP verification, but Task 3's regression-suite run should still confirm no unrelated live-app regression.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: Add the guard to `AbstractTenantMerger`, wire it into `MergeSchoolData` (proving the pattern once)

**Files:**
- Modify: `tools/multitenant/AbstractTenantMerger.php` (append new method, do not touch existing methods)
- Modify: `tools/multitenant/MergeSchoolData.php` (add one line as the first statement of `run()`, do not touch anything else)
- Modify: `tests/tools/multitenant/MergeSchoolDataTest.php` (append two new tests)

**Interfaces:**
- Produces: `AbstractTenantMerger::guardAgainstExistingData(string ...$tables): void` (protected) — consumed by all 8 concrete tools, this task and Task 2.
- Consumes: nothing from other tasks — this is the first task.

- [ ] **Step 1: Write the failing tests**

Append to `tests/tools/multitenant/MergeSchoolDataTest.php`, inside the `MergeSchoolDataTest` class, after the existing `testRollsBackTransactionOnInsertFailure` method:

```php
    public function testRefusesToRunAgainIfTenantAlreadyHasStudentRows(): void
    {
        $this->target->exec("INSERT INTO students (id, parent_id, firstname, tenant_id) VALUES (1, 0, 'Existing', 25)");

        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, firstname) VALUES (1, 1, 'Bob')");

        $merger = new MergeSchoolData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('students', $e->getMessage());
            $this->assertStringContainsString('25', $e->getMessage());
        }

        $this->assertTrue($threw, 'Expected run() to refuse when tenant 25 already has student rows');

        $studentCount = (int) $this->target->query("SELECT COUNT(*) AS c FROM students WHERE tenant_id = 25")->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $studentCount, 'Refusing to run must not insert any new rows -- only the pre-existing row should remain');
    }

    public function testGuardDoesNotFalselyBlockADifferentTenantWithNoExistingRows(): void
    {
        $this->target->exec("INSERT INTO students (id, parent_id, firstname, tenant_id) VALUES (1, 0, 'OtherTenant', 99)");

        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, firstname) VALUES (1, 1, 'Bob')");

        $merger = new MergeSchoolData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['students_migrated'], 'Tenant 25 has no existing rows -- the guard must not block it just because tenant 99 has data');
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeSchoolDataTest.php`
Expected: the two new tests FAIL — `testRefusesToRunAgainIfTenantAlreadyHasStudentRows` fails because `run()` currently duplicates instead of throwing (or the row count assertion fails); `testGuardDoesNotFalselyBlockADifferentTenantWithNoExistingRows` should currently PASS already (it's a pre-existing-behavior regression anchor, not a new-behavior test) — if it fails for a different reason, investigate before proceeding.

- [ ] **Step 3: Add the guard method to `AbstractTenantMerger`**

In `tools/multitenant/AbstractTenantMerger.php`, add this method immediately after `inTransaction()` (the last existing method), before the closing `}` of the class:

```php
    protected function guardAgainstExistingData(string ...$tables): void
    {
        foreach ($tables as $table) {
            $stmt = $this->target->prepare("SELECT COUNT(*) AS c FROM `{$table}` WHERE tenant_id = :tenant_id");
            $stmt->execute([':tenant_id' => $this->tenantId]);
            $count = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];

            if ($count > 0) {
                throw new RuntimeException(
                    "Refusing to run: tenant {$this->tenantId} already has {$count} row(s) in `{$table}`. "
                    . 'This tool has no re-run/resume support -- re-running would duplicate data '
                    . '(this is exactly the bug that duplicated tenant 25\'s attendance rows on 2026-07-10). '
                    . "If this is intentional (e.g. recovering from a partial run), delete the tenant's "
                    . "existing rows in `{$table}` first, or extend this tool with real upsert/resume logic."
                );
            }
        }
    }
```

- [ ] **Step 4: Wire the guard into `MergeSchoolData::run()`**

In `tools/multitenant/MergeSchoolData.php`, add this as the literal first line inside `run()`, before `$studentRemap = new IdRemapper(...)`:

```php
        $this->guardAgainstExistingData('students', 'users');
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeSchoolDataTest.php`
Expected: `OK (6 tests, ...)` (4 pre-existing + 2 new).

- [ ] **Step 6: Run the full suite to confirm no regressions**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: prior total (76) + 2 new = 78, all passing.

- [ ] **Step 7: Commit**

```bash
git add tools/multitenant/AbstractTenantMerger.php tools/multitenant/MergeSchoolData.php tests/tools/multitenant/MergeSchoolDataTest.php
git commit -m "feat: add re-run guard to AbstractTenantMerger, wire into MergeSchoolData"
```

---

### Task 2: Wire the guard into the remaining 7 merge tools

**Files:**
- Modify: `tools/multitenant/MergeStaffData.php`, `MergeClassData.php`, `MergeStudentSessionData.php`, `MergeAttendanceData.php`, `MergeExamData.php`, `MergeFeeData.php`, `MergeHrData.php` (one new line each, as the first statement of `run()` — do not touch anything else in any of these files)
- Modify: `tests/tools/multitenant/MergeStaffDataTest.php`, `MergeClassDataTest.php`, `MergeStudentSessionDataTest.php`, `MergeAttendanceDataTest.php`, `MergeExamDataTest.php`, `MergeFeeDataTest.php`, `MergeHrDataTest.php` (append one new test to each, following Task 1's exact pattern)

**Interfaces:**
- Consumes: `AbstractTenantMerger::guardAgainstExistingData(string ...$tables): void` from Task 1 (already committed, unchanged).
- Produces: nothing consumed by a later task.

For each of the 7 tools, using the exact table list from this plan's Global Constraints section:

- [ ] **Step 1: Add the guard call as the first line of each tool's `run()` method**

```php
// MergeStaffData.php — first line of run():
$this->guardAgainstExistingData('staff', 'roles', 'staff_roles');

// MergeClassData.php — first line of run():
$this->guardAgainstExistingData('classes', 'sections', 'class_sections');

// MergeStudentSessionData.php — first line of run():
$this->guardAgainstExistingData('student_session');

// MergeAttendanceData.php — first line of run():
$this->guardAgainstExistingData('attendence_type', 'student_attendences');

// MergeExamData.php — first line of run():
$this->guardAgainstExistingData('sessions', 'subjects', 'exam_groups', 'exam_group_class_batch_exams', 'exam_group_class_batch_exam_subjects', 'exam_group_class_batch_exam_students', 'exam_group_exam_results');

// MergeFeeData.php — first line of run():
$this->guardAgainstExistingData('feetype', 'fee_groups', 'fees_discounts', 'fee_session_groups', 'fee_groups_feetype', 'fees_reminder', 'student_fees_master', 'student_fees_deposite', 'student_fees_discounts', 'student_applied_discounts');

// MergeHrData.php — first line of run():
$this->guardAgainstExistingData('department', 'staff_designation', 'leave_types', 'staff_leave_details');
```

Read each file's current `run()` method first to confirm the exact first line to insert before (each one's current first statement is a `new IdRemapper(...)`/`new NaturalKeyIdResolver()`/`$this->fetchAll(...)` call — insert the guard call immediately before whatever that first statement currently is, without changing that statement itself).

- [ ] **Step 2: Add one new regression test to each of the 7 tools' existing test files**

For each tool, add a test following the exact pattern from Task 1's `testRefusesToRunAgainIfTenantAlreadyHasStudentRows`, adapted to that tool's own already-established fixture setup (each test file's `setUp()` already creates the throwaway `merge_test_source`/`merge_test_target` databases with that tool's exact schema — read the existing file first, do not invent a new schema). The shape is identical for every tool:

1. Insert one pre-existing row directly into the target database for tenant 25, into the FIRST table that tool's guard checks (matching the table order in this plan's Global Constraints list — e.g. for `MergeStaffData` insert into `staff`; for `MergeExamData` insert into `sessions`; for `MergeFeeData` insert into `feetype`; for `MergeHrData` insert into `department`).
2. Set up minimal valid source data for that tool's `run()` to attempt to process (reuse whatever minimal fixture the file's existing "happy path" test already uses — do not invent new source data shapes).
3. Construct the merger for tenant 25 and call `run()`.
4. Assert it throws `RuntimeException`.
5. Assert the guarded table's row count for tenant 25 is unchanged from the single pre-existing row (i.e., still 1, not duplicated or added to).

Name each new test method `testRefusesToRunAgainIfTenantAlreadyHas<TableName>Rows` (e.g. `testRefusesToRunAgainIfTenantAlreadyHasStaffRows`, `testRefusesToRunAgainIfTenantAlreadyHasSessionRows`, `testRefusesToRunAgainIfTenantAlreadyHasFeetypeRows`, `testRefusesToRunAgainIfTenantAlreadyHasDepartmentRows`), matching Task 1's naming convention.

- [ ] **Step 3: Lint all 7 modified tool files**

```bash
for f in MergeStaffData MergeClassData MergeStudentSessionData MergeAttendanceData MergeExamData MergeFeeData MergeHrData; do "C:\xampp81\php\php.exe" -l "tools/multitenant/$f.php"; done
```

Expected: `No syntax errors detected` for all 7.

- [ ] **Step 4: Run each tool's own test file to confirm its new test passes and nothing existing broke**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/MergeStaffDataTest.php tests/tools/multitenant/MergeClassDataTest.php tests/tools/multitenant/MergeStudentSessionDataTest.php tests/tools/multitenant/MergeAttendanceDataTest.php tests/tools/multitenant/MergeExamDataTest.php tests/tools/multitenant/MergeFeeDataTest.php tests/tools/multitenant/MergeHrDataTest.php
```

Expected: all pass, 7 new tests among them, zero failures.

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 78 (prior total after Task 1) + 7 new = 85, all passing.

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/MergeStaffData.php tools/multitenant/MergeClassData.php tools/multitenant/MergeStudentSessionData.php tools/multitenant/MergeAttendanceData.php tools/multitenant/MergeExamData.php tools/multitenant/MergeFeeData.php tools/multitenant/MergeHrData.php tests/tools/multitenant/MergeStaffDataTest.php tests/tools/multitenant/MergeClassDataTest.php tests/tools/multitenant/MergeStudentSessionDataTest.php tests/tools/multitenant/MergeAttendanceDataTest.php tests/tools/multitenant/MergeExamDataTest.php tests/tools/multitenant/MergeFeeDataTest.php tests/tools/multitenant/MergeHrDataTest.php
git commit -m "feat: wire the re-run guard into the remaining 7 merge tools"
```

---

### Task 3: Live proof against real tenant 25 data + roadmap update

**Files:**
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (update the "Carried-forward technical debt" section, closing this item)

**Interfaces:**
- Consumes: all 8 tools' guards from Tasks 1-2, already committed.
- Produces: nothing — this is the closing task.

This is the task that matters most: proving the guard actually blocks a real re-run against the real, already-migrated tenant 25 data — not just synthetic PHPUnit fixtures.

- [ ] **Step 1: Confirm tenant 25's real current row counts (baseline, before the re-run attempt)**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "
SELECT 'students' t, COUNT(*) c FROM students WHERE tenant_id=25
UNION SELECT 'attendence_type', COUNT(*) FROM attendence_type WHERE tenant_id=25
UNION SELECT 'student_attendences', COUNT(*) FROM student_attendences WHERE tenant_id=25
UNION SELECT 'classes', COUNT(*) FROM classes WHERE tenant_id=25
UNION SELECT 'sessions', COUNT(*) FROM sessions WHERE tenant_id=25
UNION SELECT 'department', COUNT(*) FROM department WHERE tenant_id=25;
"
```

Record these exact numbers — they must be identical after Step 2 below.

- [ ] **Step 2: Attempt a real re-run of `MergeAttendanceData` — the exact tool that caused the original 2026-07-10 incident — against the real pilot tenant**

```bash
"C:\xampp81\php\php.exe" tools/multitenant/MergeAttendanceData.php al_hafeez_campus 25
echo "Exit code: $?"
```

Expected: the command FAILS with an uncaught `RuntimeException` printed to STDERR, containing "Refusing to run" and "attendence_type" (or whichever table it checks first), non-zero exit code. It must NOT print a success message like "Migrated N attendance types and M student attendances."

- [ ] **Step 3: Re-check tenant 25's row counts — must be byte-identical to Step 1**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "
SELECT 'students' t, COUNT(*) c FROM students WHERE tenant_id=25
UNION SELECT 'attendence_type', COUNT(*) FROM attendence_type WHERE tenant_id=25
UNION SELECT 'student_attendences', COUNT(*) FROM student_attendences WHERE tenant_id=25
UNION SELECT 'classes', COUNT(*) FROM classes WHERE tenant_id=25
UNION SELECT 'sessions', COUNT(*) FROM sessions WHERE tenant_id=25
UNION SELECT 'department', COUNT(*) FROM department WHERE tenant_id=25;
"
```

Expected: identical to Step 1's numbers. If anything changed, STOP immediately and report — this would mean the guard failed against real data despite passing synthetic tests, which is far more serious than a failing unit test.

- [ ] **Step 4: Repeat Steps 2-3 for one more tool of your choice from the remaining 7** (e.g. `MergeHrData.php al_hafeez_campus 25` or `MergeFeeData.php al_hafeez_campus 25`) to confirm this isn't specific to `MergeAttendanceData` alone — same before/attempt-fails/after-identical pattern.

- [ ] **Step 5: Run the full suite one more time**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 85/85 passing (no regressions from the live CLI attempts above — they only ever read+guard-check, since the guard fires before any write).

- [ ] **Step 6: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`'s "Carried-forward technical debt" section (near the end of the file). Update the "Merge tools have no re-run/idempotency guard" bullet to mark it resolved: name the new `guardAgainstExistingData()` method, confirm all 8 tools (not just the originally-affected 5) now call it, and record the real-data proof from Steps 2-4 (which tools were actually re-run against tenant 25 and confirmed to fail closed with zero data change). Also correct the bullet's outdated tool list if it still only names 5 tools instead of 8, and note that the original debt-survey's file/table counts for the *other* remaining item (non-composite tenant FKs) were found to undercount the true scope during this stage's research (63 FKs across 9 files, not 3 files/11 tables) — leave that item itself unresolved/unchanged in substance, just correct its documented scope for whoever picks it up next.

- [ ] **Step 7: Commit and push**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 10 (merge-tool idempotency guard) complete"
git push github master
```

---

## Final whole-stage review (after Task 3)

Dispatch an adversarial reviewer (same rigor as every prior stage's final review) to independently:
- **READ-ONLY ONLY, as with every stage since the incident**: never `git checkout -- <path>`, `git restore`, `git reset`, `git clean`, `git stash apply/pop`. Use only `git show`/`git diff`/`git log`/`git status`. Do not dispatch any subagent of its own.
- Re-read the full diff across all 3 tasks.
- Confirm all 8 tools genuinely call the guard as the first statement of `run()` — not buried after other work, not skipped for any tool.
- Confirm the guard's SQL is injection-safe (bound tenant_id parameter, table names always literal constants from the plan's list, never dynamic/external input).
- Independently re-attempt a live re-run of a DIFFERENT tool than the two already exercised in Task 3 (e.g. `MergeClassData.php al_hafeez_campus 25` or `MergeExamData.php al_hafeez_campus 25`) against real tenant 25 data, confirm it fails closed with zero data change, using its own before/after row-count check.
- Confirm the fresh-tenant (non-25) path still works for at least 2 tools by reading their "no false positive" test coverage (Task 1's `testGuardDoesNotFalselyBlockADifferentTenantWithNoExistingRows` and equivalents) and independently reasoning about whether the guard could ever false-positive in production (it shouldn't, since school_saas only ever gains rows via these exact tools for these exact tenants).
- Run the full suite and confirm 85/85.
- Confirm roadmap accuracy, including the corrected FK-debt scope note.
- Confirm git hygiene — the single long-standing pre-existing omnipay-vendor-file deletion should be the only uncommitted item, if anything; do not touch it.
- Report Ready to merge (Yes/With fixes/No) plus Critical/Important/Minor findings, same format as every prior stage. Given this closes a debt item that already caused one real production data-duplication incident, treat any residual doubt about whether the guard is airtight as at least Important, not Minor.
