# Phase 3 Stage 7 — Fifth Real Controller Retrofit (Leaverequest) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the proven real-controller-retrofit mechanism (allowlist gate + one gated model/controller method pair) to a fifth real admin controller — `Leaverequest.php` — exposing the pilot tenant's 32 already-migrated real `staff_leave_details` rows to a real, tenant-scoped, gated session.

**Architecture:** Identical to Phase 3 Stages 3, 4, and 6 (Feesforward, Examgroup, Stuattendence): add one entry to `Admin_Controller`'s allowlist array (`application/core/MY_Controller.php`), add one new `getTenantScoped*` method to the real model (`Leaverequest_model`), add one new gated controller method with its own defense-in-depth session check, add one new minimal view. No new database migration, no schema change — `staff_leave_details`/`leave_types` were already migrated to `school_saas` in Phase 3 Stage 2.

**Tech Stack:** PHP 8.1, CodeIgniter 3.1.13 query builder, PHPUnit 10.5 (unchanged).

## Global Constraints

- **The allowlist gate gains exactly one new entry**: `'leaverequest' => 'tenantleaverequestlist'`. The four existing entries (`staff`/`tenantstafflist`, `feesforward`/`tenantfeeslist`, `examgroup`/`tenantexamresultslist`, `stuattendence`/`tenantattendancelist`) must be completely unaffected — verified live, not just by code reading.
- **The new model method is additive only** — `Leaverequest_model.php`'s existing methods (used by the real, un-gated `Leaverequest` controller serving real schools today) must not be touched, reordered, or reformatted.
- **The new controller method has its own defense-in-depth guard** — re-checks `$this->session->userdata('admin_tenant_id')` and `show_404()`s if absent, exactly like the four prior gated methods, even though the constructor-level gate already covers this.
- **Tenant scoping via query builder only** — `$this->db->where('tenant_id', $tenantId)->get('staff_leave_details')->result_array()`, no raw SQL, no string concatenation of the tenant id into a query.
- **`Leaverequest_model` is not in `MY_Controller`'s global autoload array** — unlike `stuattendence_model`, `staff_model`, etc. `Leaverequest.php`'s own constructor explicitly does `$this->load->model("leaverequest_model");` (confirmed live at `application/controllers/admin/Leaverequest.php:13`), so `$this->leaverequest_model` is available inside `Leaverequest` controller methods without any extra loading — just use it directly in the new controller method, same as every existing method in that file already does.
- **Known test credential** (unchanged): tenant_id=25, email `rabiachauhan923@gmail.com`, password `TestVerify123!`.
- **Real row counts to verify against**: `staff_leave_details` has 32 rows for tenant_id=25 in `school_saas` (confirmed live via `SELECT COUNT(*) FROM staff_leave_details WHERE tenant_id=25`).
- **`staff_leave_details` schema** (confirmed live via `DESCRIBE`): `id`, `tenant_id`, `staff_id`, `leave_type_id`, `alloted_leave`, `created_at`, `updated_at`.
- **Before editing `Leaverequest_model.php`/`Leaverequest.php`, always check for pre-existing unrelated uncommitted work first** (`git status --short` on both — confirmed clean as of this plan's writing, but re-check live at execution time, don't assume; Stage 5 found a file was unexpectedly already clean when the plan assumed otherwise, and Stage 6 similarly re-checked live rather than trusting a prior stage's outcome). If either file has unrelated uncommitted content by execution time, use the git-plumbing technique proven in Phase 3 Stages 3 and 4 instead of a plain commit.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: Add `leaverequest` to the allowlist gate

**Files:**
- Modify: `application/core/MY_Controller.php:64-69`

**Interfaces:**
- Consumes: nothing new.
- Produces: the route `leaverequest/tenantleaverequestlist` becomes reachable for a tenant-scoped session once Task 2 implements the method — until then it likely 307s (the same benign CI3 `method_exists`-pre-check/404_override redirect behavior independently traced and confirmed harmless in Stage 6 Task 1's review — not a bypass), not necessarily a bare 404.

- [ ] **Step 1: Confirm current state**

```bash
sed -n '60,76p' application/core/MY_Controller.php
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
                'stuattendence' => 'tenantattendancelist',
            ];
            if (!isset($allowedTenantRoutes[$activeController]) || $allowedTenantRoutes[$activeController] !== $activeMethod) {
                show_404();

                return;
            }
        }
```

If it differs, STOP and report the actual content.

- [ ] **Step 2: Add the fifth entry**

```php
            $allowedTenantRoutes = [
                'staff' => 'tenantstafflist',
                'feesforward' => 'tenantfeeslist',
                'examgroup' => 'tenantexamresultslist',
                'stuattendence' => 'tenantattendancelist',
                'leaverequest' => 'tenantleaverequestlist',
            ];
```

- [ ] **Step 3: Lint**

```bash
"C:\xampp81\php\php.exe" -l application/core/MY_Controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Live-verify the four existing routes are unaffected**

Apache/MySQL should already be running. Use a single cookie jar in one script:

```bash
CJ=/tmp/p3s7_task1_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/feesforward/tenantFeesList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/examgroup/tenantExamResultsList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/stuattendence/tenantAttendanceList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/leaverequest/tenantLeaveRequestList -w "\n%{http_code}\n"
```

Expected: staff 200 "Staff (18 real, tenant-scoped rows)"; fees 200 "Student Fee Deposits (699 real, tenant-scoped rows)"; exam 200 "Exam Results (2785 real, tenant-scoped rows)"; attendance 200 "Student Attendance (1124 real, tenant-scoped rows)"; leaverequest not-yet-implemented (likely 307, per the benign framework behavior noted above — if it's a bare 404 that's fine too, either is consistent with "not yet reachable"; if it's a 200 with any body, STOP immediately — that would mean something is unexpectedly already there).

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 67/67 passing, no regressions.

- [ ] **Step 6: Commit**

```bash
git add application/core/MY_Controller.php
git commit -m "feat: add leaverequest to Admin_Controller's tenant allowlist gate"
```

---

### Task 2: Real Leaverequest tenant-scoped method

**Files:**
- Modify: `application/models/Leaverequest_model.php` (append new method, do not touch existing methods)
- Modify: `application/controllers/admin/Leaverequest.php` (append new method, do not touch existing methods)
- Create: `application/views/admin/leaverequest/tenant_leave_request_list.php`

**Interfaces:**
- Consumes: Task 1's allowlist entry (`'leaverequest' => 'tenantleaverequestlist'`).
- Produces: `Leaverequest_model::getTenantScopedLeaveList(int $tenantId): array` — used only by this task's controller method.

**Before editing, re-check for pre-existing unrelated uncommitted work:**

```bash
git status --short application/models/Leaverequest_model.php application/controllers/admin/Leaverequest.php
```

If either file shows unrelated uncommitted changes, do NOT `git add` the whole file — use the git-plumbing technique proven in Phase 3 Stages 3 and 4 (`git hash-object -w --no-filters` + `git update-index --cacheinfo` against the current HEAD blob, adding only this task's new method). If both are clean, a plain `git add`/`commit` is correct.

- [ ] **Step 1: Add the model method**

Append to the end of the `Leaverequest_model` class in `application/models/Leaverequest_model.php` (find the class's closing `}` and insert immediately before it — do not touch any existing method):

```php
    public function getTenantScopedLeaveList($tenantId)
    {
        return $this->db->where('tenant_id', $tenantId)->get('staff_leave_details')->result_array();
    }
```

- [ ] **Step 2: Add the controller method**

Append to the end of the `Leaverequest` class in `application/controllers/admin/Leaverequest.php` (find the class's closing `}` and insert immediately before it — do not touch any existing method). Note this controller's constructor already loads `leaverequest_model` explicitly (`$this->load->model("leaverequest_model");`), so no extra load is needed here:

```php
    public function tenantLeaveRequestList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $leaveRequestList = $this->leaverequest_model->getTenantScopedLeaveList((int) $tenantId);
        $this->load->view('admin/leaverequest/tenant_leave_request_list', ['leaveRequestList' => $leaveRequestList]);
    }
