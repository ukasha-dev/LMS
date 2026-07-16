# Phase 4 Stage 1 — Real Login Verification Cutover (Pilot Tenant) Design

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
individually-safe sub-projects. This document covers the first and smallest of them.

**Prior art directly reused**: Phase 3 Stage 5 built `ShadowLoginVerifier`
(`tools/multitenant/ShadowLoginVerifier.php`), which proves `school_saas` credentials
agree with the real per-branch login for tenant 25 (Al-Hafeez / `al_hafeez_campus` /
`branch_25`) — but it is **read-only**: it only calls `log_message()`, never affects
whether a real login succeeds, never touches session state or `$this->db`. That stage
explicitly set `admin_tenant_id` for nobody and left every real controller method's
behavior byte-for-byte unchanged. This stage is the first one that actually changes real
login *behavior* — but only whether it succeeds, nothing about what happens after.

## Goal

Make `school_saas.staff` the **authoritative password check** for real logins resolving
to tenant 25, with automatic, logged fallback to today's legacy per-branch check so a
stale/out-of-sync `school_saas` password can never lock a real user out. Every other real
tenant's login, and everything that happens *after* a tenant-25 login succeeds (session
shape, `$this->db`, which database subsequent pages read from), stays completely
unchanged. This stage is intentionally narrow: it does not cut over data access, does not
touch `Db_manager`'s routing decision, and does not set `admin_tenant_id` for real
sessions.

## Why not go further (explicitly out of scope, and why)

- **Not cutting over data access this stage.** Real controller methods have zero
  tenant_id awareness (see Context). Making a real session's `$this->db` point at
  `school_saas` before those methods are individually rewritten would leak cross-tenant
  data, not just break pages. That is a separate, much larger sub-project (Phase 4 Stage
  2+, one per module) requiring its own design.
- **Not touching `Db_manager`'s routing.** The existing branch (`admin_tenant_id` set →
  `school_saas_pilot`; else → per-branch `db_group`) is untouched. This stage only
  changes whether `Site.php::login()` reports success, never what session state gets
  set afterward.
- **Not re-syncing `school_saas`'s existing password data.** The dual-check-with-fallback
  design (below) makes a one-time re-sync unnecessary for correctness — staleness is
  handled per-login, not batch-fixed up front. A future stage could still add a re-sync
  sweep once real drift-log volume shows it's worth it, but it's not a prerequisite here.
- **Not applying to the other 5 real tenants yet.** Pilot tenant (25) only, matching the
  "smallest real blast radius" framing this whole stage is built around. Rolling out to
  the remaining 5 is a follow-up stage once this one has run against real traffic.

## Architecture

One new framework-agnostic class, `RealLoginGate`, sits between `Site.php::login()`'s
existing legacy-DB password check and its success/failure branch. It takes a `PDO`
connection to `school_saas` and never touches CI3's `$this->db` — the same isolation
principle `ShadowLoginVerifier` and every other `tools/multitenant/` class already
follows, chosen again here because it makes the drift-detection logic directly
PHPUnit-testable against a throwaway PDO-backed database, independent of CI3's request
lifecycle.

`Site.php::login()` calls it only when the login resolves to the tenant-25 branch (the
same `branch_25` / `al_hafeez_campus` mapping `ShadowLoginVerifier` already keys off),
wrapped in try/catch so any unexpected error (connection failure, malformed row, anything)
falls straight through to today's unmodified legacy check. This feature can only ever
make tenant-25 login as-reliable-as-today-or-better — never worse — by construction, not
by convention.

`ShadowLoginVerifier` is not modified or reused as a base class. It stays exactly as it
is: pure observability, zero behavioral effect. `RealLoginGate` is new because it does
something meaningfully different in kind (it can cause a real login to succeed or fail),
and conflating "observe" and "gate" in one class risks a future change to "just the
logging" silently changing gating behavior.

## Components

- **`tools/multitenant/RealLoginGate.php`** (new)
  - `__construct(PDO $schoolSaasPdo)`
  - `verify(string $email, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array`
    returning `['success' => bool, 'source' => 'school_saas'|'legacy'|'none']`.
  - `$passwordVerifier` matches `Enc_lib::passHashDyc($password, $storedHash): bool`'s
    exact signature (same convention `ShadowLoginVerifier` already established), so the
    real call site can pass `[$this->enc_lib, 'passHashDyc']` directly.
  - `$legacyFallback` is a zero-arg callable the caller builds, wrapping *the existing,
    unmodified* legacy per-branch check. `RealLoginGate` never needs to know about CI3,
    per-branch DB connections, or branch-id-to-tenant-id mapping — it only orchestrates
    two checks its caller hands it, in order. This keeps the class trivially testable
    with plain stubs.
  - On a `source='legacy'` result (school_saas missed, legacy caught it), logs
    `log_message('error', 'PASSWORD_DRIFT_DETECTED tenant_id=... staff_id=...')` — same
    mechanism and severity level the existing shadow-verify code already uses. No new
    table this stage (see Decisions below).

