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
   - **Stage 3 — student_session** — ✅ complete (2026-07-09, plan:
     `2026-07-09-multi-tenant-phase2-stage3-student-session.md`). Opened
     by extracting `AbstractTenantMerger` from the three existing merge
     tools (closing the triplication debt flagged in Stage 2's review),
     then migrates `student_session` (the table that actually links a
     student to a class/section) by reconnecting already-migrated rows
     via a new `NaturalKeyIdResolver` (matching on `admission_no`) for
     students. Proven via a new `PilotStudentSessions` controller.
     **Bug found and fixed during this stage's first attempt:** the
     initial class/section resolver matched on section name alone, which
     silently dropped rows wherever a section name was reused across
     multiple classes — a real, non-hypothetical shape in
     `al_hafeez_campus`'s data (`"Green 05"` is shared across 5 different
     classes, 4 of them via the same underlying section row and 1 via a
     separate section row with the same name), losing 173 of 484
     `student_session` rows (72 of 312 students left with no session
     data) with no error raised. Fixed by adding `ClassSectionPairResolver`,
     which resolves the (class, section) pair jointly through the
     `class_sections` junction table instead of by section name alone,
     plus a hardening pass that makes the resolver raise a
     `RuntimeException` on any genuinely ambiguous pairing it can't
     safely resolve, rather than silently mis-linking or dropping rows.
     The corrected tool was re-run against the pilot tenant's real data
     (`al_hafeez_campus`, tenant 25): all 484 source rows migrated (0
     dropped), spot-checked against the source including the "Green 05"
     collision case across all 5 classes, and verified end to end via
     `PilotStudentSessions` (484 real students listed, each with a real
     non-"Unknown" class/section; the rendered "Green 05" count matches
     the source exactly).
   - **Stage 4 — Attendance** — ✅ complete (2026-07-10, plan:
     `2026-07-10-multi-tenant-phase2-stage4-attendance.md`). Migrates
     `attendence_type` + `student_attendences` (1,124 real rows in
     `al_hafeez_campus`). Introduces `StudentSessionIdResolver`
     (composite `admission_no`/`class`/`section`/`created_at` key,
     `created_at` verified preserved identically between source and
     target by Stage 3) to reconnect attendance to Stage 3's
     `student_session` rows, with collision detection built in from the
     start this time. Proven via a new `PilotAttendance` controller.
     `staff_attendance` and `student_subject_attendances` deferred (0
     rows currently, nothing real to prove). **First attempt correctly
     blocked** on an ambiguous natural-key collision in
     `StudentSessionIdResolver` — an initial fix that filtered to
     `is_active='yes'` was itself caught as wrong (every real row is
     `is_active='no'`, so it silently produced an empty map / 0
     migrated rows while still reporting success) before the
     `created_at`-keyed fix was applied and independently reviewed
     (commit `48332594` and its predecessor). With the fix in place, the real merge was
     re-run against the pilot tenant's real data (`al_hafeez_campus`,
     tenant 25): `Migrated 6 attendance types and 1124 student
     attendance records for tenant 25.` — matching the source exactly
     (6 `attendence_type` / 1124 `student_attendences` rows on both
     sides), spot-checked by admission_no/date/type against the
     source, and verified end to end via `PilotAttendance` (1,124
     `<li>` entries, each with a real non-"Unknown" student name,
     date, and attendance type).
   - **Stage 5 — Exams** — ✅ complete (2026-07-10, plan:
     `2026-07-10-multi-tenant-phase2-stage5-exams.md`). Extends
     `school_saas` with seven tenant-scoped tables (`sessions`,
     `subjects`, `exam_groups`, `exam_group_class_batch_exams`,
     `exam_group_class_batch_exam_subjects`,
     `exam_group_class_batch_exam_students`, `exam_group_exam_results`)
     — the deepest FK chain migrated yet, reaching two brand-new catalog
     tables, three tables migrated fresh within the same run via plain
     `IdRemapper`, and two tables that reconnect to data migrated in
     EARLIER, SEPARATE stages (`students` from Stage 1,
     `student_session` from Stage 3) by reusing `NaturalKeyIdResolver`
     (Stage 3) and `StudentSessionIdResolver` (Stage 4) completely
     unchanged — proving both resolvers generalize to a third consumer.
     A new `MergeExamData` tool (extends `AbstractTenantMerger`)
     performs the migration in one transaction; a new `PilotExam`
     controller proves it end to end. **Bug found and fixed during this
     stage's implementation, before any real data was touched (a
     "Post-Task-3 fix," documented in the plan doc):** the initial Part
     B code created an `IdRemapper` for `exam_group_exam_results` but
     never called `remapId()`/`getMapping()` on it, so those rows alone
     were inserted with their SOURCE database's original `id` unchanged
     instead of a freshly computed target id — harmless with only one
     tenant migrated (the two databases' autoincrement sequences don't
     happen to collide yet), but the same "ticking bomb for the next
     school" shape Stage 3 and Stage 4 each hit once already. Fixed by
     remapping the row's own id like every other table in the file,
     with a regression test that pre-seeds a colliding id 900 in the
     target and asserts the migrated row lands elsewhere. The corrected
     tool was then run against the pilot tenant's real data
     (`al_hafeez_campus`, tenant 25): `Migrated 15 sessions, 38
     subjects, 8 exam groups, 32 batch exams, 266 exam subjects, 719
     exam-student enrollments, and 2785 exam results for tenant 25.`
     with no STDERR skip warnings (both skip counts 0, as predicted by
     this stage's pre-flight dangling-reference survey) — matching the
     source exactly on all seven row counts (15 / 38 / 8 / 32 / 266 /
     719 / 2785 on both sides). Spot-checked 5 real students (90
     admission_no/subject/marks/attendance/exam rows) byte-for-byte
     identical between `al_hafeez_campus` and `school_saas`, and
     verified end to end via `PilotExam` (2785 `<li>` entries, 0
     "Unknown" occurrences, header correctly reading "8 exam groups").

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
  the merge tool's own remap logic prevents it. The same shape recurs
  in `sql/multitenant/005_add_attendance_tables.sql`:
  `fk_studentattendences_type` and `fk_studentattendences_session`
  likewise reference `attendence_type(id)`/`student_session(id)` alone,
  not `(tenant_id, id)` — same accepted debt. Worth a composite FK
  before Phase 5 migrates additional schools.
- **Merge tools have no re-run/idempotency guard** (discovered 2026-07-10
  during Stage 4's final-review fix-up, when a manual verification re-run
  of `MergeAttendanceData.php al_hafeez_campus 25` — expected to error
  against already-migrated data — instead silently duplicated all of
  tenant 25's attendance rows, 1124→2248 and 6→12, with no error and no
  count-mismatch signal; caught immediately via row counts and corrected
  by deleting exactly the duplicate rows in a transaction, verified via
  dangling-reference and per-tenant-count checks afterward). None of
  `MergeSchoolData`, `MergeStaffData`, `MergeClassData`,
  `MergeStudentSessionData`, or `MergeAttendanceData` check whether the
  target tenant already has rows before inserting more. Harmless so far
  because every real run to date has been against a clean tenant, but
  Phase 5 will re-run these same tools repeatedly (once per remaining
  school, likely with retries/resumes) — worth adding an explicit
  "tenant already has data in table X, refusing to re-run" guard to
  `AbstractTenantMerger` before Phase 5 starts.
