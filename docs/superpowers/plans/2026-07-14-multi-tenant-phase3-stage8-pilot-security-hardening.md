# Phase 3 Stage 8 — Pilot Proof-Harness Security Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close two pieces of logged security debt in the `Pilot*` proof-harness controllers: (1) an unauthenticated-tenant-selection surface (both the documented `PilotStudents::login_as()` backdoor and a newly-discovered ordering bug in `PilotLogin::login()` that leaves `pilot_tenant_id` set in session even after a failed credential check), and (2) the entire `Pilot*` surface being reachable in any deployment, not just local development — where the test-suite's committed real credential would otherwise matter.

**Architecture:** A new, framework-agnostic, directly-unit-testable `PilotAccessGate` class (`tools/multitenant/PilotAccessGate.php`) holds the pure environment-check logic — `isAllowed(string $environment): bool` — matching the established pattern of `ShadowLoginVerifier` (Phase 3 Stage 5). A new thin CI3 base controller, `Pilot_Controller` (`application/core/Pilot_Controller.php`), calls it in its constructor and `show_404()`s if not allowed; all 8 `Pilot*` controllers switch from `extends CI_Controller` to `extends Pilot_Controller` (one line each), so the gate is enforced systemically — a future 9th `Pilot*` controller inherits the gate automatically instead of needing its own copy. Separately, `PilotLogin::login()` gets a minimal ordering fix: `pilot_tenant_id` is explicitly unset on every failure path, so no session state survives a failed login attempt. `PilotStudents::login_as()` — a pure unauthenticated backdoor with no test or doc dependency — is deleted outright.

**Tech Stack:** PHP 8.1, CodeIgniter 3.1.13, PHPUnit 10.5 (unchanged).

## Global Constraints

- **This stage does not touch `index.php`** (the CI3 front controller that hardcodes `define('ENVIRONMENT', 'development')`) or any real per-branch school data. The fix must work correctly under the app's actual current `ENVIRONMENT` value without requiring that value to change.
- **This stage does not touch `Admin_Controller`'s allowlist gate, `Db_manager`, or any of the 5 already-retrofitted real controllers** (staff, feesforward, examgroup, stuattendence, leaverequest) — this is exclusively about the `Pilot*` proof-harness surface (`PilotStudents`, `PilotLogin`, `PilotClasses`, `PilotAttendance`, `PilotExam`, `PilotFees`, `PilotHr`, `PilotStudentSessions`) and one new supporting class.
- **`PilotAccessGate` must be framework-agnostic** — a plain PHP class taking a string, no CI3 dependency, directly unit-testable without a running app or database (it has none of its own state to test against).
- **Every one of the 8 `Pilot*` controller files changes by exactly one line** (`extends CI_Controller` → `extends Pilot_Controller`), plus each gets one new `require_once` line at the top of the file (needed because PHP must have the parent class defined before the `class X extends Pilot_Controller` declaration is parsed — this cannot live inside a constructor, unlike `Tenant_Model`'s existing `require_once`, which only needs to run before `Tenant_Model` is *instantiated*, not before any class *extends* it).
- **The `PilotLogin::login()` fix must not change the successful-login path at all** — same `pilot_tenant_id`, `pilot_admin`, `admin_tenant_id` session writes, same redirect, same real-data behavior for the known test credential (tenant_id=25, email `rabiachauhan923@gmail.com`, password `TestVerify123!`).
- **`PilotStudents::login_as()` has zero test or documentation dependency** — confirmed via `grep -rln "login_as" tests/ application/` before this plan was written (only appears in `PilotStudents.php` itself, `PilotLogin.php`'s own comments, and historical stage-plan prose referencing it as the thing `PilotLogin` was built to replace). Safe to delete outright, not deprecate.
- **Live verification of the environment gate is necessarily limited to a unit test**, since `ENVIRONMENT` is a PHP constant defined once in `index.php` and cannot be flipped per-request without touching the live front controller (out of scope, per the first constraint above). The plan proves `PilotAccessGate::isAllowed('production') === false` and `isAllowed('development') === true` directly, and separately proves live, over HTTP, that all 8 `Pilot*` controllers remain reachable exactly as before under the app's actual current `ENVIRONMENT` value — it does not attempt to prove a live 404 under a simulated production environment.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: `PilotAccessGate` + `Pilot_Controller`, wired into all 8 Pilot controllers

**Files:**
- Create: `tools/multitenant/PilotAccessGate.php`
- Test: `tests/tools/multitenant/PilotAccessGateTest.php`
- Create: `application/core/Pilot_Controller.php`
- Modify: `application/controllers/PilotStudents.php`, `PilotLogin.php`, `PilotClasses.php`, `PilotAttendance.php`, `PilotExam.php`, `PilotFees.php`, `PilotHr.php`, `PilotStudentSessions.php` (one line each, plus one new `require_once` line each)

**Interfaces:**
- Produces: `PilotAccessGate::isAllowed(string $environment): bool` — pure, static, no side effects. `Pilot_Controller` (a `class ... extends CI_Controller`) — any class extending it inherits a constructor that 404s unless `ENVIRONMENT === 'development'`.
- Consumes: nothing from other tasks — this is the first task.

- [ ] **Step 1: Write the failing test for the pure gate logic**

Create `tests/tools/multitenant/PilotAccessGateTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class PilotAccessGateTest extends TestCase
{
    public function testDevelopmentIsAllowed(): void
    {
        $this->assertTrue(PilotAccessGate::isAllowed('development'));
    }

    public function testProductionIsNotAllowed(): void
    {
        $this->assertFalse(PilotAccessGate::isAllowed('production'));
    }

    public function testTestingIsNotAllowed(): void
    {
        $this->assertFalse(PilotAccessGate::isAllowed('testing'));
    }

    public function testEmptyStringIsNotAllowed(): void
    {
        $this->assertFalse(PilotAccessGate::isAllowed(''));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/PilotAccessGateTest.php`
Expected: FAIL — `Class "PilotAccessGate" not found`.

- [ ] **Step 3: Write the implementation**

Create `tools/multitenant/PilotAccessGate.php`:

```php
<?php

final class PilotAccessGate
{
    public static function isAllowed(string $environment): bool
    {
        return $environment === 'development';
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/PilotAccessGateTest.php`
Expected: `OK (4 tests, 4 assertions)`.

- [ ] **Step 5: Create the CI3 base controller**

Create `application/core/Pilot_Controller.php`:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . '../tools/multitenant/PilotAccessGate.php';

class Pilot_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!PilotAccessGate::isAllowed(ENVIRONMENT)) {
            show_404();
        }
    }
}
```

- [ ] **Step 6: Lint it**

```bash
"C:\xampp81\php\php.exe" -l application/core/Pilot_Controller.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 7: Switch all 8 Pilot controllers to extend it**

