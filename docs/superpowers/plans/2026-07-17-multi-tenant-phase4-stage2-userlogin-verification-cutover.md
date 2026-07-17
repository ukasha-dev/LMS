# Phase 4 Stage 2 — Real userlogin() Verification Cutover (Pilot Tenant) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `school_saas.users`/`school_saas.students` the authoritative password check for real student/parent logins resolving to tenant 25 (Al-Hafeez / `al_hafeez_campus` / `branch_25`), with automatic logged fallback to today's legacy per-branch check, while leaving session establishment and every other tenant's `userlogin()` behavior completely unchanged.

**Architecture:** One new framework-agnostic class, `RealUserLoginGate` (`tools/multitenant/RealUserLoginGate.php`), takes a `PDO` connection to `school_saas` and orchestrates a school_saas-first, legacy-fallback dual-check without touching CI3 or `$this->db` — same isolation principle as `RealLoginGate` and `ShadowLoginVerifier`. Unlike Stage 1's *first* (later reworked) attempt, this stage wires the check in as an **independent pre-loop gate from the start**: it runs before `Site.php::userlogin()`'s existing "MULTI BRANCH STUDENT LOGIN FIX" loop, and on a match skips that loop entirely; on no match the loop runs 100% unmodified.

**Tech Stack:** PHP 8.1, CodeIgniter 3.1.13, PDO/MySQL, PHPUnit 10.5 (existing project stack, unchanged).

## Context

This is the direct sequel to Phase 4 Stage 1 (`2026-07-16-multi-tenant-phase4-stage1-real-login-verification-cutover.md`), which built the identical pattern for staff login (`Site.php::login()`). That stage shipped after three same-day rounds of real production bugs found by its final review, each deeper than the last — this plan is written with those lessons already applied, not discovered again:

1. **`school_saas_pilot` short-circuit.** The legacy loop's original skip condition only excluded `'default'`, so a login could match the non-tenant-scoped `school_saas_pilot` config group before ever reaching `branch_25`. Stage 1 shipped this bug, then fixed it reactively.
2. **`smart_school` template contamination.** `branch_20` (`smart_school`) is iterated before `branch_25` and carries password data byte-identical to real tenants' own data across 93 staff rows — consistent with `smart_school` being an onboarding template cloned into every school and never cleaned up. Stage 1's *final* architecture (an independent check running entirely before the loop, skipping the loop altogether on a match) was a same-day rework specifically to sidestep this.
3. **Shared-credential mis-routing.** A vendor/support account with an identical hash across all 6 tenants could be mis-routed by an unqualified `school_saas` match. Fixed by adding an ambiguity guard: if the same identifying value + password also matches under another tenant, the match isn't trusted, and the caller's legacy fallback decides instead.

**This plan starts from Stage 1's *final* architecture and ambiguity guard on Task 1, not its first draft** — the independent pre-loop gate and the ambiguity check are both in scope from the beginning.

