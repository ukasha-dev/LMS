# Multi-Tenant Migration — Phase 3 Stage 4: Third Real Controller Retrofit (Examgroup) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the real-controller-retrofit mechanism (proven twice now
— Staff in Phase 2 Stage 6, Feesforward in Phase 3 Stage 3) to a THIRD
real controller, `Examgroup.php`, reusing the `admin_tenant_id` gate
infrastructure completely unchanged except for one more allowlist entry.
This is the second consecutive stage to add exactly "one allowlist entry
and one gated method," confirming the mechanism scales to N controllers
without ever needing to rebuild the gate.

**Architecture:** `Admin_Controller`'s allowlist
(`application/core/MY_Controller.php`, currently `['staff' =>
'tenantstafflist', 'feesforward' => 'tenantfeeslist']`) gains one more
entry: `examgroup`/`tenantexamresultslist`. One new method each on the
REAL `Examgroup.php` controller and `Examgroup_model.php` model (not new
files — additions to the live, shared-by-all-schools files), using the
exact same "explicit `tenant_id` filter, query-builder, one new minimal
method" pattern already proven twice. No new gate, no new settings
tables, no `PilotLogin.php` changes — the already-authenticated
`PilotLogin` session (already carrying `admin_tenant_id`) simply reaches
a third allowlisted route.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, curl for live HTTP verification.

## Why `Examgroup.php` and why a brand-new method (read before touching anything)

- **The other real, already-migrated-data candidate** (per Phase 3
  Stage 3's research) besides `Feesforward.php`, now being used for this
  stage instead — proving the mechanism against BOTH surveyed real
  controllers, not just the simpler one.
- **A larger, more complex real controller than either prior retrofit**:
  1099 lines, 8 `rbac->hasPrivilege()` gates, 6
  `layout/header`/`layout/footer` chrome pairs across its ~30 methods.
  This stage deliberately does NOT touch or retrofit any of that — it
  adds ONE new, independent, READ-ONLY method, exactly like the two
  prior retrofits, proving controller SIZE/complexity doesn't matter to
  this mechanism as long as the new method stays narrow and additive.
- **The model has a clean query-builder precedent to imitate**:
  `Examgroup_model::get()` (`application/models/Examgroup_model.php:21-26`)
  is a plain `$this->db->select(...)->from('exam_groups')` call — this
  stage's new method follows that same style, not the raw-SQL style used
  by most of the file's other ~10 methods (which, per this project's
  established discipline, are left completely untouched).
- **Target data**: `exam_group_exam_results` (2785 real rows for tenant
  25, migrated in Phase 2 Stage 5) — the richest, most central real exam
  data table, already proven reachable and correct via the read-only
  `PilotExam` proof controller.

## Global Constraints

- **Do not modify `application/controllers/Site.php` at all.** Same
  absolute rule as every real-controller-retrofit stage.
- Do not modify `application/libraries/Auth.php`,
  `application/libraries/Db_manager.php`, or
  `application/controllers/PilotLogin.php` — all already correctly gate
  on/produce `admin_tenant_id` and need no changes for a third
  allowlisted route.
- Do not modify any of `Examgroup.php`'s ~30 existing methods (`index()`,
  `exportformat()`, `classmarkspdf()`, `exam()`, `examresult()`,
  `addmark()`, `edit()`, `addexam()`, `assign()`, `delete()`, and the
  rest) — this stage only appends one new method. Do not modify
  `application/models/Examgroup_model.php`'s existing methods (including
  `get()`, which this stage's new method is modeled after but does not
  call or modify), `Examgroupstudent_model.php`, or `Batchsubject_model.php`.
  Do not modify `application/views/admin/examgroup/*` (existing views)
  — this stage adds one new view file, does not touch existing ones.
- Do not modify `tools/multitenant/*` or any merge tool — no migration
  tooling is touched; the exam data was already migrated by Phase 2
  Stage 5.
- **The allowlist generalization must NOT change behavior for either
  pre-existing entry** (`staff`/`tenantstafflist`,
  `feesforward`/`tenantfeeslist`) — covered by an explicit regression
  test proving BOTH prior routes still work exactly as before, not just
  "should still be in the array."
- The new `Examgroup::tenantExamResultsList()` method must independently
  re-check `admin_tenant_id` itself (defense in depth on top of the
  `Admin_Controller` allowlist gate) and `show_404()` if absent — same
  two-layer requirement every prior retrofit established.
- All new PHP must run under PHP 8.1. Use `127.0.0.1`/`root`/empty
  password for local MySQL. Tenant id `25` is reserved for
  `al_hafeez_campus`. MySQL and Apache are already running.
