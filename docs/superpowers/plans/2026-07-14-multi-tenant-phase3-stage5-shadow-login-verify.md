# Phase 3 Stage 5 — Shadow-Verify Real Site.php Login Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prove that the tenant-scoped `school_saas` credential-verification path agrees with the real, live `Site.php::login()` production login for the pilot tenant (`al_hafeez_campus`, tenant_id 25) — without changing that login's behavior, session shape, or redirect for a single real user, anywhere.

**Architecture:** Extract the actual verification logic (does this email+password match a `school_saas.staff` row for tenant 25?) into a small, plain, framework-agnostic class (`tools/multitenant/ShadowLoginVerifier.php`) that takes a `PDO` connection and is directly unit-testable, matching the existing pattern of every other class in `tools/multitenant/`. Wire a single, narrowly-guarded, fully try/catch-wrapped call to it into `Site.php::login()`'s existing real-success branch — gated so it only ever runs when the login just resolved to the `al_hafeez_campus` branch, and it only ever logs (`log_message()`), never writes session data, never changes the redirect, never touches `$this->db`.

**Tech Stack:** PHP 8.1, CodeIgniter 3.1.13, PDO/MySQL, PHPUnit 10.5 (existing project stack, unchanged).

## Global Constraints

- **This stage sets `admin_tenant_id` for NOBODY.** The `Admin_Controller` allowlist gate (`application/core/MY_Controller.php`) is not touched this stage. Real pilot-tenant users keep using the full, un-gated real admin panel exactly as before — this is a read-only proof stage, not a cutover.
- **`Site.php::login()` must be functionally byte-for-byte unchanged for every login that does NOT resolve to `al_hafeez_campus` (branch_25).** This is production auth for 6 live schools; the other 5 must never observe any difference — not a new log line, not a slower response in a way that changes behavior, nothing.
- **`$this->db` must never be reassigned by this stage's code.** Unlike the existing (pre-existing, unrelated, uncommitted) "MULTI BRANCH STAFF LOGIN FIX" block already in this function, which does reassign `$this->db`/`$CI->db`, the shadow-verify block must use `$this->load->database('school_saas_pilot', true)` (the `true` second argument returns an isolated connection object and does **not** touch `$this->db`) or a raw `PDO` object it builds itself. Confirm this in every task.
- **No real per-branch school password is ever read, written, or logged in cleartext by this stage's code beyond what `Site.php::login()` already receives from the POST body it already trusts.** The shadow-verify log lines must never include the raw password.
- **No new production data.** This stage does not insert, modify, or touch a single row in any live per-branch school database (`al_hafeez_campus` or any other). All new testing happens against throwaway PHPUnit-managed databases and against `school_saas`'s existing real (already-migrated) staff data, read-only.
- **Known test credential** (unchanged from prior stages, `school_saas`-side only): tenant_id=25, email `rabiachauhan923@gmail.com`, password `TestVerify123!`.
- **Al-Hafeez Campus / tenant 25 mapping**: `multi_branch.id = 25`, `multi_branch.database_name = 'al_hafeez_campus'`. The dynamic per-request `$db` array built by `application/config/database.php` names this branch's CodeIgniter DB group `branch_25` (pattern: `"branch_" . $row['id']`). This numeric coincidence (branch id 25 == tenant id 25) is real, already-established, and confirmed live against the running `multi_branch` table — not an assumption.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: `ShadowLoginVerifier` — isolated, unit-tested verification class

**Files:**
- Create: `tools/multitenant/ShadowLoginVerifier.php`
- Test: `tests/tools/multitenant/ShadowLoginVerifierTest.php`

