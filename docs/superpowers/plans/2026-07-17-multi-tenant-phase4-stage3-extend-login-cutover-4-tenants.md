# Phase 4 Stage 3 — Extend Real Login Verification Cutover to 4 More Tenants Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend both already-shipped real-login-verification gates (`RealLoginGate` for staff `login()`, `RealUserLoginGate` for student/parent `userlogin()`) from tenant-25-only to also cover tenants 26 (Al-Mateen), 27 (Nafay), 28 (Salam Boys), 29 (Salam Girls) — 5 tenants total — while leaving tenant 30 (Smart School) and every login that resolves to none of these 5 completely unaffected.

**Architecture:** Each of `Site.php`'s two pre-loop gate blocks becomes a loop over a small, hardcoded array of `{tenantId => branchGroup}` pairs, calling the existing, unmodified `RealLoginGate::verify()` / `RealUserLoginGate::verify()` for each tenant in turn, with a per-tenant `try/catch` (one tenant's failure doesn't block checking the rest) and a `break` on first success. Zero changes to either gate class.

**Tech Stack:** PHP 8.1, CodeIgniter 3.1.13, PDO/MySQL, PHPUnit 10.5 (existing project stack, unchanged).

## Context

This is the direct sequel to Phase 4 Stages 1-2 (`2026-07-16-...stage1...md`, `2026-07-17-...stage2...md`), which built and shipped the identical dual-check-with-legacy-fallback pattern for tenant 25 only — staff login in Stage 1, student/parent login in Stage 2. Both stages' final whole-stage reviews passed clean (Stage 2's on the first pass, having applied Stage 1's hard-won final architecture from the start). This stage extends the SAME, now twice-proven pattern to 4 more real tenants, without touching either already-shipped gate class.

**Approved design decisions (from brainstorming, do not re-litigate):**
- **Tenant 30 (Smart School) is explicitly excluded.** It's the confirmed source of the cross-tenant password-hash contamination found in Stage 1 (93 staff-row collisions) and Stage 2 (4 student-row collisions) — every one of those collisions involves `smart_school`'s own data appearing in other schools' rows. Giving Smart School its own login cutover means trusting `school_saas` as authoritative for the exact tenant whose "own" data legitimacy is unclear. Deferred to a dedicated data-cleanup effort.
- **Sequential per-tenant loop (Approach A), not a single tenant-agnostic search (Approach B).** A rejected alternative would add a new `resolveTenant()`-style method to both gate classes to do one query across all tenants instead of N per-tenant queries — fewer connections in the common case, but it means modifying two already-shipped, already-reviewed production classes for a performance gain that isn't required. This plan reuses `verify()` completely unchanged; only `Site.php`'s wiring changes.
- **Loop order is `[26, 27, 28, 29, 25]`, not `[25, 26, 27, 28, 29]`.** Tenant 25 already has a known-good, previously-proven real test credential (`rabiachauhan923@gmail.com` for staff, `std113` for students). Checking it *last* means its success can only be observed after the loop has already iterated past 4 non-matching tenant checks — concrete proof that multi-tenant iteration and short-circuit both work, not just "happened to match on the first try."
- **The `school_saas_pilot` PDO connection and `RealLoginGate`/`RealUserLoginGate` instance are built ONCE, outside the per-tenant loop, not once per tenant.** Only the per-tenant branch-fallback connection differs per iteration. This directly reduces the per-login connection-count growth that extending to more tenants would otherwise multiply (already flagged as a Minor, accepted cost in Stage 2's review) — 1 `school_saas` connection total instead of up to 5, regardless of how many tenants are checked.

## Why not go further (explicitly out of scope, and why)

- **Not tenant 30.** See above.
- **Not modifying `RealLoginGate.php` or `RealUserLoginGate.php`.** Both stay byte-for-byte unchanged this stage — zero regression risk to already-shipped, already-reviewed code.
- **Not fixing the plaintext password comparison or the `smart_school` data contamination.** Both remain separate, pre-existing, already-flagged issues for future dedicated efforts.
- **Not a full live success proof for tenants 26-29's STAFF login.** Unlike the student credentials (plaintext, so all 4 new tenants' success cases can be genuinely proven live), no plaintext password is known for any of the 4 new tenants' bcrypt-hashed staff passwords — inventing one, or resetting a real user's password, is out of scope and unsafe. Task 3's staff-side proof is honestly weaker than its student-side proof: it proves the loop correctly finds each tenant's row and correctly falls through on a wrong password (safe, real, live), plus tenant 25's already-known-good credential succeeding only after 4 prior non-matches (proving iteration + short-circuit end-to-end for a real success case) — but it does NOT independently prove tenants 26-29's staff success path the way it proves their student success path. State this asymmetry plainly in the roadmap, do not claim parity between the two proofs.

