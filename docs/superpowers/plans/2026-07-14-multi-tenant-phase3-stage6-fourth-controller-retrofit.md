# Phase 3 Stage 6 — Fourth Real Controller Retrofit (Stuattendence) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the proven real-controller-retrofit mechanism (allowlist gate + one gated model/controller method pair) to a fourth real admin controller — `Stuattendence.php` — exposing the pilot tenant's 1,124 already-migrated real `student_attendences` rows to a real, tenant-scoped, gated session.

**Architecture:** Identical to Phase 3 Stages 3 and 4 (Feesforward, Examgroup): add one entry to `Admin_Controller`'s allowlist array (`application/core/MY_Controller.php`), add one new `getTenantScoped*` method to the already-autoloaded real model (`Stuattendence_model`), add one new gated controller method with its own defense-in-depth session check, add one new minimal view. No new database migration, no schema change — `student_attendences`/`attendence_type` were already migrated to `school_saas` in Phase 2 Stage 4.

**Tech Stack:** PHP 8.1, CodeIgniter 3.1.13 query builder, PHPUnit 10.5 (unchanged).

## Global Constraints

- **The allowlist gate gains exactly one new entry**: `'stuattendence' => 'tenantattendancelist'`. The three existing entries (`staff`/`tenantstafflist`, `feesforward`/`tenantfeeslist`, `examgroup`/`tenantexamresultslist`) must be completely unaffected — verified live, not just by code reading.
- **The new model method is additive only** — `Stuattendence_model.php`'s existing methods (used by the real, un-gated `Stuattendence` controller serving real schools today) must not be touched, reordered, or reformatted.
- **The new controller method has its own defense-in-depth guard** — re-checks `$this->session->userdata('admin_tenant_id')` and `show_404()`s if absent, exactly like the three prior gated methods, even though the constructor-level gate already covers this.
- **Tenant scoping via query builder only** — `$this->db->where('tenant_id', $tenantId)->get('student_attendences')->result_array()`, no raw SQL, no string concatenation of the tenant id into a query.
- **Known test credential** (unchanged): tenant_id=25, email `rabiachauhan923@gmail.com`, password `TestVerify123!`.
- **Real row counts to verify against**: `student_attendences` has 1,124 rows for tenant_id=25 in `school_saas` (confirmed live via `SELECT COUNT(*) FROM student_attendences WHERE tenant_id=25`). `attendence_type` has 6 rows for tenant_id=25.
- **`student_attendences` schema** (confirmed live via `DESCRIBE`): `id`, `tenant_id`, `student_session_id`, `date`, `attendence_type_id`, `remark`, `is_active`, `in_time`, `out_time`, `created_at`, `updated_at`.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: Add `stuattendence` to the allowlist gate

**Files:**
- Modify: `application/core/MY_Controller.php:64-68`

