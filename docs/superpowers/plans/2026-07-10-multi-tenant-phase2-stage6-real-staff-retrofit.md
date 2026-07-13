# Multi-Tenant Migration — Phase 2 Stage 6: Real Staff Model Retrofit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prove that the REAL, production admin-panel code path — not a
parallel `Pilot*` proof controller — can be safely, narrowly retrofitted
to serve tenant-scoped data from `school_saas` for one pilot tenant
(`al_hafeez_campus`, tenant 25), with a provable guarantee that every
other live school's admin panel is byte-for-byte unaffected and that a
tenant-scoped session cannot reach any other, not-yet-retrofitted admin
controller.

**Architecture:** Two new, narrow gates in shared infrastructure
(`Db_manager`'s per-request connection selection, and `Admin_Controller`'s
constructor) that activate ONLY when a new session key,
`admin_tenant_id`, is present — which nothing except this stage's code
ever sets. One new method each on the REAL `Staff.php` controller and
`Staff_model.php` model (not new files — additions to the live,
shared-by-all-schools files), using the exact "add an explicit
`tenant_id` filter to this one query" pattern the roadmap's very first
locked-in decision named as the eventual strategy for all ~8,000 call
sites — this stage is the first real instance of it. The existing
`PilotLogin.php` (already does real credential verification against
`school_saas`, built in Stage 1) is extended to also populate the real
`admin` session array and the new gating key, then redirects into the
real controller instead of its own proof view.

**Tech Stack:** PHP 8.1.25, CodeIgniter 3.1.13, MariaDB 10.4.32 (XAMPP at
`C:\xampp81`), PHPUnit 10.5, PDO, curl for live HTTP verification.

## Why this is scoped the way it is (read before touching anything)

This stage's scope was deliberately narrowed twice during planning, from
"wire real `Site.php` login" down to what's below — both narrowings are
safety-driven, not laziness, and matter for how you read the tasks:

1. **`Site.php::login()` itself is NOT touched, ever, in this stage.**
   Tenant 25 (`al_hafeez_campus`) is a LIVE school with real staff
   logging in today via the existing per-branch mechanism. If
   `Site.php::login()` itself checked credentials against `school_saas`,
   a real al_hafeez_campus staff member's login could nondeterministically
   land them in either the old (full-featured) admin panel or this
   stage's new (Staff-list-only) one, depending on check ordering —
   a live functional regression for a real customer. Instead, this
   stage extends `PilotLogin.php` (Stage 1, already does real credential
   verification against `school_saas`, already fully isolated from
   `Site.php`) to redirect into the real `Staff.php` controller instead
   of a proof view. `Site.php`'s own login flow is provably unreachable
   from anything this stage adds.
2. **Only ONE new method on `Staff.php`/`Staff_model.php` is added — not
   a retrofit of the real `Staff::index()`/staff-list page.** Reading
   `Staff::index()` in full during planning showed it renders through
   `layout/header`/`layout/footer` (shared chrome with its own,
   unaudited query surface), an `rbac->hasPrivilege()` check, a
   `customfield_model` call, and `staff_model->searchFullText()` (which
   builds raw SQL strings, not query-builder chains — a different
   retrofit shape than anything `TenantScope`/`Tenant_Model` was built
   for). Retrofitting all of that is several stages' worth of new
   problems, discovered only by reading the real code, not knowable
   from the plan alone. This stage instead proves the NARROWEST
   meaningful thing: one new, additive, tested method on the real
   model/controller pair, reachable end-to-end through the real
   authentication and connection-selection machinery, safely gated.
   Retrofitting the real `Staff::index()` page (and the shared chrome
   it depends on) is future work, informed by what this stage proves.

## Global Constraints

- **Do not modify `application/controllers/Site.php` at all.** No
  exceptions. This is the one absolute rule of this stage — see above.
- Do not modify `application/libraries/Auth.php`. Its existing
  `is_logged_in()` behavior (session-key presence + a `staff.is_active`
  lookup against whatever `$this->db` currently is) already works
  correctly for the tenant-scoped path once `Db_manager`'s gate (Task 2)
  has already pointed `$this->db` at `school_saas` — verified during
  planning that `school_saas.staff.id` values are globally unique
  (assigned via `nextId()`+`IdRemapper` in every merge tool to date) and
  `school_saas.staff.is_active` is an `int` matching what `Auth.php`
  already checks (`PilotLogin.php:62`'s identical check against the same
  column already proves the format is compatible).
