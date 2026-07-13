# Multi-Tenant Migration — Phase 3 Stage 3: Second Real Controller Retrofit (Feesforward) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prove that Phase 2 Stage 6's real-controller-retrofit mechanism
(the `admin_tenant_id` session gate + one narrow, gated, read-only
method on a real production controller/model) generalizes to a SECOND
real controller without rebuilding any of the gate infrastructure —
exactly as Stage 6's own final review predicted: "future stages only
need to add one entry to the allowlist and one gated method, not
rebuild the gate."

**Architecture:** `Admin_Controller`'s existing single-entry allowlist
check (`application/core/MY_Controller.php:61-69`, currently hardcoded
to `staff`/`tenantstafflist` only) is generalized into a small
associative-array allowlist, with exactly one new entry added:
`feesforward`/`tenantfeeslist`. One new method each on the REAL
`Feesforward.php` controller and `Studentfeemaster_model.php` model
(not new files — additions to the live, shared-by-all-schools files),
using the exact same "explicit `tenant_id` filter, query-builder, one
new minimal method" pattern already proven on `Staff.php`/`Staff_model.php`.
No new gate, no new settings/fixture tables, no `PilotLogin.php` changes
— all of that infrastructure already exists and is reused unchanged; the
already-authenticated `PilotLogin` session (which already carries
`admin_tenant_id`) simply reaches a second allowlisted route.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, curl for live HTTP verification.

## Why `Feesforward.php` and why a brand-new method (read before touching anything)

- **Chosen over `Examgroup.php`** (the other real candidate with
  already-migrated data) specifically for controller-level simplicity:
  `Feesforward.php` is 220 lines with a single `rbac->hasPrivilege()`
  gate and a single `layout/header`/`layout/footer` pair, versus
  `Examgroup.php`'s 1099 lines, 8 `rbac` gates, and 6 header/footer
  pairs — a much smaller surface to reason about for a second proof.
- **A brand-new method, not a retrofit of `Feesforward::index()`
  itself.** `index()`/`findPreviousBalanceFees()` are NOT a simple list
  view — they're a multi-step "carry forward previous session's unpaid
  balance as a new fee" WRITE workflow (culminating in
  `studentfeemaster_model->addPreviousBal()`), backed by
  `Studentfeemaster_model.php` (1525 lines, 28 raw-SQL `$this->db->query()`
  methods) and `Student_model::getPreviousSessionStudent()` (also raw
  SQL with a correlated subquery). None of that is touched by this
  stage. Exactly like Stage 6's `Staff::tenantStaffList()`, this stage
  adds ONE new, independent, READ-ONLY method that queries
  `student_fees_deposite` directly via CodeIgniter query-builder with an
  explicit `tenant_id` filter — the same "add an explicit filter to one
  clean new query" pattern this whole project has used since day one,
  not a retrofit of any existing raw-SQL method.

## Global Constraints

- **Do not modify `application/controllers/Site.php` at all.** Same
  absolute rule as Stage 6, for the same reason (tenant 25 is a live
  school; its real login flow must never be touched by this work).
- Do not modify `application/libraries/Auth.php` or
  `application/libraries/Db_manager.php` — both already correctly gate
  on `admin_tenant_id` from Stage 6, unchanged, and need no further
  changes for a second allowlisted route.
- Do not modify `application/controllers/PilotLogin.php` — the existing
  authenticated session it already produces (real `admin` array +
  `admin_tenant_id`) is sufficient to reach a second allowlisted route;
  no redirect-target changes needed.
- Do not modify any of the other 27 existing methods on
  `Feesforward.php` (`index()`, `findPreviousBalanceFees()`,
  `getPreviousSessionBalanceAmount()`, `findValueExists()`), or any of
  the 28 existing raw-SQL methods on `Studentfeemaster_model.php` —
  this stage only appends one new method to each. Do not modify
  `application/views/admin/feesforward/*` (existing views) — this
  stage adds one new view file, does not touch existing ones.
- Do not modify `tools/multitenant/*` or any merge tool — this stage
  touches no migration tooling at all; the fee data it reads was already
  migrated by Phase 3 Stage 1.
