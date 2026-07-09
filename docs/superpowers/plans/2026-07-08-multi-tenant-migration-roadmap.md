# Multi-Tenant Migration — Roadmap

This migration converts the app from one-database-per-school (`multi_branch` +
per-request connection switching via `Db_manager`) to a single shared
database where every row carries a `tenant_id`. It is too large for one
implementation plan — 192 tables, ~232 FK relationships, ~7,700
query-builder call sites, 278 raw SQL call sites, 159 models, 20
controllers, and a separate API layer (112 files) all touch this. Each
phase below gets its own detailed plan document when it starts; this file
is the index and the source of truth for sequencing and decisions already
made.

## Decisions locked in (2026-07-08)

- **Query-scoping strategy:** query-wrapper + manual audit. A new
  `TenantScope`/`Tenant_Model` layer auto-injects `tenant_id` on every
  operation; existing call sites are migrated to it module-by-module and
  audited by hand (this is unavoidable — someone has to touch all ~8,000
  sites — but the wrapper removes the chance of *forgetting* the filter).
- **Rollout strategy:** pilot one school first. `al_hafeez_campus` migrates
  into the new shared database (`school_saas`) and runs there for a
  validation period before any other school moves. The old per-branch
  system keeps running unmodified for all other schools throughout.

## Phases

1. **Phase 1 — Foundation** — ✅ complete (plan: `2026-07-08-multi-tenant-phase1-foundation.md`)
   Build and prove the core mechanism — schema, ID-remap/merge tooling,
   and the tenant-scoping wrapper — against one vertical slice (`students`
   + `users`) for one pilot school. Old system untouched. Exit criteria:
   pilot tenant's students are queryable/insertable in `school_saas` with
   proven cross-tenant isolation (automated tests) and a working manual
   smoke test through a real (if minimal) controller.

2. **Phase 2 — Retrofit core modules** (in progress, staged)
   Extend the schema (`tenant_id` + FK) to the next tier of tables that
   most other modules depend on: staff, classes/sections, exams,
   attendance. Migrate the corresponding models off raw `$this->db` calls
   onto `Tenant_Model`.

   **Login-wiring decision (2026-07-09):** the original wording above
   ("wire real login via a feature flag in `Site.php`") was revised once
   `Site.php::login()`'s actual size and blast radius (176 lines, live
   production auth for all 6 schools, re-evaluated on every login
   attempt) was inspected up close. Modifying it directly — even gated —
   means every login for every school runs through touched code, a much
   bigger risk than anything Phase 1 touched. Instead, Phase 2 proves the
   real-login mechanism via a new parallel pilot controller (same pattern
   as Phase 1's `PilotStudents`), and defers actually replacing `Site.php`'s
   production login path for the pilot tenant to a later stage, once more
   of the admin panel is proven tenant-safe end to end.

   - **Stage 1 — Staff + real login proof** — ✅ complete (2026-07-09,
     plan: `2026-07-09-multi-tenant-phase2-staff-login.md`). Extends
     `school_saas` with `staff`/`staff_roles`/`roles`; a new `PilotLogin`
     controller does real credential verification and role resolution
     against those tables via `Tenant_Model`, without touching `Site.php`
     or the real admin dashboard. The pilot tenant's real staff data
     (18 staff, 8 roles, 18 staff_roles for `al_hafeez_campus`, tenant 25)
     has been migrated into `school_saas` and verified end to end: a real
     login via `PilotLogin` resolves the correct staff name, email, and
     role, and a wrong password is correctly rejected.
   - **Stage 2 — Classes/sections catalog** — ✅ complete (2026-07-09,
     plan: `2026-07-09-multi-tenant-phase2-stage2-classes-sections.md`).
     Migrates `classes`/`sections`/`class_sections` (the catalog only —
     NOT `student_session`, the table that actually links a student to a
     class/section, which needs its own stage since it requires
     reconstructing Phase 1's students old-id→new-id mapping via
     `admission_no`). Proven via a new `PilotClasses` controller. The
     pilot tenant's real class/section data (7 classes, 8 sections, 13
     class_sections for `al_hafeez_campus`, tenant 25) has been migrated
     into `school_saas` and verified end to end: `PilotClasses` lists
     all 7 real class names each with their real (non-"Unknown") section
     names, cross-checked directly against the source database.
   - **Stage 3+ — student_session, exams, attendance** — not yet planned.

3. **Phase 3 — Retrofit remaining modules** (not yet planned)
   Fees, payroll, library, transport, hostel, HR, messaging, and the rest
   of the ~150 remaining tables/models. Likely broken into several
   sub-plans by module, each independently shippable.

4. **Phase 4 — API layer** (not yet planned)
   Apply the same treatment to `api/` (112 files) — separate branch-switch
   logic today, needs its own tenant-scoping pass.

5. **Phase 5 — Migrate remaining schools + cutover** (not yet planned)
   Using the now-battle-tested merge tool from Phase 1, migrate each
   remaining school one at a time into `school_saas`, validating after
   each before moving to the next. Retire `multi_branch`/`Db_manager` and
   the old per-branch databases only after every school is confirmed
   stable on the shared schema.

## Non-negotiables across every phase

- The existing per-branch system must keep working, unmodified, for every
  school not yet migrated — no phase is allowed to break a live school.
- Every new query-access path goes through the tenant-scoping wrapper, not
  raw `$this->db` calls — this is the whole point of the migration.
- No phase merges a school's data into `school_saas` without first
  confirming, via automated test, that cross-tenant reads/writes are
  blocked (see `TenantScopeTest` in Phase 1 for the pattern to replicate).
- All `Pilot*` controllers (`PilotStudents`, `PilotLogin`, `PilotClasses`,
  ...) are an unauthenticated proof harness — anyone can call
  `login_as/<any-id>` and select any tenant with data. They must be
  removed or gated behind a real auth check before Phase 5's cutover;
  flagged in Stage 2's final review (2026-07-09).

## Carried-forward technical debt

- **Merge-tool triplication** (flagged in Stage 2's final review,
  2026-07-09): `MergeSchoolData`, `MergeStaffData`, and `MergeClassData`
  share ~60 near-identical lines each (`nextId()`, `fetchAll()`,
  `insertRow()`, the transaction/rollback skeleton, the CLI bootstrap) —
  three occurrences now, past the point where copy-pasting a fourth is
  easily justified. Stage 3 (`student_session`, a fourth merge tool)
  should open with extracting the shared mechanism (e.g. an
  `AbstractTenantMerger` each tool configures with its table graph)
  before adding a fourth copy.
- **`class_sections`' FKs are not tenant-composite** (schema:
  `sql/multitenant/003_add_class_section_tables.sql`): `class_id`/
  `section_id` reference `classes(id)`/`sections(id)` by id alone, not
  `(tenant_id, id)`. Safe today with one tenant; once a second tenant
  exists, nothing at the DB level stops a cross-tenant reference — only
  the merge tool's own remap logic prevents it. Worth a composite FK
  before Phase 5 migrates additional schools.
