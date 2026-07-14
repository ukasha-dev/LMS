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