For each of `PilotStudents.php`, `PilotLogin.php`, `PilotClasses.php`, `PilotAttendance.php`, `PilotExam.php`, `PilotFees.php`, `PilotHr.php`, `PilotStudentSessions.php` in `application/controllers/`, make exactly two changes:

1. Add this line immediately after the existing `defined('BASEPATH') or exit(...)` line (before the class declaration):
```php
require_once APPPATH . 'core/Pilot_Controller.php';
```

2. Change the class declaration line from:
```php
class PilotStudents extends CI_Controller
```
to:
```php
class PilotStudents extends Pilot_Controller
```
(substituting the real class name for each file — `PilotLogin`, `PilotClasses`, etc.)

Do not touch anything else in any of these 8 files — no constructor bodies, no other logic.

- [ ] **Step 8: Lint all 8 files**

```bash
for f in application/controllers/PilotStudents.php application/controllers/PilotLogin.php application/controllers/PilotClasses.php application/controllers/PilotAttendance.php application/controllers/PilotExam.php application/controllers/PilotFees.php application/controllers/PilotHr.php application/controllers/PilotStudentSessions.php; do "C:\xampp81\php\php.exe" -l "$f"; done
```

Expected: `No syntax errors detected` for all 8.

- [ ] **Step 9: Live-verify all 8 Pilot controllers still work exactly as before (ENVIRONMENT is 'development' in this app today, so nothing should be blocked)**

Apache/MySQL should already be running. In one script, using a single cookie jar:

```bash
CJ=/tmp/p3s8_task1_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!" -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilotstudents/index -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilotclasses/index -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilotattendance/index -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilotexam/index -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilotfees/index -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilothr/index -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilotstudentsessions/index -w "\n%{http_code}\n"
```

Expected: all 8 return `200` with their usual real-data bodies (unchanged from before this stage — this is a pure regression check, not a new capability). Also re-run the real-controller regression suite to confirm nothing about the 5 already-retrofitted controllers changed:

```bash
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
```