**Interfaces:**
- Consumes: nothing new.
- Produces: the route `stuattendence/tenantattendancelist` becomes reachable for a tenant-scoped session once Task 2 implements the method — until then it 404s exactly like any other not-yet-implemented allowlist entry (this is the same, already-proven benign intermediate state documented in Stage 4 Task 1's review).

- [ ] **Step 1: Confirm current state**

```bash
sed -n '60,75p' application/core/MY_Controller.php
```

Confirm it matches:

```php
        if ($this->session->userdata('admin_tenant_id')) {
            $activeController = strtolower($this->router->fetch_class());
            $activeMethod     = strtolower($this->router->fetch_method());
            $allowedTenantRoutes = [
                'staff' => 'tenantstafflist',
                'feesforward' => 'tenantfeeslist',
                'examgroup' => 'tenantexamresultslist',
            ];
            if (!isset($allowedTenantRoutes[$activeController]) || $allowedTenantRoutes[$activeController] !== $activeMethod) {
                show_404();

                return;
            }
        }
```

If it differs, STOP and report the actual content.

- [ ] **Step 2: Add the fourth entry**

```php
            $allowedTenantRoutes = [
                'staff' => 'tenantstafflist',
                'feesforward' => 'tenantfeeslist',
                'examgroup' => 'tenantexamresultslist',
                'stuattendence' => 'tenantattendancelist',
            ];
```

- [ ] **Step 3: Lint**

```bash
"C:\xampp81\php\php.exe" -l application/core/MY_Controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Live-verify the three existing routes are unaffected and the new one still 404s (not yet implemented)**

Apache/MySQL should already be running. Use a single cookie jar in one script:

```bash
CJ=/tmp/p3s6_task1_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/feesforward/tenantFeesList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/examgroup/tenantExamResultsList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/stuattendence/tenantAttendanceList -w "\n%{http_code}\n"
```

Expected: staff 200 "Staff (18 real, tenant-scoped rows)"; fees 200 "Student Fee Deposits (699 real, tenant-scoped rows)"; exam 200 "Exam Results (2785 real, tenant-scoped rows)"; attendance **404** (route allowlisted but the method doesn't exist yet — Task 2 builds it).

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 66/66 passing, no regressions (this task adds no new tests of its own — a dedicated regression test for the 4-entry array is added in Task 3, matching the pattern from Stages 3-5).

- [ ] **Step 6: Commit**

```bash
git add application/core/MY_Controller.php
git commit -m "feat: add stuattendence to Admin_Controller's tenant allowlist gate"
```

---

### Task 2: Real Stuattendence tenant-scoped method

**Files:**
- Modify: `application/models/Stuattendence_model.php` (append new method, do not touch existing methods)
- Modify: `application/controllers/admin/Stuattendence.php` (append new method, do not touch existing methods)
- Create: `application/views/admin/stuattendence/tenant_attendance_list.php`

**Interfaces:**
- Consumes: Task 1's allowlist entry (`'stuattendence' => 'tenantattendancelist'`).
- Produces: `Stuattendence_model::getTenantScopedAttendanceList(int $tenantId): array` — used only by this task's controller method.

**Important — check for pre-existing unrelated uncommitted work first:** this project's working tree has substantial, unrelated, pre-existing uncommitted work scattered across many files. Before editing, run:

```bash
git status --short application/models/Stuattendence_model.php application/controllers/admin/Stuattendence.php
```

If either file shows unrelated uncommitted changes, do NOT `git add` the whole file. Use the same surgical git-plumbing technique already proven in Phase 3 Stages 3 and 4 (`git hash-object -w --no-filters` + `git update-index --cacheinfo` against the current HEAD blob, adding only this task's new method) so the commit contains only this task's addition. If a file is already clean (as most recently happened in Phase 3 Stage 5 Task 2 — always re-check live, don't assume either way from a prior stage), a plain `git add`/`commit` is correct and simpler. Independently re-verify with `git show --stat`/`git diff` after committing either way.

- [ ] **Step 1: Add the model method**

Append to the end of the `Stuattendence_model` class in `application/models/Stuattendence_model.php` (find the class's closing `}` and insert immediately before it — do not touch any existing method):

```php
    public function getTenantScopedAttendanceList($tenantId)
    {
        return $this->db->where('tenant_id', $tenantId)->get('student_attendences')->result_array();
    }
```

- [ ] **Step 2: Add the controller method**

Append to the end of the `Stuattendence` class in `application/controllers/admin/Stuattendence.php` (find the class's closing `}` and insert immediately before it — do not touch any existing method):

```php
    public function tenantAttendanceList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $attendanceList = $this->stuattendence_model->getTenantScopedAttendanceList((int) $tenantId);
        $this->load->view('admin/stuattendence/tenant_attendance_list', ['attendanceList' => $attendanceList]);
    }
