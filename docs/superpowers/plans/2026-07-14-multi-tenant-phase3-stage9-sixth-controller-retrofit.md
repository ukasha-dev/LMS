# Phase 3 Stage 9 — Sixth Real Controller Retrofit (Classes) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the proven real-controller-retrofit mechanism (allowlist gate + one gated model/controller method pair) to a sixth real admin controller — `Classes.php` — exposing the pilot tenant's 7 already-migrated real `classes` rows to a real, tenant-scoped, gated session.

**Architecture:** Identical to Phase 3 Stages 3, 4, 6, and 7 (Feesforward, Examgroup, Stuattendence, Leaverequest): add one entry to `Admin_Controller`'s allowlist array (`application/core/MY_Controller.php`), add one new `getTenantScoped*` method to the real, already-autoloaded model (`Class_model`), add one new gated controller method with its own defense-in-depth session check, add one new minimal view. No new database migration, no schema change — `classes` was already migrated to `school_saas` in Phase 2 Stage 2.

**Tech Stack:** PHP 8.1, CodeIgniter 3.1.13 query builder, PHPUnit 10.5 (unchanged).

## Global Constraints

- **The allowlist gate gains exactly one new entry**: `'classes' => 'tenantclasslist'`. The five existing entries (`staff`/`tenantstafflist`, `feesforward`/`tenantfeeslist`, `examgroup`/`tenantexamresultslist`, `stuattendence`/`tenantattendancelist`, `leaverequest`/`tenantleaverequestlist`) must be completely unaffected — verified live, not just by code reading.
- **`Classes.php` is a top-level controller, not under `application/controllers/admin/`** — confirmed live (`application/controllers/Classes.php`, extends `Admin_Controller`). This matches the precedent of `Student.php` (also top-level, referenced in earlier stages) — the allowlist gate's `$this->router->fetch_class()` check is controller-name-based, not path-based, so this doesn't change anything about how the gate works; just note the correct file path when editing.
- **The new model method is additive only** — `Class_model.php`'s existing methods (used by the real, un-gated `Classes` controller serving real schools today) must not be touched, reordered, or reformatted.
- **The new controller method has its own defense-in-depth guard** — re-checks `$this->session->userdata('admin_tenant_id')` and `show_404()`s if absent, exactly like the five prior gated methods.
- **Tenant scoping via query builder only** — `$this->db->where('tenant_id', $tenantId)->get('classes')->result_array()`, no raw SQL, no string concatenation of the tenant id.
- **`class_model` is confirmed in `MY_Controller`'s global model-autoload array** (`application/core/MY_Controller.php:19`, `'class_model'` is present) — no extra load needed in the new controller method.
- **Before editing `Classes.php`/`Class_model.php`, always check for pre-existing unrelated uncommitted work first** (`git status --short` on both — confirmed clean as of this plan's writing at commit `7f5c4d67`, but re-check live at execution time, don't assume). If either file has unrelated uncommitted content by execution time, use the git-plumbing technique proven in Phase 3 Stages 3 and 4 instead of a plain commit.
- **Known test credential** (unchanged): tenant_id=25, email `rabiachauhan923@gmail.com`, password `TestVerify123!`.
- **Real row count to verify against**: `classes` has 7 rows for tenant_id=25 in `school_saas` (confirmed live via `SELECT COUNT(*) FROM classes WHERE tenant_id=25`).
- **`classes` schema** (confirmed live via `DESCRIBE`): `id`, `tenant_id`, `class`, `is_active`, `created_at`, `updated_at`.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: Add `classes` to the allowlist gate

**Files:**
- Modify: `application/core/MY_Controller.php:64-70`