- **The allowlist generalization in `Admin_Controller` must NOT change
  behavior for the existing `staff`/`tenantstafflist` entry** — this is
  non-negotiable and must be covered by an explicit automated test
  proving the pre-existing entry still works exactly as before, not
  just "should be fine because it's still in the array."
- The new `Feesforward::tenantFeesList()` method must independently
  re-check `admin_tenant_id` itself (defense in depth on top of the
  `Admin_Controller` allowlist gate) and `show_404()` if absent — same
  two-layer requirement Stage 6 established, matching this project's
  general pattern of layered safety checks.
- All new PHP must run under PHP 8.1. Use `127.0.0.1`/`root`/empty
  password for local MySQL. Tenant id `25` is reserved for
  `al_hafeez_campus`. MySQL and Apache are already running.
- Verified before writing this plan: `student_fees_deposite` (699 real
  rows for tenant 25), `student_fees_master`, `student_session`,
  `students` are all already migrated (Phase 2 Stages 1/3, Phase 3
  Stage 1) — no new settings/fixture tables are needed this time, unlike
  Stage 6, which discovered `MY_Controller`'s ~100-model autoload chain
  needed `sch_settings`/`languages`/`currencies`/`email_config`/
  `permission_group` before ANY authenticated admin request could
  succeed. That gap is already closed and reused unchanged here.

---

### Task 1: Generalize the `Admin_Controller` allowlist gate to a multi-entry list

**Files:**
- Modify: `application/core/MY_Controller.php:56-76` (`Admin_Controller::__construct()`)
- Test: `tests/controllers/AdminControllerTenantGateTest.php` (extend)

**Interfaces:**
- Produces: when `session->userdata('admin_tenant_id')` is truthy, the
  current controller/method (case-insensitive) is checked against a
  small associative array of allowed pairs, currently `['staff' =>
  'tenantstafflist']`. This task adds `'feesforward' =>
  'tenantfeeslist'`. Any other controller/method still `show_404()`s.
  When `admin_tenant_id` is absent, behavior is byte-for-byte identical
  to today (unchanged from Stage 6).
- Consumed by: Task 2's new `Feesforward::tenantFeesList()` method (must
  pass through this gate to be reachable) and Task 3's verification
  (must confirm the gate now allows TWO routes and still blocks
  everything else).

Read `application/core/MY_Controller.php` in full before editing —
`Admin_Controller::__construct()` currently reads:

```php
    public function __construct()
    {
        parent::__construct();
        $this->auth->is_logged_in();

        if ($this->session->userdata('admin_tenant_id')) {
            $activeController = strtolower($this->router->fetch_class());
            $activeMethod     = strtolower($this->router->fetch_method());
            if ($activeController !== 'staff' || $activeMethod !== 'tenantstafflist') {
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
existing `testTenantScopedSessionReachesTheAllowlistedStaffListAndNothingElse`
test:

```php
    public function testAllowlistGateStillAllowsTheOriginalStaffRouteAfterGeneralization(): void
    {
        // Regression proof for Task 1's generalization: the pre-existing
        // staff/tenantstafflist entry must keep working exactly as before,
        // not just "should still be in the array."
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);
    }
```

(This reuses the existing `curlPostPilotLogin()`/`curlGet()` helpers
already in this file from Stage 6 — no new helper needed.)

- [ ] **Step 2: Run test to verify it currently passes (this is a
  regression baseline, not a RED step — the staff route already works
  today; this test locks in that it must keep working after Step 3's
  edit)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/AdminControllerTenantGateTest.php`
Expected: `OK (4 tests, ...)`.

- [ ] **Step 3: Generalize the allowlist**

Replace `Admin_Controller::__construct()` in `application/core/MY_Controller.php` with:

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

