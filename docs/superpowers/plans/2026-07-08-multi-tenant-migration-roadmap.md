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
   - **Stage 6 — Real Staff model retrofit** — ✅ complete (2026-07-13,
     plan: `2026-07-10-multi-tenant-phase2-stage6-real-staff-retrofit.md`).
     The first stage to touch code in the LIVE admin panel's shared
     execution path — every prior stage only added new schema plus a
     parallel, unauthenticated `Pilot*` proof controller. Deliberately
     narrowed twice during planning: `Site.php` itself is never touched
     (tenant 25/`al_hafeez_campus` is a live school; touching its real
     login risked a functional regression for real daily users), and
     only ONE new method is added to the real `Staff.php`/`Staff_model.php`
     (not the full `Staff::index()` page, which pulls in shared layout
     chrome, `rbac`, and raw-SQL search — out of scope). Built two narrow
     gates keyed on a new session flag, `admin_tenant_id`, set only by
     `PilotLogin`'s real credential check: an allowlist gate in
     `Admin_Controller`'s constructor (blocks a tenant-scoped session
     from reaching any controller/method except the one this stage adds)
     and a `Db_manager` connection gate (routes that session to
     `school_saas`). `Staff_model` gained one new method with an explicit
     `WHERE tenant_id = ?` filter — the first real instance of the
     query-scoping strategy locked in on day one, executed against a
     live, shared-by-all-6-schools file. **Three real bugs found and
     fixed during execution, each caught by the next verification step
     rather than by inspection:** (1) a routing bug — the redirect
     target was missing the `admin/` prefix `Staff.php` actually lives
     under, so a successful login silently fell through to an unrelated
     redirect instead of the real page — caught by a task reviewer
     before any live check ran; (2) the `admin` session array's `roles`
     key was shaped wrong (a bare int instead of `[roleName => roleId]`,
     which `Customlib::getStaffRole()` expects), throwing a real 500 on
     every authenticated request that also masked whether the allowlist
     gate worked at all — caught by Task 5's first live login attempt;
     (3) `MY_Controller`'s ~100-model unconditional autoload chain needed
     settings/reference data (`sch_settings`, `languages`, `currencies`,
     plus three schema-only existence-check tables) that `school_saas`
     never had, since no prior stage needed school-wide settings — caught
     immediately after fixing (2), by the same live request. All three
     fixes independently re-reviewed or re-verified live. Final state,
     verified end to end with a real `PilotLogin` authentication: the
     real `admin/staff/tenantStaffList` returns the real 18 tenant-25
     staff rows, and `admin/admin/dashboard`, `admin/examgroup`, and the
     real un-gated `admin/staff` index all return `404` for that same
     session — proving the gate mechanism actually blocks what it's
     supposed to, not just that it compiles. The allowlist-gate mechanism
     (Task 1) is reusable infrastructure: a future stage retrofitting
     another real controller only needs one new allowlist entry and one
     gated method, not a rebuilt gate.