```

- [ ] **Step 3: Add the view**

Create `application/views/admin/stuattendence/tenant_attendance_list.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Tenant Attendance List</title></head>
<body>
<h1>Student Attendance (<?php echo count($attendanceList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($attendanceList as $row): ?>
    <li>Attendance #<?php echo (int) $row['id']; ?> — student session <?php echo (int) $row['student_session_id']; ?>, date <?php echo htmlspecialchars((string) $row['date']); ?>, type <?php echo (int) $row['attendence_type_id']; ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

- [ ] **Step 4: Lint both PHP files**

```bash
"C:\xampp81\php\php.exe" -l application/models/Stuattendence_model.php
"C:\xampp81\php\php.exe" -l application/controllers/admin/Stuattendence.php
```

Expected: `No syntax errors detected` for both.

- [ ] **Step 5: Live-verify the new route now works, end to end**

```bash
CJ=/tmp/p3s6_task2_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/stuattendence/tenantAttendanceList -w "\n%{http_code}\n"
```

Expected: `200`, body contains `Student Attendance (1124 real, tenant-scoped rows)`.

- [ ] **Step 6: Commit**

Per the pre-check in Step 0 above, either:

```bash
git add application/models/Stuattendence_model.php application/controllers/admin/Stuattendence.php application/views/admin/stuattendence/tenant_attendance_list.php
git commit -m "feat: add tenant-scoped attendance list method to the real Stuattendence controller/model"
```

or the git-plumbing equivalent if either PHP file had unrelated pre-existing uncommitted content. The new view file has no pre-existing content risk (it's new) — always `git add` it normally.

---

### Task 3: End-to-end verification + safety regression proof

**Files:**
- Modify: `tests/controllers/AdminControllerTenantGateTest.php` (append one new test)
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (append Stage 6 entry)

**Interfaces:**
- Consumes: Task 1's allowlist entry, Task 2's gated method — both already live.
- Produces: nothing — this is the closing task.

- [ ] **Step 1: Confirm the known test credential is still intact**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT id, email FROM staff WHERE email='rabiachauhan923@gmail.com';"
```

Expected: one row, `id=1`.

- [ ] **Step 2: Real login → all four real controllers, end to end**

```bash
CJ=/tmp/p3s6_task3_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/feesforward/tenantFeesList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/examgroup/tenantExamResultsList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/stuattendence/tenantAttendanceList -w "\n%{http_code}\n"
```

Expected: staff 200 "Staff (18 real, tenant-scoped rows)"; fees 200 "Student Fee Deposits (699 real, tenant-scoped rows)"; exam 200 "Exam Results (2785 real, tenant-scoped rows)"; attendance 200 "Student Attendance (1124 real, tenant-scoped rows)".

- [ ] **Step 3: Confirm the allowlist gate still blocks everything else**

Using the same `$CJ`, confirm 404 for: `admin/admin/dashboard`, `admin/staff`, `admin/feesforward`, `admin/examgroup`, `admin/examresult`, `admin/stuattendence` (the real un-gated index), and two real sibling methods on the newly-allowlisted controller itself — `Stuattendence.php`'s actual public methods are `index`, `attendencereport`, `monthAttendance`, `saveclasstime`, `savestudentsetting` (confirmed via `grep "public function" application/controllers/admin/Stuattendence.php`); use `admin/stuattendence/attendencereport` and `admin/stuattendence/index` for this check.

- [ ] **Step 4: Add the credentialed regression test**

Append to `tests/controllers/AdminControllerTenantGateTest.php`, immediately after the existing `testTenantScopedSessionReachesAllThreeAllowlistedRoutesAndNothingElse` method (reuse the existing `curlPostPilotLogin()`/`curlGet()` helpers, do not duplicate them):

```php
    public function testTenantScopedSessionReachesAllFourAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);

        [$examResultsStatus, $examResultsBody] = $this->curlGet('admin/examgroup/tenantExamResultsList');
        $this->assertSame(200, $examResultsStatus);
        $this->assertStringContainsString('Tenant Exam Results List', $examResultsBody);

        [$attendanceStatus, $attendanceBody] = $this->curlGet('admin/stuattendence/tenantAttendanceList');
        $this->assertSame(200, $attendanceStatus);
        $this->assertStringContainsString('Tenant Attendance List', $attendanceBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$stuattendenceIndexStatus, ] = $this->curlGet('admin/stuattendence');
        $this->assertSame(404, $stuattendenceIndexStatus);
    }
```

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 66 (prior total) + 1 new = 67, all passing.

- [ ] **Step 6: Commit the test**

```bash
git add tests/controllers/AdminControllerTenantGateTest.php
git commit -m "test: add credentialed end-to-end regression test for the fourth controller retrofit"
```

- [ ] **Step 7: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` — add a Phase 3 Stage 6 entry following the Stage 3/4/5 entries' style: what was built, real commit hashes, real row counts confirmed live, confirmation the three prior routes remain unaffected, and explicit note that this is the third consecutive controller retrofit needing zero new infrastructure (allowlist gate, `Db_manager` connection gate, and settings fixture tables all unchanged since Stage 6/Phase 2).

- [ ] **Step 8: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 6 (fourth real controller retrofit — Stuattendence) complete"
```

---

## Final whole-stage review (after Task 3)

Dispatch an adversarial reviewer (same rigor as every prior stage's final review) to independently:
- Re-read the full diff across all 3 tasks.
- Independently probe sibling methods on `Stuattendence` beyond what Task 3 already tested, to confirm the gate is method-level not controller-level (the sharpest bypass vector, per Stage 4's final review).
- Independently re-verify all four allowlisted routes live with real row counts.
- Confirm the three pre-existing routes (staff/fees/examresults) are provably unaffected.
- Confirm `Stuattendence_model.php`/`Stuattendence.php`'s existing methods (serving real, un-gated schools today) are untouched — diff should show only additions.
- Run the full suite and confirm 67/67.
- Confirm roadmap accuracy.
- Confirm git hygiene — pre-existing unrelated uncommitted work still present and untouched.
- Report Ready to merge (Yes/With fixes/No) plus Critical/Important/Minor findings, same format as every prior stage.