Expected: `200`, "Staff (18 real, tenant-scoped rows)".

- [ ] **Step 10: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 68 (prior total) + 4 new = 72, all passing.

- [ ] **Step 11: Commit**

```bash
git add tools/multitenant/PilotAccessGate.php tests/tools/multitenant/PilotAccessGateTest.php application/core/Pilot_Controller.php application/controllers/PilotStudents.php application/controllers/PilotLogin.php application/controllers/PilotClasses.php application/controllers/PilotAttendance.php application/controllers/PilotExam.php application/controllers/PilotFees.php application/controllers/PilotHr.php application/controllers/PilotStudentSessions.php
git commit -m "feat: gate all Pilot* proof-harness controllers behind a development-only environment check"
```

---

### Task 2: Fix `PilotLogin::login()`'s session-persists-after-failed-login bug, and remove the `PilotStudents::login_as()` backdoor

**Files:**
- Modify: `application/controllers/PilotLogin.php:29-101` (the `login()` method)
- Modify: `application/controllers/PilotStudents.php` (delete `login_as()`)

**Interfaces:**
- Consumes: Task 1's `Pilot_Controller` (already wired, unaffected by this task's changes).
- Produces: nothing consumed by a later task.

**Before editing, check for pre-existing unrelated uncommitted work:**

```bash
git status --short application/controllers/PilotLogin.php application/controllers/PilotStudents.php
```

If either shows unrelated uncommitted changes, use the git-plumbing technique proven in Phase 3 Stages 3/4/5/6/7. If clean, a plain commit is correct — always check live.

- [ ] **Step 1: Read the current exact method to confirm line numbers before editing**

```bash
sed -n '29,60p' application/controllers/PilotLogin.php
```

Confirm the three failure branches (staff-row-not-found, password-mismatch, is_active-not-1) each currently `echo` a message and `return` with no `unset_userdata` call, and that `$this->session->set_userdata('pilot_tenant_id', $tenantId);` appears once, before the staff lookup. If the file has drifted from this shape, STOP and report the actual content.

- [ ] **Step 2: Add the unset call to each of the three failure branches**

Change:
```php
        $staffRows = $this->tenant_model->tenantGetAll('staff', ['email' => $email]);
        if (count($staffRows) !== 1) {
            echo 'Invalid email or password.';

            return;
        }

        $staff = $staffRows[0];
        if (!$this->enc_lib->passHashDyc($password, $staff['password'])) {
            echo 'Invalid email or password.';

            return;
        }

        if ((int) $staff['is_active'] !== 1) {
            echo 'Account disabled.';

            return;
        }
```
to:
```php
        $staffRows = $this->tenant_model->tenantGetAll('staff', ['email' => $email]);
        if (count($staffRows) !== 1) {
            $this->session->unset_userdata('pilot_tenant_id');
            echo 'Invalid email or password.';

            return;
        }

        $staff = $staffRows[0];
        if (!$this->enc_lib->passHashDyc($password, $staff['password'])) {
            $this->session->unset_userdata('pilot_tenant_id');
            echo 'Invalid email or password.';

            return;
        }

        if ((int) $staff['is_active'] !== 1) {
            $this->session->unset_userdata('pilot_tenant_id');
            echo 'Account disabled.';

            return;
        }
```

Do not touch anything else in this file — the successful-login branch below (which sets `pilot_admin`/`admin_tenant_id` and redirects) is unchanged.

Note the behavioral consequence, worth being deliberate about: if a session already had a legitimately-set `pilot_tenant_id` from an earlier successful login, and the same session then submits a *new, failed* login attempt (e.g. a typo), that prior valid `pilot_tenant_id` is now cleared too. This is the correct, safer default for a proof harness — a failed re-auth should not silently continue trusting old state — and is a one-line, well-contained behavior change scoped to this method only.

- [ ] **Step 3: Delete `PilotStudents::login_as()`**

In `application/controllers/PilotStudents.php`, delete this entire method (and nothing else):

```php
    public function login_as($tenantId)
    {
        $this->session->set_userdata('pilot_tenant_id', (int) $tenantId);
        echo "Pilot session set to tenant_id={$tenantId}. Now visit /web-app/pilotstudents/index\n";
    }
```

- [ ] **Step 4: Lint both files**

```bash
"C:\xampp81\php\php.exe" -l application/controllers/PilotLogin.php
"C:\xampp81\php\php.exe" -l application/controllers/PilotStudents.php
```

Expected: `No syntax errors detected` for both.