- Do not modify `tools/multitenant/TenantScope.php`, `IdRemapper.php`,
  `AbstractTenantMerger.php`, `NaturalKeyIdResolver.php`,
  `ClassSectionPairResolver.php`, `StudentSessionIdResolver.php`,
  `application/core/Tenant_Model.php`, or any existing merge tool.
- Do not modify any of the other 61 existing public methods on
  `application/models/Staff_model.php`, or any other method on
  `application/controllers/admin/Staff.php` besides the one new method
  this stage adds. Do not modify `application/views/admin/staff/*`
  (existing views) — this stage adds one new view file, does not touch
  existing ones.
- **The new session key is `admin_tenant_id`** (a new top-level session
  key, confirmed via full-codebase grep during planning to be used
  nowhere today — collision-free with both the real `'admin'` key and
  the pilot scaffolding's `'pilot_admin'`/`'pilot_tenant_id'` keys).
  Every gate in this stage keys off this one flag. Nothing outside this
  stage's own code may ever set it.
- **Every gate must default to today's exact behavior when
  `admin_tenant_id` is absent.** This is non-negotiable and must be
  covered by an explicit automated test in every task that touches
  shared infrastructure (`Db_manager.php`, `MY_Controller.php`) — not
  just "should be fine because of the `if`," an actual test asserting
  the untouched branch's behavior is unchanged.
- A tenant-scoped session (`admin_tenant_id` set) must be structurally
  unable to reach any admin controller/method other than the one this
  stage adds (`Staff::tenantStaffList`) — enforced by an allowlist gate
  in `Admin_Controller`'s constructor (Task 1), not by convention or
  by the new controller method simply not being linked from anywhere.
- Reuse the existing `school_saas_pilot` connection group
  (`application/config/database.php:28`, already points at `school_saas`
  on `127.0.0.1`/`root`/no password) — do not add a new connection group
  for this stage; there is no behavioral reason to duplicate it.
- All new PHP must run under PHP 8.1. Use `127.0.0.1`/`root`/empty
  password for local MySQL. Tenant id `25` is reserved for
  `al_hafeez_campus`. MySQL and Apache are already running.
- The new `Staff::tenantStaffList()` method must independently re-check
  `admin_tenant_id` itself (defense in depth on top of the
  `Admin_Controller` allowlist gate) and `show_404()` if absent — two
  layers, not one, matching this project's established pattern of
  layered safety checks (e.g. `ClassSectionPairResolver`'s and
  `NaturalKeyIdResolver`'s collision detection, `StudentSessionIdResolver`'s
  independently-reviewed key design).

---

### Task 1: `Admin_Controller` allowlist gate

**Files:**
- Modify: `application/core/MY_Controller.php:56-65` (`Admin_Controller::__construct()`)
- Test: `tests/controllers/AdminControllerTenantGateTest.php` (new)

**Interfaces:**
- Produces: when `session->userdata('admin_tenant_id')` is truthy, every
  `Admin_Controller`-derived request is checked against a one-entry
  allowlist (`staff` / `tenantstafflist`, case-insensitive) before any
  further constructor work runs; a non-matching request calls
  `show_404()` and execution stops. When `admin_tenant_id` is absent,
  `Admin_Controller::__construct()` is byte-identical to today.
- Consumed by: Task 4's `PilotLogin`-driven flow (must pass through this
  gate to reach Task 3's new method) and Task 5's verification (must be
  blocked by this gate when targeting anything else).

This is the single most safety-critical piece of this stage: it is the
only thing standing between a tenant-scoped session and every
not-yet-retrofitted admin controller running fully unscoped queries
against the shared `school_saas` database.

Read `application/core/MY_Controller.php` in full before editing —
`Admin_Controller::__construct()` currently reads:

```php
    public function __construct()
    {
        parent::__construct();
        $this->auth->is_logged_in();
        $this->check_license();
        $this->load->library('rbac');
        $this->config->load('app-config');
        $this->config->load('ci-blog');
        $this->config->load('custom_filed-config');
    }
```

(`Rbac::__construct()` was checked during planning and confirmed to run
no query — only loads a config file — so loading it unconditionally
before the gate check is safe regardless of which branch runs.)

- [ ] **Step 1: Write the failing test**

CI3 controllers are awkward to unit-test directly (they depend on the
global `CI` singleton, superglobals, and the full bootstrap). Test this
via a real HTTP request against the live local Apache/XAMPP instance,
using PHPUnit only to assert on the HTTP response — this is a
integration-level test, not a unit test, and belongs in a new
`tests/controllers/` directory (does not exist yet — create it).

Create `tests/controllers/AdminControllerTenantGateTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class AdminControllerTenantGateTest extends TestCase
{
    private const BASE_URL = 'http://localhost/web-app/';

    private string $cookieJar;

    protected function setUp(): void
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_');
    }

    protected function tearDown(): void
    {
        @unlink($this->cookieJar);
    }

    private function curlGet(string $path): array
    {
        $ch = curl_init(self::BASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    }

    public function testUngatedAdminSessionReachesDashboardExactlyAsBefore(): void
    {
        // No admin_tenant_id in play at all -- this proves Task 1's edit
        // introduced zero behavior change for a request that never sets
        // the new session key. We can't log in as a real school here
        // (no test credentials), so instead we confirm the UNAUTHENTICATED
        // redirect-to-login behavior is unchanged -- the earliest
        // observable behavior of Admin_Controller's constructor chain,
        // and the one most likely to regress if the gate were placed
        // incorrectly (e.g. before the auth check instead of after).
        [$status, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertContains($status, [200, 302]);
    }
}
```

(This first test is intentionally minimal — it only proves the
unauthenticated path is unaffected, because a full "log in as a real
school" test needs real credentials this test suite doesn't have and
shouldn't fabricate against a live per-branch database. Task 5 adds the
real, credentialed, end-to-end tenant-path tests once `PilotLogin` is
wired in Task 4.)

- [ ] **Step 2: Run test to verify it currently passes (this is a smoke
  test of the unmodified baseline, not a RED step — there is no new
  observable behavior to assert failing yet, since the allowlist gate
  only activates for a session key nothing sets today)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/AdminControllerTenantGateTest.php`
Expected: `OK (1 test, ...)` — confirms curl/Apache/the base URL work
before you rely on them for the real edit's verification.

- [ ] **Step 3: Implement the allowlist gate**

Replace `Admin_Controller::__construct()` in `application/core/MY_Controller.php` with:

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

(`show_404()` is a CI3 global helper that ultimately calls `exit()` —
execution does not continue past it; the `return;` immediately after is
defensive/for-readability, not load-bearing, but keep it.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/AdminControllerTenantGateTest.php`
Expected: `OK (1 test, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (46 tests, ...)` (45 prior + 1 new).

- [ ] **Step 6: Manual smoke test — confirm the app still boots for an
  ungated request**

Run: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost/web-app/admin/admin/dashboard`
Expected: `302` (redirect to login, unauthenticated) — NOT a 500 or a
PHP fatal error page. This is the cheapest possible proof that editing
`Admin_Controller::__construct()` didn't break the constructor chain for
every admin controller in the app.

- [ ] **Step 7: Commit**

```bash
git add application/core/MY_Controller.php tests/controllers/AdminControllerTenantGateTest.php
git commit -m "feat: add tenant-session allowlist gate to Admin_Controller"
```

---

### Task 2: `Db_manager` connection gate

**Files:**
- Modify: `application/libraries/Db_manager.php`
- Test: `tests/controllers/AdminControllerTenantGateTest.php` (extend)

**Interfaces:**
- Produces: when `session->userdata('admin_tenant_id')` is truthy,
  `Db_manager::__construct()` connects `$this->CI->db` to the
  `school_saas_pilot` connection group instead of reading
  `admin['db_array']['db_group']`. When `admin_tenant_id` is absent,
  behavior is byte-identical to today (including the existing `student`
  and neither-admin-nor-student branches, both untouched).
- Consumed by: Task 3's new model method (needs `$this->db` already
  pointed at `school_saas` to work), Task 4/5's real end-to-end flow.

Read `application/libraries/Db_manager.php` in full before editing — it
currently reads:

```php
    public function __construct()
    {
         $this->CI = &get_instance();

        if ($this->CI->session->has_userdata('admin')) {

            $database_session = $this->CI->session->userdata('admin');
            $database_group   = $database_session['db_array']['db_group'];

            $this->CI->db=$this->CI->load->database($database_group, TRUE);
        } elseif ($this->CI->session->has_userdata('student')) {

            $database_session = $this->CI->session->userdata('student');
            $database_group   = isset($database_session['db_array']['db_group']) ? $database_session['db_array']['db_group'] : 'default';

            $this->CI->db=$this->CI->load->database($database_group, TRUE);
        } else {

            $this->CI->db=$this->CI->load->database('default', TRUE);
        }

    }
```

- [ ] **Step 1: Add the failing test**

Add to `tests/controllers/AdminControllerTenantGateTest.php`, after the
existing test method:

```php
    public function testUngatedStudentAndDefaultSessionPathsAreUnaffected(): void
    {
        // No admin_tenant_id, no admin session at all -- exercises
        // Db_manager's third (neither-admin-nor-student) branch, which
        // Task 2's edit must leave completely untouched. A bare request
        // to a public controller is enough to prove the app still
        // boots and connects to its default database correctly.
        [$status, ] = $this->curlGet('site/login');
        $this->assertSame(200, $status);
    }
```

- [ ] **Step 2: Run test to verify it currently passes (baseline smoke test)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/AdminControllerTenantGateTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 3: Implement the connection gate**

Replace `Db_manager::__construct()` in `application/libraries/Db_manager.php` with:

```php
    public function __construct()
    {
         $this->CI = &get_instance();

        if ($this->CI->session->has_userdata('admin')) {

            if ($this->CI->session->userdata('admin_tenant_id')) {
                $this->CI->db = $this->CI->load->database('school_saas_pilot', TRUE);
            } else {
                $database_session = $this->CI->session->userdata('admin');
                $database_group   = $database_session['db_array']['db_group'];

                $this->CI->db=$this->CI->load->database($database_group, TRUE);
            }
        } elseif ($this->CI->session->has_userdata('student')) {

            $database_session = $this->CI->session->userdata('student');
            $database_group   = isset($database_session['db_array']['db_group']) ? $database_session['db_array']['db_group'] : 'default';

            $this->CI->db=$this->CI->load->database($database_group, TRUE);
        } else {

            $this->CI->db=$this->CI->load->database('default', TRUE);
        }

    }
```

(Only the `has_userdata('admin')` branch gained an inner `if`/`else`;
the `student` and default branches are byte-identical to before —
diff them against the Step-0 content above to confirm before moving on.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/AdminControllerTenantGateTest.php`
Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (47 tests, ...)` (46 prior + 1 new — this task extends
the same test file, no net-new test count).

- [ ] **Step 6: Commit**

```bash
git add application/libraries/Db_manager.php tests/controllers/AdminControllerTenantGateTest.php
git commit -m "feat: add tenant-session connection gate to Db_manager"
```

---

### Task 3: Real `Staff.php`/`Staff_model.php` tenant-scoped method

**Files:**
- Modify: `application/models/Staff_model.php` (add one method)
- Modify: `application/controllers/admin/Staff.php` (add one method)
- Create: `application/views/admin/staff/tenant_staff_list.php`
- Test: `tests/models/StaffModelTenantScopeTest.php` (new)

**Interfaces:**
- Produces: `Staff_model::getTenantScopedStaffList(int $tenantId): array`
  (returns `array<int, array>`, one row per `school_saas.staff` row for
  that tenant) and `Staff::tenantStaffList(): void` (renders the new
  minimal view). Consumed by Task 4 (redirect target) and Task 5
  (end-to-end verification).

This is the first real instance of the "add an explicit `tenant_id`
filter to one query, in the real shared model file, without touching
its other 61 methods" pattern — the actual strategy the roadmap named
as the eventual plan for the whole codebase, being executed for real
for the first time.

- [ ] **Step 1: Write the failing test**

`Staff_model` extends `MY_Model`, which is only bootstrapped as a side
effect of CI3's model-loading bootstrap (the same CI3 gotcha documented
in every `Pilot*` controller in this project). Testing it in true
isolation (no CI3 bootstrap) isn't practical for this one query method,
so test it the same way `PilotLogin.php`'s underlying data was
originally proven in Stage 1: a direct PDO query against the real
`school_saas` database, asserting the SQL the new model method will run
produces the expected real result — then Task 5's live HTTP test proves
the actual CI3 method wraps that SQL correctly end to end.

Create `tests/models/StaffModelTenantScopeTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class StaffModelTenantScopeTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testTenantScopedQueryMatchesExactlyOneTenantsStaffRows(): void
    {
        // Mirrors exactly the query Staff_model::getTenantScopedStaffList()
        // will run (WHERE tenant_id = ? against `staff`). Tenant 25's
        // real staff count (18) was established and verified during
        // Stage 1 -- this test re-derives it independently rather than
        // trusting that number, so it also catches any drift in the
        // real data since then.
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM staff WHERE tenant_id = :tenant_id');
        $stmt->execute([':tenant_id' => 25]);
        $count = (int) $stmt->fetchColumn();

        $this->assertGreaterThan(0, $count);

        // No other tenant currently has data -- this assertion documents
        // that fact and will need updating (not silently pass/fail
        // differently) once a second tenant is migrated in Phase 5.
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM staff WHERE tenant_id != :tenant_id');
        $stmt->execute([':tenant_id' => 25]);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }
}
```

- [ ] **Step 2: Run test to verify it passes against real data (there is
  no RED step here — the data already exists from Stage 1; this test
  documents and locks in the expected real-data shape before the CI3
  method is written)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/models/StaffModelTenantScopeTest.php`