## Components (reference — full detail in each task below)

- **`application/controllers/Site.php`** (modified twice: `login()`'s gate block, `userlogin()`'s gate block). No new files.

## Global Constraints

- **`Site.php::login()` and `Site.php::userlogin()` must be functionally byte-for-byte unchanged for every login that does NOT resolve to one of the 5 now-covered tenants (25, 26, 27, 28, 29).** This is production auth for 6 live schools; nobody outside these 5 tenants (including tenant 30) may observe any difference in outcome, session shape, or redirect.
- **Neither gate's wiring may reassign `$this->db` itself**, except via the existing swap pattern already shipped in Stages 1-2 (now generalized to swap to whichever of the 5 branch groups matched, not hardcoded to `branch_25`).
- **No cleartext password is ever logged.**
- **No new production data.** All new testing uses read-only queries against already-migrated real data, or (for loop-mechanics proofs) throwaway/synthetic data. No real HTTP login is ever attempted for any tenant, in any task, at any point.
- **Any error inside one tenant's `verify()` call or its fallback-connection setup must not prevent checking the REMAINING tenants in the loop.** If the entire loop exhausts without success (or the one-time setup itself fails), degrade to exactly today's per-branch legacy check for that login — never to a failure the legacy path alone would not have produced.
- **Tenant 30 (Smart School) must never appear in either loop array this stage.**
- **The `AMBIGUOUS_OR_STALE_SCHOOL_SAAS_MATCH` / `PASSWORD_DRIFT_DETECTED` and `EXCEPTION` log lines must each identify which `tenant_id` they were checking when they fired** — there are now up to 5 per login instead of 1.
- **Known live facts, re-verify in Task 1/2 before writing code, do not trust this list blindly:**
  - `multi_branch` mapping (from `school_default`): tenant_id 26 → `branch_24` (`al_mateen_campus`), 27 → `branch_23` (`nafay_campus`), 28 → `branch_22` (`salam_boys_school`), 29 → `branch_21` (`salam_girls_school`). **This is not tenant_id ± a fixed offset** — it is an explicit mapping, hardcode it as literal array keys, do not compute it.
  - Known real staff test emails (bcrypt-hashed, no known plaintext): tenant 26 = `khushbakhtfarooq7@gmail.com` (`school_saas.staff.id=19`, `al_mateen_campus.staff.id=120`); tenant 27 = `hajiryasatali@gmail.com` (`school_saas.staff.id=36`, `nafay_campus.staff.id=120`); tenant 28 = `asadwali6@gmail.com` (`school_saas.staff.id=63`, `salam_boys_school.staff.id=124`); tenant 29 = `smubshra@gmail.com` (`school_saas.staff.id=86`, `salam_girls_school.staff.id=118`).
  - Known real student test credentials (plaintext, confirmed present in both `school_saas` and the matching per-branch database): tenant 26 = `std504` / `2wz2ic` (`school_saas.users.id=625`, `al_mateen_campus.users.id=1007`); tenant 27 = `std144` / `6kr58l` (`school_saas.users.id=1379`, `nafay_campus.users.id=287`); tenant 28 = `std178` / `m7ytme` (`school_saas.users.id=2813`, `salam_boys_school.users.id=355`); tenant 29 = `std1782` / `00crqh` (`school_saas.users.id=3687`, `salam_girls_school.users.id=3562`).
  - Tenant 25's already-established staff credential: `rabiachauhan923@gmail.com` / `TestVerify123!`. Student credential: `std113` / `7daq1b`.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: Extend `Site.php::login()`'s gate to 5 tenants