**Live investigation already done this session** (do not repeat blindly — re-verify only where a task below says to, since schema/data can change):
- `school_saas.users` has **no `email` column**; it has `tenant_id` (NOT NULL), `user_id`, `username`, `password` (**plaintext, not hashed**), `role`, `is_active`.
- The legacy lookup (`Site.php::userlogin()`'s "MULTI BRANCH STUDENT LOGIN FIX" block, currently lines 658–702 as of commit `09a3193d`) does, per per-branch database group:
  ```sql
  SELECT users.id FROM users
  LEFT JOIN students ON (students.id = users.user_id OR students.parent_id = users.id)
  WHERE users.password = [plaintext posted password]
  AND (
    users.username = [posted username]
    OR students.admission_no = [posted username]
    OR students.mobileno = [posted username]
    OR students.email = [posted username]
    OR students.guardian_phone = [posted username]
    OR students.guardian_email = [posted username]
  )
  LIMIT 1
  ```
- `school_saas.students` also has `tenant_id` (NOT NULL) — the joined `students` row must be scoped to the same `tenant_id` as `users`, not just joined on `id`/`parent_id` alone.
- Zero existing cross-tenant password collisions in `school_saas.users` involving tenant 25 (checked via a self-join on `password` across `tenant_id`, tenant-25 side). The ambiguity guard is precautionary for the pilot tenant, not exercising a known live case — same shape as Stage 1's own guard, which also never fired for tenant 25's real test credential.
- 4 existing cross-tenant password-collision pairs confirmed live, all between `tenant_id` 26 and `tenant_id` 30 (`smart_school`), same identical `username`+`password` on both sides each time (e.g. `std1233`/`sfxcqb` under both tenant 26 and tenant 30). This is the same `smart_school` template-contamination root cause Stage 1 found for staff, now confirmed to also touch student accounts, at smaller scale. **Not fixed by this stage** — logged in Task 3's roadmap update as an extension of Stage 1's finding #2.
- Real, live test credential (tenant 25, confirmed present in both `school_saas` and `al_hafeez_campus`'s own per-branch database, ids differ because of migration remapping): `school_saas.users.id = 1`, `username = 'std113'`, `password = '7daq1b'`, `role = 'student'`, `tenant_id = 25`; `al_hafeez_campus.users.id = 225`, same `username`/`password`. The linked `school_saas.students` row (`id = 1`, `tenant_id = 25`) has `admission_no = '10187'`, `mobileno`/`email`/`guardian_phone`/`guardian_email` all `NULL` for this particular student — Task 1's OR-branch tests for those NULL-able fields must use their own constructed fixture rows, not this real row, since this real row can't exercise them.
- `userlogin()` never sets `admin_tenant_id` anywhere in its flow (confirmed by reading the full method) — it sets `session->set_userdata('student', [...])` with a `db_group` key. The `Admin_Controller` allowlist gate is unrelated to this method entirely.

## Why not go further (explicitly out of scope, and why)

- **Not extending to the other 5 tenants.** Pilot tenant (25) only, matching Stage 1's precedent.
- **Not fixing the plaintext password comparison.** `userlogin()` compares `users.password` directly against the posted plaintext with no hashing at all — a real, separate, pre-existing security weakness. This stage mirrors that behavior exactly (the new gate's `$passwordVerifier` is plain string equality) rather than silently "fixing" it as a side effect of an unrelated migration stage. Logged in Task 3's roadmap update as its own follow-up item.
- **Not cleaning up the `smart_school` collisions.** Same reasoning as Stage 1: real customer data hygiene debt, pre-existing, affecting more than this stage's scope, needs its own dedicated investigation.
- **Not touching `RealLoginGate.php` at all.** A new, separate class avoids any risk to Stage 1's already-shipped, already-reviewed staff login path. Per this project's own established convention, a third near-duplicate (should one ever arise) is the trigger to generalize — not a second one (see Phase 2 Stage 3, where `MergeSchoolData`/`MergeStaffData`/`MergeClassData` were unified into a shared base only once a third near-identical tool appeared).
- **Not touching `Db_manager`'s routing or session shape.** Only whether `userlogin()` reports success changes; everything downstream (the `session_data` array, `db_group`, redirects) is identical to today.

## Components (reference — full detail in each task below)

- **`tools/multitenant/RealUserLoginGate.php`** (new) — `__construct(PDO $schoolSaasPdo)`, `verify(string $identifier, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array` returning `['success' => bool, 'source' => 'school_saas'|'legacy'|'none']`.
- **One pre-loop block in `Site.php::userlogin()`**, immediately before the existing "MULTI BRANCH STUDENT LOGIN FIX START" comment.

## Global Constraints

- **`Site.php::userlogin()` must be functionally byte-for-byte unchanged for every login that does NOT resolve to `branch_25` via the new gate.** This is production auth for 6 live schools' students and parents; the other 5 must never observe any difference in outcome, session shape, or redirect.
- **`RealUserLoginGate` must never reassign `$this->db`.** It builds its own isolated `PDO` connection, same pattern `RealLoginGate`/`ShadowLoginVerifier` already use. The pre-existing multi-branch-fix block's own `$this->db` reassignment for whichever branch ultimately matches is unrelated, untouched, and still happens exactly as before.
- **No cleartext password is ever logged**, even though the comparison itself is plaintext — never write a submitted or stored password value into a log line, only metadata (tenant id, identifier, outcome).
- **No new production data.** This stage's runtime code never inserts, modifies, or deletes a row in any live per-branch database or in `school_saas`. All new testing uses throwaway PHPUnit-managed databases, plus read-only queries against `school_saas`'s and `al_hafeez_campus`'s existing already-migrated real data.
- **No real HTTP login is ever attempted for any tenant**, in any task, at any point — this migration's constraint, unchanged throughout every prior stage.
- **Known test data** (all confirmed live, read-only, both locally accessible on this dev MySQL instance): tenant 25, `username = 'std113'`, `password = '7daq1b'`; `school_saas.users.id = 1`; `al_hafeez_campus.users.id = 225` (same real migrated student login, two databases — this is a real migrated row, not a synthetic fixture).
- **Al-Hafeez Campus / tenant 25 mapping** (same as Stage 1, re-verify live in Task 2 rather than trusting this line): `multi_branch.id = 25`, `multi_branch.database_name = 'al_hafeez_campus'`, lives in the `school_default` database. The dynamic per-request `$db` array built by `application/config/database.php` names this branch's CI3 db-group `branch_25`.
- **Any error inside `RealUserLoginGate::verify()` or its wiring** must degrade to exactly today's per-branch legacy check for that row — never to a login failure the legacy path alone would not have produced.
- **This stage sets `admin_tenant_id` for nobody** and does not touch the `Admin_Controller` allowlist gate. Only whether `userlogin()` succeeds changes; everything downstream is identical to today.
- **The ambiguity/drift log line must be labeled accurately from the start.** Stage 1's final review found its log line conflated "ambiguous shared credential" with "stale password drift" — a Minor finding fixed reactively after shipping. This stage's log line (fired only when `source === 'legacy'`) must say `AMBIGUOUS_OR_STALE_SCHOOL_SAAS_MATCH` rather than reusing Stage 1's `PASSWORD_DRIFT_DETECTED` wording verbatim, since the class genuinely cannot distinguish "school_saas match was ambiguous across tenants" from "school_saas password is stale" — a neutral name that doesn't overclaim which case it is.
- Every task ends with a real, runnable verification step. No task is "done" on code review alone.

---

### Task 1: `RealUserLoginGate` — isolated, unit-tested dual-check class

**Files:**
- Create: `tools/multitenant/RealUserLoginGate.php`
- Test: `tests/tools/multitenant/RealUserLoginGateTest.php`

**Interfaces:**
- Produces: `RealUserLoginGate::__construct(PDO $pdo)`, `RealUserLoginGate::verify(string $identifier, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array` returning `['success' => bool, 'source' => 'school_saas'|'legacy'|'none']`. `$passwordVerifier` is called as `$passwordVerifier($submittedPassword, $storedPassword): bool` (same convention `RealLoginGate` established, but callers pass plain string-equality here since `userlogin()`'s legacy comparison is plaintext, not hashed). `$legacyFallback` is a zero-arg callable returning `bool`, called only if the `school_saas` check doesn't produce a trusted match.

- [ ] **Step 1: Write the failing tests**

Create `tests/tools/multitenant/RealUserLoginGateTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class RealUserLoginGateTest extends TestCase
{
    private PDO $pdo;
    private RealUserLoginGate $gate;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS real_user_login_gate_test');
        $admin->exec('CREATE DATABASE real_user_login_gate_test');

        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=real_user_login_gate_test;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, tenant_id INT NOT NULL, user_id INT NOT NULL DEFAULT 0, username VARCHAR(50), password VARCHAR(255), role VARCHAR(30))');
        $this->pdo->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, tenant_id INT NOT NULL, parent_id INT NOT NULL DEFAULT 0, admission_no VARCHAR(100), mobileno VARCHAR(100), email VARCHAR(100), guardian_phone VARCHAR(100), guardian_email VARCHAR(100))');

        $this->pdo->exec("INSERT INTO students (id, tenant_id, parent_id, admission_no, mobileno, email, guardian_phone, guardian_email) VALUES (1, 25, 0, 'ADM-1', '5550001', 'stu1@example.com', '5559001', 'guardian1@example.com')");
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (1, 25, 1, 'std113', 'real-password', 'student')");

        $this->gate = new RealUserLoginGate($this->pdo);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS real_user_login_gate_test');
    }

    private function plainVerifier(): callable
    {
        return fn (string $submitted, string $stored): bool => $submitted === $stored;
    }

    public function testSucceedsViaSchoolSaasWhenUsernameAndPasswordMatchAndNeverCallsLegacyFallback(): void
    {
        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called when school_saas already matched');
        };

        $result = $this->gate->verify('std113', 'real-password', 25, $this->plainVerifier(), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testFallsBackToLegacyWhenIdentifierDoesNotExistInSchoolSaas(): void
    {
        $result = $this->gate->verify('nobody', 'anything', 25, $this->plainVerifier(), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFallsBackToLegacyWhenRowExistsButPasswordDoesNotMatch(): void
    {
        $result = $this->gate->verify('std113', 'wrong-password', 25, $this->plainVerifier(), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFailsWhenNeitherSchoolSaasNorLegacyMatch(): void
    {
        $result = $this->gate->verify('nobody', 'anything', 25, $this->plainVerifier(), fn (): bool => false);

        $this->assertSame(['success' => false, 'source' => 'none'], $result);
    }

    public function testDoesNotMatchAcrossTenants(): void
    {
        // std113/real-password exists under tenant 25 only in this fixture. Asking
        // under tenant 99 must not match school_saas -- falls through to whatever
        // the legacy fallback says.
        $result = $this->gate->verify('std113', 'real-password', 99, $this->plainVerifier(), fn (): bool => false);

        $this->assertSame(['success' => false, 'source' => 'none'], $result);
    }

    public function testPasswordVerifierReceivesSubmittedAndStoredPasswordInThatOrder(): void
    {
        $seenArgs = null;
        $spy = function (string $submitted, string $stored) use (&$seenArgs): bool {
            $seenArgs = [$submitted, $stored];

            return true;
        };

        $this->gate->verify('std113', 'my-password', 25, $spy, fn (): bool => false);

        $this->assertSame(['my-password', 'real-password'], $seenArgs);
    }

    public function testMatchesViaAdmissionNo(): void
    {
        $result = $this->gate->verify('ADM-1', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaMobileno(): void
    {
        $result = $this->gate->verify('5550001', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaStudentEmail(): void
    {
        $result = $this->gate->verify('stu1@example.com', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaGuardianPhone(): void
    {
        $result = $this->gate->verify('5559001', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaGuardianEmail(): void
    {
        $result = $this->gate->verify('guardian1@example.com', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaParentLinkedThroughParentId(): void
    {
        // A parent user (users.user_id = 0, not linked via students.id) is joined
        // via students.parent_id = users.id instead -- students.id (2) must equal
        // the parent user's own id.
        $this->pdo->exec("INSERT INTO students (id, tenant_id, parent_id, guardian_email) VALUES (2, 25, 7, 'parent-only@example.com')");
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (7, 25, 0, 'parent113', 'parent-password', 'parent')");

        $result = $this->gate->verify('parent-only@example.com', 'parent-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testFallsBackToLegacyWhenTheSameCredentialAlsoVerifiesUnderAnotherTenant(): void
    {
        // A shared/template-contaminated account: same username+password exists
        // under both tenant 25 and tenant 30, mirroring the real smart_school
        // collision pattern found live (e.g. std1233/sfxcqb under tenants 26 and
        // 30). Must NOT be trusted as an authoritative tenant-25 match -- falls
        // through to whatever the legacy fallback decides instead.
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (2, 30, 99, 'shared-student', 'shared-password', 'student')");
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (3, 25, 99, 'shared-student', 'shared-password', 'student')");

        $result = $this->gate->verify('shared-student', 'shared-password', 25, $this->plainVerifier(), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFallsBackToLegacyWhenTheSameCredentialVerifiesUnderMultipleOtherTenants(): void
    {
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (10, 25, 50, 'vendor-student', 'vendor-password', 'student')");
        foreach ([26, 27, 28, 29, 30] as $otherTenant) {
            $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES ({$otherTenant}0, {$otherTenant}, 50, 'vendor-student', 'vendor-password', 'student')");
        }

        $result = $this->gate->verify('vendor-student', 'vendor-password', 25, $this->plainVerifier(), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testStillSucceedsViaSchoolSaasWhenAnotherTenantHasTheSameUsernameButADifferentPassword(): void
    {
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (11, 30, 60, 'coincidental', 'tenant30-different-password', 'student')");
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (12, 25, 60, 'coincidental', 'tenant25-password', 'student')");

        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called for an unambiguous school_saas match');
        };

        $result = $this->gate->verify('coincidental', 'tenant25-password', 25, $this->plainVerifier(), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testAmbiguityCheckOnlyConsidersTheRequestedIdentifier(): void
    {
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (13, 30, 70, 'unrelated-student', 'real-password', 'student')");

        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called when the requested-tenant match is unambiguous');
        };

        $result = $this->gate->verify('std113', 'real-password', 25, $this->plainVerifier(), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit tests/tools/multitenant/RealUserLoginGateTest.php`
Expected: FAIL with `Class "RealUserLoginGate" not found`

- [ ] **Step 3: Write the implementation**

Create `tools/multitenant/RealUserLoginGate.php`:

```php
<?php

final class RealUserLoginGate
{
    public function __construct(private PDO $pdo)
    {
    }

    // A school_saas match is only trusted as authoritative for $tenantId if it is
    // UNIQUE -- i.e. no other tenant's users row with the same matched identifying
    // value also verifies against the submitted password. school_saas already
    // holds every real tenant's data in one table, so this is a single-table
    // check, not a cross-database lookup, and doesn't compromise tenant isolation:
    // it never reveals or uses another tenant's data beyond "does this password
    // also verify there," purely to avoid mis-routing a shared/template-cloned
    // credential (see the smart_school student-account collisions found live
    // during this stage's design) into the wrong tenant's session. When
    // ambiguous, the caller's legacy fallback decides instead -- preserving
    // whatever the pre-existing, untouched legacy behavior for that credential
    // already was, rather than this class inventing a new, confident answer for
    // a case it cannot actually disambiguate.
    public function verify(string $identifier, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array
    {
        $row = $this->findMatch($identifier, $tenantId);

        if ($row !== null
            && $passwordVerifier($password, $row['password'])
            && !$this->matchesUnderAnotherTenant($identifier, $password, $tenantId, $passwordVerifier)
        ) {
            return ['success' => true, 'source' => 'school_saas'];
        }

        if ($legacyFallback()) {
            return ['success' => true, 'source' => 'legacy'];
        }

        return ['success' => false, 'source' => 'none'];
    }

    private function findMatch(string $identifier, int $tenantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT users.password AS password
             FROM users
             LEFT JOIN students
               ON (students.id = users.user_id OR students.parent_id = users.id)
               AND students.tenant_id = users.tenant_id
             WHERE users.tenant_id = :tenant_id
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
        $stmt->execute(['identifier' => $identifier, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    private function matchesUnderAnotherTenant(string $identifier, string $password, int $tenantId, callable $passwordVerifier): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT users.password AS password
             FROM users
             LEFT JOIN students
               ON (students.id = users.user_id OR students.parent_id = users.id)
               AND students.tenant_id = users.tenant_id
             WHERE users.tenant_id != :tenant_id
             AND (
               users.username = :identifier
               OR students.admission_no = :identifier
               OR students.mobileno = :identifier
               OR students.email = :identifier
               OR students.guardian_phone = :identifier
               OR students.guardian_email = :identifier
             )'
        );
        $stmt->execute(['identifier' => $identifier, 'tenant_id' => $tenantId]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $otherRow) {
            if ($passwordVerifier($password, $otherRow['password'])) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit tests/tools/multitenant/RealUserLoginGateTest.php`
Expected: `OK (17 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add tools/multitenant/RealUserLoginGate.php tests/tools/multitenant/RealUserLoginGateTest.php
git commit -m "feat: add RealUserLoginGate, a school_saas-first dual-check with legacy fallback for student/parent login"
```

---

### Task 2: Wire `RealUserLoginGate` into the real `Site.php::userlogin()` for tenant 25

**Files:**
- Modify: `application/controllers/Site.php` (immediately before the "MULTI BRANCH STUDENT LOGIN FIX START" block; re-read the live file first — this plan's line numbers are from commit `09a3193d` and may have shifted)

**Interfaces:**
- Consumes: `RealUserLoginGate::__construct(PDO $pdo)`, `RealUserLoginGate::verify(string $identifier, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array` from Task 1.
- Produces: `$found_group` set to `'branch_25'` when the gate resolves successfully, causing the existing "MULTI BRANCH STUDENT LOGIN FIX" loop below to be skipped (its own `if (isset($db) ...)` block still runs, but every iteration's `break` condition is moot once `$found_group` already left `'default'` — verify this by reading the loop: it re-checks `$found_group !== 'default'` only *after* the loop completes, so a pre-set `$found_group` must actually skip the loop's own iteration, not just be overwritten by it. Guard this explicitly in Step 3 below with an `if ($found_group === 'default') { ...legacy loop... }` wrapper, matching Stage 1 Task 2's exact final structure.)

- [ ] **Step 1: Re-verify live facts before writing code**

Run these against the local MySQL instance to confirm nothing has drifted since this plan was written:

```bash
/c/xampp81/mysql/bin/mysql.exe -u root school_default -e "SELECT id, database_name FROM multi_branch WHERE id = 25;"
/c/xampp81/mysql/bin/mysql.exe -u root school_saas -e "SELECT id, tenant_id, username, password FROM users WHERE id = 1;"
```
Expected: first query returns `25, al_hafeez_campus`; second returns `1, 25, std113, 7daq1b`. If either differs, stop and re-derive the plan's test data before continuing.

Also re-read `application/controllers/Site.php`'s `userlogin()` method in full (search for `function userlogin`) to confirm the "MULTI BRANCH STUDENT LOGIN FIX START" block's current exact position and content still matches this plan's Context section.

- [ ] **Step 2: Read the current file section to edit**

Locate the exact text immediately before:
```php
            // --- MULTI BRANCH STUDENT LOGIN FIX START ---
            include(APPPATH . 'config/database.php');
            if (isset($db) && is_array($db) && count($db) > 1) {
                $found_group = 'default';
```

- [ ] **Step 3: Insert the pre-loop gate**

Replace:
```php
            // --- MULTI BRANCH STUDENT LOGIN FIX START ---
            include(APPPATH . 'config/database.php');
            if (isset($db) && is_array($db) && count($db) > 1) {
                $found_group = 'default';
```

With:
```php
            // --- REAL USER LOGIN GATE (Phase 4 Stage 2) ---
            // Independent pre-loop check for tenant 25 (al_hafeez_campus/branch_25)
            // only. On a match, $found_group is set directly and the legacy loop
            // below is skipped entirely for this login. On no match, $found_group
            // stays 'default' and the legacy loop runs 100% unmodified, exactly as
            // before this stage, for every case including tenant 25's own
            // non-matches. This mirrors Phase 4 Stage 1's final architecture
            // (tools/multitenant/RealLoginGate.php's wiring in login()) applied
            // from the start here, not discovered reactively: school_saas_pilot
            // and branch_20 (smart_school) both precede branch_25 in the same $db
            // array this method also includes below, and smart_school is known to
            // carry template-contaminated student data (see the roadmap's Phase 4
            // Stage 1 entry, finding #2, and this stage's own smaller-scale
            // confirmation). Never reassigns $this->db in this block except via
            // the swap below.
            $found_group = 'default';
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

                $branch25Config = $db['branch_25'] ?? null;
                $branch25UserLoginFallback = function () use ($branch25Config, $login_post): bool {
                    if ($branch25Config === null) {
                        return false;
                    }
                    $branch25Pdo = new PDO(
                        'mysql:host=' . $branch25Config['hostname'] . ';dbname=' . $branch25Config['database'] . ';charset=utf8mb4',
                        $branch25Config['username'],
                        $branch25Config['password']
                    );
                    $branch25Pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $branch25Stmt = $branch25Pdo->prepare(
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
                    $branch25Stmt->execute([
                        'identifier' => $login_post['username'],
                        'password' => $login_post['password'],
                    ]);

                    return $branch25Stmt->fetch(PDO::FETCH_ASSOC) !== false;
                };

                $realUserLoginGate = new RealUserLoginGate($realUserLoginPdo);
                $gateResult = $realUserLoginGate->verify(
                    $login_post['username'],
                    $login_post['password'],
                    25,
                    fn (string $submitted, string $stored): bool => $submitted === $stored,
                    $branch25UserLoginFallback
                );
                if ($gateResult['source'] === 'legacy') {
                    log_message('error', '[RealUserLoginGate] AMBIGUOUS_OR_STALE_SCHOOL_SAAS_MATCH tenant_id=25 identifier=' . $login_post['username']);
                }
                if ($gateResult['success']) {
                    $found_group = 'branch_25';
                }
            } catch (\Throwable $e) {
                log_message('error', '[RealUserLoginGate] EXCEPTION ' . $e->getMessage());
            }
            // --- END REAL USER LOGIN GATE ---

            if ($found_group === 'branch_25') {
                $CI =& get_instance();
                $new_db = $CI->load->database($found_group, TRUE);
                $CI->db->close();
                $CI->db = $new_db;
                $this->db = $new_db;
                $this->setting_model->db = $new_db;
                $this->user_model->db = $new_db;
                $this->student_model->db = $new_db;
                $this->customlib->db = $new_db;
                $this->config->set_item('active_db_group', $found_group);
            } else {
            // --- MULTI BRANCH STUDENT LOGIN FIX START ---
            include(APPPATH . 'config/database.php');
            if (isset($db) && is_array($db) && count($db) > 1) {
                $found_group = 'default';
```

- [ ] **Step 4: Close the new `else` block**

Find the existing end of the legacy block:
```php
            // --- MULTI BRANCH STUDENT LOGIN FIX END ---

            $login_details         = $this->user_model->checkLogin($login_post);
```

Replace with:
```php
            // --- MULTI BRANCH STUDENT LOGIN FIX END ---
            }

            $login_details         = $this->user_model->checkLogin($login_post);
```

(This closes the `if ($found_group === 'default') { ... } else { ...legacy... }`-shaped wrapper opened in Step 3 — note the actual shape is `if ($found_group === 'branch_25') { ...new... } else { ...legacy (unmodified)... }`, matching Stage 1 Task 2's exact precedent structure.)

- [ ] **Step 5: Lint the file**

Run: `/c/xampp81/php/php.exe -l application/controllers/Site.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Verify the legacy loop body is byte-for-byte unchanged**

Run: `git diff application/controllers/Site.php` and confirm every line inside the "MULTI BRANCH STUDENT LOGIN FIX START"..."END" markers is unmodified (only the surrounding `if`/`else` wrapper and the new pre-loop block above it are new). Cross-check against `git show 09a3193d:application/controllers/Site.php` for the exact pre-stage text of that block.

- [ ] **Step 7: Run the full test suite**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit`
Expected: no new failures beyond the pre-existing, already-documented connection-exhaustion flake in `AdminControllerTenantGateTest` (unrelated file); this task's own `RealUserLoginGateTest` (Task 1) stays green.

- [ ] **Step 8: Commit**

```bash
git add application/controllers/Site.php
git commit -m "feat: wire RealUserLoginGate into real Site.php userlogin for tenant 25 (branch_25)"
```

---

### Task 3: End-to-end proof + integration test + roadmap update

**Files:**
- Create: `tests/controllers/SiteUserloginRealUserLoginGateTest.php`
- Modify: `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md` (Phase 4 section, adding a Stage 2 entry after Stage 1)

**Interfaces:**
- Consumes: the live `userlogin()` HTTP endpoint (failure-path only, per the no-real-login constraint) and `RealUserLoginGate` directly (Task 1).

- [ ] **Step 1: Direct-class proof against real data**

Write and run a throwaway read-only PHP script (not committed) that constructs a `RealUserLoginGate` against a real `school_saas` PDO connection and a real `al_hafeez_campus` PDO connection, and exercises all 4 expected outcomes using the known test credential (`username = 'std113'`, `password = '7daq1b'`, tenant 25):
1. Correct identifier + correct password → `['success' => true, 'source' => 'school_saas']`
2. Correct identifier + wrong password, with the `al_hafeez_campus` fallback also failing → `['success' => false, 'source' => 'none']`
3. A nonexistent identifier under tenant 25 → `['success' => false, 'source' => 'none']` (with a fallback that returns `false`)
4. Simulated ambiguity: reuse Task 1's fixture approach (a temporary throwaway row under another tenant with the same identifier+password) to confirm the ambiguity guard fires against real school_saas connectivity, not just the unit-test fixture database.

Expected: all 4 outcomes match exactly. Delete the script when done (never commit throwaway verification scripts, matching every prior stage's convention).

- [ ] **Step 2: Write the failing integration test**

Create `tests/controllers/SiteUserloginRealUserLoginGateTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

final class SiteUserloginRealUserLoginGateTest extends TestCase
{
    private const BASE_URL = 'http://localhost/web-app/index.php/site/userlogin';

    public function testFailedLoginForNonExistentAccountIsUnaffectedAndLogsNoNewException(): void
    {
        $ch = curl_init(self::BASE_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => 'definitely-not-a-real-user-' . bin2hex(random_bytes(4)),
                'password' => 'definitely-not-a-real-password',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        // Matches the actual rendered validation-failure text, not a lang key --
        // Phase 4 Stage 1's Task 3 found the literal lang key
        // ('invalid_username_or_password') is never what's rendered; confirm the
        // real rendered string for userlogin()'s failure path before asserting it
        // (it may differ from login()'s -- verify live via curl during
        // implementation rather than assuming it's identical).
        $this->assertStringContainsString('Invalid Username Or Password', $body);
    }

    public function testUserloginFormRendersIdenticallyOnGet(): void
    {
        $ch = curl_init(self::BASE_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        $this->assertNotEmpty($body);
    }
}
```

- [ ] **Step 3: Run to verify it passes against the live dev server**

Run: `/c/xampp81/php/php.exe vendor/bin/phpunit tests/controllers/SiteUserloginRealUserLoginGateTest.php`
Expected: `OK (2 tests, ...)`. If the rendered failure-text assertion fails, curl the endpoint manually with bogus credentials, read the actual rendered text, and correct the assertion — do not weaken it to a lang key.

- [ ] **Step 4: Update the roadmap doc**

Add a new stage entry to `docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md`'s Phase 4 section, immediately after Stage 1's entry, following that entry's exact level of detail and honesty about caveats:

- State the goal (student/parent login verification cutover for tenant 25, mirroring Stage 1's staff pattern).
- State that this stage started from Stage 1's *final* architecture and ambiguity guard from the beginning, rather than repeating Stage 1's first, later-reworked attempt.
- Cross-reference Stage 1's finding #2 (`smart_school` contamination) and state that the same pattern was independently confirmed to exist at smaller scale in `school_saas.users` (4 cross-tenant collision pairs, tenants 26/30), not fixed here.
- Explicitly state the plaintext-password comparison was mirrored, not fixed, and flag it as a separate, pre-existing security weakness worth a dedicated future pass.
- State the actual final test count and any deviations found during implementation (fill in once Tasks 1–3 are actually complete — do not guess a number here in advance).

- [ ] **Step 5: Commit**

```bash
git add tests/controllers/SiteUserloginRealUserLoginGateTest.php docs/superpowers/plans/2026-07-08-multi-tenant-migration-roadmap.md
git commit -m "feat: prove RealUserLoginGate end to end for tenant 25 userlogin, update roadmap"
```

## Final whole-stage review

After all 3 tasks are complete, dispatch a final whole-stage adversarial review (most capable available model, given production sensitivity — same precedent as Stage 1's 3-round review) covering the full range from before Task 1's first commit through Task 3's last commit. Explicitly ask it to:

- Independently re-verify the legacy "MULTI BRANCH STUDENT LOGIN FIX" loop is byte-for-byte unchanged (diff against `git show 09a3193d:application/controllers/Site.php`).
- Independently re-derive the `$db` array's iteration order live and confirm `school_saas_pilot`/`branch_20` positions relative to `branch_25` don't matter given the independent-pre-loop-gate architecture (unlike Stage 1, which had to discover this reactively).
- Independently verify the ambiguity guard actually prevents a shared/template-contaminated student credential (e.g. construct a test case shaped like the real tenant-26/30 `std1233` collision) from being trusted as an authoritative tenant-25 match.
- Check whether Stage 1's exact "legacy fallback checks branch_25's own row directly, which can carry the same shared credential" gap (found in Stage 1's round-3 review, left as a known, accepted limitation) has an equivalent here — it likely does, for the same structural reason, and should be documented the same way rather than treated as a new surprise.
- Confirm no cleartext password appears in any log line.
- Confirm full test suite results and that only the known, pre-existing connection-exhaustion flake (if any) explains any failures.

If the review finds a Critical or Important issue, follow this project's established pattern: stop, present the finding and concrete evidence to the user, get explicit direction before any further change to this live production authentication code.