Expected: `OK (1 test, ...)`.

- [ ] **Step 3: Add the model method**

In `application/models/Staff_model.php`, add this method (append near
the end of the class, before the closing `}` — do not reorder or touch
any existing method):

```php
    public function getTenantScopedStaffList($tenantId)
    {
        return $this->db->where('tenant_id', $tenantId)->get('staff')->result_array();
    }
```

- [ ] **Step 4: Add the controller method**

In `application/controllers/admin/Staff.php`, add this method (append
near the end of the class, before the closing `}` — do not touch
`index()` or any other existing method):

```php
    public function tenantStaffList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $staffList = $this->staff_model->getTenantScopedStaffList((int) $tenantId);
        $this->load->view('admin/staff/tenant_staff_list', ['staffList' => $staffList]);
    }
```

(This method's own `show_404()` guard is a second, independent layer on
top of Task 1's `Admin_Controller`-level allowlist gate — see this
plan's Global Constraints on defense in depth.)

- [ ] **Step 5: Add the view**

Create `application/views/admin/staff/tenant_staff_list.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Tenant Staff List</title></head>
<body>
<h1>Staff (<?php echo count($staffList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($staffList as $staff): ?>
    <li><?php echo htmlspecialchars($staff['name'] . ' ' . ($staff['surname'] ?? '')); ?> — <?php echo htmlspecialchars($staff['email']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
```

(Deliberately bare — no `layout/header`/`layout/footer` — per this
plan's stated scope: proving the model/controller layer, not the shared
chrome.)

- [ ] **Step 6: Lint the new/changed PHP files**

Run: `"C:\xampp81\php\php.exe" -l application/models/Staff_model.php`
Run: `"C:\xampp81\php\php.exe" -l application/controllers/admin/Staff.php`
Run: `"C:\xampp81\php\php.exe" -l application/views/admin/staff/tenant_staff_list.php`
Expected: `No syntax errors detected` for all three.

- [ ] **Step 7: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (48 tests, ...)` (47 prior + 1 new).

- [ ] **Step 8: Commit**

```bash
git add application/models/Staff_model.php application/controllers/admin/Staff.php application/views/admin/staff/tenant_staff_list.php tests/models/StaffModelTenantScopeTest.php
git commit -m "feat: add tenant-scoped staff list method to the real Staff controller/model"
```

---

### Task 4: Wire `PilotLogin` into the real controller

**Files:**
- Modify: `application/controllers/PilotLogin.php`

**Interfaces:**
- Modifies `PilotLogin::login()`'s post-authentication behavior only —
  the pre-authentication checks (email/password/is_active) are
  unchanged. Adds the real `admin` session array + `admin_tenant_id`,
  redirects to `staff/tenantStaffList` instead of `pilotlogin/dashboard`.
  The existing `pilotlogin/dashboard` route/view remains present and
  reachable (not deleted) — see Step 1's rationale.

- [ ] **Step 1: Read the current file and identify the exact edit**

Read `application/controllers/PilotLogin.php` in full (101 lines). The
edit is entirely inside `login()`, replacing only its last two lines:

```php
        $this->session->set_userdata('pilot_admin', $sessionData);
        redirect('pilotlogin/dashboard');
```

Everything before this (email/password checks, `is_active` check,
`staff_roles`/`roles` lookups, `$sessionData` construction) is
unchanged. Do not touch `dashboard()` — it stays exactly as is, so the
original Stage 1 proof page remains independently reachable (useful for
debugging: if something in the new redirect chain breaks, hitting
`pilotlogin/dashboard` directly still proves whether the underlying
`school_saas` auth data itself is intact, isolating whether a failure is
in this stage's new code or in the data).

- [ ] **Step 2: Implement**

Replace those two lines with:

```php
        $this->session->set_userdata('pilot_admin', $sessionData);

        $this->session->set_userdata('admin', [
            'id' => $staff['id'],
            'username' => $sessionData['username'],
            'email' => $staff['email'],
            'roles' => $staff['id'],
            'language' => ['language' => 'English'],
            'db_array' => ['base_url' => '', 'folder_path' => '', 'db_group' => 'school_saas_pilot'],
        ]);
        $this->session->set_userdata('admin_tenant_id', $tenantId);

        redirect('staff/tenantStaffList');
```

(`'language' => ['language' => 'English']` matches the exact shape
`MY_Controller.php:28` reads — `$admin['language']['language']` — and
`'English'` is confirmed to be a real, existing language directory
(`application/language/English/`). `'db_array'` is populated for
structural completeness/consistency with `Site.php`'s shape, even though
Task 2's `Db_manager` gate bypasses reading it entirely once
`admin_tenant_id` is set — nothing else is known to read `db_array`
without going through `Db_manager`, but populating it costs nothing and
avoids a latent `undefined array key` if that assumption is ever wrong.)

- [ ] **Step 3: Lint**

Run: `"C:\xampp81\php\php.exe" -l application/controllers/PilotLogin.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Run the full suite (regression check only — this task
  adds no new automated tests; Task 5 verifies this change live)**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (48 tests, ...)` (unchanged from Task 3).

**Post-Task-4 fix (found by the task reviewer before any live
verification ran):** the `redirect('staff/tenantStaffList')` call above
is wrong — `Staff.php` lives at `application/controllers/admin/Staff.php`,
not `application/controllers/Staff.php`, so a bare `staff/tenantStaffList`
URL never resolves to it. CI3's router only descends into the `admin/`
subdirectory when the URL's first segment is literally `admin`; a
top-level `staff/...` URL falls through to `404_override`
(`welcome/show_404`), which in this app redirects on to `site/userlogin`
rather than rendering a bare 404 — so the failure was silently masked as
"yet another redirect," not an obvious error, which is exactly why this
was worth catching in review rather than trusting the brief's literal
text. Independently confirmed live: `GET /admin/staff/tenantStaffList`
(unauthenticated) returns the expected `307` redirect to login — proving
that URL resolves to a real, valid, gated controller/method — while the
bare `GET /staff/tenantStaffList` also returns `307` but to a different,
unrelated destination (`site/userlogin` via the 404 handler), confirming
it never reaches `Staff::tenantStaffList()` at all. Task 1's allowlist
gate itself needed no change — `fetch_class()`/`fetch_method()` return
`staff`/`tenantstafflist` regardless of the `admin/` URL prefix, since
that prefix is a directory, not part of the class name.

- [ ] **Fix Step 1: Correct the redirect target**

In `application/controllers/PilotLogin.php`, change the last line of
`login()` from:

```php
        redirect('staff/tenantStaffList');
```

to:

```php
        redirect('admin/staff/tenantStaffList');
```

(Nothing else in the file changes.)

- [ ] **Fix Step 2: Verify live**

```bash
COOKIEJAR=$(mktemp)
curl -s -c "$COOKIEJAR" -b "$COOKIEJAR" -o /dev/null -w "%{http_code}\n" http://localhost/web-app/admin/staff/tenantStaffList
```
Expected: `307` (redirect to login — unauthenticated, but this now
proves the URL resolves to the real, gated controller; Task 5 proves the
authenticated path reaches it for real).

- [ ] **Fix Step 3: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (48 tests, ...)` (unchanged — this fix touches no tests).

- [ ] **Fix Step 4: Commit**

```bash
git add application/controllers/PilotLogin.php
git commit -m "fix: correct PilotLogin's redirect target to admin/staff/tenantStaffList"
```

**Post-Task-4 fix, round 2 (found while running Task 5 against a real
authenticated session — the first time anything exercised the full
`MY_Controller` autoload chain for this flow, since every prior
verification only checked the unauthenticated redirect):** a real login
via the fixed redirect target reached `admin/staff/tenantStaffList`, but
the response was `500 Internal Server Error`, not the expected staff
list. Root cause, confirmed by reading the actual code (not guessed):
`PilotLogin.php`'s `admin` session array sets `'roles' => $staff['id']`
— a bare integer. But `MY_Controller::__construct()` unconditionally
autoloads `staff_model` (`application/core/MY_Controller.php:19`), whose
own constructor calls `Customlib::getStaffRole()`
(`application/libraries/Customlib.php:940-949`), which does
`$role_key = key($admin['roles'])` — `key()` requires an array argument;
PHP 8.1 throws a `TypeError` on an int, uncaught → 500. This 500 fires
inside `MY_Controller::__construct()`, which runs BEFORE
`Admin_Controller::__construct()`'s Task-1 allowlist gate ever executes
— meaning the crash also fully masked whether the gate correctly blocks
`admin/admin/dashboard`, `admin/examgroup`, and `admin/staff` (all three
also 500'd, not 404'd, so Task 5's Step 2 could not actually prove
anything about the gate on this attempt). This defect originated in this
plan's own Task 4 Step 2 code block (written during planning, before the
real `Customlib::getStaffRole()`/`Staff_model::checkLogin()` shape
requirement was checked against actual usage) and survived Task 4's two
review rounds because both only exercised the unauthenticated path — the
gap Task 5 exists to close.