**Interfaces:**
- Produces: `ShadowLoginVerifier::__construct(PDO $pdo)`, `ShadowLoginVerifier::verify(string $email, string $password, int $tenantId, callable $passwordVerifier): array` returning `['matched' => bool, 'reason' => string]` where `reason` is one of `'ok'`, `'no_matching_row'`, `'password_mismatch'`. `$passwordVerifier` is called as `$passwordVerifier($plaintextPassword, $storedHash): bool` (matches `Enc_lib::passHashDyc($password, $encrypt_password)`'s exact signature, so Task 2 can pass `[$this->enc_lib, 'passHashDyc']` directly).
- Consumes: nothing from other tasks — this is the first task.

This class is deliberately framework-agnostic (no CI3, no `$this->db`) so it can be constructed and tested with a plain `PDO` connection, exactly like `tools/multitenant/NaturalKeyIdResolver.php` already is. It does one read-only `SELECT`, then delegates hashing to the caller-supplied verifier (so it never needs to know or duplicate `password_verify()`/`Enc_lib` — same separation of concerns Task 2 relies on).

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/ShadowLoginVerifierTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class ShadowLoginVerifierTest extends TestCase
{
    private PDO $pdo;
    private ShadowLoginVerifier $verifier;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS shadow_verify_test');
        $admin->exec('CREATE DATABASE shadow_verify_test');

        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=shadow_verify_test;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE staff (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(200), password VARCHAR(255), tenant_id INT NOT NULL)');
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('real@example.com', 'stored-hash-25', 25), ('other-tenant@example.com', 'stored-hash-99', 99)");

        $this->verifier = new ShadowLoginVerifier($this->pdo);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS shadow_verify_test');
    }

    private function passthroughVerifier(string $expectedPassword): callable
    {
        return function (string $password, string $storedHash) use ($expectedPassword): bool {
            return $password === $expectedPassword && $storedHash === 'stored-hash-25';
        };
    }

    public function testMatchedWhenEmailTenantAndPasswordAllAgree(): void
    {
        $result = $this->verifier->verify('real@example.com', 'correct-password', 25, $this->passthroughVerifier('correct-password'));

        $this->assertSame(['matched' => true, 'reason' => 'ok'], $result);
    }

    public function testNoMatchingRowWhenEmailDoesNotExist(): void
    {
        $result = $this->verifier->verify('nobody@example.com', 'anything', 25, $this->passthroughVerifier('anything'));

        $this->assertSame(['matched' => false, 'reason' => 'no_matching_row'], $result);
    }

    public function testNoMatchingRowWhenEmailExistsOnlyUnderAnotherTenant(): void
    {
        // real@example.com exists under tenant 25 only in this fixture;
        // other-tenant@example.com exists under tenant 99 only. Asking for
        // other-tenant@example.com under tenant 25 must not match — proves
        // the WHERE clause is tenant-scoped, not just email-scoped.
        $result = $this->verifier->verify('other-tenant@example.com', 'anything', 25, $this->passthroughVerifier('anything'));

        $this->assertSame(['matched' => false, 'reason' => 'no_matching_row'], $result);
    }

    public function testPasswordMismatchWhenRowFoundButVerifierRejects(): void
    {
        $result = $this->verifier->verify('real@example.com', 'wrong-password', 25, $this->passthroughVerifier('correct-password'));

        $this->assertSame(['matched' => false, 'reason' => 'password_mismatch'], $result);
    }

    public function testPasswordVerifierReceivesThePlaintextPasswordAndTheStoredHashInThatOrder(): void
    {
        $seenArgs = null;
        $spy = function (string $password, string $storedHash) use (&$seenArgs): bool {
            $seenArgs = [$password, $storedHash];

            return true;
        };

        $this->verifier->verify('real@example.com', 'my-password', 25, $spy);

        $this->assertSame(['my-password', 'stored-hash-25'], $seenArgs);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/ShadowLoginVerifierTest.php`
Expected: FAIL — `Error: Class "ShadowLoginVerifier" not found`.

- [ ] **Step 3: Write the implementation**

Create `tools/multitenant/ShadowLoginVerifier.php`:

```php
<?php

final class ShadowLoginVerifier
{
    public function __construct(private PDO $pdo)
    {
    }

    public function verify(string $email, string $password, int $tenantId, callable $passwordVerifier): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT password FROM staff WHERE email = :email AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return ['matched' => false, 'reason' => 'no_matching_row'];
        }

        if (!$passwordVerifier($password, $row['password'])) {
            return ['matched' => false, 'reason' => 'password_mismatch'];
        }

        return ['matched' => true, 'reason' => 'ok'];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit tests/tools/multitenant/ShadowLoginVerifierTest.php`
Expected: `OK (5 tests, ...)`.

- [ ] **Step 5: Run the full suite to confirm no regressions**

Run: `"C:\xampp81\php\php.exe" vendor/bin/phpunit`
Expected: prior total (61) + 5 new = 66 tests, all passing.

- [ ] **Step 6: Commit**

```bash
git add tools/multitenant/ShadowLoginVerifier.php tests/tools/multitenant/ShadowLoginVerifierTest.php
git commit -m "feat: add ShadowLoginVerifier, an isolated read-only tenant-credential check"
```

---

### Task 2: Wire the shadow-verify into `Site.php::login()`, guarded and try/catch-wrapped

**Files:**
- Modify: `application/controllers/Site.php:196-208` (inside the real-success branch, after `$this->session->set_userdata('admin', $session_data);` and before the role/redirect lines)

**Interfaces:**
- Consumes: `ShadowLoginVerifier::__construct(PDO $pdo)` and `verify(string $email, string $password, int $tenantId, callable $passwordVerifier): array` from Task 1, unchanged.
- Produces: nothing consumed by a later task — this is a leaf integration.

**Important — read this before editing:** `Site.php::login()` (lines 46-222) already contains a separate, pre-existing, **uncommitted** change of yours (the "MULTI BRANCH STAFF LOGIN FIX" block, lines 98-136) that is unrelated to this migration. Do not touch, reformat, or re-flow that block. Use the same surgical git-plumbing technique already proven twice in this project (Phase 3 Stage 3 Task 2, Phase 3 Stage 4 Task 2 — `git hash-object -w --no-filters` + `git update-index --cacheinfo` against the current HEAD blob, adding only this task's new lines) so the commit contains only the shadow-verify addition and none of the unrelated pending multi-branch work. Independently re-verify with `git show --stat`/`git diff` after committing that only the intended lines are in the commit and the rest of the file's uncommitted changes are still present and untouched in the working tree.

The existing multi-branch fix block (already in the file, do not modify) leaves a local `$db` array in scope (from `include(APPPATH . 'config/database.php')` at line 99) and, when a non-default branch matches, a `$found_group` variable holding that branch's CI3 db-group name (e.g. `'branch_25'` for Al-Hafeez Campus). Both are read-only inputs to this task's new code — reuse them, don't duplicate the `include` or re-derive the group name.

- [ ] **Step 1: Read the current exact block to confirm line numbers before editing**

```bash
sed -n '190,209p' application/controllers/Site.php
```

Confirm it matches (whitespace aside):

```php
                    $this->session->set_userdata('admin', $session_data);

                    $role      = $this->customlib->getStaffRole();
                    $role_name = json_decode($role)->name;
                    $this->customlib->setUserLog($this->input->post('username'), $role_name);

                    if (isset($_SESSION['redirect_to'])) {
                        redirect($_SESSION['redirect_to']);
                    } else {
                        redirect('admin/admin/dashboard');
                    }
```

If the surrounding lines have drifted from this (someone else edited the file since this plan was written), STOP and report the actual current content instead of guessing where to insert — do not force the edit.

- [ ] **Step 2: Insert the guarded shadow-verify block**

Insert immediately after `$this->customlib->setUserLog($this->input->post('username'), $role_name);` and before the `if (isset($_SESSION['redirect_to']))` line — i.e. as the very last thing that happens before the real redirect, so it can never affect anything the real login flow still depends on:

```php
                    // --- SHADOW TENANT LOGIN VERIFY (Phase 3 Stage 5) ---
                    // Read-only, pilot-tenant-only, best-effort proof that
                    // school_saas agrees with this real login. Never sets
                    // session data, never changes the redirect below, never
                    // touches $this->db (uses the `true` second arg to get
                    // an isolated connection object instead), and any
                    // failure here is swallowed. branch_25 == al_hafeez_campus
                    // == tenant_id 25 (multi_branch.id 25, confirmed live) —
                    // this never runs for the other 5 schools.
                    if (isset($found_group) && $found_group === 'branch_25') {
                        try {
                            require_once APPPATH . '../tools/multitenant/ShadowLoginVerifier.php';
                            $shadowDbConfig = $db['school_saas_pilot'];
                            $shadowPdo = new PDO(
                                'mysql:host=' . $shadowDbConfig['hostname'] . ';dbname=' . $shadowDbConfig['database'] . ';charset=utf8mb4',
                                $shadowDbConfig['username'],
                                $shadowDbConfig['password']
                            );
                            $shadowPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $shadowVerifier = new ShadowLoginVerifier($shadowPdo);
                            $shadowResult = $shadowVerifier->verify(
                                $login_post['email'],
                                $login_post['password'],
                                25,
                                [$this->enc_lib, 'passHashDyc']
                            );
                            log_message(
                                $shadowResult['matched'] ? 'info' : 'error',
                                '[ShadowTenantLoginVerify] email=' . $login_post['email']
                                . ' matched=' . ($shadowResult['matched'] ? '1' : '0')
                                . ' reason=' . $shadowResult['reason']
                            );
                        } catch (\Throwable $e) {
                            log_message('error', '[ShadowTenantLoginVerify] EXCEPTION ' . $e->getMessage());
                        }
                    }
                    // --- END SHADOW TENANT LOGIN VERIFY ---

```

- [ ] **Step 3: Lint the file**

```bash
"C:\xampp81\php\php.exe" -l application/controllers/Site.php
```

Expected: `No syntax errors detected in application/controllers/Site.php`.

- [ ] **Step 4: Live smoke-test that the common failure path is byte-for-byte unchanged**

This requires no real credentials — it is by far the most frequent real request this function receives in production (typos, wrong passwords), so proving it untouched is the highest-value, lowest-risk check available. Capture a baseline BEFORE this task's change (if not already captured, `git stash` this task's edit, run the request, note the response, then `git stash pop` to restore the edit) and compare:

```bash
curl -s -o /tmp/site_login_after.html -w "%{http_code}\n" http://localhost/web-app/site/login \
  -d "username=definitely-not-a-real-account@example.invalid&password=wrong"
grep -c "invalid_username_or_password\|Invalid" /tmp/site_login_after.html
```

Expected: HTTP `200`, and the response contains the same "invalid username or password" message CI3 rendered before this change (compare wording/structure against the baseline capture — do not just eyeball it, diff the two saved files). If the two differ in anything beyond incidental (e.g. CSRF token value, timestamp), STOP and report — that would mean the new code executed on a path it should never reach (this failed login never reaches the success branch, so `$found_group` gating this task added should be entirely unreached here; a diff would indicate something is wrong elsewhere in the request, not necessarily this task, but must be understood before proceeding).

- [ ] **Step 5: Confirm the guard truly cannot fire for a failed login**

```bash
grep "ShadowTenantLoginVerify" application/logs/log-$(date +%Y-%m-%d).php
```

Expected: no output (grep exits 1) — the failed login in Step 4 never reached the success branch at all, so no shadow-verify log line should exist from it. If a line IS present, STOP — the guard is in the wrong place and is firing on failure, which violates this stage's Global Constraints.

- [ ] **Step 6: Commit only this task's addition (git-plumbing technique, per the note above)**

```bash
git diff application/controllers/Site.php
```

Read the full diff first. Confirm it shows ONLY this task's new block as an addition, with the pre-existing uncommitted multi-branch fix block (lines 98-136) present but unmodified in the diff context. Then construct a minimal commit containing only the new lines, following the exact technique used in Phase 3 Stage 3 Task 2 and Phase 3 Stage 4 Task 2:

```bash
git show HEAD:application/controllers/Site.php > /tmp/site_php_head.php
# Manually splice this task's new block into a copy of the HEAD version at
# the same location (after setUserLog, before the redirect if-block), then:
git hash-object -w --no-filters /tmp/site_php_head_with_shadow_verify.php
git update-index --cacheinfo 100644 <blob-hash-from-previous-command> application/controllers/Site.php
git commit -m "feat: shadow-verify real Site.php pilot-tenant logins against school_saas (read-only)"
git show --stat HEAD
git diff HEAD -- application/controllers/Site.php
```

The final `git diff HEAD -- application/controllers/Site.php` must show the pre-existing uncommitted multi-branch fix block still present and unmodified in the working tree (i.e. still shows as a diff against the new HEAD, exactly as it did against the old HEAD) — proving nothing from that unrelated pending work was swept into this commit.

---

### Task 3: End-to-end proof + roadmap update

**Files:**
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`

**Interfaces:**
- Consumes: Task 1's `ShadowLoginVerifier` (already committed, unit-tested), Task 2's wiring (already committed, smoke-tested).
- Produces: nothing — this is the closing task.

- [ ] **Step 1: Confirm the known test credential is still intact (read-only)**

```bash
"C:\xampp81\mysql\bin\mysql.exe" -u root school_saas -e "SELECT id, email, tenant_id FROM staff WHERE email='rabiachauhan923@gmail.com';"
```

Expected: one row, `id=1`, `tenant_id=25`.

- [ ] **Step 2: Directly exercise `ShadowLoginVerifier` against the real `school_saas` data (still no HTTP, still read-only, still no Site.php involved)**

This proves the class correctly matches/rejects against real migrated data, closing the gap between "unit-tested against a synthetic fixture" (Task 1) and "works against the actual pilot tenant's real staff table" — without needing a real `al_hafeez_campus` password.

```bash
"C:\xampp81\php\php.exe" -r '
require "vendor/autoload.php";
require "tools/multitenant/ShadowLoginVerifier.php";
require "application/libraries/Enc_lib.php";
$pdo = new PDO("mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4", "root", "");
$enc = new Enc_lib();
$verifier = new ShadowLoginVerifier($pdo);
$ok = $verifier->verify("rabiachauhan923@gmail.com", "TestVerify123!", 25, [$enc, "passHashDyc"]);
$wrongPw = $verifier->verify("rabiachauhan923@gmail.com", "definitely-wrong", 25, [$enc, "passHashDyc"]);
$wrongTenant = $verifier->verify("rabiachauhan923@gmail.com", "TestVerify123!", 99, [$enc, "passHashDyc"]);
echo "correct: " . json_encode($ok) . "\n";
echo "wrong password: " . json_encode($wrongPw) . "\n";
echo "wrong tenant: " . json_encode($wrongTenant) . "\n";
'
```

Expected:
```
correct: {"matched":true,"reason":"ok"}
wrong password: {"matched":false,"reason":"password_mismatch"}
wrong tenant: {"matched":false,"reason":"no_matching_row"}
```

If any line doesn't match, STOP and report — do not proceed to the roadmap update.

- [ ] **Step 3: Re-run the full smoke test from Task 2 Step 4 once more, now against the fully-committed state**

```bash
curl -s -w "\n%{http_code}\n" http://localhost/web-app/site/login \
  -d "username=definitely-not-a-real-account@example.invalid&password=wrong"
```

Expected: same `200` + "invalid username or password" content as before. Confirms the committed (not just working-tree) state behaves identically.

- [ ] **Step 4: Run the full suite**

```bash
"C:\xampp81\php\php.exe" vendor/bin/phpunit
```

Expected: 66/66 passing (61 from before this stage + 5 from Task 1; Task 2 added no new automated tests since it cannot be driven without a real branch_25 credential — this is a known, documented limitation of this stage, not an oversight).

- [ ] **Step 5: Update the roadmap**

Edit `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`. Add a new entry after the Phase 3 Stage 4 entry, following that entry's style: what was built, the real commit range, what was proven, and — importantly — an honest statement of this stage's actual limitation (no real `branch_25` HTTP-level trigger was exercised, because that would have required either touching a real school's live password or inserting new data into a live production database, both explicitly rejected as out of scope for this stage; Task 3 Step 2's direct-class invocation against real `school_saas` data is the closest verification available without doing either). Also note this stage deliberately does NOT set `admin_tenant_id` and does NOT change the allowlist gate — real pilot-tenant users see zero behavior change.

- [ ] **Step 6: Commit the roadmap update**

```bash
git add docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "docs: mark Phase 3 Stage 5 (shadow-verify real Site.php login) complete"
```

---

## Final whole-stage review (after Task 3)

Dispatch an adversarial reviewer (same rigor as every prior stage's final review) to independently:
- Re-read the full diff across all 3 tasks and confirm `Site.php`'s change is exactly the guarded, try/catch-wrapped, `$this->db`-untouched block described above — no scope creep.
- Independently re-run Task 3 Step 2's direct-class check against real `school_saas` data.
- Independently re-run the Task 2 Step 4 failure-path smoke test with the reviewer's own choice of bogus credentials, and additionally try a GET to `site/login` (unauthenticated form render) to confirm it too is unaffected.
- Confirm via code reading that no other of the 6 schools' `$found_group` values can ever equal `'branch_25'` (i.e. `branch_25` is uniquely Al-Hafeez Campus — re-verify against the live `multi_branch` table, don't just trust this plan's earlier claim).
- Confirm `Site.php`'s commit (`1ae18656` + doc-fix `839e6786`) contains only the shadow-verify addition. NOTE (correction, discovered mid-Task-2): this plan's earlier text assumed the "MULTI BRANCH STAFF LOGIN FIX" block was still uncommitted at execution time — it was not; it had already landed in commit `2e507f3c`, four days before this plan was written. `Site.php` was clean going into Task 2, so a plain commit was used instead of the git-plumbing splice technique (see the progress ledger's Task 2 entry). Confirm that plain commit is still exactly this task's addition (`git show --stat 1ae18656`) and that the multi-branch-fix block is present and unmodified in the file (it is now committed history, not pending work — do not expect it to show as uncommitted).
- Confirm the roadmap entry is factually accurate against everything above.
- Report Ready to merge (Yes/With fixes/No) plus Critical/Important/Minor findings, same format as every prior stage.