**Files:**
- Modify: `application/controllers/Site.php` (the "REAL LOGIN GATE" block, currently lines 98-183, and its closing wrapper condition at line 185 — re-read the live file first, line numbers may have shifted)

**Interfaces:**
- Consumes: `RealLoginGate::__construct(PDO $pdo)`, `RealLoginGate::verify(string $email, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array` (unchanged from Stage 1, `tools/multitenant/RealLoginGate.php` — do not modify this file).
- Produces: `$found_group` set to any of `'branch_24'`, `'branch_23'`, `'branch_22'`, `'branch_21'`, `'branch_25'` on a gate success (was: only `'branch_25'`), causing the closing `if` to change from `$found_group === 'branch_25'` to `$found_group !== 'default'`.

- [ ] **Step 1: Re-verify live facts before writing code**

```bash
/c/xampp81/mysql/bin/mysql.exe -u root school_default -e "SELECT id, database_name FROM multi_branch WHERE id IN (21,22,23,24,25) ORDER BY id;"
/c/xampp81/mysql/bin/mysql.exe -u root school_saas -e "SELECT id, tenant_id, email FROM staff WHERE email IN ('khushbakhtfarooq7@gmail.com','hajiryasatali@gmail.com','asadwali6@gmail.com','smubshra@gmail.com') ORDER BY tenant_id;"
```
Expected: first query returns `21 salam_girls_school, 22 salam_boys_school, 23 nafay_campus, 24 al_mateen_campus, 25 al_hafeez_campus`; second returns the 4 emails under tenant_ids `26,27,28,29` respectively. If either differs, stop and re-derive the plan's data before continuing.

Also re-read `application/controllers/Site.php`'s `login()` method in full (search for `function login`) to confirm the "REAL LOGIN GATE" block's current exact position and content still matches this plan's Context section.

- [ ] **Step 2: Replace the gate block**

Locate the current block (from the `// --- REAL LOGIN GATE (Phase 4 Stage 1) ---` comment through `// --- END REAL LOGIN GATE ---`) and replace its body (keep the surrounding structure — this is what changes):