The correct shape, matching the real login flow's own
`Staff_model::checkLogin()` (`application/models/Staff_model.php:757`:
`$record->roles = array($roles[0]->name => $roles[0]->role_id);`), is an
associative array `[roleName => roleId]` — data `PilotLogin::login()`
already computes a few lines earlier (`$roleName`, `$staffRoleRows[0]['role_id']`)
for the `pilot_admin` session's `'role'` key, just never reused for the
real `admin` array. A secondary, non-fatal warning
(`Customlib::superadmin_visible()`, line 624, reads
`$admin['superadmin_restriction']`, absent from the session array) was
also found by the same live request — not fatal on its own, but worth
closing at the same time since it's the same class of "session array
shape doesn't match what shared library code expects" gap.

- [ ] **Fix Step 1: Correct the `admin['roles']` shape and add the
  missing `superadmin_restriction` key**

In `application/controllers/PilotLogin.php`, replace:

```php
        $this->session->set_userdata('admin', [
            'id' => $staff['id'],
            'username' => $sessionData['username'],
            'email' => $staff['email'],
            'roles' => $staff['id'],
            'language' => ['language' => 'English'],
            'db_array' => ['base_url' => '', 'folder_path' => '', 'db_group' => 'school_saas_pilot'],
        ]);
```

