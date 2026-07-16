# Phase 4 Stage 1 — Real Login Verification Cutover (Pilot Tenant) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `school_saas.staff` the authoritative password check for real logins resolving to tenant 25 (Al-Hafeez / `al_hafeez_campus` / `branch_25`), with automatic logged fallback to today's legacy per-branch check so a stale `school_saas` password can never lock a real user out — while leaving session establishment, `$this->db`, and every other tenant's login behavior completely unchanged.

**Architecture:** One new framework-agnostic class, `RealLoginGate` (`tools/multitenant/RealLoginGate.php`), takes a `PDO` connection to `school_saas` and orchestrates a school_saas-first, legacy-fallback dual-check without touching CI3 or `$this->db` — same isolation principle as `ShadowLoginVerifier`. It's wired into exactly one place inside `Site.php::login()`'s existing multi-branch password-matching loop, special-casing only the `branch_25` iteration; every other branch's check is untouched.

**Tech Stack:** PHP 8.1, CodeIgniter 3.1.13, PDO/MySQL, PHPUnit 10.5 (existing project stack, unchanged).

## Context

This opens **Phase 4 — Production Cutover**, a new and materially different phase from
everything Phase 1-3 built. Every prior stage was purely additive: new `tenant*`
controller methods added alongside completely untouched legacy methods, safe because the
old code path kept working unchanged regardless of bugs in the new one. As of Phase 3
Stage 14, 43 controllers have `tenant*` routes, but **every real (non-`tenant*`) method
across all of them remains completely unaware of `school_saas`** — e.g. `Staff.php`'s
real `index()` does `$this->db->get('staff')` with zero `tenant_id` filtering, the only
tenant-aware line in that entire model backs the additive `tenantStaffList` route alone.

This matters concretely: pointing a real session's `$this->db` at `school_saas` today
would not just break unconverted pages, it would **leak cross-tenant data** — a real
`Staff.php::index()` load would show all 6 schools' staff mixed on one page, since the
real method has no tenant filter to leak-guard it. Production cutover therefore cannot be
"flip the DB connection" as a first move; it has to be decomposed into much smaller,
individually-safe sub-projects. This stage covers the first and smallest of them.

**Prior art directly reused**: Phase 3 Stage 5 built `ShadowLoginVerifier`
(`tools/multitenant/ShadowLoginVerifier.php`), which proves `school_saas` credentials
agree with the real per-branch login for tenant 25 — but it is **read-only**: it only
calls `log_message()`, never affects whether a real login succeeds. This stage is the
first one that actually changes real login *behavior* — but only whether it succeeds,
nothing about what happens after.

## Why not go further (explicitly out of scope, and why)

- **Not cutting over data access this stage.** Real controller methods have zero
  tenant_id awareness. Making a real session's `$this->db` point at `school_saas` before
  those methods are individually rewritten would leak cross-tenant data. Separate,
  much larger sub-project (Phase 4 Stage 2+, one per module), own design.
- **Not touching `Db_manager`'s routing.** Untouched. This stage only changes whether
  `Site.php::login()` reports success, never what session state gets set afterward.
- **Not re-syncing `school_saas`'s existing password data.** The dual-check-with-fallback
  design makes a one-time re-sync unnecessary for correctness.
- **Not applying to the other 5 real tenants yet.** Pilot tenant (25) only.

## Components (reference — full detail in each task below)

- **`tools/multitenant/RealLoginGate.php`** (new) — `__construct(PDO $schoolSaasPdo)`,
  `verify(string $email, string $password, int $tenantId, callable $passwordVerifier,
  callable $legacyFallback): array` returning `['success' => bool, 'source' =>
  'school_saas'|'legacy'|'none']`.
- **One call site in `Site.php::login()`**, inside the existing multi-branch
  password-matching loop, special-casing only `$group_name === 'branch_25'`.

## Global Constraints

- **`Site.php::login()` must be functionally byte-for-byte unchanged for every login that
  does NOT resolve to `branch_25`.** This is production auth for 6 live schools; the
  other 5 must never observe any difference in outcome, session shape, or redirect.