- [ ] **Step 5: Live-verify the fix closes the actual vulnerability**

In one script, using a single fresh cookie jar:

```bash
CJ=/tmp/p3s8_task2_verify.txt
rm -f "$CJ"
curl -s -c "$CJ" -b "$CJ" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=totally-bogus@example.invalid&password=wrong" -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilotstudents/index -w "\n%{http_code}\n"
curl -s -c "$CJ" -b "$CJ" http://localhost/web-app/pilotstudents/login_as/25 -w "\n%{http_code}\n"
```

Expected: the login attempt shows "Invalid email or password." (200, that's the app's existing error-echo behavior, not a redirect); `pilotstudents/index` afterward must NOT return a 200 page listing real student data — it should error (uncaught `RuntimeException` from `Tenant_Model::currentTenantId()` finding no `pilot_tenant_id` in session is the expected mechanism; a 500 with a stack trace is an acceptable outcome for this dev-only proof harness, the only unacceptable outcome is real student data appearing) — confirm the response body contains NO real student names/data; `pilotstudents/login_as/25` must now be unreachable (404 or a redirect, consistent with the benign not-a-real-method pattern already established in this project — NOT reachable data).

- [ ] **Step 6: Confirm the legitimate login path still works exactly as before**

Using a fresh cookie jar in the same script:

```bash
CJ2=/tmp/p3s8_task2_happy_path.txt
rm -f "$CJ2"
curl -s -c "$CJ2" -b "$CJ2" -X POST http://localhost/web-app/pilotlogin/login -d "tenant_id=25&email=rabiachauhan923@gmail.com&password=TestVerify123!" -w "\n%{http_code}\n"
curl -s -c "$CJ2" -b "$CJ2" http://localhost/web-app/pilotstudents/index -w "\n%{http_code}\n"
curl -s -c "$CJ2" -b "$CJ2" http://localhost/web-app/admin/staff/tenantStaffList -w "\n%{http_code}\n"
```

Expected: real login succeeds as before; `pilotstudents/index` returns 200 with real tenant-25 student data (this control confirms the fix didn't break the happy path); `admin/staff/tenantStaffList` still returns 200 with 18 rows (confirms `admin_tenant_id`/the real-controller gate is unaffected by this stage).

- [ ] **Step 7: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 72/72 passing, no regressions.

- [ ] **Step 8: Commit**

Per the pre-check in Step 0, either:

```bash
git add application/controllers/PilotLogin.php application/controllers/PilotStudents.php
git commit -m "fix: close pilot_tenant_id session leak on failed PilotLogin attempts; remove unauthenticated login_as backdoor"
```

or the git-plumbing equivalent if either file had unrelated pre-existing uncommitted content by execution time.

---

### Task 3: Regression test + roadmap update

**Files:**
- Modify: `tests/controllers/AdminControllerTenantGateTest.php` (append tests, or create a new dedicated test file — see Step 1)
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`

**Interfaces:**
- Consumes: Task 1's `Pilot_Controller`/`PilotAccessGate`, Task 2's `PilotLogin`/`PilotStudents` fixes — all already committed.
- Produces: nothing — this is the closing task.

- [ ] **Step 1: Decide test file placement and add the regression test**

`AdminControllerTenantGateTest.php` is scoped to the real `Admin_Controller` allowlist gate, not the `Pilot*` surface — create a new, separate test file `tests/controllers/PilotSecurityTest.php` instead, matching the project's convention of one test file per concern. Reuse the curl-helper pattern already established in `AdminControllerTenantGateTest.php` (private `$cookieJar` in `setUp()`/`tearDown()`, `curlGet()`/`curlPost()` helpers) rather than duplicating it — either copy the small helper pair into the new file (they're ~15 lines total) or factor them into a shared trait if that's cleaner; use your judgment, small copy is acceptable here given the project's established preference against premature abstraction.

```php
<?php

use PHPUnit\Framework\TestCase;

final class PilotSecurityTest extends TestCase
{
    private const BASE_URL = 'http://localhost/web-app/';

    private string $cookieJar;

    protected function setUp(): void
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'pilot_security_test_');
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

    private function curlPostPilotLogin(string $email, string $password): array
    {
        $ch = curl_init(self::BASE_URL . 'pilotlogin/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'tenant_id' => 25,
                'email' => $email,
                'password' => $password,
            ]),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    }

    public function testFailedPilotLoginDoesNotLeavePilotTenantIdUsable(): void
    {
        // application/views/pilot_students.php's actual heading, confirmed
        // by direct read: "<h1>Pilot Students (tenant_id = ...)</h1>". Its
        // absence proves index() did not render a normal successful page
        // (Tenant_Model::currentTenantId() throws when pilot_tenant_id is
        // unset, so this should error out rather than list real students).
        [, $loginBody] = $this->curlPostPilotLogin('totally-bogus@example.invalid', 'wrong');
        $this->assertStringContainsString('Invalid email or password', $loginBody);

        [, $indexBody] = $this->curlGet('pilotstudents/index');
        $this->assertStringNotContainsString('Pilot Students', $indexBody);
    }

    public function testLoginAsBackdoorNoLongerExists(): void
    {
        [$status, ] = $this->curlGet('pilotstudents/login_as/25');
        $this->assertNotSame(200, $status);
    }

    public function testLegitimatePilotLoginStillReachesRealStudentData(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin('rabiachauhan923@gmail.com', 'TestVerify123!');
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$indexStatus, $indexBody] = $this->curlGet('pilotstudents/index');
        $this->assertSame(200, $indexStatus);
        $this->assertStringContainsString('Pilot Students', $indexBody);
    }
}
```

- [ ] **Step 2: Run just this new test file**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/PilotSecurityTest.php
```