with:

```php
        $roleId = ($roleName !== 'Unknown' && count($staffRoleRows) === 1) ? $staffRoleRows[0]['role_id'] : null;

        $this->session->set_userdata('admin', [
            'id' => $staff['id'],
            'username' => $sessionData['username'],
            'email' => $staff['email'],
            'roles' => [$roleName => $roleId],
            'language' => ['language' => 'English'],
            'db_array' => ['base_url' => '', 'folder_path' => '', 'db_group' => 'school_saas_pilot'],
            'superadmin_restriction' => 0,
        ]);
```

(`$roleName` and `$staffRoleRows` are the same local variables already
computed a few lines earlier in this method — no new lookups needed.
`[$roleName => $roleId]` matches `Customlib::getStaffRole()`'s
`key($admin['roles'])` usage regardless of whether `$roleId` is `null`
— `key()` only reads the array's first key, not its value.)

- [ ] **Fix Step 2: Verify live**

Re-run the same real-credential login → `admin/staff/tenantStaffList`
request from this task's Step 1 (a known test password already set on
one real `school_saas` tenant-25 staff row for this exact purpose — see
Task 5's brief for how that was established). Expected: `200`, page
titled "Tenant Staff List", `<h1>Staff (18 real, tenant-scoped rows)</h1>`.
Then re-run Step 2's three blocked-route checks
(`admin/admin/dashboard`, `admin/examgroup`, `admin/staff`) with the
SAME authenticated cookie jar — expected: all three now `404` (not 500),
proving the allowlist gate is actually reachable and actually blocks
them, which the crash previously prevented from being provable.

- [ ] **Fix Step 3: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (48 tests, ...)` (unchanged — this fix touches no test
files; Task 5 adds the credentialed test afterward).

- [ ] **Fix Step 4: Commit**

```bash
git add application/controllers/PilotLogin.php
git commit -m "fix: shape PilotLogin's admin session roles as Customlib/Rbac expect, add superadmin_restriction default"
```

---

### Task 5: End-to-end verification + safety regression proof

**Files:** none created — this task exercises the full stack built in
Tasks 1-4 against the real, running application and the real
`al_hafeez_campus`/`school_saas` data, then adds the credentialed tests
Task 1 deferred.

- [ ] **Step 1: Real login → real controller, end to end**

```bash
COOKIEJAR=$(mktemp)
curl -s -c "$COOKIEJAR" -b "$COOKIEJAR" -X POST http://localhost/web-app/pilotlogin/login \
  -d "tenant_id=25&email=<a real al_hafeez_campus staff email from school_saas.staff, tenant_id=25>&password=<that staff member's real password>"
curl -s -c "$COOKIEJAR" -b "$COOKIEJAR" http://localhost/web-app/admin/staff/tenantStaffList
```

(Look up a real tenant-25 staff email via
`"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT email FROM staff WHERE tenant_id=25 LIMIT 1;"`
— the real password is only known to whoever owns that account in
`al_hafeez_campus`'s original system; if it's not available, use
`PilotStudents::login_as` style direct session manipulation instead:
confirm with the plan owner before fabricating or resetting a real
account's password, since that account belongs to a live school.)

Expected: the second `curl` returns HTTP 200, an HTML page titled
"Tenant Staff List", with a `<h1>` reading `Staff (18 real, tenant-scoped
rows)` (18 is Stage 1's already-verified real count for tenant 25 — if
it differs, treat as a real discrepancy to investigate before
continuing, the same discipline as every prior stage's Task 5).

- [ ] **Step 2: Confirm the allowlist gate blocks everything else**

Using the SAME authenticated cookie jar from Step 1:

```bash
curl -s -o /dev/null -w "%{http_code}\n" -b "$COOKIEJAR" http://localhost/web-app/admin/admin/dashboard
curl -s -o /dev/null -w "%{http_code}\n" -b "$COOKIEJAR" http://localhost/web-app/admin/examgroup
curl -s -o /dev/null -w "%{http_code}\n" -b "$COOKIEJAR" http://localhost/web-app/admin/staff
```

Expected: all three return `404`. This is the single most important
assertion in this stage — it proves a tenant-scoped session cannot reach
any not-yet-retrofitted admin surface, even though `$this->db` for that
session is now pointed at the shared `school_saas` database.

- [ ] **Step 3: Add the credentialed regression tests Task 1 deferred**

Add to `tests/controllers/AdminControllerTenantGateTest.php`:

```php
    public function testTenantScopedSessionReachesTheAllowlistedStaffListAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$examgroupStatus, ] = $this->curlGet('admin/examgroup');
        $this->assertSame(404, $examgroupStatus);
    }

    private function curlPostPilotLogin(): array
    {
        $ch = curl_init(self::BASE_URL . 'pilotlogin/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'tenant_id' => 25,
                'email' => '<fill in with the same real tenant-25 staff email used in Step 1>',
                'password' => '<fill in with the same real password used in Step 1>',
            ]),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    }