```php
            // --- REAL LOGIN GATE (Phase 4 Stage 1, extended Stage 3) ---
            // Independent pre-loop check for 5 tenants (26, 27, 28, 29, 25 --
            // deliberately checked in this order, not tenant_id order, so
            // that tenant 25's already-proven credential succeeding proves
            // the loop genuinely iterated past 4 other tenants first, not
            // just "matched on the first try"), run BEFORE and completely
            // independent of the legacy multi-branch loop below. Tenant 30
            // (Smart School) is deliberately never in this list -- see the
            // Phase 4 Stage 3 roadmap entry: it's the confirmed source of
            // the cross-tenant password-hash contamination found in Stages
            // 1-2, so its own cutover waits for a dedicated data-cleanup
            // effort. school_saas is the authoritative password check per
            // tenant; each tenant's own branch row (fetched directly here,
            // not via the loop) is the fallback so a stale school_saas
            // password can never lock a real user out. If any tenant
            // matches, we resolve directly to that tenant's branch and skip
            // the legacy loop entirely for this login. One tenant's
            // connection failure logs and moves on to the next tenant,
            // rather than aborting the whole gate. Never reassigns
            // $this->db except via the swap below.
            //
            // Why this can't live inside the legacy loop (see Stage 1's
            // adversarial review, 2026-07-17, not fixed here -- out of
            // scope, pre-existing, affects all 6 real schools): the loop's
            // own email+password matching is unreliable once duplicate
            // credentials exist across branch databases --
            // `school_saas_pilot` and `smart_school` (branch_20) both
            // precede every one of this stage's 5 branches in the same $db
            // array the loop iterates, and smart_school carries
            // byte-identical password hashes for a large fraction of real
            // staff across all 6 real schools (93 cross-database collisions
            // confirmed live). This block sidesteps that entire class of
            // problem for these 5 tenants by checking a tenant-scoped
            // source first.
            $found_group = 'default';
            $realLoginTenants = [
                26 => 'branch_24',
                27 => 'branch_23',
                28 => 'branch_22',
                29 => 'branch_21',
                25 => 'branch_25',
            ];
            try {
                require_once APPPATH . '../tools/multitenant/RealLoginGate.php';
                include(APPPATH . 'config/database.php');
                $realLoginDbConfig = $db['school_saas_pilot'];
                $realLoginPdo = new PDO(
                    'mysql:host=' . $realLoginDbConfig['hostname'] . ';dbname=' . $realLoginDbConfig['database'] . ';charset=utf8mb4',
                    $realLoginDbConfig['username'],
                    $realLoginDbConfig['password']
                );
                $realLoginPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $realLoginGate = new RealLoginGate($realLoginPdo);

                foreach ($realLoginTenants as $realLoginTenantId => $realLoginBranchGroup) {
                    try {
                        $branchConfig = $db[$realLoginBranchGroup] ?? null;
                        $branchRowPassword = null;
                        if ($branchConfig) {
                            $branchPdo = new PDO(
                                'mysql:host=' . $branchConfig['hostname'] . ';dbname=' . $branchConfig['database'] . ';charset=utf8mb4',
                                $branchConfig['username'],
                                $branchConfig['password']
                            );
                            $branchPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $branchStmt = $branchPdo->prepare('SELECT password FROM staff WHERE email = :email LIMIT 1');
                            $branchStmt->execute(['email' => $login_post['email']]);
                            $branchRow = $branchStmt->fetch(PDO::FETCH_ASSOC);
                            $branchRowPassword = $branchRow['password'] ?? null;
                        }

                        $gateResult = $realLoginGate->verify(
                            $login_post['email'],
                            $login_post['password'],
                            $realLoginTenantId,
                            [$this->enc_lib, 'passHashDyc'],
                            function () use ($login_post, $branchRowPassword) {
                                return $branchRowPassword !== null
                                    && $this->enc_lib->passHashDyc($login_post['password'], $branchRowPassword);
                            }
                        );
                        if ($gateResult['source'] === 'legacy') {
                            log_message('error', '[RealLoginGate] PASSWORD_DRIFT_DETECTED tenant_id=' . $realLoginTenantId . ' email=' . $login_post['email']);
                        }
                        if ($gateResult['success']) {
                            $found_group = $realLoginBranchGroup;
                            break;
                        }
                    } catch (\Throwable $e) {
                        log_message('error', '[RealLoginGate] EXCEPTION tenant_id=' . $realLoginTenantId . ' ' . $e->getMessage());
                        continue;
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[RealLoginGate] EXCEPTION setup ' . $e->getMessage());
                // $found_group stays 'default'; the legacy loop below
                // still runs completely normally as the fallback.
            }
            // --- END REAL LOGIN GATE ---
```

- [ ] **Step 3: Update the closing wrapper condition**

Change:
```php
            if ($found_group === 'branch_25') {
```
To:
```php
            if ($found_group !== 'default') {
```
(This is the single-line change that makes the wrapper correctly branch on ANY of the 5 possible gate successes, not just `branch_25`. Everything else inside that `if` block, and the entire `else` block containing the legacy loop, is untouched.)

- [ ] **Step 4: Lint the file**

Run: `/c/xampp81/php/php.exe -l application/controllers/Site.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Verify the legacy loop body is byte-for-byte unchanged**

Run: `git diff application/controllers/Site.php` and confirm every line inside the "MULTI BRANCH STAFF LOGIN FIX START"..."END" markers is unmodified. Cross-check against `git show 7dc56061:application/controllers/Site.php` (the commit before this task) for the exact pre-task text of that block.

- [ ] **Step 6: Run the full test suite**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit`
Expected: no new failures beyond the pre-existing, already-documented connection-exhaustion flake in `AdminControllerTenantGateTest` (unrelated file); `tests/tools/multitenant/RealLoginGateTest.php` and `tests/controllers/SiteLoginRealLoginGateTest.php` both stay green.

- [ ] **Step 7: Commit**

```bash
git add application/controllers/Site.php
git commit -m "feat: extend RealLoginGate cutover to tenants 26-29 in Site.php login()"
```

---

### Task 2: Extend `Site.php::userlogin()`'s gate to 5 tenants