(Only the `if`/`else` single-comparison check becomes an array lookup;
`check_license()` and everything after remains byte-identical.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/AdminControllerTenantGateTest.php`
Expected: `OK (4 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (57 tests, ...)` (unchanged — this task extends an existing
test file, no net-new test file, but DOES add one new test method; if
the count differs from 57 recompute from the actual current baseline
before treating it as a discrepancy — this project has had plan-doc
arithmetic mistakes on table/test counts twice already, always prefer
the actual `phpunit` output over a memorized number).

- [ ] **Step 6: Manual smoke test — confirm the app still boots for an
  ungated request**

Run: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost/web-app/admin/admin/dashboard`
Expected: `307` (unauthenticated redirect to login — NOT a 500), same as
every prior stage's cheapest possible "did I break the constructor
chain" check.

- [ ] **Step 7: Commit**

```bash
git add application/core/MY_Controller.php tests/controllers/AdminControllerTenantGateTest.php
git commit -m "feat: generalize Admin_Controller's tenant allowlist gate to support multiple routes"
```

---

### Task 2: Real `Feesforward.php`/`Studentfeemaster_model.php` tenant-scoped method

**Files:**
- Modify: `application/models/Studentfeemaster_model.php` (add one method)
- Modify: `application/controllers/admin/Feesforward.php` (add one method)
- Create: `application/views/admin/feesforward/tenant_fees_list.php`

**Interfaces:**
- Produces: `Studentfeemaster_model::getTenantScopedFeesList(int $tenantId): array`
  (returns `array<int, array>`, one row per `school_saas.student_fees_deposite`
  row for that tenant) and `Feesforward::tenantFeesList(): void` (renders
  the new minimal view). Consumed by Task 3 (end-to-end verification).

This is the second real instance of the "add an explicit `tenant_id`
filter to one new query, in the real shared model file, without
touching its other existing methods" pattern — proving it generalizes
beyond `Staff_model.php` to a second, much larger and messier model file
(1525 lines, 28 raw-SQL methods) without needing to touch, understand,
or imitate any of that existing raw SQL.

- [ ] **Step 1: Add the model method**

In `application/models/Studentfeemaster_model.php`, add this method
(append near the end of the class, before the closing `}` — do not
reorder or touch any existing method):

```php
    public function getTenantScopedFeesList($tenantId)
    {
        return $this->db->where('tenant_id', $tenantId)->get('student_fees_deposite')->result_array();
    }
```

- [ ] **Step 2: Add the controller method**

In `application/controllers/admin/Feesforward.php`, add this method
(append near the end of the class, before the closing `}` — do not
touch `index()`, `findPreviousBalanceFees()`,
`getPreviousSessionBalanceAmount()`, or `findValueExists()`):

```php
    public function tenantFeesList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $feesList = $this->studentfeemaster_model->getTenantScopedFeesList((int) $tenantId);
        $this->load->view('admin/feesforward/tenant_fees_list', ['feesList' => $feesList]);
    }
```

(This method's own `show_404()` guard is the second, independent layer
on top of Task 1's `Admin_Controller`-level allowlist gate — matching
this plan's Global Constraints on defense in depth.)

- [ ] **Step 3: Add the view**

Create `application/views/admin/feesforward/tenant_fees_list.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Tenant Fees List</title></head>
<body>
<h1>Student Fee Deposits (<?php echo count($feesList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($feesList as $deposit): ?>
    <li>Deposit #<?php echo (int) $deposit['id']; ?> — student_fees_master_id <?php echo (int) $deposit['student_fees_master_id']; ?>, fee_groups_feetype_id <?php echo (int) $deposit['fee_groups_feetype_id']; ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

(Deliberately bare — no `layout/header`/`layout/footer` — per this
stage's stated scope: proving the model/controller layer only, same as
`Staff::tenantStaffList()`'s view. It shows raw FK ids rather than
resolved student/fee-type names, mirroring the minimal ambition of
Stage 6's original proof — a future task could join these to
`student_fees_master`/`student_session`/`students`/`fee_groups_feetype`/
`feetype` for human-readable names, exactly as `PilotFees` already does,
but that join logic is explicitly NOT required to prove THIS stage's
point: that a second real controller can be safely gated and can query
real tenant-scoped data.)

- [ ] **Step 4: Lint the new/changed PHP files**

Run: `"C:\xampp81\php\php.exe" -l application/models/Studentfeemaster_model.php`
Run: `"C:\xampp81\php\php.exe" -l application/controllers/admin/Feesforward.php`
Run: `"C:\xampp81\php\php.exe" -l application/views/admin/feesforward/tenant_fees_list.php`
Expected: `No syntax errors detected` for all three.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: unchanged from Task 1's final count (this task adds no new
automated tests — Task 3 verifies live).

- [ ] **Step 6: Commit**

```bash
git add application/models/Studentfeemaster_model.php application/controllers/admin/Feesforward.php application/views/admin/feesforward/tenant_fees_list.php
git commit -m "feat: add tenant-scoped fees list method to the real Feesforward controller/model"
```

---

### Task 3: End-to-end verification + safety regression proof

**Files:** none created — this task exercises the full stack built in
Tasks 1-2 against the real, running application and the real
`school_saas` data (already migrated by Phase 3 Stage 1 — no new
migration run needed this stage), then adds a credentialed test proving
BOTH allowlisted routes work and everything else is still blocked.

- [ ] **Step 1: Real login → both real controllers, end to end**

Using the SAME known test credential already established in Phase 2
Stage 6 (tenant_id=25, email `rabiachauhan923@gmail.com`, a known test
password set on that one `school_saas`-only staff row — confirm it's
still set; if not, re-establish it exactly as Stage 6's Task 5 did,
documenting that clearly in the task report) and a single fixed
cookie-jar FILE PATH across all curl calls in ONE shell script (shell
variables do not persist across separate tool-call invocations in this
environment — a documented, previously-hit pitfall):

```bash
CJ=/tmp/p3s3_cookiejar.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/feesforward/tenantFeesList -w "\n%{http_code}\n"
```

Expected: the staff-list request returns `200` with `Staff (18 real,
tenant-scoped rows)` (already proven in Stage 6 — this re-confirms
generalizing the gate didn't break it). The fees-list request returns
`200` with `Student Fee Deposits (699 real, tenant-scoped rows)`
(matching Phase 3 Stage 1's real `student_fees_deposite` count for
tenant 25).

- [ ] **Step 2: Confirm the allowlist gate still blocks everything else**

Using the SAME authenticated cookie jar:

```bash
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/admin/dashboard
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/examgroup
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/staff
curl -s -o /dev/null -w "%{http_code}\n" -b "$CJ" http://localhost/web-app/admin/feesforward
```

Expected: all four return `404` — including `admin/feesforward` (the
real, un-gated `index()` action, the write-heavy carry-forward workflow
this stage deliberately never touches), proving the allowlist is
specific to the exact new method, not the whole controller.

- [ ] **Step 3: Add the credentialed regression test**

Add to `tests/controllers/AdminControllerTenantGateTest.php`:

```php
    public function testTenantScopedSessionReachesBothAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$feesforwardIndexStatus, ] = $this->curlGet('admin/feesforward');
        $this->assertSame(404, $feesforwardIndexStatus);
    }
```

- [ ] **Step 4: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: prior count + 1 new test — use the ACTUAL prior count from
Task 1's Step 5 run, not a recomputed guess.

- [ ] **Step 5: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`
to add this stage's entry, following the exact style of Stage 6's entry
— including the explicit confirmation that the allowlist mechanism
generalized to a second route with a one-line array addition and no
gate rebuild, exactly as Stage 6's final review predicted it would.

- [ ] **Step 6: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 3 (second real controller retrofit — Feesforward) complete"
```

---

### Final whole-stage review (after Task 3)

Once Task 3 succeeds, dispatch an Opus adversarial review of the whole
stage's final state, following the exact pattern used at the end of
Stage 6: independently attempt to reach non-allowlisted routes with a
tenant-scoped session (try several beyond the four already tested),
confirm the generalized allowlist didn't introduce any bypass (e.g. a
case-sensitivity or array-key-collision edge case the array form might
have that the original single `if` didn't), confirm a normal
(non-tenant) admin session's behavior is provably unaffected by every
edit in this stage, and confirm the roadmap accurately reflects that
this was a genuinely small, low-risk stage BECAUSE Stage 6 already paid
down the hard infrastructure cost. Fix any Critical/Important findings
(with independent re-review) before considering this stage done.