```

(The email/password placeholders must be filled in with the same real
credentials used in Step 1 before this test is committed — do not
commit literal placeholder text. If real credentials cannot be used in
an automated, repeatable test, downgrade this to a manually-documented
verification in the task report instead of a committed test, and say so
explicitly rather than committing a test that silently can't run.)

- [ ] **Step 4: Run the full suite**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: `OK (49 tests, ...)` (48 prior + 1 new), assuming Step 3's test
could be committed with real, working credentials.

- [ ] **Step 5: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`
to add this stage's entry, following the exact style of Stages 1-5 —
including the two scope-narrowing decisions from this plan's opening
section (not touching `Site.php`, not retrofitting the full
`Staff::index()` page), real verification results, and an explicit note
that the allowlist-gate mechanism (Task 1) is now available for any
future stage that retrofits another real controller — future stages
only need to add one entry to the allowlist and one gated method, not
rebuild the gate.

- [ ] **Step 6: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 2 Stage 6 (real Staff model retrofit) complete"
```

---

### Final whole-stage review (after Task 5)

Once Task 5 succeeds, dispatch an Opus adversarial review of the whole
stage's final state — this stage carries materially higher risk than
Stages 1-5 (it is the first stage to modify code in the LIVE admin
panel's shared execution path, even though gated), so the final review
should specifically:
- Independently attempt to reach a non-allowlisted admin controller with
  a tenant-scoped session, from scratch, not trusting Task 5's report.
- Independently confirm a normal (non-tenant) admin session's behavior
  is provably unaffected by every one of this stage's edits (`Db_manager`,
  `MY_Controller`, `Staff.php`, `Staff_model.php`) — ideally by testing
  against one of the other 5 live schools' real login if that can be
  done safely and with the account owner's knowledge, or at minimum by
  full line-by-line diff confirmation that every untouched branch is
  byte-identical to pre-stage `git show`.
- Check whether the allowlist gate's `show_404()` could be bypassed by
  any CI3 routing quirk (case sensitivity, URI segment aliases, `_remap()`
  methods, or a second route to the same controller/method under a
  different name) that `strtolower($this->router->fetch_class())`/
  `fetch_method()` might not normalize correctly.
- Confirm `PilotLogin.php`'s new session-population code cannot be
  reached or spoofed to fabricate an `admin_tenant_id` for a tenant that
  doesn't match the authenticated `staff` row (i.e., the `tenant_id`
  driving the gate comes from server-verified data, not client input) —
  re-read `PilotLogin::login()` Step 1-2 closely: `$tenantId` is taken
  from `$this->input->post('tenant_id')` BEFORE the credential check
  (line 44, used at line 46 to scope the `tenantGetAll('staff', ['email'
  => $email])` lookup) — confirm this can't be used to authenticate as
  tenant 25's staff while claiming a different tenant id, or vice versa.
Fix any Critical/Important findings (with independent re-review) before
considering Stage 6 done.