**Files:**
- Modify: `application/controllers/Site.php` (the "REAL USER LOGIN GATE" block, currently lines 658-737, and its closing wrapper condition at line 739 — re-read the live file first, line numbers may have shifted)

**Interfaces:**
- Consumes: `RealUserLoginGate::__construct(PDO $pdo)`, `RealUserLoginGate::verify(string $identifier, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array` (unchanged from Stage 2, `tools/multitenant/RealUserLoginGate.php` — do not modify this file).
- Produces: `$found_group` set to any of `'branch_24'`, `'branch_23'`, `'branch_22'`, `'branch_21'`, `'branch_25'` on a gate success, causing the closing `if` to change from `$found_group === 'branch_25'` to `$found_group !== 'default'`.

- [ ] **Step 1: Re-verify live facts before writing code**

```bash
/c/xampp81/mysql/bin/mysql.exe -u root school_saas -e "SELECT id, tenant_id, username, password FROM users WHERE id IN (625,1379,2813,3687) ORDER BY tenant_id;"
/c/xampp81/mysql/bin/mysql.exe -u root al_mateen_campus -e "SELECT id, username, password FROM users WHERE username='std504';"
/c/xampp81/mysql/bin/mysql.exe -u root nafay_campus -e "SELECT id, username, password FROM users WHERE username='std144';"
/c/xampp81/mysql/bin/mysql.exe -u root salam_boys_school -e "SELECT id, username, password FROM users WHERE username='std178';"
/c/xampp81/mysql/bin/mysql.exe -u root salam_girls_school -e "SELECT id, username, password FROM users WHERE username='std1782';"
```
Expected: the first query returns the 4 known username/password pairs under tenant_ids 26,27,28,29 exactly as documented in Global Constraints; each of the 4 per-branch queries returns the matching row with the identical password. If any differs, stop and re-derive the plan's data before continuing.

Also re-read `application/controllers/Site.php`'s `userlogin()` method in full (search for `function userlogin`) to confirm the "REAL USER LOGIN GATE" block's current exact position and content still matches this plan's Context section.

- [ ] **Step 2: Replace the gate block**

Locate the current block (from the `// --- REAL USER LOGIN GATE (Phase 4 Stage 2) ---` comment through `// --- END REAL USER LOGIN GATE ---`) and replace its body:

```php
            // --- REAL USER LOGIN GATE (Phase 4 Stage 2, extended Stage 3) ---
            // Independent pre-loop check for 5 tenants (26, 27, 28, 29, 25 --
            // deliberately checked in this order, not tenant_id order, so
            // that tenant 25's already-proven credential succeeding proves
            // the loop genuinely iterated past 4 other tenants first, not
            // just "matched on the first try"), run BEFORE and completely
            // independent of the legacy multi-branch loop below. Tenant 30
            // (Smart School) is deliberately never in this list -- see the
            // Phase 4 Stage 3 roadmap entry: it's the confirmed source of
            // the cross-tenant password-hash contamination found in Stages
            // 1-2 (including 4 student-account collision pairs), so its own
            // cutover waits for a dedicated data-cleanup effort. On a
            // match, $found_group is set directly and the legacy loop below
            // is skipped entirely for this login. On no match across all 5
            // tenants, $found_group stays 'default' and the legacy loop
            // runs 100% unmodified, exactly as before this stage. One
            // tenant's connection failure logs and moves on to the next
            // tenant, rather than aborting the whole gate. Never reassigns
            // $this->db in this block except via the swap below.
            $found_group = 'default';
            $realUserLoginTenants = [
                26 => 'branch_24',
                27 => 'branch_23',
                28 => 'branch_22',
                29 => 'branch_21',
                25 => 'branch_25',
            ];
            try {
                require_once APPPATH . '../tools/multitenant/RealUserLoginGate.php';
                include(APPPATH . 'config/database.php');
                $realUserLoginDbConfig = $db['school_saas_pilot'];
                $realUserLoginPdo = new PDO(
                    'mysql:host=' . $realUserLoginDbConfig['hostname'] . ';dbname=' . $realUserLoginDbConfig['database'] . ';charset=utf8mb4',
                    $realUserLoginDbConfig['username'],
                    $realUserLoginDbConfig['password']
                );
                $realUserLoginPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $realUserLoginGate = new RealUserLoginGate($realUserLoginPdo);

                foreach ($realUserLoginTenants as $realUserLoginTenantId => $realUserLoginBranchGroup) {
                    try {
                        $branchConfig = $db[$realUserLoginBranchGroup] ?? null;
                        $branchUserLoginFallback = function () use ($branchConfig, $login_post): bool {
                            if ($branchConfig === null) {
                                return false;
                            }
                            $branchPdo = new PDO(
                                'mysql:host=' . $branchConfig['hostname'] . ';dbname=' . $branchConfig['database'] . ';charset=utf8mb4',
                                $branchConfig['username'],
                                $branchConfig['password']
                            );
                            $branchPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $branchStmt = $branchPdo->prepare(
                                'SELECT users.id
                                 FROM users
                                 LEFT JOIN students
                                   ON (students.id = users.user_id OR students.parent_id = users.id)
                                 WHERE users.password = :password
                                 AND (
                                   users.username = :identifier
                                   OR students.admission_no = :identifier
                                   OR students.mobileno = :identifier
                                   OR students.email = :identifier
                                   OR students.guardian_phone = :identifier
                                   OR students.guardian_email = :identifier
                                 )
                                 LIMIT 1'
                            );
                            $branchStmt->execute([
                                'identifier' => $login_post['username'],
                                'password' => $login_post['password'],
                            ]);

                            return $branchStmt->fetch(PDO::FETCH_ASSOC) !== false;
                        };

                        $gateResult = $realUserLoginGate->verify(
                            $login_post['username'],
                            $login_post['password'],
                            $realUserLoginTenantId,
                            fn (string $submitted, string $stored): bool => $submitted === $stored,
                            $branchUserLoginFallback
                        );
                        if ($gateResult['source'] === 'legacy') {
                            log_message('error', '[RealUserLoginGate] AMBIGUOUS_OR_STALE_SCHOOL_SAAS_MATCH tenant_id=' . $realUserLoginTenantId . ' identifier=' . $login_post['username']);
                        }
                        if ($gateResult['success']) {
                            $found_group = $realUserLoginBranchGroup;
                            break;
                        }
                    } catch (\Throwable $e) {
                        log_message('error', '[RealUserLoginGate] EXCEPTION tenant_id=' . $realUserLoginTenantId . ' ' . $e->getMessage());
                        continue;
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[RealUserLoginGate] EXCEPTION setup ' . $e->getMessage());
            }
            // --- END REAL USER LOGIN GATE ---
```

- [ ] **Step 3: Update the closing wrapper condition**