- **Empirically confirmed branch iteration order** (`SELECT * FROM multi_branch WHERE
  is_verified=1` in the `school_default` database, no `ORDER BY`, re-verify live before
  Task 2): ids `19, 20, 21, 22, 23, 24, 25` in that order, with `25` (`al_hafeez_campus`
  / `branch_25`) **last**. This is not a language/SQL guarantee, only an empirical
  observation of current InnoDB behavior on unmodified data — Task 2 and the final
  review both independently re-confirm it live, never assume it silently holds. Its
  consequence: a real login for any of the other 5 schools matches its own branch and
  `break`s out of the loop *before* the loop ever reaches `branch_25`'s position, so
  `RealLoginGate` never executes for those logins at all — this is what makes "byte for
  byte unchanged for the other 5" concretely true, not just intended. Only a genuine
  tenant-25 attempt, or a fully bogus attempt matching no branch at all (which reaches
  `branch_25` last, as the loop's final check), ever executes the new code.
- **`$this->db` must never be reassigned by `RealLoginGate`'s own code.** It builds its
  own isolated `PDO` connection (same pattern `ShadowLoginVerifier` already uses). The
  pre-existing multi-branch-fix block's own `$this->db` reassignment for whichever branch
  ultimately matches is unrelated, untouched, and still happens exactly as before.
- **No real per-branch school password is ever read, written, or logged in cleartext by
  this stage's code**, beyond what `Site.php::login()` already receives from the POST
  body it already trusts.
- **No new production data.** This stage's runtime code never inserts, modifies, or
  deletes a row in any live per-branch database or in `school_saas`. All new testing uses
  throwaway PHPUnit-managed databases, plus read-only queries against `school_saas`'s and
  `al_hafeez_campus`'s existing already-migrated real data.
- **Known test data** (all confirmed live, read-only, both locally accessible on this dev
  MySQL instance): tenant 25, email `rabiachauhan923@gmail.com`, password
  `TestVerify123!`; `school_saas.staff.id = 1`; `al_hafeez_campus.staff.id = 121` (same
  person, two databases — this is the real migrated row, not a synthetic fixture).
- **Al-Hafeez Campus / tenant 25 mapping**: `multi_branch.id = 25`,
  `multi_branch.database_name = 'al_hafeez_campus'`. **`multi_branch` lives in the
  `school_default` database, not `school_saas`** (confirmed live — do not assume
  otherwise). The dynamic per-request `$db` array built by
  `application/config/database.php` names this branch's CI3 db-group `branch_25`
  (pattern: `"branch_" . $row['id']`).
- **Any error inside `RealLoginGate::verify()` or its wiring** (connection failure,
  unexpected exception, anything) must degrade to exactly today's per-branch legacy
  password check for that row — never to a login failure the legacy path alone would not
  have produced.
- **This stage sets `admin_tenant_id` for NOBODY** and does not touch the
  `Admin_Controller` allowlist gate. Only whether login succeeds changes; everything
  downstream is identical to today.
- Every task ends with a real, runnable verification step. No task is "done" on code
  review alone.

---

### Task 1: `RealLoginGate` — isolated, unit-tested dual-check class

**Files:**
- Create: `tools/multitenant/RealLoginGate.php`
- Test: `tests/tools/multitenant/RealLoginGateTest.php`

**Interfaces:**
- Produces: `RealLoginGate::__construct(PDO $pdo)`,
  `RealLoginGate::verify(string $email, string $password, int $tenantId, callable
  $passwordVerifier, callable $legacyFallback): array` returning `['success' => bool,
  'source' => 'school_saas'|'legacy'|'none']`. `$passwordVerifier` is called as
  `$passwordVerifier($plaintextPassword, $storedHash): bool` (matches
  `Enc_lib::passHashDyc($password, $encrypt_password)`'s exact signature, same convention
  `ShadowLoginVerifier` already established). `$legacyFallback` is a zero-arg callable
  returning `bool` — the caller wraps its own existing legacy check in it; this class
  never needs to know about CI3, per-branch DB connections, or branch-id-to-tenant-id
  mapping.
- Consumes: nothing from other tasks — this is the first task.

This class is deliberately framework-agnostic (no CI3, no `$this->db`), matching
`ShadowLoginVerifier`'s and every other `tools/multitenant/` class's existing pattern, so
it's directly testable with a plain `PDO` connection.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/RealLoginGateTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class RealLoginGateTest extends TestCase
{
    private PDO $pdo;
    private RealLoginGate $gate;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS real_login_gate_test');
        $admin->exec('CREATE DATABASE real_login_gate_test');

        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=real_login_gate_test;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE staff (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(200), password VARCHAR(255), tenant_id INT NOT NULL)');
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('real@example.com', 'school-saas-hash', 25)");

        $this->gate = new RealLoginGate($this->pdo);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS real_login_gate_test');
    }

    private function verifierMatching(string $expectedHash): callable
    {
        return function (string $password, string $storedHash) use ($expectedHash): bool {
            return $storedHash === $expectedHash;
        };
    }

    public function testSucceedsViaSchoolSaasWhenPasswordMatchesThereAndNeverCallsLegacyFallback(): void
    {
        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called when school_saas already matched');
        };

        $result = $this->gate->verify('real@example.com', 'anything', 25, $this->verifierMatching('school-saas-hash'), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testFallsBackToLegacyWhenEmailDoesNotExistInSchoolSaas(): void
    {
        $result = $this->gate->verify('nobody@example.com', 'anything', 25, $this->verifierMatching('school-saas-hash'), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFallsBackToLegacyWhenSchoolSaasRowExistsButPasswordDoesNotMatch(): void
    {
        $result = $this->gate->verify('real@example.com', 'wrong-password', 25, $this->verifierMatching('a-different-hash'), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFailsWhenNeitherSchoolSaasNorLegacyMatch(): void
    {
        $result = $this->gate->verify('nobody@example.com', 'anything', 25, $this->verifierMatching('school-saas-hash'), fn (): bool => false);

        $this->assertSame(['success' => false, 'source' => 'none'], $result);
    }

    public function testDoesNotMatchAcrossTenants(): void
    {
        // real@example.com exists under tenant 25 only in this fixture. Asking under
        // tenant 99 must not match school_saas, proving the WHERE clause is
        // tenant-scoped -- falls through to whatever the legacy fallback says.
        $result = $this->gate->verify('real@example.com', 'anything', 99, $this->verifierMatching('school-saas-hash'), fn (): bool => false);

        $this->assertSame(['success' => false, 'source' => 'none'], $result);
    }

    public function testPasswordVerifierReceivesThePlaintextPasswordAndStoredHashInThatOrder(): void
    {
        $seenArgs = null;
        $spy = function (string $password, string $storedHash) use (&$seenArgs): bool {
            $seenArgs = [$password, $storedHash];

            return true;
        };

        $this->gate->verify('real@example.com', 'my-password', 25, $spy, fn (): bool => false);

        $this->assertSame(['my-password', 'school-saas-hash'], $seenArgs);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/RealLoginGateTest.php`
Expected: FAIL — `Error: Class "RealLoginGate" not found`.

- [ ] **Step 3: Write the implementation**

Create `tools/multitenant/RealLoginGate.php`:

```php
<?php

final class RealLoginGate
{
    public function __construct(private PDO $pdo)
    {
    }

    public function verify(string $email, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT password FROM staff WHERE email = :email AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false && $passwordVerifier($password, $row['password'])) {
            return ['success' => true, 'source' => 'school_saas'];
        }

        if ($legacyFallback()) {
            return ['success' => true, 'source' => 'legacy'];
        }

        return ['success' => false, 'source' => 'none'];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/RealLoginGateTest.php`
Expected: `OK (6 tests, ...)`.

- [ ] **Step 5: Run the full suite to confirm no regressions**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: prior total + 6 new, all passing.

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/RealLoginGate.php tests/tools/multitenant/RealLoginGateTest.php
git commit -m "feat: add RealLoginGate, a school_saas-first dual-check with legacy fallback"
```

---

### Task 2: Wire `RealLoginGate` into `Site.php::login()`'s branch_25 password check

**Files:**
- Modify: `application/controllers/Site.php` (inside the multi-branch password-matching
  loop, currently lines 100-135 — re-confirm exact lines in Step 1 before editing)

**Interfaces:**
- Consumes: `RealLoginGate::__construct(PDO $pdo)` and
  `verify(string $email, string $password, int $tenantId, callable $passwordVerifier,
  callable $legacyFallback): array` from Task 1, unchanged.
- Produces: nothing consumed by a later task — this is a leaf integration.

**Before editing:** confirm `Site.php` is currently clean (`git status --short
application/controllers/Site.php` — expect no output). If it is NOT clean, STOP and
report what's there instead of guessing whether it's related — do not silently work
around or overwrite unrelated pending changes the way earlier stages had to route around
a genuinely pre-existing "MULTI BRANCH STAFF LOGIN FIX" block (that block is now fully
committed history, part of the loop this task edits, not pending work).

- [ ] **Step 1: Read the current exact block to confirm line numbers before editing**

```bash
sed -n '96,140p' application/controllers/Site.php
```

Confirm it matches (whitespace aside):

```php
            $setting_result        = $this->setting_model->get();

            // --- MULTI BRANCH STAFF LOGIN FIX START ---
            include(APPPATH . 'config/database.php');
            if (isset($db) && is_array($db) && count($db) > 1) {
                $found_group = 'default';
                foreach ($db as $group_name => $config_item) {
                    if ($group_name === 'default') continue;
                    $test_db = @$this->load->database($group_name, TRUE);
                    if ($test_db && $test_db->conn_id) {
                        $test_db->select('password');
                        $test_db->where('email', $login_post['email']);
                        $test_db->limit(1);
                        $query = $test_db->get('staff');
                        if ($query && $query->num_rows() == 1) {
                            $row = $query->row();
                            if ($this->enc_lib->passHashDyc($login_post['password'], $row->password)) {
                                $found_group = $group_name;
                                $test_db->close();
                                break;
                            }
                        }
                        $test_db->close();
                    }
                }
                
                if ($found_group !== 'default') {
```

If the surrounding lines have drifted from this, STOP and report the actual current
content instead of guessing where to insert.

- [ ] **Step 2: Replace the single password-check line with a branch_25-special-cased dual-check**

Replace exactly this block:

```php
                        if ($query && $query->num_rows() == 1) {
                            $row = $query->row();
                            if ($this->enc_lib->passHashDyc($login_post['password'], $row->password)) {
                                $found_group = $group_name;
                                $test_db->close();
                                break;
                            }
                        }
```

with:

```php
                        if ($query && $query->num_rows() == 1) {
                            $row = $query->row();
                            if ($group_name === 'branch_25') {
                                // --- REAL LOGIN GATE (Phase 4 Stage 1) ---
                                // school_saas is now the authoritative password
                                // check for tenant 25 (branch_25 / al_hafeez_campus).
                                // This branch's own row (fetched above by the
                                // unmodified surrounding loop) is used as the
                                // fallback so a stale school_saas password can
                                // never lock a real user out. Any error here
                                // degrades to exactly today's check, never worse.
                                // Never reassigns $this->db.
                                try {
                                    require_once APPPATH . '../tools/multitenant/RealLoginGate.php';
                                    $realLoginDbConfig = $db['school_saas_pilot'];
                                    $realLoginPdo = new PDO(
                                        'mysql:host=' . $realLoginDbConfig['hostname'] . ';dbname=' . $realLoginDbConfig['database'] . ';charset=utf8mb4',
                                        $realLoginDbConfig['username'],
                                        $realLoginDbConfig['password']
                                    );
                                    $realLoginPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    $realLoginGate = new RealLoginGate($realLoginPdo);
                                    $branchRowPassword = $row->password;
                                    $gateResult = $realLoginGate->verify(
                                        $login_post['email'],
                                        $login_post['password'],
                                        25,
                                        [$this->enc_lib, 'passHashDyc'],
                                        function () use ($login_post, $branchRowPassword) {
                                            return $this->enc_lib->passHashDyc($login_post['password'], $branchRowPassword);
                                        }
                                    );
                                    if ($gateResult['source'] === 'legacy') {
                                        log_message('error', '[RealLoginGate] PASSWORD_DRIFT_DETECTED tenant_id=25 email=' . $login_post['email']);
                                    }
                                    $passwordMatched = $gateResult['success'];
                                } catch (\Throwable $e) {
                                    log_message('error', '[RealLoginGate] EXCEPTION ' . $e->getMessage());
                                    $passwordMatched = $this->enc_lib->passHashDyc($login_post['password'], $row->password);
                                }
                                // --- END REAL LOGIN GATE ---
                            } else {
                                $passwordMatched = $this->enc_lib->passHashDyc($login_post['password'], $row->password);
                            }
                            if ($passwordMatched) {
                                $found_group = $group_name;
                                $test_db->close();
                                break;
                            }
                        }
```

Note the `else` branch is byte-for-byte the same check as the original single line, just
reached through a conditional — not a behavior change for any branch other than
`branch_25`.

- [ ] **Step 3: Lint the file**

```bash
"C:\xampp81\php\php.exe" -l application/controllers/Site.php
```

Expected: `No syntax errors detected in application/controllers/Site.php`.

- [ ] **Step 4: Live smoke-test that the common failure path is byte-for-byte unchanged**

Capture a baseline BEFORE this task's change is live (if not already captured, `git
stash` this task's edit, run the request, save the response, then `git stash pop` to
restore the edit) and compare:

```bash
curl -s -o /tmp/site_login_baseline.html -w "%{http_code}\n" http://localhost/web-app/site/login \
  -d "username=definitely-not-a-real-account@example.invalid&password=wrong"
# (after git stash pop, restoring this task's edit)
curl -s -o /tmp/site_login_after.html -w "%{http_code}\n" http://localhost/web-app/site/login \
  -d "username=definitely-not-a-real-account@example.invalid&password=wrong"
diff /tmp/site_login_baseline.html /tmp/site_login_after.html
```

Expected: both HTTP `200`, `diff` shows no differences beyond incidental (CSRF token
value, timestamp). If it differs in anything else, STOP and report.

- [ ] **Step 5: Confirm no drift/exception log line fired for that failed, non-matching attempt**

```bash
grep "RealLoginGate" application/logs/log-$(date +%Y-%m-%d).php
```

Expected: no output (grep exits 1) — a login matching no real account produces
`source='none'` inside `RealLoginGate`, which this stage's code never logs (only
`source='legacy'` and exceptions are logged). If a `PASSWORD_DRIFT_DETECTED` or
`EXCEPTION` line IS present from this exact request, STOP — something matched
unexpectedly and needs to be understood before proceeding.

- [ ] **Step 6: Independently re-confirm the branch iteration order this task's safety argument depends on**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_default -e "SELECT id, database_name FROM multi_branch WHERE is_verified=1;"
```

Expected: `25` (`al_hafeez_campus`) is the LAST row. This is the live re-confirmation of
the Global Constraints section's claim — do not skip this step, do not trust the doc
alone.

- [ ] **Step 7: Commit**

```bash
git add application/controllers/Site.php
git commit -m "feat: wire RealLoginGate into real Site.php login for tenant 25 (branch_25)"
```

---

### Task 3: End-to-end proof against real data + automated other-tenants-unchanged test + roadmap update

**Files:**
- Create: `tests/controllers/SiteLoginRealLoginGateTest.php`
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`

**Interfaces:**
- Consumes: Task 1's `RealLoginGate` (committed, unit-tested), Task 2's wiring
  (committed, smoke-tested).
- Produces: nothing — this is the closing task.

- [ ] **Step 1: Confirm the known test credential is still intact in both real databases (read-only)**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT id, email, tenant_id FROM staff WHERE email='rabiachauhan923@gmail.com';"
"C:\xampp81\mysql\bin\mysql.exe" -u root al_hafeez_campus -e "SELECT id, email FROM staff WHERE email='rabiachauhan923@gmail.com';"
```

Expected: one row each, `school_saas` shows `tenant_id=25`; `al_hafeez_campus` shows the
same email under its own local `id`.

- [ ] **Step 2: Directly exercise `RealLoginGate` against BOTH real databases (still no HTTP, still read-only, still no `Site.php` involved)**

This proves the dual-check correctly matches/rejects against the actual real,
already-migrated data in both `school_saas` and `al_hafeez_campus` — closing the gap
between "unit-tested against a synthetic fixture" (Task 1) and "works against the real
pilot tenant's real staff tables" — without ever going through the live HTTP login
endpoint (same caution Phase 3 Stage 5 exercised: never attempt a real successful
HTTP-level login, since that touches real session/log state on what is still a real
migrated production row).

```bash
"C:\xampp81\php\php.exe" -r '
require "tools/multitenant/RealLoginGate.php";
require "application/libraries/Enc_lib.php";

$schoolSaasPdo = new PDO("mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4", "root", "");
$branchPdo = new PDO("mysql:host=127.0.0.1;dbname=al_hafeez_campus;charset=utf8mb4", "root", "");
$enc = new Enc_lib();
$gate = new RealLoginGate($schoolSaasPdo);

$branchLegacyFallback = function () use ($branchPdo, $enc) {
    $stmt = $branchPdo->prepare("SELECT password FROM staff WHERE email = :email LIMIT 1");
    $stmt->execute(["email" => "rabiachauhan923@gmail.com"]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false && $enc->passHashDyc("TestVerify123!", $row["password"]);
};

$correct = $gate->verify("rabiachauhan923@gmail.com", "TestVerify123!", 25, [$enc, "passHashDyc"], $branchLegacyFallback);
echo "correct (should match via school_saas): " . json_encode($correct) . "\n";

$wrongPasswordFallback = function () use ($branchPdo, $enc) {
    $stmt = $branchPdo->prepare("SELECT password FROM staff WHERE email = :email LIMIT 1");
    $stmt->execute(["email" => "rabiachauhan923@gmail.com"]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false && $enc->passHashDyc("definitely-wrong", $row["password"]);
};
$wrongPw = $gate->verify("rabiachauhan923@gmail.com", "definitely-wrong", 25, [$enc, "passHashDyc"], $wrongPasswordFallback);
echo "wrong password (should fail both): " . json_encode($wrongPw) . "\n";

$wrongTenant = $gate->verify("rabiachauhan923@gmail.com", "TestVerify123!", 99, [$enc, "passHashDyc"], fn() => false);
echo "wrong tenant (should fail, no school_saas row under 99): " . json_encode($wrongTenant) . "\n";

// Simulates the real drift scenario: school_saas has a stale hash, but the
// branch'\''s own (legacy) row still has the current password.
$staleSchoolSaasVerifier = fn($pw, $hash) => false;
$driftFallback = fn() => true;
$drift = $gate->verify("rabiachauhan923@gmail.com", "TestVerify123!", 25, $staleSchoolSaasVerifier, $driftFallback);
echo "simulated drift (should succeed via legacy): " . json_encode($drift) . "\n";
'
```

Expected:
```
correct (should match via school_saas): {"success":true,"source":"school_saas"}
wrong password (should fail both): {"success":false,"source":"none"}
wrong tenant (should fail, no school_saas row under 99): {"success":false,"source":"none"}
simulated drift (should succeed via legacy): {"success":true,"source":"legacy"}
```

If any line doesn't match, STOP and report — do not proceed.

- [ ] **Step 3: Write the automated other-tenants-unchanged integration test**

Create `tests/controllers/SiteLoginRealLoginGateTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class SiteLoginRealLoginGateTest extends TestCase
{
    private const BASE_URL = 'http://localhost/web-app/';

    public function testFailedLoginForNonExistentAccountIsUnaffectedAndLogsNoDriftOrException(): void
    {
        $logFile = __DIR__ . '/../../application/logs/log-' . date('Y-m-d') . '.php';
        $logSizeBefore = file_exists($logFile) ? filesize($logFile) : 0;

        $ch = curl_init(self::BASE_URL . 'site/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => 'definitely-not-a-real-account@example.invalid',
                'password' => 'wrong',
            ]),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        $this->assertStringContainsString('invalid_username_or_password', $body, 'unauthenticated failure message must be present, unchanged');

        if (file_exists($logFile)) {
            $newLogContent = file_get_contents($logFile);
            $newLogContent = substr($newLogContent, $logSizeBefore);
            $this->assertStringNotContainsString('RealLoginGate', $newLogContent, 'a login matching no real account must never trigger RealLoginGate logging');
        }
    }

    public function testLoginFormRendersIdenticallyOnGet(): void
    {
        $ch = curl_init(self::BASE_URL . 'site/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        $this->assertStringContainsString('name="username"', $body);
        $this->assertStringContainsString('name="password"', $body);
    }
}
```

This deliberately never attempts a real successful login (for tenant 25 or any other
tenant) over HTTP — consistent with the Global Constraints' "no new production data" and
Phase 3 Stage 5's established precedent. It proves the two things that ARE safe and
meaningful to prove over real HTTP: (1) a failed/non-matching login is completely
unaffected in output and produces zero new logging from this stage's code, and (2) the
unauthenticated form itself is unaffected.

- [ ] **Step 4: Run the new test**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/controllers/SiteLoginRealLoginGateTest.php
```

Expected: `OK (2 tests, ...)`.

- [ ] **Step 5: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: all passing, prior total + 6 (Task 1) + 2 (this task) new.

- [ ] **Step 6: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`. Add a new
entry after the Phase 3 Stage 14 entry, opening the "Phase 4 — Production Cutover"
section, following the existing entries' style: what was built, the real commit range,
what was proven, and an honest statement that this stage covers ONLY login verification
for the pilot tenant — data access, `Db_manager` routing, and the other 5 tenants remain
entirely for future stages.

- [ ] **Step 7: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 4 Stage 1 (real login verification cutover, pilot tenant) complete"
```

---

## Final whole-stage review (after Task 3)

Dispatch an adversarial reviewer (same rigor as every prior stage's final review) to
independently:
- Re-read the full diff across all 3 tasks and confirm `Site.php`'s change is exactly the
  branch_25-special-cased, try/catch-wrapped, `$this->db`-untouched block described
  above — no scope creep, no change to any other branch's code path.
- Independently re-run Task 3 Step 2's direct-class checks against real `school_saas` and
  `al_hafeez_campus` data.
- Independently re-run Task 2 Step 4's failure-path smoke test with the reviewer's own
  choice of bogus credentials, and additionally try a GET to `site/login` to confirm the
  unauthenticated form render is unaffected.
- Independently re-verify the branch iteration order claim (Task 2 Step 6) against the
  live `multi_branch` table in `school_default` — do not trust this plan's claim alone.
- Confirm via code reading that no other of the 6 schools' `$group_name` can ever equal
  `'branch_25'` — re-verify against the live `multi_branch` table.
- Confirm each task's commit contains only that task's intended change (`git show
  --stat` on each).
- Confirm the roadmap entry is factually accurate against everything above, and honestly
  states this stage's real scope limits (verification-only, pilot tenant only).
- Report Ready to merge (Yes/With fixes/No) plus Critical/Important/Minor findings, same
  format as every prior stage.

## Architecture (detail)

`RealLoginGate` sits between `Site.php::login()`'s existing legacy-DB password check and
its success/failure branch, gated to `branch_25` only, wrapped in try/catch so any
unexpected error falls straight through to today's unmodified legacy check.
`ShadowLoginVerifier` is not modified or reused as a base class — it stays exactly as
pure observability, zero behavioral effect. `RealLoginGate` is new because it does
something meaningfully different in kind (it can cause a real login to succeed or fail).

## Data flow (detail)

1. User submits email+password; request resolves to the tenant-25 / `branch_25` /
   `al_hafeez_campus` case (existing resolution logic, unchanged, and — per the
   Global Constraints' empirically-confirmed iteration order — this is the LAST branch
   the loop checks, so any earlier-matching real school never reaches this code at all).
2. `RealLoginGate::verify()` runs inside a try/catch:
   a. Check `school_saas.staff` → match → `success=true, source='school_saas'`.
   b. No match → call the branch's own (legacy) row check → match → log drift →
      `success=true, source='legacy'` → user logs in exactly as today.
   c. Neither matches → `success=false, source='none'` → today's real failure behavior,
      unchanged.
3. Any exception anywhere in step 2 → caught → falls through to the legacy check
   untouched.
4. Session establishment, `$this->db`, redirect target: unchanged in every branch of
   every case — this stage only ever decides *whether* login succeeds.

## Rollback

If this stage needs to be reverted after shipping: revert Task 2's commit (one code
block, clearly delimited by `--- REAL LOGIN GATE ---` / `--- END REAL LOGIN GATE ---`
comments in `Site.php`, restoring the single original `passHashDyc` line). No session
shape, no `Db_manager` routing, and no data ever depends on `RealLoginGate` having
run — reverting it returns tenant-25 login to exactly the legacy-only check that's live
today, with zero downstream cleanup required.