- Verified before writing this plan: `exam_group_exam_results` (2785
  real rows for tenant 25) is already migrated (Phase 2 Stage 5) and
  already proven correct via `PilotExam`; the known test credential from
  the two prior retrofit stages (tenant_id=25, email
  `rabiachauhan923@gmail.com`, password `TestVerify123!`, a
  `school_saas`-only test password, real `al_hafeez_campus` account
  never touched) is still intact — confirm this directly before Task 3
  rather than assuming.

---

### Task 1: Add `Examgroup`/`tenantExamResultsList` to the allowlist gate

**Files:**
- Modify: `application/core/MY_Controller.php` (`Admin_Controller::__construct()`)
- Test: `tests/controllers/AdminControllerTenantGateTest.php` (extend)

**Interfaces:**
- Produces: `$allowedTenantRoutes` in `Admin_Controller::__construct()`
  gains a third entry: `'examgroup' => 'tenantexamresultslist'`. Both
  pre-existing entries (`staff`/`tenantstafflist`,
  `feesforward`/`tenantfeeslist`) continue to work identically.
- Consumed by: Task 2's new `Examgroup::tenantExamResultsList()` method
  (must pass through this gate) and Task 3's verification.

Read `application/core/MY_Controller.php` in full before editing —
`Admin_Controller::__construct()` currently reads (after Phase 3 Stage
3's generalization):

```php
    public function __construct()
    {
        parent::__construct();
        $this->auth->is_logged_in();

        if ($this->session->userdata('admin_tenant_id')) {
            $activeController = strtolower($this->router->fetch_class());
            $activeMethod     = strtolower($this->router->fetch_method());
            $allowedTenantRoutes = [
                'staff' => 'tenantstafflist',
                'feesforward' => 'tenantfeeslist',
            ];
            if (!isset($allowedTenantRoutes[$activeController]) || $allowedTenantRoutes[$activeController] !== $activeMethod) {
                show_404();

                return;
            }
        }

        $this->check_license();
        $this->load->library('rbac');
        $this->config->load('app-config');
        $this->config->load('ci-blog');
        $this->config->load('custom_filed-config');
    }
```

- [ ] **Step 1: Write the failing test**

Add to `tests/controllers/AdminControllerTenantGateTest.php`, after the
existing `testTenantScopedSessionReachesBothAllowlistedRoutesAndNothingElse`
test:

```php
    public function testAllowlistGateStillAllowsBothPriorRoutesAfterAThirdIsAdded(): void
    {
        // Regression proof for Task 1's third allowlist entry: both
        // pre-existing routes must keep working exactly as before.
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);
    }
```

- [ ] **Step 2: Run test to verify it currently passes (regression
  baseline, not a RED step — both prior routes already work today; this
  locks in that they must keep working after Step 3's edit)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/AdminControllerTenantGateTest.php`
Expected: `OK (6 tests, ...)` (5 prior + 1 new).

- [ ] **Step 3: Add the third allowlist entry**

Replace the `$allowedTenantRoutes` array literal inside
`Admin_Controller::__construct()` in `application/core/MY_Controller.php`
from:

```php
            $allowedTenantRoutes = [
                'staff' => 'tenantstafflist',
                'feesforward' => 'tenantfeeslist',
            ];
```

to:

```php
            $allowedTenantRoutes = [
                'staff' => 'tenantstafflist',
                'feesforward' => 'tenantfeeslist',
                'examgroup' => 'tenantexamresultslist',
            ];
```

(Nothing else in the method changes — the `isset()`/`!==` check logic,
`check_license()`, `rbac` load, and config loads are all untouched.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/AdminControllerTenantGateTest.php`
Expected: `OK (6 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: prior count (59, from Phase 3 Stage 3's final state) + 1 new
test = 60. Use the ACTUAL current count if it differs from this
plan-doc estimate — this project has had plan-doc arithmetic drift
before; always trust the real `phpunit` output.

- [ ] **Step 6: Manual smoke test — confirm the app still boots for an
  ungated request**

Run: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost/web-app/admin/admin/dashboard`
Expected: `307` (unauthenticated redirect to login — NOT a 500).

- [ ] **Step 7: Commit**

```bash
git add application/core/MY_Controller.php tests/controllers/AdminControllerTenantGateTest.php
git commit -m "feat: add examgroup/tenantExamResultsList as a third entry in Admin_Controller's tenant allowlist gate"
```

---

### Task 2: Real `Examgroup.php`/`Examgroup_model.php` tenant-scoped method

**Files:**
- Modify: `application/models/Examgroup_model.php` (add one method)
- Modify: `application/controllers/admin/Examgroup.php` (add one method)
- Create: `application/views/admin/examgroup/tenant_exam_results_list.php`

**Interfaces:**
- Produces: `Examgroup_model::getTenantScopedExamResultsList(int $tenantId): array`
  (returns `array<int, array>`, one row per `school_saas.exam_group_exam_results`
  row for that tenant) and `Examgroup::tenantExamResultsList(): void`
  (renders the new minimal view). Consumed by Task 3.

This is the third real instance of the "add an explicit `tenant_id`
filter to one new query, in the real shared model file, without
touching its other existing methods" pattern — proving it generalizes
to a third model file (858 lines, mostly raw-SQL, but with a clean
query-builder `get()` method this new method's style matches) without
needing to touch, understand, or imitate the raw-SQL methods.

- [ ] **Step 1: Add the model method**

In `application/models/Examgroup_model.php`, add this method (append
near the end of the class, before the closing `}` — do not reorder or
touch any existing method, including `get()`):

```php
    public function getTenantScopedExamResultsList($tenantId)
    {
        return $this->db->where('tenant_id', $tenantId)->get('exam_group_exam_results')->result_array();
    }
```

- [ ] **Step 2: Add the controller method**

In `application/controllers/admin/Examgroup.php`, add this method
(append near the end of the class, before the closing `}` — do not
touch `index()`, `exam()`, `examresult()`, `addmark()`, `edit()`,
`addexam()`, `assign()`, `delete()`, `exportformat()`, `classmarkspdf()`,
or any other existing method):

```php
    public function tenantExamResultsList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $examResultsList = $this->examgroup_model->getTenantScopedExamResultsList((int) $tenantId);
        $this->load->view('admin/examgroup/tenant_exam_results_list', ['examResultsList' => $examResultsList]);
    }
```

(This method's own `show_404()` guard is the second, independent layer
on top of Task 1's `Admin_Controller`-level allowlist gate. `examgroup_model`
is already autoloaded by `MY_Controller::__construct()` — confirmed via
the same autoload list every prior retrofit relied on — so no explicit
`$this->load->model()` call is needed.)

- [ ] **Step 3: Add the view**

Create `application/views/admin/examgroup/tenant_exam_results_list.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Tenant Exam Results List</title></head>
<body>
<h1>Exam Results (<?php echo count($examResultsList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($examResultsList as $result): ?>
    <li>Result #<?php echo (int) $result['id']; ?> — student link <?php echo (int) $result['exam_group_class_batch_exam_student_id']; ?>, marks <?php echo htmlspecialchars((string) $result['get_marks']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

(Deliberately bare — no `layout/header`/`layout/footer` — matching both
prior retrofits' minimal-view scope: proving the model/controller layer
only. Shows raw FK ids and the marks value rather than resolved
student/subject names, mirroring `Feesforward`'s equally minimal
`tenant_fees_list.php` — a future task could join to
`exam_group_class_batch_exam_students`/`students`/`exam_group_class_batch_exam_subjects`/`subjects`
for human-readable names, exactly as `PilotExam` already does, but that
join logic is explicitly NOT required to prove THIS stage's point.)

- [ ] **Step 4: Lint the new/changed PHP files**

Run: `"C:\xampp81\php\php.exe" -l application/models/Examgroup_model.php`
Run: `"C:\xampp81\php\php.exe" -l application/controllers/admin/Examgroup.php`
Run: `"C:\xampp81\php\php.exe" -l application/views/admin/examgroup/tenant_exam_results_list.php`
Expected: `No syntax errors detected` for all three.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: unchanged from Task 1's final count (this task adds no new
automated tests — Task 3 verifies live).

- [ ] **Step 6: Commit**

**Before staging, check for pre-existing unrelated uncommitted changes
in these exact two files** — this project's working tree has carried
substantial unrelated in-progress work in `Examgroup.php` and other exam
module files since before this migration effort began (confirmed
present at the start of this whole project, and Phase 3 Stage 3 hit the
same situation in `Feesforward.php`/`Studentfeemaster_model.php`). Run
`git diff --stat -- application/models/Examgroup_model.php application/controllers/admin/Examgroup.php`
BEFORE editing to see the pre-edit baseline, and after making only the
two additive method changes above, diff again to confirm your change is
a clean, minimal delta on top of whatever was already there — do NOT
`git add -A`/`git add .`, and if a plain `git add <file>` would stage
more than just your two new methods (because the file already had
unrelated uncommitted changes mixed in), use `git diff` to identify
exactly your added lines and stage a minimal, correct commit the same
way Phase 3 Stage 3's Task 2 handled this exact situation (documented in
that stage's task report) — asking for guidance rather than guessing if
it's not straightforward.

```bash
git add application/models/Examgroup_model.php application/controllers/admin/Examgroup.php application/views/admin/examgroup/tenant_exam_results_list.php
git commit -m "feat: add tenant-scoped exam results list method to the real Examgroup controller/model"
```

---

### Task 3: End-to-end verification + safety regression proof

**Files:** none created — this task exercises the full stack built in
Tasks 1-2 against the real, running application and the real
`school_saas` data (already migrated by Phase 2 Stage 5 — no new
migration run needed).

- [ ] **Step 1: Confirm the known test credential is still intact**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT id, email FROM staff WHERE email='rabiachauhan923@gmail.com';"
```
Expected: one row, `id=1`. If the password hash was somehow reset or the
row is gone, STOP and report — do not silently re-create it without
flagging that something unexpected happened to prior stages' test
fixture.

- [ ] **Step 2: Real login → all three real controllers, end to end**

Using a single fixed cookie-jar FILE PATH across all curl calls in ONE
shell script (shell variables do not persist across separate tool-call
invocations in this environment — a documented, previously-hit pitfall
in this exact project):

```bash
CJ=/tmp/p3s4_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/feesforward/tenantFeesList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/examgroup/tenantExamResultsList -w "\n%{http_code}\n"
```

Expected: staff route `200` with `Staff (18 real, tenant-scoped rows)`
(already proven twice — re-confirms the third allowlist entry didn't
break it); fees route `200` with `Student Fee Deposits (699 real,
tenant-scoped rows)` (already proven — re-confirms too); exam route
`200` with `Exam Results (2785 real, tenant-scoped rows)` (matching
Phase 2 Stage 5's real `exam_group_exam_results` count for tenant 25 —
the new proof this task establishes).

- [ ] **Step 3: Confirm the allowlist gate still blocks everything else**

Using the SAME authenticated cookie jar:

```bash
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/admin/dashboard
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/staff
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/feesforward
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/examgroup
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/examresult
```

Expected: all five return `404` — including `admin/examgroup` (the real,
un-gated `index()` action) and `admin/examresult` (a completely
different, unrelated real controller), proving the allowlist is
specific to the three exact new methods across three controllers, never
opening up a whole controller or an unrelated one.

- [ ] **Step 4: Add the credentialed regression test**

Add to `tests/controllers/AdminControllerTenantGateTest.php`:

```php
    public function testTenantScopedSessionReachesAllThreeAllowlistedRoutesAndNothingElse(): void
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

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$examgroupIndexStatus, ] = $this->curlGet('admin/examgroup');
        $this->assertSame(404, $examgroupIndexStatus);

        [$examresultStatus, ] = $this->curlGet('admin/examresult');
        $this->assertSame(404, $examresultStatus);
    }
```

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: Task 1's final count + 1 new test.

- [ ] **Step 6: Commit the test**

```bash
git add tests/controllers/AdminControllerTenantGateTest.php
git commit -m "test: add credentialed end-to-end regression test for the third controller retrofit"
```

(This stage's plan explicitly includes this commit step, unlike Phase 3
Stage 3's plan, which omitted it and required a follow-up fix — lesson
applied.)

- [ ] **Step 7: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`
to add this stage's entry, following the exact style of Stage 3's entry
— including confirming the mechanism now scales to three controllers
with the same one-line-per-stage cost, and noting this is the SECOND
consecutive stage to add "one allowlist entry and one gated method" with
zero new infrastructure needed.

- [ ] **Step 8: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 4 (third real controller retrofit — Examgroup) complete"
```

---

### Final whole-stage review (after Task 3)

Once Task 3 succeeds, dispatch an Opus adversarial review of the whole
stage's final state, following the exact pattern used at the end of
Stage 3: independently attempt to reach non-allowlisted routes with a
tenant-scoped session (choose several beyond the five already tested,
including at least one from the exam module specifically, e.g.
`admin/examresult/pdf` or similar sub-routes if any exist), confirm the
three-entry allowlist didn't introduce any bypass, confirm a normal
(non-tenant) admin session's behavior is provably unaffected by every
edit in this stage, independently re-verify all three routes live with
the known test credential, and confirm the roadmap accurately reflects
that this was again a genuinely small, low-risk stage. Fix any
Critical/Important findings (with independent re-review) before
considering this stage done.