**Interfaces:**
- Consumes: nothing new.
- Produces: the route `classes/tenantclasslist` becomes reachable for a tenant-scoped session once Task 2 implements the method — until then it will likely 307 (the same benign CI3 `method_exists`-pre-check/404_override redirect behavior independently confirmed harmless in Stages 4, 6, and 7's Task 1 reviews) or 404 — either is consistent with "not yet implemented," a bare `200` would not be.

- [ ] **Step 1: Confirm current state**

```bash
sed -n '60,77p' application/core/MY_Controller.php
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
                'leaverequest' => 'tenantleaverequestlist',
            ];
            if (!isset($allowedTenantRoutes[$activeController]) || $allowedTenantRoutes[$activeController] !== $activeMethod) {
                show_404();

                return;
            }
        }
```

If it differs, STOP and report the actual content.

- [ ] **Step 2: Add the sixth entry**

```php
            $allowedTenantRoutes = [
                'staff' => 'tenantstafflist',
                'feesforward' => 'tenantfeeslist',
                'examgroup' => 'tenantexamresultslist',
                'stuattendence' => 'tenantattendancelist',
                'leaverequest' => 'tenantleaverequestlist',
                'classes' => 'tenantclasslist',
            ];
```

- [ ] **Step 3: Lint**

```bash
"C:\xampp81\php\php.exe" -l application/core/MY_Controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Live-verify the five existing routes are unaffected**

Apache/MySQL should already be running. Use a single cookie jar in one script:

```bash
CJ=/tmp/p3s9_task1_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/feesforward/tenantFeesList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/examgroup/tenantExamResultsList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/stuattendence/tenantAttendanceList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/leaverequest/tenantLeaveRequestList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/classes/tenantClassList -w "\n%{http_code}\n"
```

Expected: staff 200 (18 rows); fees 200 (699 rows); exam 200 (2785 rows); attendance 200 (1124 rows); leaverequest 200 (32 rows); classes not-yet-implemented (307 or 404, NOT 200).

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 75/75 passing, no regressions (this task adds no new tests of its own — a dedicated regression test for the 6-entry array is added in Task 3, matching the established pattern).

- [ ] **Step 6: Commit**

```bash
git add application/core/MY_Controller.php
git commit -m "feat: add classes to Admin_Controller's tenant allowlist gate"
```

---

### Task 2: Real Classes tenant-scoped method

**Files:**
- Modify: `application/models/Class_model.php` (append new method, do not touch existing methods)
- Modify: `application/controllers/Classes.php` (append new method, do not touch existing methods)
- Create: `application/views/class/tenant_class_list.php`

**Interfaces:**
- Consumes: Task 1's allowlist entry (`'classes' => 'tenantclasslist'`).
- Produces: `Class_model::getTenantScopedClassList(int $tenantId): array` — used only by this task's controller method.

**Before editing, re-check for pre-existing unrelated uncommitted work:**

```bash
git status --short application/models/Class_model.php application/controllers/Classes.php
```

If either file shows unrelated uncommitted changes, do NOT `git add` the whole file — use the git-plumbing technique proven in Phase 3 Stages 3 and 4. If both are clean, a plain commit is correct.

- [ ] **Step 1: Add the model method**

Append to the end of the `Class_model` class in `application/models/Class_model.php` (find the class's closing `}` and insert immediately before it — do not touch any existing method):

```php
    public function getTenantScopedClassList($tenantId)
    {
        return $this->db->where('tenant_id', $tenantId)->get('classes')->result_array();
    }
```

- [ ] **Step 2: Add the controller method**

Append to the end of the `Classes` class in `application/controllers/Classes.php` (find the class's closing `}` and insert immediately before it — do not touch any existing method):

```php
    public function tenantClassList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $classList = $this->class_model->getTenantScopedClassList((int) $tenantId);
        $this->load->view('class/tenant_class_list', ['classList' => $classList]);
    }