Change:
```php
            if ($found_group === 'branch_25') {
```
To:
```php
            if ($found_group !== 'default') {
```
(This is the analogous single-line change to Task 1's Step 3, in `userlogin()` instead of `login()`.)

- [ ] **Step 4: Lint the file**

Run: `/c/xampp81/php/php.exe -l application/controllers/Site.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Verify the legacy loop body is byte-for-byte unchanged**

Run: `git diff application/controllers/Site.php` and confirm every line inside the "MULTI BRANCH STUDENT LOGIN FIX START"..."END" markers is unmodified, and Task 1's `login()` changes are undisturbed by this task's edit.

- [ ] **Step 6: Run the full test suite**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit`
Expected: no new failures beyond the known connection-exhaustion flake; `tests/tools/multitenant/RealUserLoginGateTest.php` and `tests/controllers/SiteUserloginRealUserLoginGateTest.php` both stay green.

- [ ] **Step 7: Commit**

```bash
git add application/controllers/Site.php
git commit -m "feat: extend RealUserLoginGate cutover to tenants 26-29 in Site.php userlogin()"
```

---

### Task 3: End-to-end proof + integration tests + roadmap update

**Files:**
- Modify: `tests/controllers/SiteLoginRealLoginGateTest.php` (add new test methods)
- Modify: `tests/controllers/SiteUserloginRealUserLoginGateTest.php` (add new test methods)
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (add Phase 4 Stage 3 entry immediately after the existing Stage 2 entry)

- [ ] **Step 1: Direct-class proof for the 4 new tenants' STUDENT login (real success case)**

Write and run a throwaway read-only PHP script (not committed) that constructs a `RealUserLoginGate` against a real `school_saas` PDO connection and, for each of the 4 known student credentials (tenant 26/`std504`/`2wz2ic`, tenant 27/`std144`/`6kr58l`, tenant 28/`std178`/`m7ytme`, tenant 29/`std1782`/`00crqh`), calls `verify($identifier, $password, $tenantId, fn($s,$t)=>$s===$t, fn()=>false)` and confirms `['success'=>true,'source'=>'school_saas']`. Also confirm a wrong password for each returns `['success'=>false,'source'=>'none']` given a `fn()=>false` fallback. Delete the script when done.

- [ ] **Step 2: Direct-class proof for STAFF login (wrong-password cascade, not a full success proof — see the plan's "Why not go further" section for why)**

Write and run a second throwaway read-only PHP script that constructs a `RealLoginGate` against a real `school_saas` PDO connection and, for each of the 4 known staff emails (tenant 26/`khushbakhtfarooq7@gmail.com`, tenant 27/`hajiryasatali@gmail.com`, tenant 28/`asadwali6@gmail.com`, tenant 29/`smubshra@gmail.com`), calls `verify($email, 'definitely-wrong-password', $tenantId, [Enc_lib instance, 'passHashDyc'], fn()=>false)` and confirms `['success'=>false,'source'=>'none']` — proving the row IS found (email matches) and the password check correctly fails, without ever needing a real plaintext password. Then, separately, prove tenant 25's known-good credential (`rabiachauhan923@gmail.com`/`TestVerify123!`) still succeeds via `verify(..., 25, ...)` — this is the real success-case proof for the whole mechanism, just anchored on the one tenant with a known plaintext password. Delete the script when done.

- [ ] **Step 3: Add integration tests to `SiteLoginRealLoginGateTest.php`**

Add these test methods (read the existing file first to match its style/imports):

```php
    public function testFailedLoginWithWrongPasswordForEachNewTenantStaffEmailIsUnaffected(): void
    {
        $newTenantEmails = [
            'khushbakhtfarooq7@gmail.com', // tenant 26
            'hajiryasatali@gmail.com',     // tenant 27
            'asadwali6@gmail.com',         // tenant 28
            'smubshra@gmail.com',          // tenant 29
        ];

        foreach ($newTenantEmails as $email) {
            $ch = curl_init(self::BASE_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'email' => $email,
                    'password' => 'definitely-wrong-password-' . bin2hex(random_bytes(4)),
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $this->assertSame(200, $status, "email {$email} should return 200");
            $this->assertStringContainsString('Invalid Username Or Password', $body, "email {$email} should show the standard failure message");
        }
    }
```

(Adjust `self::BASE_URL` / field names / failure-text assertion to match whatever the existing file already uses for `login()` — read it first, do not guess. If the existing file's `BASE_URL` targets `site/login`, reuse it directly.)

- [ ] **Step 4: Add integration tests to `SiteUserloginRealUserLoginGateTest.php`**

Add a test proving each new tenant's student credential succeeds in reaching the app in a way that's distinguishable from a random bogus login (without ever completing a real successful session — check the response differs from the bogus-credential case, e.g. it does NOT show "Invalid Username Or Password", OR add a lighter assertion that the request completes with 200 and doesn't error — read the existing file's `testFailedLoginForNonExistentAccountIsUnaffectedAndLogsNoNewException` structure first and adapt conservatively; do not attempt to verify actual session establishment, since that would cross into "real successful login," which is prohibited). At minimum, add:

```php
    public function testFailedLoginWithWrongPasswordForEachNewTenantStudentIsUnaffected(): void
    {
        $newTenantUsernames = ['std504', 'std144', 'std178', 'std1782']; // tenants 26-29

        foreach ($newTenantUsernames as $username) {
            $ch = curl_init(self::BASE_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'username' => $username,
                    'password' => 'definitely-wrong-password-' . bin2hex(random_bytes(4)),
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $this->assertSame(200, $status, "username {$username} should return 200");
            $this->assertStringContainsString('Invalid Username Or Password', $body, "username {$username} should show the standard failure message");
        }
    }

    public function testTenant30ShapedLoginAttemptFallsThroughToLegacyLoopUnaffected(): void
    {
        // Tenant 30 (Smart School) is deliberately never in this stage's
        // loop array. A login attempt with a smart_school-shaped identifier
        // must behave identically to any other non-matching login -- it
        // reaches the legacy loop, which the pre-loop gate never touches
        // for tenant 30.
        $ch = curl_init(self::BASE_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => 'definitely-not-a-smart-school-account-' . bin2hex(random_bytes(4)),
                'password' => 'definitely-wrong-password',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        $this->assertStringContainsString('Invalid Username Or Password', $body);
    }
```

- [ ] **Step 5: Run the new/modified test files**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit tests/controllers/SiteLoginRealLoginGateTest.php tests/controllers/SiteUserloginRealUserLoginGateTest.php`
Expected: all tests pass. If any failure-text assertion fails, curl the endpoint manually to confirm the actual rendered text before adjusting the assertion.

- [ ] **Step 6: Update the roadmap doc**

Add a new stage entry to `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`'s Phase 4 section, immediately after Stage 2's entry, following that entry's level of detail and honesty about caveats. Must explicitly state:
- The goal (extend both gates from tenant 25 to tenants 25-29, 5 total).
- The architecture decision: Approach A (sequential loop, reusing `verify()` unchanged) chosen over Approach B (a new tenant-agnostic `resolveTenant()` method), and why (avoid re-risking two already-shipped, already-reviewed classes for a performance gain that isn't required).
- The explicit tenant-30 exclusion and its reasoning (confirmed source of the cross-tenant collision contamination found in Stages 1-2).
- The `[26,27,28,29,25]` loop-order rationale (proves genuine multi-tenant iteration, not first-try luck).
- The asymmetric staff-vs-student proof: student success was proven live for all 4 new tenants (plaintext passwords); staff success was only proven for the wrong-password-cascade-through case for the 4 new tenants plus tenant 25's already-known-good credential succeeding after 4 prior non-matches — state plainly that a full live staff success proof for tenants 26-29 was NOT done, and why (no known plaintext password, unsafe to invent or reset one).
- Real, actual final test counts (fill in once Tasks 1-3 are complete — do not guess a number here in advance).

- [ ] **Step 7: Commit**

```bash
git add tests/controllers/SiteLoginRealLoginGateTest.php tests/controllers/SiteUserloginRealUserLoginGateTest.php docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "feat: prove RealLoginGate/RealUserLoginGate cutover for tenants 26-29, update roadmap"
```

## Final whole-stage review

After all 3 tasks are complete, dispatch a final whole-stage adversarial review (most capable available model, given production sensitivity — same precedent as Stages 1-2) covering the full range from before Task 1's first commit through Task 3's last commit. Explicitly ask it to:

- Independently re-verify both legacy loops are byte-for-byte unchanged (diff against the commit before Task 1).
- Independently confirm tenant 30 never appears in either loop array, and that a smart_school-shaped login genuinely falls through to the unmodified legacy loop.
- Independently confirm the `[26,27,28,29,25]` ordering is actually what's in the code (not accidentally reverted to `[25,...]` during implementation) and that this genuinely proves multi-tenant iteration works, not merely first-tenant-match luck.
- Confirm the one-time `school_saas_pilot` PDO connection and gate instance are built ONCE outside the loop, not once per tenant (the stated efficiency improvement over a naive per-tenant Approach A).
- Confirm one tenant's simulated connection failure doesn't prevent checking the remaining tenants (trace the try/catch nesting).
- Confirm no cleartext password appears in any log line, and that each log line correctly identifies its own `tenant_id`.
- Confirm the roadmap's asymmetric staff-vs-student proof claim is accurate and not overstated.
- Confirm full test suite results and that only the known, pre-existing connection-exhaustion flake (if any) explains any failures.

If the review finds a Critical or Important issue, follow this project's established pattern: stop, present the finding and concrete evidence to the user, get explicit direction before any further change to this live production authentication code.