- **One call site in `Site.php::login()`**, replacing the current pass-through success
  branch for the tenant-25 case only. Every other branch (the other 5 real schools, any
  future tenant, any non-matching login) is untouched — same "byte-for-byte unchanged for
  everyone else" constraint Phase 3 Stage 5 already established and proved with its own
  test.

## Data flow

1. User submits email+password to the real, live `Site.php::login()`; request resolves to
   the tenant-25 / `branch_25` / `al_hafeez_campus` case (existing resolution logic,
   unchanged).
2. `RealLoginGate::verify()` runs inside a try/catch:
   a. Check `school_saas.staff` via `$passwordVerifier` → match → `success=true,
      source='school_saas'`. **This is now the primary, authoritative path.**
   b. No match → call `$legacyFallback` (today's real, unmodified check) → match → log
      drift → `success=true, source='legacy'` → user logs in exactly as they would today.
   c. Neither matches → `success=false, source='none'` → today's real failure behavior,
      unchanged (error message, no session).
3. Any exception anywhere in step 2 → caught at the `Site.php::login()` call site →
   treated identically to `source='none'` triggering fallback → falls through to the
   legacy check untouched, so a `school_saas` outage degrades this feature to "exactly
   like today," never to "logins broken."
4. Session establishment, `$this->db`, redirect target: **unchanged in every branch of
   every case.** This stage only ever decides *whether* login succeeds — never what
   happens after.

## Decisions already made (from brainstorming Q&A, recorded so they aren't re-litigated)

1. **Cutover shape**: verification-only. Session/data access behavior is unchanged this
   stage.
2. **Staleness handling**: dual-check with legacy fallback. A real user can never be
   locked out by this change — worst case, login is exactly as reliable as it is today.
3. **Drift signal**: `log_message()` only, no new table this stage. A future stage can add
   a `auth_drift_log` table (or similar) once real signal volume from this stage shows
   it's worth building — not assumed speculatively here.
4. **Implementation shape**: new isolated class (`RealLoginGate`), not an extension of
   `ShadowLoginVerifier` and not inlined into `Site.php::login()` — matches this
   migration's established pattern of small, isolated, unit-tested classes in
   `tools/multitenant/`.

## Testing

- `RealLoginGate` gets its own `PDO`-based unit tests (throwaway PHPUnit-managed
  database, matching `ShadowLoginVerifierTest`'s existing pattern) covering:
  - `school_saas` match → `success=true, source='school_saas'`, no drift log.
  - `school_saas` miss, legacy match → `success=true, source='legacy'`, drift logged.
  - Both miss → `success=false, source='none'`.
  - `$passwordVerifier` or `$legacyFallback` throwing → falls through to legacy /
    `source='none'` per the caller's try/catch, never propagates.
- One integration-style test proving the **other 5 real tenants'** login behavior is
  provably byte-for-byte unchanged — the same kind of verification Phase 3 Stage 5 already
  did for its own (read-only) change. This is the test that actually protects production,
  more than the unit tests above.
- No real per-branch school password is ever read, written, or logged in cleartext by
  this stage's code, beyond what `Site.php::login()` already receives from the POST body
  it already trusts — same constraint Phase 3 Stage 5 already established.
- No production data is inserted, modified, or touched by any test in this stage. All
  testing happens against throwaway PHPUnit-managed databases and `school_saas`'s existing
  real (already-migrated) tenant-25 staff data, read-only, using the known test credential
  (`rabiachauhan923@gmail.com` / `TestVerify123!`).

## Rollback

If this stage needs to be reverted after shipping: remove the single call site in
`Site.php::login()` (one code block, clearly delimited, matching how the existing
shadow-verify block is already isolated in the same function). No session shape, no
`Db_manager` routing, and no data ever depends on `RealLoginGate` having run — reverting
it returns tenant-25 login to exactly the legacy-only check that's live today, with zero
downstream cleanup required.