```

- [ ] **Step 3: Add the view**

Create `application/views/class/tenant_class_list.php`. Note the directory is `class/` (singular), not `classes/` — confirmed live: the existing `Classes` controller's `index()`/`edit()` methods call `$this->load->view('class/classList', ...)` and `$this->load->view('class/classEdit', ...)` respectively (`application/controllers/Classes.php:50,104`), so this task's `load->view('class/tenant_class_list', ...)` call in Step 2 matches that established convention exactly.

```php
<!DOCTYPE html>
<html>
<head><title>Tenant Class List</title></head>
<body>
<h1>Classes (<?php echo count($classList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($classList as $row): ?>
    <li>Class #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['class']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

If the existing `Classes` controller's view calls use a different base view-path convention than `classes/...` (verify by reading its `index()` method's `$this->load->view(...)` call before writing this file), use that same convention instead and note the deviation in your report.

- [ ] **Step 4: Lint both PHP files**

```bash
"C:\xampp81\php\php.exe" -l application/models/Class_model.php
"C:\xampp81\php\php.exe" -l application/controllers/Classes.php
```

Expected: `No syntax errors detected` for both.

- [ ] **Step 5: Live-verify the new route now works, end to end**

```bash
CJ=/tmp/p3s9_task2_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/classes/tenantClassList -w "\n%{http_code}\n"
```

Expected: `200`, body contains `Classes (7 real, tenant-scoped rows)`.

- [ ] **Step 6: Commit**

Per the pre-check above, either:

```bash
git add application/models/Class_model.php application/controllers/Classes.php application/views/class/tenant_class_list.php
git commit -m "feat: add tenant-scoped class list method to the real Classes controller/model"
```

or the git-plumbing equivalent if either PHP file had unrelated pre-existing uncommitted content by execution time. The new view file has no pre-existing content risk — always `git add` it normally.

---

### Task 3: End-to-end verification + safety regression proof

**Files:**
- Modify: `tests/controllers/AdminControllerTenantGateTest.php` (append one new test)
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (append Stage 9 entry)

**Interfaces:**
- Consumes: Task 1's allowlist entry, Task 2's gated method — both already live.
- Produces: nothing — this is the closing task.

- [ ] **Step 1: Confirm the known test credential is still intact**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT id, email FROM staff WHERE email='rabiachauhan923@gmail.com';"
```

Expected: one row, `id=1`.

- [ ] **Step 2: Real login → all six real controllers, end to end**

```bash
CJ=/tmp/p3s9_task3_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/feesforward/tenantFeesList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/examgroup/tenantExamResultsList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/stuattendence/tenantAttendanceList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/leaverequest/tenantLeaveRequestList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/classes/tenantClassList -w "\n%{http_code}\n"
```

Expected: staff 200 (18); fees 200 (699); exam 200 (2785); attendance 200 (1124); leaverequest 200 (32); classes 200 (7).

- [ ] **Step 3: Confirm the allowlist gate still blocks everything else**

Using the same `$CJ`, confirm 404 for: `admin/admin/dashboard`, `admin/staff`, `admin/feesforward`, `admin/examgroup`, `admin/examresult`, `admin/stuattendence`, `admin/leaverequest`, and two real sibling methods on the newly-allowlisted `Classes` controller itself — its real public methods are `index`, `delete`, `edit`, `get_section` (confirmed via `grep "public function" application/controllers/Classes.php`); use `classes/index` and `classes/edit/1` for this check. Also confirm the bare `classes` route (no method segment, defaults to `index()`) is blocked — this exercises the actual `index()` method rather than a nonexistent one, unlike the Stage 7 anomaly.

- [ ] **Step 4: Add the credentialed regression test**

Append to `tests/controllers/AdminControllerTenantGateTest.php`, immediately after the existing `testTenantScopedSessionReachesAllFiveAllowlistedRoutesAndNothingElse` method (reuse the existing `curlPostPilotLogin()`/`curlGet()` helpers, do not duplicate them):

```php
    public function testTenantScopedSessionReachesAllSixAllowlistedRoutesAndNothingElse(): void
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

        [$classListStatus, $classListBody] = $this->curlGet('classes/tenantClassList');
        $this->assertSame(200, $classListStatus);
        $this->assertStringContainsString('Tenant Class List', $classListBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$classesIndexStatus, ] = $this->curlGet('classes/index');
        $this->assertSame(404, $classesIndexStatus);
    }
```

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 75 (prior total) + 1 new = 76, all passing.

- [ ] **Step 6: Commit the test**

```bash
git add tests/controllers/AdminControllerTenantGateTest.php
git commit -m "test: add credentialed end-to-end regression test for the sixth controller retrofit"
```

- [ ] **Step 7: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` — add a Phase 3 Stage 9 entry following the Stage 3/4/6/7 entries' style: what was built, real commit hashes, real row counts confirmed live, confirmation the five prior routes remain unaffected, and explicit note that this is the fifth real-controller retrofit (Stages 3, 4, 6, 7, 9 — Stage 5 and Stage 8 are not retrofits, don't count them, matching the precision the Stage 7 final review required) needing zero new infrastructure.

- [ ] **Step 8: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 9 (sixth real controller retrofit — Classes) complete"
```

- [ ] **Step 9: Push**

```bash
git push github master
```

---

## Final whole-stage review (after Task 3)

Dispatch an adversarial reviewer (same rigor as every prior stage's final review) to independently:
- Re-read the full diff across all 3 tasks.
- Independently probe sibling methods on `Classes` beyond what Task 3 already tested, to confirm the gate is method-level not controller-level.
- Independently re-verify all six allowlisted routes live with real row counts.
- Confirm the five pre-existing routes are provably unaffected.
- Confirm `Class_model.php`/`Classes.php`'s existing methods (serving real, un-gated schools today) are untouched — diff should show only additions; read a couple of the pre-existing methods in the live file to sanity-check they're intact.
- Run the full suite and confirm 76/76.
- Confirm roadmap accuracy, including the precise retrofit-count phrasing.
- Confirm git hygiene — pre-existing unrelated uncommitted work (if any remains) still present and untouched; the single known-open omnipay-vendor-file deletion should be the only thing left in `git status`, if anything.
- **Read-only only**: as with Stage 8, do not run any git command that mutates the working tree, index, or history (no `checkout`, `restore`, `reset`, `clean`, `stash apply/pop`). Use `git show`/`git diff`/`git log`/`git status` only.
- Report Ready to merge (Yes/With fixes/No) plus Critical/Important/Minor findings, same format as every prior stage.