```

- [ ] **Step 3: Add the view**

Create `application/views/admin/leaverequest/tenant_leave_request_list.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Tenant Leave Request List</title></head>
<body>
<h1>Staff Leave Details (<?php echo count($leaveRequestList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($leaveRequestList as $row): ?>
    <li>Leave #<?php echo (int) $row['id']; ?> — staff <?php echo (int) $row['staff_id']; ?>, leave type <?php echo (int) $row['leave_type_id']; ?>, alloted <?php echo htmlspecialchars((string) $row['alloted_leave']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

- [ ] **Step 4: Lint both PHP files**

```bash
"C:\xampp81\php\php.exe" -l application/models/Leaverequest_model.php
"C:\xampp81\php\php.exe" -l application/controllers/admin/Leaverequest.php
```

Expected: `No syntax errors detected` for both.

- [ ] **Step 5: Live-verify the new route now works, end to end**

```bash
CJ=/tmp/p3s7_task2_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/leaverequest/tenantLeaveRequestList -w "\n%{http_code}\n"
```

Expected: `200`, body contains `Staff Leave Details (32 real, tenant-scoped rows)`.

- [ ] **Step 6: Commit**

Per the pre-check above, either a plain commit:

```bash
git add application/models/Leaverequest_model.php application/controllers/admin/Leaverequest.php application/views/admin/leaverequest/tenant_leave_request_list.php
git commit -m "feat: add tenant-scoped leave list method to the real Leaverequest controller/model"
```

or the git-plumbing equivalent if either PHP file had unrelated pre-existing uncommitted content by execution time. The new view file has no pre-existing content risk (it's new) — always `git add` it normally.

---

### Task 3: End-to-end verification + safety regression proof

**Files:**
- Modify: `tests/controllers/AdminControllerTenantGateTest.php` (append one new test)
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (append Stage 7 entry)

**Interfaces:**
- Consumes: Task 1's allowlist entry, Task 2's gated method — both already live.
- Produces: nothing — this is the closing task.

- [ ] **Step 1: Confirm the known test credential is still intact**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT id, email FROM staff WHERE email='rabiachauhan923@gmail.com';"
```

Expected: one row, `id=1`.

- [ ] **Step 2: Real login → all five real controllers, end to end**

```bash
CJ=/tmp/p3s7_task3_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/feesforward/tenantFeesList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/examgroup/tenantExamResultsList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/stuattendence/tenantAttendanceList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/leaverequest/tenantLeaveRequestList -w "\n%{http_code}\n"
```

Expected: staff 200 (18 rows); fees 200 (699 rows); exam 200 (2785 rows); attendance 200 (1124 rows); leaverequest 200 (32 rows).

- [ ] **Step 3: Confirm the allowlist gate still blocks everything else**

Using the same `$CJ`, confirm 404 for: `admin/admin/dashboard`, `admin/staff`, `admin/feesforward`, `admin/examgroup`, `admin/examresult`, `admin/stuattendence`, `admin/leaverequest` (the real un-gated index — this is literally `leaverequest()`, the controller's first real method, not the CI3-default `index()`; check with `grep "public function" application/controllers/admin/Leaverequest.php` first, which lists `leaverequest`, `countLeave`, `leaveStatus`, `remove`, `leaveRecord`, `dateDifference`, `addLeave`, `add_staff_leave`, `handle_upload`, `downloadleaverequestdoc` — use `admin/leaverequest/leaverequest` and `admin/leaverequest/leaveRecord` as the two real sibling-method probes).

- [ ] **Step 4: Add the credentialed regression test**

Append to `tests/controllers/AdminControllerTenantGateTest.php`, immediately after the existing `testTenantScopedSessionReachesAllFourAllowlistedRoutesAndNothingElse` method (reuse the existing `curlPostPilotLogin()`/`curlGet()` helpers, do not duplicate them):

```php
    public function testTenantScopedSessionReachesAllFiveAllowlistedRoutesAndNothingElse(): void
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

        [$leaveListStatus, $leaveListBody] = $this->curlGet('admin/leaverequest/tenantLeaveRequestList');
        $this->assertSame(200, $leaveListStatus);
        $this->assertStringContainsString('Tenant Leave Request List', $leaveListBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$leaverequestIndexStatus, ] = $this->curlGet('admin/leaverequest/leaverequest');
        $this->assertSame(404, $leaverequestIndexStatus);
    }
```

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 67 (prior total) + 1 new = 68, all passing.

- [ ] **Step 6: Commit the test**

```bash
git add tests/controllers/AdminControllerTenantGateTest.php
git commit -m "test: add credentialed end-to-end regression test for the fifth controller retrofit"
```

- [ ] **Step 7: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` — add a Phase 3 Stage 7 entry following the Stage 3/4/6 entries' style: what was built, real commit hashes, real row counts confirmed live, confirmation the four prior routes remain unaffected, and explicit note that this is the fourth real-controller retrofit needing zero new infrastructure (allowlist gate, `Db_manager` connection gate, and settings fixture tables all unchanged since Phase 2 Stage 6). Be precise about counting — this is the fourth *retrofit*, following Stage 3 (2nd), Stage 4 (3rd), Stage 6 (4th) — avoid the "Nth consecutive stage" phrasing Stage 6's review flagged as imprecise (Stage 5 sits in the sequence and isn't a retrofit).

- [ ] **Step 8: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 7 (fifth real controller retrofit — Leaverequest) complete"
```

---

## Final whole-stage review (after Task 3)

Dispatch an adversarial reviewer (same rigor as every prior stage's final review) to independently:
- Re-read the full diff across all 3 tasks.
- Independently probe sibling methods on `Leaverequest` beyond what Task 3 already tested (e.g. `countLeave`, `addLeave`, `dateDifference`), to confirm the gate is method-level not controller-level.
- Independently re-verify all five allowlisted routes live with real row counts.
- Confirm the four pre-existing routes (staff/fees/examresults/attendance) are provably unaffected.
- Confirm `Leaverequest_model.php`/`Leaverequest.php`'s existing methods (serving real, un-gated schools today) are untouched — diff should show only additions; read a couple of the pre-existing methods in the live file to sanity-check they're intact, not just diff-empty.
- Run the full suite and confirm 68/68.
- Confirm roadmap accuracy, including the retrofit-count phrasing.
- Confirm git hygiene — pre-existing unrelated uncommitted work still present and untouched.
- Report Ready to merge (Yes/With fixes/No) plus Critical/Important/Minor findings, same format as every prior stage.