3. **Phase 3 — Retrofit remaining modules** (in progress, staged)
   Fees, payroll, library, transport, hostel, HR, messaging, and the rest
   of the ~150 remaining tables/models. Broken into several sub-plans by
   module, each independently shippable.

   - **Stage 1 — Fees** — ✅ complete (2026-07-13, plan:
     `2026-07-13-multi-tenant-phase3-stage1-fees.md`). Extends
     `school_saas` with ten tenant-scoped tables spanning the deepest FK
     chain migrated yet (six layers: catalog → session-scoped pricing →
     per-student assignment → per-student deposit/collection →
     per-student applied discount) — `feetype`, `fee_groups`,
     `fees_discounts`, `fee_session_groups`, `fee_groups_feetype`,
     `fees_reminder`, `student_fees_master`, `student_fees_deposite`,
     `student_fees_discounts`, `student_applied_discounts`. A new
     `MergeFeeData` tool (extends `AbstractTenantMerger`) migrates all
     ten tables in one transaction: six via plain `IdRemapper` (fresh
     catalog data), four by reconnecting to `student_session` rows
     migrated in the EARLIER, SEPARATE Stage 3 via
     `StudentSessionIdResolver` — reused completely unchanged, proving
     it generalizes to a fourth consumer (after Stage 3 created it,
     Stage 4 hardened it, Stage 5 proved it reusable). A new `PilotFees`
     controller proves it end to end. **Two real bugs found and fixed
     during this stage:** (1) a Post-Task-3 dead-code cleanup — the
     implementer's own self-review caught a copy-pasted
     `NaturalKeyIdResolver::resolve(..., 'students', 'admission_no')`
     call carried over from Stage 5's `MergeExamData`, where a direct
     `student_id` FK genuinely needed it; neither `student_fees_master`
     nor `student_fees_discounts` has a `student_id` column at all —
     both only reference `student_session_id`, already fully resolved by
     `StudentSessionIdResolver` two lines below — so the computed map was
     never read anywhere, wasting one full-table resolver query (two
     SELECTs) on every real run for no purpose; removed before Task 4
     built more code around the file, no behavior change, still `OK (52
     tests, ...)`. (2) A Post-Task-6 fix, found by Step 1's first REAL
     migration run: it threw `RuntimeException: Ambiguous natural key:
     multiple distinct ids share the value "2025-26" in column "session"
     of table "sessions"` instead of migrating. Root cause, confirmed by
     direct SQL: both `al_hafeez_campus.sessions` (source, ids 21 and 26)
     and `school_saas.sessions` for tenant 25 (target, ids 10 and 15)
     genuinely have two rows named `"2025-26"` — a known, pre-documented
     duplicate from Stage 5's planning notes, which Stage 5 sidestepped
     entirely by using fresh `IdRemapper` instead of natural-key
     reconnection. This fee stage was the first to actually need
     natural-key reconnection to the already-migrated `sessions` table,
     and its Task 2 design called the shared
     `NaturalKeyIdResolver::resolve()` unscoped — against the WHOLE
     `sessions` table — which fails fast on ANY duplicate name it finds,
     regardless of whether that duplicate is ever referenced by the data
     actually being migrated. Verified via direct SQL that zero fee rows
     reference session id 21 or 26 — the only sessions any fee data
     touches are 20 (`"2024-25"`) and 22 (`"2026-27"`), the same two
     clean sessions Stage 5 already used successfully — the same failure
     shape already named once in this project as Stage 4's `is_active`
     saga: "the resolver's uniqueness domain being broader than it needs
     to be." Fixed by replacing the blanket `NaturalKeyIdResolver::resolve()`
     call with a new private method local to `MergeFeeData` that
     resolves and ambiguity-checks ONLY the session ids actually
     referenced by the four fee tables with a `session_id` column —
     collision detection is narrowed, not removed: an actually-referenced
     ambiguous session would still throw. This does NOT touch the shared
     `NaturalKeyIdResolver.php` (a protected file per this stage's Global
     Constraints, still used unchanged by other merge tools) — the new
     logic lives entirely inside `MergeFeeData.php`, which also made
     `NaturalKeyIdResolver` entirely unused there (its other use was
     already removed by the Post-Task-3 cleanup), so its `require_once`
     was removed too. Two new regression tests added (one proving an
     unrelated duplicate elsewhere in `sessions` does NOT block migration,
     one proving a genuinely-referenced ambiguous session still throws) —
     full suite `OK (55 tests, ...)`. The corrected tool was then run
     against the pilot tenant's real data (`al_hafeez_campus`, tenant
     25): `Migrated 6 fee types, 26 fee groups, 35 fee discounts, 25 fee
     session groups, 46 fee group pricing rows, 4 fee reminders, 626
     student fee assignments, 699 student fee deposits, 215 student fee
     discounts, and 111 applied discounts for tenant 25.` with no STDERR
     skip warnings (all four skip counts 0, as predicted by this stage's
     pre-flight dangling-reference survey) — matching the source exactly
     on all ten row counts (6 / 26 / 35 / 25 / 46 / 4 / 626 / 699 / 215 /
     111 on both sides, independently re-verified twice). Spot-checked 5
     real students with the most fee deposits (admission_no 9839, 9824,
     7745, 5439, 9315) — fee group names, fee type names, and deposit
     amounts byte-for-byte identical between `al_hafeez_campus` and
     `school_saas` despite the expected id remapping — and verified end
     to end via `PilotFees` (699 `<li>` entries, 0 "Unknown"
     occurrences).

   - **Stage 2 — HR/staff leave** — ✅ complete (2026-07-13, plan:
     `2026-07-13-multi-tenant-phase3-stage2-hr-leave.md`). Extends
     `school_saas` with four tenant-scoped tables — `department`,
     `staff_designation`, `leave_types`, `staff_leave_details` — the
     shallowest FK chain of any Phase 3 stage so far (one layer: catalog
     → per-staff leave-type allotment). A new `MergeHrData` tool (extends
     `AbstractTenantMerger`) migrates all four tables in one transaction:
     the three catalog tables via plain `IdRemapper` (fresh data), and
     `staff_leave_details` by reconnecting `staff_id` to the
     already-migrated `staff` table via `NaturalKeyIdResolver::resolve()`
     on `staff`/`email` — reused completely unchanged, proving it
     generalizes to a THIRD table/column shape (after `sessions`/`session`
     in Stage 1, `students`/`admission_no` in the earlier exam-data
     stage). A new `PilotHr` controller proves it end to end. This
     stage's plan explicitly surveyed and deferred, as 0-real-rows and
     out of scope: `staff_leave_request` (the actual leave
     request/approval workflow, distinct from the leave-type ALLOTMENT
     records this stage migrates), `homework`, `subject_timetable`, and
     everything under payroll (`staff_payroll`, `staff_payslip`,
     `payslip_allowance`), library (`book_issues`, `visitors_book`;
     `books` has exactly 1 row, also excluded as not-meaningfully-real),
     hostel (`hostel`, `hostel_rooms`), and transport (`vehicles`,
     `vehicle_routes`, `transport_route`, `route_pickup_point`) — all
     confirmed 0 rows in `al_hafeez_campus` before this stage was scoped,
     so there is nothing real to migrate for any of those modules yet;
     they remain open for a future Phase 3 stage once (or if) real data
     ever lands in them. **First real run succeeded on the first try, no
     bugs found:** `Migrated 5 departments, 12 designations, 2 leave
     types, and 32 staff leave allotments for tenant 25.` with no STDERR
     skip warning (skip count 0, as predicted by this stage's pre-flight
     dangling-reference survey, and by the confirmed-unique email
     addresses across all 18 real staff) — matching the source exactly
     on all four row counts (5 / 12 / 2 / 32 on both sides). Spot-checked
     5 real staff (by email: `rabiachauhan923@gmail.com`,
     `aliakhan031047@gmail.com`, `hassamchuhanchuhan@gmail.com`,
     `rabiach.iqbal@gmail.com`, `sana909943@gmail.com`) — leave type
     names (`medical`, `Half Leave`) and `alloted_leave` values (empty
     string in the real source data for every row checked, carried
     through unchanged) identical between `al_hafeez_campus` and
     `school_saas` despite the expected id remapping — and verified end
     to end via `PilotHr` (32 `<li>` entries, 0 "Unknown" occurrences,
     header reading "Staff Leave Allotments (32 results, 5 departments,
     12 designations)").

   - **Stage 3 — Second real controller retrofit (Feesforward)** — ✅
     complete (2026-07-13, plan:
     `2026-07-13-multi-tenant-phase3-stage3-second-controller-retrofit.md`).
     The second stage to touch code in the LIVE admin panel's shared
     execution path (after Phase 2 Stage 6's `Staff.php`), and a
     deliberately small one: unlike Stage 6, no new database migration
     or settings-fixture tables were needed — `student_fees_deposite`
     was already migrated by Phase 3 Stage 1 (699 real rows for tenant
     25), and the `sch_settings`/`languages`/`currencies` fixture tables
     `MY_Controller`'s autoload chain needs were already put in place by
     Stage 6's Post-Task-5 fix. This stage's only job was to prove Stage
     6's allowlist-gate mechanism (`Admin_Controller`'s `admin_tenant_id`
     check, generalized from a single `if` to a keyed array in this
     stage's own Task 1) actually generalizes to a second real route, not
     just in theory. It did, exactly as Stage 6's final review predicted:
     the allowlist gained one new entry (`'feesforward' =>
     'tenantfeeslist'`) alongside the pre-existing `'staff' =>
     'tenantstafflist'` entry, with zero changes to the gate's
     conditional logic, `Db_manager`'s connection-routing gate, or any
     other shared file — a one-line array addition, no gate rebuild.
     `Feesforward.php`/`Studentfeemaster_model.php` gained one new
     tenant-scoped method each (`tenantFeesList()` /
     `getTenantScopedFeesList($tenantId)`, an explicit `WHERE tenant_id
     = ?` filter matching the query-scoping strategy locked in during
     Phase 2 Stage 6), added surgically via `git hash-object`/
     `git update-index` to avoid sweeping in substantial unrelated
     pre-existing uncommitted work already present in both files before
     this stage began (documented in this stage's Task 2 report). No
     bugs found during implementation or verification — the smooth
     landing Stage 6's final review anticipated for the next controller,
     now that the hard infrastructure cost (the gate itself, the
     `Db_manager` routing gate, and the settings-fixture tables) had
     already been paid down. Verified end to end with a real `PilotLogin`
     authentication, in one script against a single fixed cookie jar (the
     documented shell-variable-persistence pitfall from earlier stages):
     the real `admin/staff/tenantStaffList` still returns the real 18
     tenant-25 staff rows (proving the generalization didn't regress the
     original route), the real `admin/feesforward/tenantFeesList` returns
     the real 699 tenant-25 fee-deposit rows, and `admin/admin/dashboard`,
     `admin/examgroup`, the real un-gated `admin/staff` index, AND the
     real un-gated `admin/feesforward` index (the write-heavy carry-forward
     workflow this stage deliberately never touches) all return `404` for
     that same tenant-scoped session — proving the allowlist is specific
     to the exact two gated methods, not either controller as a whole.
     One new credentialed regression test added to
     `tests/controllers/AdminControllerTenantGateTest.php` codifying all
     of the above; full suite `OK (59 tests, 231 assertions)` (58 from
     Phase 3 Stage 2 + this stage's 1 new test, no regressions).

   - **Stage 4 — Third real controller retrofit (Examgroup)** — ✅
     complete (2026-07-13, plan:
     `2026-07-13-multi-tenant-phase3-stage4-third-controller-retrofit.md`).
     The THIRD stage to touch code in the LIVE admin panel's shared
     execution path (after Phase 2 Stage 6's `Staff.php` and Phase 3
     Stage 3's `Feesforward.php`), and — like Stage 3 before it — a
     deliberately small one: no new database migration was needed at all,
     since `exam_group_exam_results` (2785 real rows for tenant 25) was
     already migrated by an earlier stage, and the
     `sch_settings`/`languages`/`currencies` fixture tables
     `MY_Controller`'s autoload chain needs were already put in place by
     Stage 6's Post-Task-5 fix and reused unchanged by Stage 3. This
     stage's only job was to prove the allowlist-gate mechanism
     (`Admin_Controller`'s `admin_tenant_id` check in
     `application/core/MY_Controller.php`, generalized from a single `if`
     to a keyed array by Stage 3's own Task 1) generalizes to a THIRD real
     route, not just two. It did, exactly as Stage 3's final review
     predicted: the allowlist gained one new entry (`'examgroup' =>
     'tenantexamresultslist'`) alongside the two pre-existing entries
     (`'staff' => 'tenantstafflist'`, `'feesforward' =>
     'tenantfeeslist'`), with zero changes to the gate's conditional
     logic, `Db_manager`'s connection-routing gate, or any other shared
     file — another one-line array addition, no gate rebuild.
     `Examgroup.php`/`Examgroup_model.php` gained one new tenant-scoped
     method each (`tenantExamResultsList()` /
     `getTenantScopedExamResultsList($tenantId)`, the same explicit
     `WHERE tenant_id = ?` filter strategy locked in during Phase 2 Stage
     6 and reused unchanged by Stage 3). This is the SECOND consecutive
     stage to add "one allowlist entry and one gated method" with zero
     new infrastructure needed — confirming the mechanism now scales
     cleanly to three controllers at the same one-line-per-stage cost, not
     just two. No bugs found during implementation or verification.
     Verified end to end with a real `PilotLogin` authentication, in one
     script against a single fixed cookie jar (the documented
     shell-variable-persistence pitfall from earlier stages): the real
     `admin/staff/tenantStaffList` still returns the real 18 tenant-25
     staff rows and `admin/feesforward/tenantFeesList` still returns the
     real 699 tenant-25 fee-deposit rows (proving the third allowlist
     entry didn't regress either prior route), the real
     `admin/examgroup/tenantExamResultsList` returns the real 2785
     tenant-25 exam-result rows (matching Phase 2 Stage 5's migrated count
     exactly), and `admin/admin/dashboard`, the real un-gated `admin/staff`
     index, the real un-gated `admin/feesforward` index, the real un-gated
     `admin/examgroup` index, AND a completely unrelated real controller
     (`admin/examresult`) all return `404` for that same tenant-scoped
     session — proving the allowlist is specific to the exact three gated
     methods, never opening up a whole controller or an unrelated one. One
     new credentialed regression test added to
     `tests/controllers/AdminControllerTenantGateTest.php` codifying all
     of the above; full suite `OK (61 tests, 246 assertions)` (59 from
     Phase 3 Stage 3 + this stage's 2 new tests, no regressions).

   - **Stage 5 — Shadow-verify real Site.php login** — ✅ complete
     (2026-07-14, plan:
     `2026-07-14-multi-tenant-phase3-stage5-shadow-login-verify.md`;
     commits `e0c00ae9`, `1ae18656`, `839e6786`, `58415c8a`). Unlike Stages 3 and 4,
     this stage does not add a new gated method or touch the allowlist at
     all — it wires a small, isolated, read-only credential check into the
     one real production login path every school shares,
     `Site.php::login()`. (Note: `multi_branch` actually has 7
     `is_verified=1` rows, ids 19-25; "6 schools" below excludes id 19,
     "A Branch" / `new_db`, a test/placeholder branch, not a real school.)
     Task 1 built `tools/multitenant/ShadowLoginVerifier.php`,
     a standalone PDO-based class (no CodeIgniter dependencies) that takes
     an email/password/tenant_id and an injected password-hash-matching
     callable, queries `school_saas.staff` directly, and returns
     `{matched, reason}` — unit-tested against a synthetic fixture (5 new
     tests). Task 2 wired a guarded call to it into the real `Site.php`:
     after a real login already succeeds and the pre-existing multi-branch
     login fix has already resolved `$found_group === 'branch_25'`
     (Al-Hafeez Campus, tenant_id 25, the sole pilot tenant), it opens an
     isolated PDO connection to `school_saas` (via the `school_saas_pilot`
     config entry, never `$this->db`), re-verifies the same credentials
     the user just logged in with, and `log_message`s the result —
     wrapped in try/catch so any failure is swallowed, never surfacing to
     the user. No session key, no redirect target, and no response body
     changes as a result of this block, for any of the 6 schools,
     ever — the block runs purely for its logged side effect. `Site.php`
     was clean going into this stage (the "MULTI BRANCH STAFF LOGIN FIX"
     block Task 2 gates on had already landed separately, in commit
     `2e507f3c`, four days earlier), so a plain commit (`1ae18656`) was
     used, plus a tiny follow-up doc-fix commit (`839e6786`) correcting one
     inaccurate code comment a reviewer caught about how the block avoids
     touching `$this->db`.

     **Verification and its honest limitation:** Task 3 confirmed the
     known test credential is still intact in `school_saas`
     (`rabiachauhan923@gmail.com`, id 1, tenant_id 25), then directly
     invoked `ShadowLoginVerifier` against that real (not synthetic) row
     for three cases — correct password, wrong password, wrong tenant —
     all three matching expectations exactly
     (`{"matched":true,"reason":"ok"}`,
     `{"matched":false,"reason":"password_mismatch"}`,
     `{"matched":false,"reason":"no_matching_row"}`), closing the gap
     between "unit-tested against a fixture" and "works against the
     actual pilot tenant's real staff table." The Task 2 Step 4
     failure-path smoke test (`site/login` with a bogus, non-existent
     account) was re-run against the fully-committed state and returned
     the same `200` + "Invalid Username Or Password" content as before,
     confirming the committed code behaves identically to the working-tree
     version already smoke-tested in Task 2. Full suite:
     `OK (66 tests, 251 assertions)` (61 from Phase 3 Stage 4 + 5 new
     `ShadowLoginVerifier` tests from Task 1; Task 2 added no new automated
     tests, a known and deliberate limitation, not an oversight — see
     below). **What this stage did NOT do, and why:** no real
     `branch_25`/Al-Hafeez-Campus HTTP-level login was ever exercised
     against `Site.php` with this stage's shadow-verify code live. Doing
     so would have required either using a real school's live production
     password (touching a real school's real credentials was explicitly
     out of scope) or inserting new synthetic test data into the live
     `al_hafeez_campus` production database purely to manufacture a
     crash-test credential (also explicitly out of scope, unlike the
     read-only `school_saas` checks above). Task 3 Step 2's direct-class
     invocation against real `school_saas` data is the closest verification
     available without doing either — it proves the verifier logic is
     correct against real migrated rows, but it does not prove the guarded
     call site inside `Site.php` actually fires and logs correctly on a
     genuine authenticated request. That end-to-end HTTP-level gap remains
     open, by design, until a future stage can either use a disposable
     test-only tenant or gets sign-off to touch real pilot-tenant
     credentials.

     **Deliberately no `admin_tenant_id`, no allowlist change:** unlike
     Phase 2 Stage 6 and Phase 3 Stages 3-4, this stage does not set the
     `admin_tenant_id` session flag and does not add or modify any entry
     in `Admin_Controller`'s allowlist gate — it has nothing to do with
     that mechanism. This is pure read-only shadow verification bolted
     onto the tail of the existing real-login success path: real users,
     including the real `al_hafeez_campus` staff, see zero behavior
     change — same session, same redirect, same response, for every
     school, every time. The only observable effect is a new log line on
     pilot-tenant logins.

   - **Stage 6 — Fourth real controller retrofit (Stuattendence)** — ✅
     complete (2026-07-14, plan:
     `2026-07-14-multi-tenant-phase3-stage6-fourth-controller-retrofit.md`;
     commits `4b51bd41` (allowlist entry), `5fbe4d5f` (gated method),
     `faee9a56` (regression test), plus this roadmap-update commit). The
     FOURTH stage to touch code in the LIVE admin panel's shared execution
     path (after Phase 2 Stage 6's `Staff.php`, Phase 3 Stage 3's
     `Feesforward.php`, and Phase 3 Stage 4's `Examgroup.php`), and — like
     Stages 3 and 4 before it — a deliberately small one: no new database
     migration was needed at all, since `student_attendences` (1124 real
     rows for tenant 25) was already migrated by an earlier stage, and the
     `sch_settings`/`languages`/`currencies` fixture tables
     `MY_Controller`'s autoload chain needs were already put in place by
     Phase 2 Stage 6's Post-Task-5 fix and reused unchanged by Stages 3-4.
     This stage's only job was to prove the allowlist-gate mechanism
     (`Admin_Controller`'s `admin_tenant_id` check in
     `application/core/MY_Controller.php`, generalized from a single `if`
     to a keyed array by Phase 3 Stage 3's own Task 1) generalizes to a
     FOURTH real route, not just three. It did, exactly as Stage 4's final
     review predicted: the allowlist gained one new entry (`'stuattendence'
     => 'tenantattendancelist'`) alongside the three pre-existing entries
     (`'staff' => 'tenantstafflist'`, `'feesforward' => 'tenantfeeslist'`,
     `'examgroup' => 'tenantexamresultslist'`), with zero changes to the
     gate's conditional logic, `Db_manager`'s connection-routing gate, or
     any other shared file — another one-line array addition, no gate
     rebuild. `Stuattendence.php`/`Stuattendence_model.php` gained one new
     tenant-scoped method each (`tenantAttendanceList()` /
     `getTenantScopedAttendanceList($tenantId)`, the same explicit
     `WHERE tenant_id = ?` filter strategy locked in during Phase 2 Stage 6
     and reused unchanged by Stages 3-4). This is the THIRD real-controller
     retrofit (Stages 3, 4, and 6 — Stage 5 sat between them and added no
     allowlist entry, so it's three consecutive *retrofits*, not three
     consecutive *stages*) to add "one allowlist entry and one gated
     method" with zero new infrastructure needed — confirming the
     mechanism now scales cleanly to four controllers at the same
     one-line-per-stage cost, not just three. The allowlist gate, `Db_manager` connection gate, and settings
     fixture tables have all been unchanged since Phase 2 Stage 6. No bugs
     found during implementation or verification. Verified end to end with
     a real `PilotLogin` authentication, in one script against a single
     fixed cookie jar (the documented shell-variable-persistence pitfall
     from earlier stages): the real `admin/staff/tenantStaffList` still
     returns the real 18 tenant-25 staff rows, `admin/feesforward/tenantFeesList`
     still returns the real 699 tenant-25 fee-deposit rows, and
     `admin/examgroup/tenantExamResultsList` still returns the real 2785
     tenant-25 exam-result rows (proving the fourth allowlist entry didn't
     regress any of the three prior routes), the real
     `admin/stuattendence/tenantAttendanceList` returns the real 1124
     tenant-25 attendance rows, and `admin/admin/dashboard`, the real
     un-gated `admin/staff` index, `admin/feesforward` index, `admin/examgroup`
     index, a completely unrelated real controller (`admin/examresult`), the
     real un-gated `admin/stuattendence` index, AND two real sibling methods
     on the newly-allowlisted controller itself
     (`admin/stuattendence/attendencereport`,
     `admin/stuattendence/index`) all return `404` for that same
     tenant-scoped session — proving the allowlist is specific to the exact
     four gated methods, never opening up a whole controller (including the
     one that was JUST allowlisted) or an unrelated one. One new
     credentialed regression test added to
     `tests/controllers/AdminControllerTenantGateTest.php` codifying all of
     the above; full suite `OK (67 tests, 262 assertions)` (66 from Phase 3
     Stage 5 + this stage's 1 new test, no regressions). `git diff` for
     `Stuattendence.php`/`Stuattendence_model.php` confirms only additions —
     the pre-existing `index`, `attendencereport`, `monthAttendance`,
     `saveclasstime`, and `savestudentsetting` methods serving real,
     un-gated schools today were left untouched. Pre-existing unrelated
     uncommitted work in the working tree (noted at the start of Phase 3
     Stage 3 and carried since) remains present and untouched by this
     stage's commits, each of which staged only its own target files.

   - **Stage 7 — Fifth real controller retrofit (Leaverequest)** — ✅
     complete (2026-07-14, plan:
     `2026-07-14-multi-tenant-phase3-stage7-fifth-controller-retrofit.md`;
     commits `eb6ece4b` (allowlist entry), `8a0dc204` (gated method),
     `929b68df` (regression test), plus this roadmap-update commit). The
     FIFTH stage to touch code in the LIVE admin panel's shared execution
     path (after Phase 2 Stage 6's `Staff.php`, Phase 3 Stage 3's
     `Feesforward.php`, Phase 3 Stage 4's `Examgroup.php`, and Phase 3
     Stage 6's `Stuattendence.php`), and — like Stages 3, 4, and 6 before
     it — a deliberately small one: no new database migration was needed
     at all, since `staff_leave_details`/`leave_types` (32 real rows for
     tenant 25) were already migrated to `school_saas` in Phase 3 Stage 2,
     and the `sch_settings`/`languages`/`currencies` fixture tables
     `MY_Controller`'s autoload chain needs were already put in place by
     Phase 2 Stage 6's Post-Task-5 fix and reused unchanged by Stages 3,
     4, and 6. This stage's only job was to prove the allowlist-gate
     mechanism (`Admin_Controller`'s `admin_tenant_id` check in
     `application/core/MY_Controller.php`, generalized from a single `if`
     to a keyed array by Phase 3 Stage 3's own Task 1) generalizes to a
     FIFTH real controller, not just four. It did, exactly as Stage 6's
     final review predicted: the allowlist gained one new entry
     (`'leaverequest' => 'tenantleaverequestlist'`) alongside the four
     pre-existing entries (`'staff' => 'tenantstafflist'`, `'feesforward'
     => 'tenantfeeslist'`, `'examgroup' => 'tenantexamresultslist'`,
     `'stuattendence' => 'tenantattendancelist'`), with zero changes to
     the gate's conditional logic, `Db_manager`'s connection-routing gate,
     or any other shared file — another one-line array addition, no gate
     rebuild. `Leaverequest.php`/`Leaverequest_model.php` gained one new
     tenant-scoped method each (`tenantLeaveRequestList()` /
     `getTenantScopedLeaveList($tenantId)`, the same explicit `WHERE
     tenant_id = ?` filter strategy locked in during Phase 2 Stage 6 and
     reused unchanged by Stages 3, 4, and 6). This is the FOURTH
     real-controller retrofit (Stages 3, 4, 6, and 7 — Stage 5 sat between
     Stages 4 and 6 and added no allowlist entry, so these are four
     consecutive *retrofits*, not four consecutive *stages*; counting from
     Phase 2 Stage 6's original `Staff.php` implementation, this is the
     FIFTH real controller in the series overall — staff, feesforward,
     examgroup, stuattendence, now leaverequest) to add "one allowlist
     entry and one gated method" with zero new infrastructure needed —
     confirming the mechanism now scales cleanly to five controllers at
     the same one-line-per-stage cost, not just four. The allowlist gate,
     `Db_manager` connection gate, and settings fixture tables have all
     been unchanged since Phase 2 Stage 6. No bugs found during
     implementation or verification. Verified end to end with a real
     `PilotLogin` authentication, in one script against a single fixed
     cookie jar (the documented shell-variable-persistence pitfall from
     earlier stages): the real `admin/staff/tenantStaffList` still returns
     the real 18 tenant-25 staff rows, `admin/feesforward/tenantFeesList`
     still returns the real 699 tenant-25 fee-deposit rows,
     `admin/examgroup/tenantExamResultsList` still returns the real 2785
     tenant-25 exam-result rows, and `admin/stuattendence/tenantAttendanceList`
     still returns the real 1124 tenant-25 attendance rows (proving the
     fifth allowlist entry didn't regress any of the four prior routes),
     the real `admin/leaverequest/tenantLeaveRequestList` returns the real
     32 tenant-25 leave-request rows, and `admin/admin/dashboard`, the
     real un-gated `admin/staff` index, `admin/feesforward` index,
     `admin/examgroup` index, `admin/stuattendence` index, a completely
     unrelated real controller (`admin/examresult`), AND two real sibling
     methods on the newly-allowlisted controller itself
     (`admin/leaverequest/leaverequest`, `admin/leaverequest/leaveRecord`)
     all return `404` for that same tenant-scoped session — proving the
     allowlist is specific to the exact five gated methods, never opening
     up a whole controller (including the one that was JUST allowlisted)
     or an unrelated one. One new credentialed regression test added to
     `tests/controllers/AdminControllerTenantGateTest.php` codifying all
     of the above; full suite `OK (68 tests, 275 assertions)` (67 from
     Phase 3 Stage 6 + this stage's 1 new test, no regressions). `git
     diff` for `Leaverequest.php`/`Leaverequest_model.php` confirms only
     additions — the pre-existing `leaverequest`, `countLeave`,
     `leaveStatus`, `remove`, `leaveRecord`, `dateDifference`, `addLeave`,
     `add_staff_leave`, `handle_upload`, and `downloadleaverequestdoc`
     methods serving real, un-gated schools today were left untouched.
     Pre-existing unrelated uncommitted work in the working tree (noted at
     the start of Phase 3 Stage 3 and carried since) remains present and
     untouched by this stage's commits, each of which staged only its own
     target files.

     **One observed anomaly, investigated and confirmed benign:** the bare
     `admin/leaverequest` URL (no method segment) returns `307` redirecting
     to `site/userlogin` rather than a literal `404`. Root cause: unlike
     `Staff.php`/`Feesforward.php`/`Examgroup.php`/`Stuattendence.php`
     (which all define an `index()` method, so a bare request routes to
     that method and is then blocked by the allowlist gate's own
     `show_404()` call), `Leaverequest.php` has never had an `index()`
     method — its first real method has always been `leaverequest()`. CI3
     therefore cannot resolve a callable method for the bare URL at all,
     which triggers the global `404_override` route
     (`welcome/show_404`); `Welcome` extends `Front_Controller`
     (`application/core/MY_Controller.php`, lines ~276-305), whose
     constructor unconditionally redirects to `site/userlogin` when the
     front CMS / online-admission features aren't active — pre-existing,
     tenant-gate-unrelated, global app behavior, confirmed unchanged by
     this stage's commits. Both real sibling-method probes the regression
     test actually exercises (`admin/leaverequest/leaverequest`,
     `admin/leaverequest/leaveRecord`) correctly return `404`, proving the
     gate itself is unaffected; this anomaly is scoped entirely to the one
     bare-URL manual check and does not weaken the security property under
     test.

   - **Stage 8 — Pilot proof-harness security hardening** — ✅ complete
     (2026-07-14, plan: `p3s8-task-3-brief.md` and its Task 1/2 companions;
     commits `42812b52` (environment gate), `0d8d978a` (session-leak fix +
     backdoor removal), `db977eae` (regression test), plus this
     roadmap-update commit). Unlike Stages 3-7, this stage touches zero
     code in the live admin panel's shared execution path
     (`application/core/MY_Controller.php`, `application/libraries/Db_manager.php`
     are untouched) — it closes out the `Pilot*` proof-harness debt items
     flagged in the "Non-negotiables" section above rather than adding a
     sixth real-controller retrofit.

     Two originally-logged debt items are addressed. First, the
     "Non-negotiables" section's original wording: "All `Pilot*`
     controllers (`PilotStudents`, `PilotLogin`, `PilotClasses`, ...) are
     an unauthenticated proof harness — anyone can call
     `login_as/<any-id>` and select any tenant with data. They must be
     removed or gated behind a real auth check before Phase 5's cutover."
     Second, the same section's note that "this credential must be
     rotated or the test restructured to avoid a committed working login
     before any non-local deployment, at the same time the broader
     `Pilot*` removal/gating happens" (referring to the
     `rabiachauhan923@gmail.com` / `TestVerify123!` tenant-25 test
     credential committed in `tests/controllers/AdminControllerTenantGateTest.php`
     since Stage 6) — this stage's environment gate is the "broader
     removal/gating" event that note was waiting on; the credential
     itself was left in place (still local-dev-only, still low-risk per
     Stage 6's review) since the gate now prevents it from being usable
     outside `ENVIRONMENT === 'development'` at all.

     While closing these, Task 2 found a second, more severe bug than the
     originally-logged `login_as` issue alone: `PilotLogin::login()` set
     `pilot_tenant_id` in session *before* validating credentials
     (`$this->session->set_userdata('pilot_tenant_id', $tenantId);` ran
     immediately after reading the POSTed `tenant_id`, ahead of the
     staff-row lookup, password check, and active-flag check), and none
     of the three failure branches (`count($staffRows) !== 1`, bad
     password, `is_active !== 1`) unset it before returning their
     "Invalid email or password." / "Account disabled." message. The
     practical effect: *any* failed login attempt against
     `pilotlogin/login` — regardless of whether the credentials were
     merely wrong or complete nonsense — still left a fully usable
     `pilot_tenant_id` in the requester's session. Since the login form
     itself hardcodes `<input type="hidden" name="tenant_id" value="25">`
     in its own HTML (visible to anyone who loads `pilotlogin/login`
     unauthenticated), this meant simply POSTing garbage credentials to
     that endpoint was enough to leave a session that could then reach
     any of the other 7 real, tenant-scoped `Pilot*` controllers
     (`PilotStudents`, `PilotClasses`, `PilotAttendance`, `PilotExam`,
     `PilotFees`, `PilotHr`, `PilotStudentSessions`) and read/write their
     tenant-25 data — worse than the already-logged `login_as` backdoor,
     since it required no working knowledge of that specific method name
     and looked, from the outside, like a normal failed-login response.
     Fixed by adding `$this->session->unset_userdata('pilot_tenant_id');`
     to all three failure branches (`application/controllers/PilotLogin.php`,
     commit `0d8d978a`) so a failed attempt leaves no session state
     capable of reaching `Tenant_Model::currentTenantId()` successfully;
     confirmed live by `PilotSecurityTest::testFailedPilotLoginDoesNotLeavePilotTenantIdUsable`,
     which posts bogus credentials, then immediately requests
     `pilotstudents/index` and asserts the real `<h1>Pilot Students
     (tenant_id = ...)</h1>` heading is absent (it throws instead, since
     `Tenant_Model::currentTenantId()` — `application/core/Tenant_Model.php`
     — raises `RuntimeException` when `pilot_tenant_id` is empty). The
     same commit also deleted `PilotStudents::login_as($tenantId)`
     outright — a six-line unauthenticated method that set
     `pilot_tenant_id` from a raw URL segment with no credential check at
     all — closing the originally-logged issue directly rather than
     relying on the environment gate alone to contain it.

     The systemic fix, from Task 1, is `PilotAccessGate::isAllowed()`
     (`tools/multitenant/PilotAccessGate.php`) — a one-line
     `$environment === 'development'` check — wired into a new
     `Pilot_Controller` base class (`application/core/Pilot_Controller.php`)
     whose constructor calls `show_404()` when the gate rejects, and
     confirmed all 8 `Pilot*` controllers (`PilotClasses`,
     `PilotAttendance`, `PilotExam`, `PilotFees`, `PilotHr`,
     `PilotStudentSessions`, `PilotLogin`, `PilotStudents`) now `extends
     Pilot_Controller` rather than `CI_Controller` directly. `ENVIRONMENT`
     is the real CI3 constant defined in `index.php` (`define('ENVIRONMENT',
     'development');`), not a typo'd or shadowed variable — so today,
     locally, the gate is a no-op (the whole harness still works exactly
     as before for local verification), but it will `show_404()` the
     entire `Pilot*` surface the moment `ENVIRONMENT` is anything other
     than `'development'`, which is exactly the "removed or gated" bar
     the Non-negotiables section set.

     `PilotSecurityTest.php` (3 new tests) covers all three properties:
     (1) a failed login doesn't leave usable tenant data reachable
     (above), (2) `pilotstudents/login_as/25` no longer resolves to a
     200 (proving the backdoor method is gone, not just inaccessible),
     (3) the legitimate credentialed path (`rabiachauhan923@gmail.com` /
     `TestVerify123!`, tenant 25) still reaches the real 312-row
     tenant-25 student list via `pilotstudents/index`'s real `<h1>Pilot
     Students (tenant_id = 25)</h1>` heading, proving the fix didn't
     break the harness's actual purpose. Full suite: `OK (75 tests, 285
     assertions)` (68 from Phase 3 Stage 7 + Task 1's 4-test
     `PilotAccessGateTest` + this stage's 3 new `PilotSecurityTest` tests
     = 75, no regressions).
     `git diff` for `application/core/MY_Controller.php` and
     `application/libraries/Db_manager.php` is empty — this stage is
     completely isolated from the 5 already-retrofitted real controllers
     (staff/feesforward/examgroup/stuattendence/leaverequest) and the
     `admin_tenant_id`/allowlist-gate mechanism. Pre-existing unrelated
     uncommitted work in the working tree (noted at the start of Phase 3
     Stage 3 and carried since) remains present and untouched by this
     stage's commits, each of which staged only its own target files.

     **Honest framing, not full closure:** this stage closes the
     *documented* debt items (the `Pilot*` harness is now gated, and the
     credential-rotation note tied to that gating event is resolved) —
     it does not eliminate the underlying `Pilot*` controllers, which
     remain an unauthenticated-by-design proof harness, still not
     intended for any real deployment, still carrying the same
     `tenant_id`-in-hidden-form-field pattern and the same lack of any
     real authentication scheme beyond "check a staff password against
     `school_saas_pilot`." The roadmap's Phase 5 guidance ("must be
     removed or gated behind a real auth check before Phase 5's
     cutover") is now satisfied via the environment gate, not by
     removing the harness outright — an explicit choice to keep the
     harness available for continued local verification work in Phases
     3-4 rather than deleting 8 controllers this stage still finds
     useful. That decision — gate vs. delete — should be revisited if
     and when Phase 5 cutover planning actually begins, at which point
     the harness's ongoing value (if any) should be weighed against the
     residual risk of shipping 8 controllers whose entire design assumes
     `ENVIRONMENT !== 'production'` is enforced correctly forever.

   - **Stage 9 — Sixth real controller retrofit (Classes)** — ✅ complete
     (2026-07-14, plan:
     `2026-07-14-multi-tenant-phase3-stage9-sixth-controller-retrofit.md`;
     commits `3db01c8f` (allowlist entry), `9bd89af6` (gated method),
     `8dd183e1` (regression test), plus this roadmap-update commit). The
     SIXTH stage to touch code in the LIVE admin panel's shared execution
     path (after Phase 2 Stage 6's `Staff.php`, Phase 3 Stage 3's
     `Feesforward.php`, Phase 3 Stage 4's `Examgroup.php`, Phase 3 Stage
     6's `Stuattendence.php`, and Phase 3 Stage 7's `Leaverequest.php`),
     and — like Stages 3, 4, 6, and 7 before it — a deliberately small
     one: no new database migration was needed at all, since `classes`
     (7 real rows for tenant 25) was already migrated to `school_saas` in
     Phase 2 Stage 2, and the `sch_settings`/`languages`/`currencies`
     fixture tables `MY_Controller`'s autoload chain needs were already
     put in place by Phase 2 Stage 6's Post-Task-5 fix and reused
     unchanged by Stages 3, 4, 6, and 7. This stage's only job was to
     prove the allowlist-gate mechanism (`Admin_Controller`'s
     `admin_tenant_id` check in `application/core/MY_Controller.php`)
     generalizes to a SIXTH real controller, not just five. It did,
     exactly as Stage 7's final review predicted: the allowlist gained
     one new entry (`'classes' => 'tenantclasslist'`) alongside the five
     pre-existing entries (`'staff' => 'tenantstafflist'`, `'feesforward'
     => 'tenantfeeslist'`, `'examgroup' => 'tenantexamresultslist'`,
     `'stuattendence' => 'tenantattendancelist'`, `'leaverequest' =>
     'tenantleaverequestlist'`), with zero changes to the gate's
     conditional logic, `Db_manager`'s connection-routing gate, or any
     other shared file — another one-line array addition, no gate
     rebuild (confirmed live: `git show --stat 3db01c8f` shows exactly
     `application/core/MY_Controller.php | 1 +`). `Classes.php`/
     `Class_model.php` gained one new tenant-scoped method each
     (`tenantClassList()` / `getTenantScopedClassList($tenantId)`, the
     same explicit `WHERE tenant_id = ?` filter strategy locked in during
     Phase 2 Stage 6 and reused unchanged by Stages 3, 4, 6, and 7);
     `git show --stat 9bd89af6` confirms `application/controllers/Classes.php`
     (+13), `application/models/Class_model.php` (+5), and the new
     `application/views/class/tenant_class_list.php` (+12) — 30
     insertions, 0 deletions across all three files, i.e. pure additions.
     This is the FIFTH real-controller retrofit within Phase 3 (Stages 3,
     4, 6, 7, and 9 — Stage 5 sat before Stage 6 and Stage 8 sat between
     Stage 7 and Stage 9, and neither added an allowlist entry, so these
     are five consecutive *retrofits* within Phase 3, not five
     consecutive *stages*; counting from Phase 2 Stage 6's original
     `Staff.php` implementation, this is the SIXTH real controller in the
     series overall — staff, feesforward, examgroup, stuattendence,
     leaverequest, now classes) to add "one allowlist entry and one gated
     method" with zero new infrastructure needed — confirming the
     mechanism now scales cleanly to six controllers at the same
     one-line-per-stage cost, not just five. The allowlist gate,
     `Db_manager` connection gate, and settings fixture tables have all
     been unchanged since Phase 2 Stage 6. No bugs found during
     implementation or verification. Verified end to end with a real
     `PilotLogin` authentication, in one script against a single fixed
     cookie jar (the documented shell-variable-persistence pitfall from
     earlier stages): the real `admin/staff/tenantStaffList` still
     returns the real 18 tenant-25 staff rows, `admin/feesforward/tenantFeesList`
     still returns the real 699 tenant-25 fee-deposit rows,
     `admin/examgroup/tenantExamResultsList` still returns the real 2785
     tenant-25 exam-result rows, `admin/stuattendence/tenantAttendanceList`
     still returns the real 1124 tenant-25 attendance rows, and
     `admin/leaverequest/tenantLeaveRequestList` still returns the real 32
     tenant-25 leave-request rows (proving the sixth allowlist entry
     didn't regress any of the five prior routes), the real
     `classes/tenantClassList` returns the real 7 tenant-25 class rows,
     and `admin/admin/dashboard`, the real un-gated `admin/staff` index,
     `admin/feesforward` index, `admin/examgroup` index,
     `admin/stuattendence` index, a completely unrelated real controller
     (`admin/examresult`), AND two real sibling methods plus the bare
     index route on the newly-allowlisted controller itself
     (`classes/index`, `classes/edit/1`, and the bare `classes` route,
     which resolves to the real `index()` method) all return `404` for
     that same tenant-scoped session — proving the allowlist is specific
     to the exact six gated methods, never opening up a whole controller
     (including the one that was JUST allowlisted) or an unrelated one.
     Unlike Stage 7's `Leaverequest.php` (which has no `index()` method,
     producing the documented benign 307-to-login anomaly on its own bare
     route), `Classes.php` does define `index()`, so `classes` (bare) and
     `classes/index` both exercise the actual allowlist-gate `show_404()`
     path and return a literal `404`, not the Stage 7-style redirect —
     confirmed live, no anomaly this stage. One new credentialed
     regression test added to
     `tests/controllers/AdminControllerTenantGateTest.php`
     (`testTenantScopedSessionReachesAllSixAllowlistedRoutesAndNothingElse`)
     codifying all of the above; full suite `OK (76 tests, 300
     assertions)` (75 from Phase 3 Stage 8 + this stage's 1 new test, no
     regressions). `git diff`/`git show --stat` for `Classes.php`/
     `Class_model.php` confirms only additions — the pre-existing
     `index`, `delete`, `edit`, and `get_section` methods serving real,
     un-gated schools today were left untouched (confirmed by direct
     read of the live file alongside the diff). Pre-existing unrelated
     uncommitted work in the working tree (the omnipay vendor-file
     deletion, the only item remaining as of Stage 8's close) remains
     present and untouched by this stage's commits, each of which staged
     only its own target files.

   - **Stage 14 — Full schema completeness (no data)** — ✅ complete
     (2026-07-15, plan:
     `2026-07-14-multi-tenant-phase3-stage14-schema-completeness.md`;
     commits `b91bebff` (`SchemaMirror` + tests), `9803e0bc` (guard against
     composite primary keys), `b952a029` (create all 152 remaining table
     schemas, no FKs yet), `aa6a357e` (link `tenant_id` + sibling FKs for
     all 152 new tables), plus this roadmap-update commit). Closes the gap
     where `school_saas` had only 39 of the real app's 193 tables — any
     future module referencing an unmigrated table would hard-fail with
     "table doesn't exist" the moment more of the codebase queries the
     shared schema, and any Phase 5 school that actually populates a module
     this pilot tenant doesn't use would have nowhere to land. A new,
     framework-agnostic `tools/multitenant/SchemaMirror.php` reads a source
     table's real column definitions live from `information_schema.columns`
     and generates a `tenant_id`-augmented `CREATE TABLE` statement — no FKs
     at all. Two CLI driver scripts apply this in deliberately separate
     phases, avoiding any need for a topological sort over the FK dependency
     graph: `CloneAllSchemas.php` creates all 152 in-scope tables first
     (Task 2), then, only once every table already exists, `LinkAllSchemaFKs.php`
     adds each table's `tenant_id → tenants(id)` FK plus every sibling FK as
     a separate follow-up pass (Task 3). **Three source tables deliberately
     excluded, not cloned:** `multi_branch` (per-branch connection
     config — meaningless in a single shared database), `migrations` (CI3's
     own schema-migration bookkeeping — this project tracks `school_saas`'s
     migrations separately via `sql/multitenant/*.sql`), `captcha`
     (transient challenge state, not business data). Confirmed live this
     task (Task 4): all three remain absent from `school_saas`, and the
     table-count arithmetic reconciles exactly — 193 source tables − 3
     excluded = 190 mirrored, + the 1 `school_saas`-only `tenants` table
     (which has no source-side counterpart) = 191, matching the live count.
     **Schema only, zero data population** — confirmed live this task:
     `SELECT table_name FROM information_schema.tables WHERE
     table_schema='school_saas' AND table_rows > 0` returns exactly 35
     tables, all of them tables that already carried real migrated data from
     earlier stages (students, staff, classes, fees, exams, HR, grades,
     etc.) — none of the 152 new tables added by this stage show any rows,
     for any tenant.

     **Real final counts (live-queried, Task 4, 2026-07-15):** 191 total
     tables (39 pre-existing + 152 new) and 411 total FK constraints
     (`SELECT COUNT(*) FROM information_schema.table_constraints WHERE
     table_schema='school_saas' AND constraint_type='FOREIGN KEY'`) — of
     those 411, 186 are `tenant_id → tenants(id)` FKs (152 net-new this
     stage; the other 4 in-scope tables — `currencies`, `email_config`,
     `languages`, `permission_group` — are pre-existing, legitimately global
     lookup tables with no `tenant_id` column by original design, so
     191 − 1 (`tenants` itself) − 4 = 186) and 225 are sibling FKs (36
     pre-existing + 189 net-new this stage). A live duplicate-relationship
     check (`GROUP BY table_name, column_name, referenced_table_name HAVING
     COUNT(*) > 1` against `information_schema.key_column_usage`) returned
     zero rows. Full suite: `OK (90 tests, 356 assertions)`, re-confirmed
     live this task. **Note on stale predictions:** this stage's own task
     briefs were written before Tasks 2-3 actually ran and predicted smaller
     figures in places (e.g. Task 4's brief still said "Expected: 89/89"
     tests and referenced a smaller FK total) — the real, live-confirmed
     numbers above supersede those stale predictions; flagged here rather
     than silently following an outdated figure, the same discipline Task 3
     applied to its own brief (see below) and Stage 10 applied to its
     original "63 FKs" prediction.

     **The Task 3 incident — a real duplicate-FK bug, found, root-caused,
     and fixed against the live database, not a hypothetical:**
     `LinkAllSchemaFKs.php`'s first real run against the live `school_saas`
     database uncovered a genuine defect in its own FK-dedup logic. The
     "skip if this FK already exists" guard originally pulled
     `FETCH_COLUMN, 0` from a query whose column list was `table_name,
     constraint_name` — index 0 is `table_name`, not `constraint_name` — so
     comparing a generated constraint name (e.g.
     `fk_exam_group_class_batch_exams_tenant`) against a list of bare table
     names could never match, silently making the "already exists" check
     dead code. Because 39 pre-existing tables already carried `tenant_id`
     FKs under an older, abbreviated naming convention (e.g.
     `fk_egcbe_tenant` for `exam_group_class_batch_exams`), the broken guard
     never collided with those existing names and happily added a second,
     genuinely redundant FK enforcing the identical relationship on each —
     **54 duplicate FK constraints silently created across 23 pre-existing
     tables**, with no error raised. Found by direct inspection of the real
     run's results (not by a test — no test covered this dedup path against
     already-migrated data), root-caused to the exact `FETCH_COLUMN`
     indexing bug above, the live database was cleaned up (the 54 duplicate
     constraints dropped) — twice, per the commit message, since the first
     cleanup attempt was itself re-examined before being trusted — and the
     dedup logic was rewritten to compare actual `(table, column,
     referenced_table)` relationships read live from
     `information_schema.key_column_usage`, rather than trusting generated
     constraint-name strings: a genuinely different, more robust comparison,
     not a narrower patch of the same broken one. A clean third run against
     the live database, and the resulting 411-FK, zero-duplicate end state,
     were committed together in `aa6a357e`.

     **Independently re-verified clean twice since, by two separate
     sessions:** (1) Phase 3 Stage 14's own Task 3 session re-ran the
     corrected script fresh against the live, already-411-FK database and
     confirmed true idempotency — `Tenant FKs added: 0`, `Sibling FKs added:
     0`, FK count unchanged at 411 immediately before and after, `git
     status` on the script itself empty (full detail:
     `.superpowers/sdd/p3s14-task-3-report.md`); (2) this task (Task 4), in
     a separate session, independently re-ran the live duplicate-relationship
     query fresh against the current database and got the same empty result
     — zero duplicate `(table, column, referenced_table)` groups, confirmed
     a second, independent time. The remaining 11 STDERR lines from the
     corrected script's re-run (4 tenant-FK skips for the legitimately-global
     lookup tables named above, 7 sibling-FK skips for columns absent from
     two pre-existing tables' intentionally-reduced schemas from earlier
     stages — `staff.designation`/`staff.department`,
     `student_session.session_id`/`.vehroute_id`/`.route_pickup_point_id`/
     `.hostel_room_id`, and one vestigial 0-row column on
     `exam_group_exam_results`) are all individually understood and
     non-actionable, not further instances of the dedup bug.

     **Live regression proof (Task 4):** with the corrected, 411-FK schema
     live, the already-retrofitted real routes were re-verified unaffected.
     A real `PilotLogin` authentication (tenant 25,
     `rabiachauhan923@gmail.com`) followed by `admin/staff/tenantStaffList`
     (200, real 18 tenant-25 staff rows) and `admin/grade/tenantGradeList`
     (200, real 14 tenant-25 grade rows) both matched every prior stage's
     verification exactly, and a bare, unauthenticated `site/login` request
     (fresh session, no cookies) returned 200 with the real login page.
     (**One test-script wrinkle, investigated and confirmed benign, not a
     regression:** chaining the `site/login` GET onto the SAME cookie jar as
     the `PilotLogin` call above instead returns `307` to
     `admin/admin/dashboard`. Root cause: `Auth::is_logged_in()`
     (`application/libraries/Auth.php`) redirects any session already
     carrying `admin` session userdata away from the login page, and
     `PilotLogin` sets that exact `admin` session key on real credential
     success — by design, unchanged since Phase 2 Stage 6. It only fires
     here because the verification script reuses one cookie jar across both
     calls; a fresh, cookie-less `site/login` request returns the expected
     `200`, confirming this is a test-script artifact of session reuse, not
     a behavior change caused by this schema-only stage.) Full suite: `OK
     (90 tests, 356 assertions)`, run three times this task for confidence —
     an isolated first run showed 8 transient failures (all in
     `AdminControllerTenantGateTest`/`PilotSecurityTest`, both real-HTTP
     credentialed tests hitting the same local Apache instance moments after
     this task's own manual curl verification against the same server), and
     two immediate re-runs both came back clean at exactly the confirmed
     baseline (90 tests, 356 assertions) with zero code or schema changes in
     between — consistent with transient local session/server contention
     from the just-prior manual verification, not a real regression.

     This stage substantially grows the "non-composite sibling FK" debt
     item in the Carried-forward technical debt section below — see that
     section's Stage 14 scope correction for the real, live-queried current
     total (225, up from 36).

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
- **Any stage that retrofits a real, live admin controller (not a
  parallel `Pilot*` proof controller) must include a live, credentialed,
  end-to-end HTTP request as a required task step — an unauthenticated
  smoke test is not sufficient.** Learned the hard way in Stage 6: two of
  its three real bugs (a wrong session-array shape causing a 500, and
  `MY_Controller`'s ~100-model autoload chain needing settings tables
  `school_saas` didn't have) were only reachable by a real authenticated
  request through the full controller/model chain — by design, no
  unauthenticated-redirect check or code review could have caught either
  one. Future plans for this kind of stage (e.g. Phase 3's eventual real
  controller retrofits) should schedule the live credentialed check as
  its own early task, not bundled at the very end, so this class of bug
  surfaces before more work is built on top of an untested assumption.
- All `Pilot*` controllers (`PilotStudents`, `PilotLogin`, `PilotClasses`,
  ...) are an unauthenticated proof harness — anyone can call
  `login_as/<any-id>` and select any tenant with data. They must be
  removed or gated behind a real auth check before Phase 5's cutover;
  flagged in Stage 2's final review (2026-07-09). Stage 6 sharpens this:
  `PilotLogin` now does real credential verification and, on success,
  reaches a real production controller (`Staff::tenantStaffList`) — its
  test suite (`tests/controllers/AdminControllerTenantGateTest.php`)
  necessarily commits a real, working tenant-25 staff email/test-password
  pair to source control to exercise this live. Reviewed and accepted as
  low-risk for now (local-dev-only environment, password is a
  known-test value set specifically for this purpose, the real
  `al_hafeez_campus` per-branch account/password was never touched) —
  but this credential must be rotated or the test restructured to avoid
  a committed working login before any non-local deployment, at the
  same time the broader `Pilot*` removal/gating happens.

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
  not `(tenant_id, id)` — same accepted debt. The same shape recurs again
  in all seven tables of `sql/multitenant/006_add_exam_tables.sql`: every
  FK (`session_id`, `exam_group_id`, `subject_id`, `student_id`,
  `student_session_id`, and the intra-stage exam-table links) references
  `<table>(id)` alone. Worth a composite FK before Phase 5 migrates
  additional schools.
  **Scope correction (Phase 3 Stage 10 research, 2026-07-14):** this
  item's original survey undercounted the true scope — it is not
  limited to 3 files / 11 tables. A full re-read of every file in
  `sql/multitenant/` (all 9: `001_create_school_saas.sql` through
  `009_add_hr_tables.sql`), cross-checked live against
  `information_schema.KEY_COLUMN_USAGE` for the actual `school_saas`
  database, found 69 total FK constraints across those 9 files, of
  which 33 are `tenant_id → tenants(id)` FKs (fine — `tenants` has no
  tenant scoping of its own, so those aren't part of this debt) and
  **36 are non-composite intra-schema sibling FKs spread across all 9
  files**, not just 003/005/006 — e.g. `fk_staffroles_staff` /
  `fk_staffroles_role` (002), `fk_classsections_class` /
  `fk_classsections_section` (003), `fk_studentsession_student` /
  `_class` / `_section` (004), `fk_studentattendences_type` /
  `_session` (005), nine such FKs across 006's exam tables, sixteen
  across 008's fee tables, and `fk_sld_staff` / `fk_sld_leavetype`
  (009). This item remains open and unresolved — only its documented
  scope is corrected here for whoever picks it up next. Note: this
  stage's planning brief anticipated a "63 FKs across 9 files" figure;
  this stage's direct SQL-file grep and live-schema audit consistently
  produced 69 total / 36 in-scope instead, and could not reproduce 63
  by any counting method tried (total FKs, non-tenant FKs, or distinct
  tables). The "9 files" part of the brief's figure checks out exactly;
  the "63" count does not — flagging this discrepancy rather than
  transcribing an unverified number.
  **Scope correction (Phase 3 Stage 14, 2026-07-15):** this item's true
  scope just grew dramatically. Stage 14 added 152 new tables (191 total
  now, not 39/9-files), each linked via `tools/multitenant/SchemaMirror.php`/
  `CloneAllSchemas.php`/`LinkAllSchemaFKs.php` rather than a new numbered
  `sql/multitenant/*.sql` file — so "36 FKs across 9 files" is now a stale
  description of a subset, not the whole debt item. Live-queried directly
  against `school_saas`'s `information_schema` (Task 4, not estimated):
  **411 total FK constraints, of which 186 are `tenant_id → tenants(id)`**
  (fine, same reasoning as the Stage 10 correction above — `tenants` isn't
  itself tenant-scoped, so these aren't part of this debt item) **and 225
  are non-composite intra-schema sibling FKs** — every one of them shaped
  exactly like the 36 Stage 10 catalogued, `<table>(id)` rather than
  `(tenant_id, id)`. Confirmed live that none of the 411 FKs are composite
  at all (`GROUP BY constraint_name HAVING COUNT(*) > 1` against
  `information_schema.key_column_usage` returns zero rows), so the count of
  "non-composite in-scope" FKs is simply every sibling FK: all 225. This
  item remains open and unresolved — **225 is the real, current, live
  total**, spanning all 191 tables, awaiting a composite-FK hardening pass
  before Phase 5 migrates additional schools.
- **Merge tools have no re-run/idempotency guard** — **RESOLVED in
  Phase 3 Stage 10 (2026-07-14).** (Originally discovered 2026-07-10
  during Stage 4's final-review fix-up, when a manual verification re-run
  of `MergeAttendanceData.php al_hafeez_campus 25` — expected to error
  against already-migrated data — instead silently duplicated all of
  tenant 25's attendance rows, 1124→2248 and 6→12, with no error and no
  count-mismatch signal; caught immediately via row counts and corrected
  by deleting exactly the duplicate rows in a transaction, verified via
  dangling-reference and per-tenant-count checks afterward.) At the time
  this was discovered, none of `MergeSchoolData`, `MergeStaffData`,
  `MergeClassData`, `MergeStudentSessionData`, or `MergeAttendanceData`
  (5 tools) checked whether the target tenant already had rows before
  inserting more; the debt note below has been corrected — the full set
  is 8 tools, not 5 (`MergeSchoolData`, `MergeStaffData`,
  `MergeClassData`, `MergeStudentSessionData`, `MergeAttendanceData`,
  `MergeExamData`, `MergeFeeData`, `MergeHrData`), and all 8 were
  affected equally.

  **Fix:** a new `AbstractTenantMerger::guardAgainstExistingData(string
  ...$tables): void` method (added in Stage 10 Task 1) runs a bound
  `SELECT COUNT(*) FROM `{table}` WHERE tenant_id = :tenant_id` for each
  named table and throws a `RuntimeException` — message includes
  "Refusing to run" and the offending table/row-count — if any of them
  already has rows for that tenant. Every one of the 8 concrete
  `Merge*Data::run()` methods (Stage 10 Tasks 1–2) calls it as the
  literal first statement, before any `fetchAll`, `nextId`, or remapper
  construction, listing every table that tool populates. Verified by 9
  new PHPUnit tests (2 for `MergeSchoolData` in Task 1, 1 each for the
  remaining 7 tools in Task 2) plus the full suite (85/85 passing).

  **Real-data proof (Stage 10 Task 3, 2026-07-14), not just synthetic
  fixtures:** with MySQL running against the live `school_saas`
  database, two of the 8 tools were actually re-run from the CLI
  against the real, already-migrated pilot tenant 25
  (`al_hafeez_campus`) — the same tenant and the same class of command
  that caused the original 2026-07-10 incident:
  - `php tools/multitenant/MergeAttendanceData.php al_hafeez_campus 25`
    — failed immediately with `RuntimeException: Refusing to run:
    tenant 25 already has 6 row(s) in `attendence_type`...`, exit code
    255, no success message. Tenant 25's row counts across `students`,
    `attendence_type`, `student_attendences`, `classes`, `sessions`,
    and `department` were confirmed byte-identical before and after
    (312 / 6 / 1124 / 7 / 15 / 5 both times).
  - `php tools/multitenant/MergeHrData.php al_hafeez_campus 25` —
    failed immediately with `RuntimeException: Refusing to run: tenant
    25 already has 5 row(s) in `department`...`, exit code 255, no
    success message. Tenant 25's row counts across `department`,
    `staff_designation`, `leave_types`, `staff_leave_details`,
    `students`, `classes`, and `sessions` were confirmed
    byte-identical before and after (5 / 12 / 2 / 32 / 312 / 7 / 15
    both times).

  Both attempts failed closed with zero data change against real
  production-equivalent data, not just PHPUnit fixtures. Full details
  in `.superpowers/sdd/p3s10-task-3-report.md`.