Expected: `OK (3 tests, ...)`.

- [ ] **Step 3: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 72 (prior total) + 3 new = 75, all passing.

- [ ] **Step 4: Commit the test**

```bash
git add tests/controllers/PilotSecurityTest.php
git commit -m "test: add regression coverage for the Pilot* security hardening"
```

- [ ] **Step 5: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` — add a Phase 3 Stage 8 entry. Cover: the two debt items being addressed (cite the "Non-negotiables"/"Carried-forward technical debt" section's exact original wording about `Pilot*` controllers and the committed test credential), the newly-discovered `PilotLogin` ordering bug (more severe than the originally-logged `login_as` issue alone — describe the actual mechanism: any failed login attempt against `pilotlogin/login` left `pilot_tenant_id` set in session regardless of credential validity, exposing all 7 other real, tenant-scoped `Pilot*` controllers' data to anyone who could guess or read `tenant_id=25` from the login form's own HTML), the `PilotAccessGate`/`Pilot_Controller` systemic fix, and an honest statement that this closes the *documented* debt items but the underlying `Pilot*` proof-harness controllers are still not intended for any real deployment — the roadmap's Phase 5 guidance ("must be removed or gated... before cutover") is now satisfied via the environment gate, not by removing the harness outright, and that decision should be revisited if/when Phase 5 planning begins.

- [ ] **Step 6: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 8 (Pilot proof-harness security hardening) complete"
```

---

## Final whole-stage review (after Task 3)

Dispatch an adversarial reviewer (same rigor as every prior stage's final review, but with an even sharper security lens given this stage is explicitly about closing an access-control hole) to independently:
- Re-read the full diff across all 3 tasks.
- Independently reproduce the original vulnerability against a fresh checkout at the commit *before* Task 2 (`git stash`/checkout the pre-fix state or simply re-read the pre-fix code) to confirm it was real, then confirm the fix closes it live.
- Independently probe: a failed login for a DIFFERENT bogus tenant_id (not 25) to confirm no cross-tenant leakage was ever possible either.
- Confirm `PilotAccessGate::isAllowed()` is correctly integrated (not just unit-tested in isolation) — trace `Pilot_Controller`'s constructor and confirm `ENVIRONMENT` really is the PHP constant CI3 defines, not a typo'd or shadowed variable.
- Confirm all 8 Pilot controllers were updated, not 7 (easy to miss one).
- Confirm the 5 already-retrofitted real controllers (staff/feesforward/examgroup/stuattendence/leaverequest) and the `admin_tenant_id`/allowlist-gate mechanism are completely unaffected — this stage should have zero diff in `application/core/MY_Controller.php` or `application/libraries/Db_manager.php`.
- Run the full suite and confirm 75/75.
- Confirm roadmap accuracy, including that it correctly frames this as environment-gating (not elimination) of the debt item.
- Confirm git hygiene — pre-existing unrelated uncommitted work still present and untouched.
- Report Ready to merge (Yes/With fixes/No) plus Critical/Important/Minor findings, same format as every prior stage. Given the security nature of this stage, treat any residual doubt about whether the fix is complete as at least Important, not Minor.
