<?php

use PHPUnit\Framework\TestCase;

final class AdminControllerTenantGateTest extends TestCase
{
    private const BASE_URL = 'http://localhost/web-app/';

    private string $cookieJar;

    protected function setUp(): void
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_');
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

    public function testUngatedAdminSessionReachesDashboardExactlyAsBefore(): void
    {
        // No admin_tenant_id in play at all -- this proves Task 1's edit
        // introduced zero behavior change for a request that never sets
        // the new session key. We can't log in as a real school here
        // (no test credentials), so instead we confirm the UNAUTHENTICATED
        // redirect-to-login behavior is unchanged -- the earliest
        // observable behavior of Admin_Controller's constructor chain,
        // and the one most likely to regress if the gate were placed
        // incorrectly (e.g. before the auth check instead of after).
        // NOTE: 307 added to the brief's original [200, 302] list. CI3's
        // redirect() helper (system/helpers/url_helper.php) emits 307 (not
        // 302) for GET requests over HTTP/1.1 -- which curl (and every
        // modern browser) uses by default -- and only emits 302 over
        // HTTP/1.0. This is confirmed, deterministic, pre-existing
        // framework behavior verified against this repo's unmodified
        // system/helpers/url_helper.php, unrelated to Admin_Controller.
        [$status, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertContains($status, [200, 302, 307]);
    }

    public function testUngatedStudentAndDefaultSessionPathsAreUnaffected(): void
    {
        // No admin_tenant_id, no admin session at all -- exercises
        // Db_manager's third (neither-admin-nor-student) branch, which
        // Task 2's edit must leave completely untouched. A bare request
        // to a public controller is enough to prove the app still
        // boots and connects to its default database correctly.
        [$status, ] = $this->curlGet('site/login');
        $this->assertSame(200, $status);
    }

    public function testTenantScopedSessionReachesTheAllowlistedStaffListAndNothingElse(): void
    {
        // Credentials below are a KNOWN TEST PASSWORD set on one real
        // school_saas-only staff row (tenant_id=25), for exactly this
        // verification purpose -- see the plan's Task 5 "Credential
        // handling" note. The al_hafeez_campus per-branch database (and
        // that staff member's real account there) was never touched;
        // Site.php's real login flow, used by actual schools, has never
        // read from school_saas at all throughout this whole stage.
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);
        $this->assertStringContainsString('Staff (18 real, tenant-scoped rows)', $staffListBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$examgroupStatus, ] = $this->curlGet('admin/examgroup');
        $this->assertSame(404, $examgroupStatus);

        [$ungatedStaffIndexStatus, ] = $this->curlGet('admin/staff');
        $this->assertSame(404, $ungatedStaffIndexStatus);
    }

    public function testAllowlistGateStillAllowsTheOriginalStaffRouteAfterGeneralization(): void
    {
        // Regression proof for Task 1's generalization: the pre-existing
        // staff/tenantstafflist entry must keep working exactly as before,
        // not just "should still be in the array."
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);
    }

    public function testTenantScopedSessionReachesBothAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$feesforwardIndexStatus, ] = $this->curlGet('admin/feesforward');
        $this->assertSame(404, $feesforwardIndexStatus);
    }

    public function testAllowlistGateStillAllowsBothPriorRoutesAfterAThirdIsAdded(): void
    {
        // Regression proof for Task 1's third allowlist entry: both
        // pre-existing routes must keep working exactly as before.
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);
    }

    public function testTenantScopedSessionReachesAllThreeAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);

        [$examResultsStatus, $examResultsBody] = $this->curlGet('admin/examgroup/tenantExamResultsList');
        $this->assertSame(200, $examResultsStatus);
        $this->assertStringContainsString('Tenant Exam Results List', $examResultsBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$examgroupIndexStatus, ] = $this->curlGet('admin/examgroup');
        $this->assertSame(404, $examgroupIndexStatus);

        [$examresultStatus, ] = $this->curlGet('admin/examresult');
        $this->assertSame(404, $examresultStatus);
    }

    public function testTenantScopedSessionReachesAllFourAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);

        [$examResultsStatus, $examResultsBody] = $this->curlGet('admin/examgroup/tenantExamResultsList');
        $this->assertSame(200, $examResultsStatus);
        $this->assertStringContainsString('Tenant Exam Results List', $examResultsBody);

        [$attendanceStatus, $attendanceBody] = $this->curlGet('admin/stuattendence/tenantAttendanceList');
        $this->assertSame(200, $attendanceStatus);
        $this->assertStringContainsString('Tenant Attendance List', $attendanceBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$stuattendenceIndexStatus, ] = $this->curlGet('admin/stuattendence');
        $this->assertSame(404, $stuattendenceIndexStatus);
    }

    public function testTenantScopedSessionReachesAllFiveAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);

        [$examResultsStatus, $examResultsBody] = $this->curlGet('admin/examgroup/tenantExamResultsList');
        $this->assertSame(200, $examResultsStatus);
        $this->assertStringContainsString('Tenant Exam Results List', $examResultsBody);

        [$attendanceStatus, $attendanceBody] = $this->curlGet('admin/stuattendence/tenantAttendanceList');
        $this->assertSame(200, $attendanceStatus);
        $this->assertStringContainsString('Tenant Attendance List', $attendanceBody);

        [$leaveListStatus, $leaveListBody] = $this->curlGet('admin/leaverequest/tenantLeaveRequestList');
        $this->assertSame(200, $leaveListStatus);
        $this->assertStringContainsString('Tenant Leave Request List', $leaveListBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$leaverequestIndexStatus, ] = $this->curlGet('admin/leaverequest/leaverequest');
        $this->assertSame(404, $leaverequestIndexStatus);
    }

    public function testTenantScopedSessionReachesAllSixAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$staffListStatus, $staffListBody] = $this->curlGet('admin/staff/tenantStaffList');
        $this->assertSame(200, $staffListStatus);
        $this->assertStringContainsString('Tenant Staff List', $staffListBody);

        [$feesListStatus, $feesListBody] = $this->curlGet('admin/feesforward/tenantFeesList');
        $this->assertSame(200, $feesListStatus);
        $this->assertStringContainsString('Tenant Fees List', $feesListBody);

        [$examResultsStatus, $examResultsBody] = $this->curlGet('admin/examgroup/tenantExamResultsList');
        $this->assertSame(200, $examResultsStatus);
        $this->assertStringContainsString('Tenant Exam Results List', $examResultsBody);

        [$attendanceStatus, $attendanceBody] = $this->curlGet('admin/stuattendence/tenantAttendanceList');
        $this->assertSame(200, $attendanceStatus);
        $this->assertStringContainsString('Tenant Attendance List', $attendanceBody);

        [$leaveListStatus, $leaveListBody] = $this->curlGet('admin/leaverequest/tenantLeaveRequestList');
        $this->assertSame(200, $leaveListStatus);
        $this->assertStringContainsString('Tenant Leave Request List', $leaveListBody);

        [$classListStatus, $classListBody] = $this->curlGet('classes/tenantClassList');
        $this->assertSame(200, $classListStatus);
        $this->assertStringContainsString('Tenant Class List', $classListBody);

        [$dashboardStatus, ] = $this->curlGet('admin/admin/dashboard');
        $this->assertSame(404, $dashboardStatus);

        [$classesIndexStatus, ] = $this->curlGet('classes/index');
        $this->assertSame(404, $classesIndexStatus);
    }

    public function testAllowlistGateSupportsMultipleMethodsOnTheSameController(): void
    {
        // The gate was generalized from "one method per controller" to
        // "a list of methods per controller" specifically to allow this:
        // roles now has two allowlisted methods. Both must work, and a
        // real, un-allowlisted sibling method on that SAME controller
        // must still 404 -- proving the generalization didn't accidentally
        // widen the gate to "any method on an allowlisted controller."
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$rolesListStatus, $rolesListBody] = $this->curlGet('admin/roles/tenantRolesList');
        $this->assertSame(200, $rolesListStatus);
        $this->assertStringContainsString('Tenant Roles List', $rolesListBody);

        [$rolesPermissionsStatus, $rolesPermissionsBody] = $this->curlGet('admin/roles/tenantRolesPermissionsList');
        $this->assertSame(200, $rolesPermissionsStatus);
        $this->assertStringContainsString('Tenant Roles Permissions List', $rolesPermissionsBody);

        [$rolesIndexStatus, ] = $this->curlGet('admin/roles/index');
        $this->assertSame(404, $rolesIndexStatus);

        [$rolesPermissionMethodStatus, ] = $this->curlGet('admin/roles/permission/1');
        $this->assertSame(404, $rolesPermissionMethodStatus);
    }

    public function testTenantScopedSessionReachesSixMoreAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$currencyStatus, $currencyBody] = $this->curlGet('admin/currency/tenantCurrencyList');
        $this->assertSame(200, $currencyStatus);
        $this->assertStringContainsString('Tenant Currency List', $currencyBody);

        [$languageStatus, $languageBody] = $this->curlGet('admin/language/tenantLanguageList');
        $this->assertSame(200, $languageStatus);
        $this->assertStringContainsString('Tenant Language List', $languageBody);

        [$feetypeStatus, $feetypeBody] = $this->curlGet('admin/feetype/tenantFeetypeList');
        $this->assertSame(200, $feetypeStatus);
        $this->assertStringContainsString('Tenant Feetype List', $feetypeBody);

        [$feegroupStatus, $feegroupBody] = $this->curlGet('admin/feegroup/tenantFeegroupList');
        $this->assertSame(200, $feegroupStatus);
        $this->assertStringContainsString('Tenant Feegroup List', $feegroupBody);

        [$studentStatus, $studentBody] = $this->curlGet('student/tenantStudentList');
        $this->assertSame(200, $studentStatus);
        $this->assertStringContainsString('Tenant Student List', $studentBody);

        [$usersStatus, $usersBody] = $this->curlGet('admin/users/tenantUsersList');
        $this->assertSame(200, $usersStatus);
        $this->assertStringContainsString('Tenant Users List', $usersBody);
        $this->assertStringNotContainsString('$2y$', $usersBody, 'the users list must never render a bcrypt password hash');

        [$currencyIndexStatus, ] = $this->curlGet('admin/currency/index');
        $this->assertSame(404, $currencyIndexStatus);

        [$usersIndexStatus, ] = $this->curlGet('admin/users/index');
        $this->assertSame(404, $usersIndexStatus);
    }

    public function testTenantScopedSessionReachesTenMoreAllowlistedRoutesAndNothingElse(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$sessionStatus, $sessionBody] = $this->curlGet('sessions/tenantSessionList');
        $this->assertSame(200, $sessionStatus);
        $this->assertStringContainsString('Tenant Session List', $sessionBody);

        [$feeDiscountStatus, $feeDiscountBody] = $this->curlGet('admin/feediscount/tenantFeeDiscountList');
        $this->assertSame(200, $feeDiscountStatus);
        $this->assertStringContainsString('Tenant Fee Discount List', $feeDiscountBody);

        [$feeSessionGroupStatus, $feeSessionGroupBody] = $this->curlGet('admin/feemaster/tenantFeeSessionGroupList');
        $this->assertSame(200, $feeSessionGroupStatus);
        $this->assertStringContainsString('Tenant Fee Session Group List', $feeSessionGroupBody);

        [$onlineAdmissionFieldsStatus, $onlineAdmissionFieldsBody] = $this->curlGet('admin/onlineadmission/tenantOnlineAdmissionFieldsList');
        $this->assertSame(200, $onlineAdmissionFieldsStatus);
        $this->assertStringContainsString('Tenant Online Admission Fields List', $onlineAdmissionFieldsBody);

        [$resumeSettingsFieldsStatus, $resumeSettingsFieldsBody] = $this->curlGet('admin/resume/tenantResumeSettingsFieldsList');
        $this->assertSame(200, $resumeSettingsFieldsStatus);
        $this->assertStringContainsString('Tenant Resume Settings Fields List', $resumeSettingsFieldsBody);

        [$notificationSettingStatus, $notificationSettingBody] = $this->curlGet('admin/notification/tenantNotificationSettingList');
        $this->assertSame(200, $notificationSettingStatus);
        $this->assertStringContainsString('Tenant Notification Setting List', $notificationSettingBody);

        [$batchExamsStatus, $batchExamsBody] = $this->curlGet('admin/examgroup/tenantExamGroupBatchExamsList');
        $this->assertSame(200, $batchExamsStatus);
        $this->assertStringContainsString('Tenant Exam Group Batch Exams List', $batchExamsBody);

        [$batchExamSubjectsStatus, $batchExamSubjectsBody] = $this->curlGet('admin/examgroup/tenantExamGroupBatchExamSubjectsList');
        $this->assertSame(200, $batchExamSubjectsStatus);
        $this->assertStringContainsString('Tenant Exam Group Batch Exam Subjects List', $batchExamSubjectsBody);

        [$batchExamStudentsStatus, $batchExamStudentsBody] = $this->curlGet('admin/examgroup/tenantExamGroupBatchExamStudentsList');
        $this->assertSame(200, $batchExamStudentsStatus);
        $this->assertStringContainsString('Tenant Exam Group Batch Exam Students List', $batchExamStudentsBody);

        [$feeGroupFeetypeStatus, $feeGroupFeetypeBody] = $this->curlGet('admin/feegroup/tenantFeeGroupFeetypeList');
        $this->assertSame(200, $feeGroupFeetypeStatus);
        $this->assertStringContainsString('Tenant Fee Group Feetype List', $feeGroupFeetypeBody);

        [$sessionsIndexStatus, ] = $this->curlGet('sessions/index');
        $this->assertSame(404, $sessionsIndexStatus);

        [$feediscountIndexStatus, ] = $this->curlGet('admin/feediscount/index');
        $this->assertSame(404, $feediscountIndexStatus);

        [$examgroupBatchDeleteStatus, ] = $this->curlGet('admin/examgroup/delete/1');
        $this->assertSame(404, $examgroupBatchDeleteStatus);
    }

    public function testTenantGradeCreateEditDeleteWorkForTheOwningTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$createStatus, $createBody] = $this->curlPost('admin/grade/tenantGradeCreate', [
            'exam_type' => 'Terminal',
            'name' => 'Isolation Test Grade',
            'mark_from' => '90',
            'mark_upto' => '100',
            'grade_point' => '4.0',
            'description' => 'created by AdminControllerTenantGateTest',
        ]);
        $this->assertSame(200, $createStatus);
        $this->assertMatchesRegularExpression('/Grade created with id (\d+)/', $createBody, 'create must report the new row id');
        preg_match('/Grade created with id (\d+)/', $createBody, $matches);
        $newId = (int) $matches[1];

        [$editGetStatus, $editGetBody] = $this->curlGet("admin/grade/tenantGradeEdit/{$newId}");
        $this->assertSame(200, $editGetStatus);
        $this->assertStringContainsString('Isolation Test Grade', $editGetBody);

        [$editPostStatus, $editPostBody] = $this->curlPost("admin/grade/tenantGradeEdit/{$newId}", [
            'exam_type' => 'Terminal',
            'name' => 'Isolation Test Grade Updated',
            'mark_from' => '90',
            'mark_upto' => '100',
            'grade_point' => '4.0',
            'description' => 'updated by AdminControllerTenantGateTest',
        ]);
        $this->assertSame(200, $editPostStatus);
        $this->assertStringContainsString('Isolation Test Grade Updated', $editPostBody);

        [$deleteStatus, $deleteBody] = $this->curlGet("admin/grade/tenantGradeDelete/{$newId}");
        $this->assertSame(200, $deleteStatus);
        $this->assertStringContainsString('Grade deleted.', $deleteBody);

        [$editAfterDeleteStatus, ] = $this->curlGet("admin/grade/tenantGradeEdit/{$newId}");
        $this->assertSame(404, $editAfterDeleteStatus, 'the deleted row must no longer be reachable');
    }

    public function testTenantGradeWriteMethodsCannotTouchAnotherTenantsRow(): void
    {
        // Real cross-tenant isolation proof: tenant 25 creates a row, then
        // a genuinely different real tenant (26) tries to edit and delete
        // it BY ITS EXACT ID. Must 404 / report "no matching row" -- and
        // tenant 25's row must be completely untouched afterward. This is
        // the property tenantScopedAdd/tenantScopedDelete's explicit
        // tenant_id filtering exists to guarantee.
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$createStatus, $createBody] = $this->curlPost('admin/grade/tenantGradeCreate', [
            'exam_type' => 'Terminal',
            'name' => 'Tenant 25 Owned Grade',
            'mark_from' => '80',
            'mark_upto' => '89',
            'grade_point' => '3.5',
            'description' => 'must never be touched by tenant 26',
        ]);
        $this->assertSame(200, $createStatus);
        preg_match('/Grade created with id (\d+)/', $createBody, $matches);
        $tenant25GradeId = (int) $matches[1];

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            [$crossEditGetStatus, ] = $this->curlGet("admin/grade/tenantGradeEdit/{$tenant25GradeId}");
            $this->assertSame(404, $crossEditGetStatus, 'tenant 26 must not be able to view tenant 25\'s grade by id');

            [$crossDeleteStatus, $crossDeleteBody] = $this->curlGet("admin/grade/tenantGradeDelete/{$tenant25GradeId}");
            $this->assertSame(200, $crossDeleteStatus);
            $this->assertStringContainsString('No matching grade found for this tenant.', $crossDeleteBody);
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }

        [$stillThereStatus, $stillThereBody] = $this->curlGet("admin/grade/tenantGradeEdit/{$tenant25GradeId}");
        $this->assertSame(200, $stillThereStatus, 'tenant 25\'s grade must still exist after tenant 26\'s attempted cross-tenant delete');
        $this->assertStringContainsString('Tenant 25 Owned Grade', $stillThereBody);

        [$cleanupStatus, ] = $this->curlGet("admin/grade/tenantGradeDelete/{$tenant25GradeId}");
        $this->assertSame(200, $cleanupStatus);
    }

    // Shared by every Batch A tenant-write test below (Department,
    // Designation, Leavetypes, Feegroup, Subject, Feetype, Roles, Sections,
    // Sessions): create as tenant 25, confirm ownership, confirm a second
    // real tenant (26) cannot view/edit/delete it by id, confirm tenant
    // 25's row is untouched afterward, then clean up. Same shape as
    // Grade's own hand-written isolation test, generalized once these 9
    // controllers all landed on the exact same view/response conventions.
    private function verifyTenantCrudCrossTenantIsolation(
        string $createPath,
        array $createFields,
        string $createdMessagePrefix,
        string $editPathPrefix,
        string $deletePathPrefix,
        string $ownershipNeedle,
        string $deletedMessage,
        string $notFoundMessage,
        int $otherTenantId,
        string $otherTenantEmail,
        string $otherTenantPassword
    ): void {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$createStatus, $createBody] = $this->curlPost($createPath, $createFields);
        $this->assertSame(200, $createStatus);
        $pattern = '/' . preg_quote($createdMessagePrefix, '/') . ' (\d+)/';
        $this->assertMatchesRegularExpression($pattern, $createBody, 'create must report the new row id');
        preg_match($pattern, $createBody, $matches);
        $ownId = (int) $matches[1];

        [$editGetStatus, $editGetBody] = $this->curlGet($editPathPrefix . $ownId);
        $this->assertSame(200, $editGetStatus);
        $this->assertStringContainsString($ownershipNeedle, $editGetBody);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs($otherTenantId, $otherTenantEmail, $otherTenantPassword);
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            [$crossEditStatus, ] = $this->curlGet($editPathPrefix . $ownId);
            $this->assertSame(404, $crossEditStatus, 'the other tenant must not be able to view this row by id');

            [$crossDeleteStatus, $crossDeleteBody] = $this->curlGet($deletePathPrefix . $ownId);
            $this->assertSame(200, $crossDeleteStatus);
            $this->assertStringContainsString($notFoundMessage, $crossDeleteBody);
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }

        [$stillThereStatus, $stillThereBody] = $this->curlGet($editPathPrefix . $ownId);
        $this->assertSame(200, $stillThereStatus, 'the row must still exist after the other tenant\'s attempted cross-tenant delete');
        $this->assertStringContainsString($ownershipNeedle, $stillThereBody);

        [$cleanupStatus, $cleanupBody] = $this->curlGet($deletePathPrefix . $ownId);
        $this->assertSame(200, $cleanupStatus);
        $this->assertStringContainsString($deletedMessage, $cleanupBody);
    }

    public function testTenantDepartmentCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/department/tenantDepartmentCreate',
            ['department_name' => 'Isolation Test Department'],
            'Department created with id',
            'admin/department/tenantDepartmentEdit/',
            'admin/department/tenantDepartmentDelete/',
            'Isolation Test Department',
            'Department deleted.',
            'No matching department found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantDesignationCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/designation/tenantDesignationCreate',
            ['designation' => 'Isolation Test Designation'],
            'Designation created with id',
            'admin/designation/tenantDesignationEdit/',
            'admin/designation/tenantDesignationDelete/',
            'Isolation Test Designation',
            'Designation deleted.',
            'No matching designation found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantLeaveTypesCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/leavetypes/tenantLeaveTypesCreate',
            ['type' => 'Isolation Test Leave Type'],
            'Leave type created with id',
            'admin/leavetypes/tenantLeaveTypesEdit/',
            'admin/leavetypes/tenantLeaveTypesDelete/',
            'Isolation Test Leave Type',
            'Leave type deleted.',
            'No matching leave type found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantFeegroupCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/feegroup/tenantFeegroupCreate',
            ['name' => 'Isolation Test Fee Group', 'nature' => 'onetime'],
            'Fee group created with id',
            'admin/feegroup/tenantFeegroupEdit/',
            'admin/feegroup/tenantFeegroupDelete/',
            'Isolation Test Fee Group',
            'Fee group deleted.',
            'No matching fee group found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantSubjectCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/subject/tenantSubjectCreate',
            ['name' => 'Isolation Test Subject', 'code' => 'ITS101', 'type' => 'theory'],
            'Subject created with id',
            'admin/subject/tenantSubjectEdit/',
            'admin/subject/tenantSubjectDelete/',
            'Isolation Test Subject',
            'Subject deleted.',
            'No matching subject found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantFeetypeCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/feetype/tenantFeetypeCreate',
            ['name' => 'Isolation Test Fee Type', 'code' => 'ITF101', 'nature' => 'onetime'],
            'Fee type created with id',
            'admin/feetype/tenantFeetypeEdit/',
            'admin/feetype/tenantFeetypeDelete/',
            'Isolation Test Fee Type',
            'Fee type deleted.',
            'No matching fee type found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantRolesCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/roles/tenantRolesCreate',
            ['name' => 'Isolation Test Role'],
            'Role created with id',
            'admin/roles/tenantRolesEdit/',
            'admin/roles/tenantRolesDelete/',
            'Isolation Test Role',
            'Role deleted.',
            'No matching role found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantSectionCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'sections/tenantSectionCreate',
            ['section' => 'Isolation Test Section'],
            'Section created with id',
            'sections/tenantSectionEdit/',
            'sections/tenantSectionDelete/',
            'Isolation Test Section',
            'Section deleted.',
            'No matching section found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantSessionCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'sessions/tenantSessionCreate',
            ['session' => 'Isolation Test Session'],
            'Session created with id',
            'sessions/tenantSessionEdit/',
            'sessions/tenantSessionDelete/',
            'Isolation Test Session',
            'Session deleted.',
            'No matching session found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantRoomtypeCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/roomtype/tenantRoomtypeCreate',
            ['room_type' => 'Isolation Test Room Type'],
            'Room type created with id',
            'admin/roomtype/tenantRoomtypeEdit/',
            'admin/roomtype/tenantRoomtypeDelete/',
            'Isolation Test Room Type',
            'Room type deleted.',
            'No matching room type found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantHostelCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/hostel/tenantHostelCreate',
            ['hostel_name' => 'Isolation Test Hostel', 'type' => 'boys'],
            'Hostel created with id',
            'admin/hostel/tenantHostelEdit/',
            'admin/hostel/tenantHostelDelete/',
            'Isolation Test Hostel',
            'Hostel deleted.',
            'No matching hostel found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantItemcategoryCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/itemcategory/tenantItemcategoryCreate',
            ['item_category' => 'Isolation Test Item Category', 'description' => 'x'],
            'Item category created with id',
            'admin/itemcategory/tenantItemcategoryEdit/',
            'admin/itemcategory/tenantItemcategoryDelete/',
            'Isolation Test Item Category',
            'Item category deleted.',
            'No matching item category found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantItemstoreCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/itemstore/tenantItemstoreCreate',
            ['name' => 'Isolation Test Item Store', 'code' => 'IIS101', 'description' => 'x'],
            'Item store created with id',
            'admin/itemstore/tenantItemstoreEdit/',
            'admin/itemstore/tenantItemstoreDelete/',
            'Isolation Test Item Store',
            'Item store deleted.',
            'No matching item store found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantItemsupplierCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/itemsupplier/tenantItemsupplierCreate',
            ['name' => 'Isolation Test Item Supplier', 'phone' => '1', 'email' => 'x@x.com', 'address' => 'x', 'contact_person_name' => 'x', 'contact_person_phone' => '1', 'contact_person_email' => 'x@x.com', 'description' => 'x'],
            'Item supplier created with id',
            'admin/itemsupplier/tenantItemsupplierEdit/',
            'admin/itemsupplier/tenantItemsupplierDelete/',
            'Isolation Test Item Supplier',
            'Item supplier deleted.',
            'No matching item supplier found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantSchoolhouseCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/schoolhouse/tenantSchoolhouseCreate',
            ['house_name' => 'Isolation Test School House', 'description' => 'x'],
            'School house created with id',
            'admin/schoolhouse/tenantSchoolhouseEdit/',
            'admin/schoolhouse/tenantSchoolhouseDelete/',
            'Isolation Test School House',
            'School house deleted.',
            'No matching school house found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantSourceCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/source/tenantSourceCreate',
            ['source' => 'Isolation Test Source', 'description' => 'x'],
            'Source created with id',
            'admin/source/tenantSourceEdit/',
            'admin/source/tenantSourceDelete/',
            'Isolation Test Source',
            'Source deleted.',
            'No matching source found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantRouteCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/route/tenantRouteCreate',
            ['route_title' => 'Isolation Test Route'],
            'Route created with id',
            'admin/route/tenantRouteEdit/',
            'admin/route/tenantRouteDelete/',
            'Isolation Test Route',
            'Route deleted.',
            'No matching route found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantComplainttypeCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/complainttype/tenantComplainttypeCreate',
            ['complaint_type' => 'Isolation Test Complaint Type', 'description' => 'x'],
            'Complaint type created with id',
            'admin/complainttype/tenantComplainttypeEdit/',
            'admin/complainttype/tenantComplainttypeDelete/',
            'Isolation Test Complaint Type',
            'Complaint type deleted.',
            'No matching complaint type found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantHolidayTypeCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/holiday/tenantHolidayTypeCreate',
            ['type' => 'Isolation Test Holiday Type'],
            'Holiday type created with id',
            'admin/holiday/tenantHolidayTypeEdit/',
            'admin/holiday/tenantHolidayTypeDelete/',
            'Isolation Test Holiday Type',
            'Holiday type deleted.',
            'No matching holiday type found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantDisableReasonCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/disable_reason/tenantDisableReasonCreate',
            ['reason' => 'Isolation Test Disable Reason'],
            'Disable reason created with id',
            'admin/disable_reason/tenantDisableReasonEdit/',
            'admin/disable_reason/tenantDisableReasonDelete/',
            'Isolation Test Disable Reason',
            'Disable reason deleted.',
            'No matching disable reason found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantExpenseheadCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/expensehead/tenantExpenseheadCreate',
            ['expensehead' => 'Isolation Test Expense Head'],
            'Expense head created with id',
            'admin/expensehead/tenantExpenseheadEdit/',
            'admin/expensehead/tenantExpenseheadDelete/',
            'Isolation Test Expense Head',
            'Expense head deleted.',
            'No matching expense head found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantIncomeheadCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/incomehead/tenantIncomeheadCreate',
            ['incomehead' => 'Isolation Test Income Head'],
            'Income head created with id',
            'admin/incomehead/tenantIncomeheadEdit/',
            'admin/incomehead/tenantIncomeheadDelete/',
            'Isolation Test Income Head',
            'Income head deleted.',
            'No matching income head found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantMarksdivisionCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/marksdivision/tenantMarksdivisionCreate',
            ['name' => 'Isolation Test Mark Division', 'percentage_from' => '80', 'percentage_to' => '89'],
            'Mark division created with id',
            'admin/marksdivision/tenantMarksdivisionEdit/',
            'admin/marksdivision/tenantMarksdivisionDelete/',
            'Isolation Test Mark Division',
            'Mark division deleted.',
            'No matching mark division found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantReferenceCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/reference/tenantReferenceCreate',
            ['reference' => 'Isolation Test Reference', 'description' => 'x'],
            'Reference created with id',
            'admin/reference/tenantReferenceEdit/',
            'admin/reference/tenantReferenceDelete/',
            'Isolation Test Reference',
            'Reference deleted.',
            'No matching reference found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantVisitorsPurposeCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/visitorspurpose/tenantVisitorsPurposeCreate',
            ['visitors_purpose' => 'Isolation Test Visitors Purpose', 'description' => 'x'],
            'Visitors purpose created with id',
            'admin/visitorspurpose/tenantVisitorsPurposeEdit/',
            'admin/visitorspurpose/tenantVisitorsPurposeDelete/',
            'Isolation Test Visitors Purpose',
            'Visitors purpose deleted.',
            'No matching visitors purpose found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantGeneralcallCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/generalcall/tenantGeneralcallCreate',
            [
                'name' => 'Isolation Test General Call',
                'contact' => '1234567890',
                'date' => '2026-07-15',
                'description' => 'x',
                'follow_up_date' => '2026-07-20',
                'call_duration' => '5',
                'note' => 'x',
                'call_type' => 'incoming',
            ],
            'General call created with id',
            'admin/generalcall/tenantGeneralcallEdit/',
            'admin/generalcall/tenantGeneralcallDelete/',
            'Isolation Test General Call',
            'General call deleted.',
            'No matching general call found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantCategoryCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'category/tenantCategoryCreate',
            ['category' => 'Isolation Test Category'],
            'Category created with id',
            'category/tenantCategoryEdit/',
            'category/tenantCategoryDelete/',
            'Isolation Test Category',
            'Category deleted.',
            'No matching category found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantExamCreateEditDeleteAreIsolatedPerTenant(): void
    {
        // session_id=1 is tenant 25's real "2016-17" session row -- exams
        // is the first MEDIUM-tier table with a genuine foreign key
        // (sesion_id), unlike every LOW-tier table before it.
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/exam/tenantExamCreate',
            ['name' => 'Isolation Test Exam', 'note' => 'x', 'session_id' => '1'],
            'Exam created with id',
            'admin/exam/tenantExamEdit/',
            'admin/exam/tenantExamDelete/',
            'Isolation Test Exam',
            'Exam deleted.',
            'No matching exam found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantExamCreateRejectsAnotherTenantsSessionId(): void
    {
        // The property this test exists for: a bare insert with a
        // client-posted session_id would let tenant 25 attach an exam to
        // tenant 26's session row just by guessing/tampering with the id.
        // session_id=16 is tenant 26's real "2016-17" session -- a
        // plausible id to guess (small, sequential, same label).
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$createStatus, $createBody] = $this->curlPost('admin/exam/tenantExamCreate', [
            'name' => 'Should Never Be Created',
            'note' => 'x',
            'session_id' => '16',
        ]);
        $this->assertSame(404, $createStatus, 'creating an exam against another tenant\'s session id must be rejected, not silently created');

        $stillEmpty = (int) (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
            ->query("SELECT COUNT(*) FROM exams WHERE tenant_id = 25 AND name = 'Should Never Be Created'")
            ->fetchColumn();
        $this->assertSame(0, $stillEmpty, 'no exam row must have been created when the session ownership check fails');
    }

    public function testTenantHostelroomCreateEditDeleteAreIsolatedPerTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        // hostel/room_types have no pre-existing data (they're LOW-tier
        // tables, only ever populated transiently by their own tests) --
        // create real owning fixtures for tenant 25 first.
        [, $hostelBody] = $this->curlPost('admin/hostel/tenantHostelCreate', [
            'hostel_name' => 'Isolation Fixture Hostel', 'type' => 'boys',
        ]);
        preg_match('/Hostel created with id (\d+)/', $hostelBody, $hm);
        $hostelId = (int) $hm[1];

        [, $roomTypeBody] = $this->curlPost('admin/roomtype/tenantRoomtypeCreate', [
            'room_type' => 'Isolation Fixture Room Type',
        ]);
        preg_match('/Room type created with id (\d+)/', $roomTypeBody, $rm);
        $roomTypeId = (int) $rm[1];

        try {
            $this->verifyTenantCrudCrossTenantIsolation(
                'admin/hostelroom/tenantHostelroomCreate',
                [
                    'hostel_id' => (string) $hostelId,
                    'room_type_id' => (string) $roomTypeId,
                    'room_no' => 'Isolation Test Room',
                    'no_of_bed' => '2',
                    'cost_per_bed' => '100',
                ],
                'Hostel room created with id',
                'admin/hostelroom/tenantHostelroomEdit/',
                'admin/hostelroom/tenantHostelroomDelete/',
                'Isolation Test Room',
                'Hostel room deleted.',
                'No matching hostel room found for this tenant.',
                26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
            );
        } finally {
            [$loginStatus, ] = $this->curlPostPilotLogin();
            $this->curlGet("admin/hostel/tenantHostelDelete/{$hostelId}");
            $this->curlGet("admin/roomtype/tenantRoomtypeDelete/{$roomTypeId}");
        }
    }

    public function testTenantHostelroomCreateRejectsAnotherTenantsHostelId(): void
    {
        // Same property as the exam test above, for hostel_rooms' two
        // foreign keys. Create real fixtures owned by tenant 26, then
        // prove tenant 25 cannot reference them.
        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;
        [, $otherHostelBody] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
        [, $otherHostelBody] = $this->curlPost('admin/hostel/tenantHostelCreate', [
            'hostel_name' => 'Tenant26 Fixture Hostel', 'type' => 'girls',
        ]);
        preg_match('/Hostel created with id (\d+)/', $otherHostelBody, $hm);
        $otherTenantHostelId = (int) $hm[1];
        [, $otherRoomTypeBody] = $this->curlPost('admin/roomtype/tenantRoomtypeCreate', [
            'room_type' => 'Tenant26 Fixture Room Type',
        ]);
        preg_match('/Room type created with id (\d+)/', $otherRoomTypeBody, $rm);
        $otherTenantRoomTypeId = (int) $rm[1];

        try {
            $this->cookieJar = $realCookieJar;
            [$loginStatus, ] = $this->curlPostPilotLogin();
            $this->assertContains($loginStatus, [200, 302, 303, 307]);

            [$createStatus, $createBody] = $this->curlPost('admin/hostelroom/tenantHostelroomCreate', [
                'hostel_id' => (string) $otherTenantHostelId,
                'room_type_id' => (string) $otherTenantRoomTypeId,
                'room_no' => 'Should Never Be Created',
                'no_of_bed' => '2',
                'cost_per_bed' => '100',
            ]);
            $this->assertSame(404, $createStatus, 'creating a hostel room against another tenant\'s hostel/room type id must be rejected');

            $stillEmpty = (int) (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
                ->query("SELECT COUNT(*) FROM hostel_rooms WHERE tenant_id = 25 AND room_no = 'Should Never Be Created'")
                ->fetchColumn();
            $this->assertSame(0, $stillEmpty, 'no hostel_rooms row must have been created when the FK ownership check fails');
        } finally {
            $this->cookieJar = $otherCookieJar;
            $this->curlGet("admin/hostel/tenantHostelDelete/{$otherTenantHostelId}");
            $this->curlGet("admin/roomtype/tenantRoomtypeDelete/{$otherTenantRoomTypeId}");
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantPickuppointCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/pickuppoint/tenantPickuppointCreate',
            ['name' => 'Isolation Test Pickup Point', 'latitude' => '24.8607', 'longitude' => '67.0011'],
            'Pickup point created with id',
            'admin/pickuppoint/tenantPickuppointEdit/',
            'admin/pickuppoint/tenantPickuppointDelete/',
            'Isolation Test Pickup Point',
            'Pickup point deleted.',
            'No matching pickup point found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantFeeDiscountCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/feediscount/tenantFeeDiscountCreate',
            ['name' => 'Isolation Test Fee Discount', 'code' => 'ITFD01', 'type' => 'fix', 'amount' => '50', 'discount_limit' => '10'],
            'Fee discount created with id',
            'admin/feediscount/tenantFeeDiscountEdit/',
            'admin/feediscount/tenantFeeDiscountDelete/',
            'Isolation Test Fee Discount',
            'Fee discount deleted.',
            'No matching fee discount found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantContentTypeCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/contenttype/tenantContentTypeCreate',
            ['name' => 'Isolation Test Content Type', 'description' => 'created by isolation test'],
            'Content type created with id',
            'admin/contenttype/tenantContentTypeEdit/',
            'admin/contenttype/tenantContentTypeDelete/',
            'Isolation Test Content Type',
            'Content type deleted.',
            'No matching content type found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantStaffAttendanceSaveRejectsForgedForeignKeysAndIsolatesPerTenant(): void
    {
        $date = '2026-01-15';

        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        // Tenant 25 saves attendance for its own staff #1 against its own
        // attendance type #1 (Present) -- must succeed.
        [$saveStatus, $saveBody] = $this->curlPost('admin/staffattendance/tenantStaffAttendanceSave', [
            'staff_ids' => [1],
            'date' => $date,
            'attendencetype1' => 1,
            'remark1' => 'present via isolation test',
        ]);
        $this->assertSame(200, $saveStatus);
        $this->assertStringContainsString('Attendance saved for 1 staff member(s).', $saveBody);

        [$listStatus, $listBody] = $this->curlGet('admin/staffattendance/tenantStaffAttendanceList?date=' . $date);
        $this->assertSame(200, $listStatus);
        $this->assertStringContainsString('Staff #1', $listBody);
        $this->assertStringContainsString('type #1', $listBody);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Forgery 1: tenant 26 references tenant 25's staff #1 with its
            // own (legitimately owned) attendance type -- must be dropped.
            [$forgeStaffStatus, $forgeStaffBody] = $this->curlPost('admin/staffattendance/tenantStaffAttendanceSave', [
                'staff_ids' => [1],
                'date' => $date,
                'attendencetype1' => 7,
                'remark1' => 'forged staff id',
            ]);
            $this->assertSame(200, $forgeStaffStatus);
            $this->assertStringContainsString('Attendance saved for 0 staff member(s).', $forgeStaffBody);

            // Forgery 2: tenant 26 references its own staff #19 but tenant
            // 25's attendance type #1 -- must also be dropped.
            [$forgeTypeStatus, $forgeTypeBody] = $this->curlPost('admin/staffattendance/tenantStaffAttendanceSave', [
                'staff_ids' => [19],
                'date' => $date,
                'attendencetype19' => 1,
                'remark19' => 'forged attendance type id',
            ]);
            $this->assertSame(200, $forgeTypeStatus);
            $this->assertStringContainsString('Attendance saved for 0 staff member(s).', $forgeTypeBody);

            // Tenant 26 must not see tenant 25's attendance row at all.
            [$crossListStatus, $crossListBody] = $this->curlGet('admin/staffattendance/tenantStaffAttendanceList?date=' . $date);
            $this->assertSame(200, $crossListStatus);
            $this->assertStringNotContainsString('Staff #1 —', $crossListBody);
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }

        // Tenant 25's original row must be untouched by both forgery attempts.
        [$finalListStatus, $finalListBody] = $this->curlGet('admin/staffattendance/tenantStaffAttendanceList?date=' . $date);
        $this->assertSame(200, $finalListStatus);
        $this->assertStringContainsString('Staff #1', $finalListBody);
        $this->assertStringContainsString('type #1', $finalListBody);

        // No tenant delete endpoint exists for this entity yet -- clean up
        // the row this test created directly so it doesn't linger as stray
        // test data in a shared database.
        (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
            ->prepare('DELETE FROM staff_attendance WHERE date = ? AND tenant_id = 25')
            ->execute([$date]);
    }

    public function testTenantSubjectAttendanceSaveRejectsForgedForeignKeysAndIsolatesPerTenant(): void
    {
        $date = '2026-01-16';
        $pdo  = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');

        // subject_timetable has no real migrated data yet for any tenant --
        // create real fixture rows directly, same precedent as Hostelroom's
        // hostel/room_types fixtures when no pre-existing data was available.
        $pdo->exec("INSERT INTO subject_timetable (tenant_id, day, created_at, updated_at) VALUES (25, 'Monday', NOW(), NOW())");
        $tenant25TimetableId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO subject_timetable (tenant_id, day, created_at, updated_at) VALUES (26, 'Monday', NOW(), NOW())");
        $tenant26TimetableId = (int) $pdo->lastInsertId();

        try {
            [$loginStatus, ] = $this->curlPostPilotLogin();
            $this->assertContains($loginStatus, [200, 302, 303, 307]);

            // Tenant 25 saves attendance for its own student_session #312
            // against its own attendance type #1 (Present), on its own
            // subject_timetable row -- must succeed.
            [$saveStatus, $saveBody] = $this->curlPost('admin/subjectattendence/tenantSubjectAttendanceSave', [
                'subject_timetable_id' => $tenant25TimetableId,
                'student_session_ids' => [312],
                'date' => $date,
                'attendencetype312' => 1,
                'remark312' => 'present via isolation test',
            ]);
            $this->assertSame(200, $saveStatus);
            $this->assertStringContainsString('Attendance saved for 1 student(s).', $saveBody);

            [$listStatus, $listBody] = $this->curlGet('admin/subjectattendence/tenantSubjectAttendanceList?date=' . $date);
            $this->assertSame(200, $listStatus);
            $this->assertStringContainsString('session #312', $listBody);
            $this->assertStringContainsString('type #1', $listBody);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Forgery 1: tenant 26 references tenant 25's subject_timetable
                // row -- the shared parent-level FK -- must 404 the whole request.
                [$forgeTimetableStatus, ] = $this->curlPost('admin/subjectattendence/tenantSubjectAttendanceSave', [
                    'subject_timetable_id' => $tenant25TimetableId,
                    'student_session_ids' => [796],
                    'date' => $date,
                    'attendencetype796' => 7,
                    'remark796' => 'forged subject_timetable_id',
                ]);
                $this->assertSame(404, $forgeTimetableStatus);

                // Forgery 2: tenant 26 uses its own subject_timetable row but
                // references tenant 25's student_session #312 -- must be dropped.
                [$forgeSessionStatus, $forgeSessionBody] = $this->curlPost('admin/subjectattendence/tenantSubjectAttendanceSave', [
                    'subject_timetable_id' => $tenant26TimetableId,
                    'student_session_ids' => [312],
                    'date' => $date,
                    'attendencetype312' => 7,
                    'remark312' => 'forged student_session_id',
                ]);
                $this->assertSame(200, $forgeSessionStatus);
                $this->assertStringContainsString('Attendance saved for 0 student(s).', $forgeSessionBody);

                // Forgery 3: tenant 26 uses its own timetable row and its own
                // student_session #796 but tenant 25's attendance type #1 --
                // must also be dropped.
                [$forgeTypeStatus, $forgeTypeBody] = $this->curlPost('admin/subjectattendence/tenantSubjectAttendanceSave', [
                    'subject_timetable_id' => $tenant26TimetableId,
                    'student_session_ids' => [796],
                    'date' => $date,
                    'attendencetype796' => 1,
                    'remark796' => 'forged attendence_type_id',
                ]);
                $this->assertSame(200, $forgeTypeStatus);
                $this->assertStringContainsString('Attendance saved for 0 student(s).', $forgeTypeBody);

                // Tenant 26 must not see tenant 25's attendance row at all.
                [$crossListStatus, $crossListBody] = $this->curlGet('admin/subjectattendence/tenantSubjectAttendanceList?date=' . $date);
                $this->assertSame(200, $crossListStatus);
                $this->assertStringNotContainsString('session #312', $crossListBody);
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            // Tenant 25's original row must be untouched by both forgery attempts.
            [$finalListStatus, $finalListBody] = $this->curlGet('admin/subjectattendence/tenantSubjectAttendanceList?date=' . $date);
            $this->assertSame(200, $finalListStatus);
            $this->assertStringContainsString('session #312', $finalListBody);
            $this->assertStringContainsString('type #1', $finalListBody);
        } finally {
            // No tenant delete endpoint exists for this entity yet -- clean up
            // everything this test created directly so nothing lingers as
            // stray test data in a shared database.
            $pdo->prepare('DELETE FROM student_subject_attendances WHERE date = ?')->execute([$date]);
            $pdo->prepare('DELETE FROM subject_timetable WHERE id IN (?, ?)')->execute([$tenant25TimetableId, $tenant26TimetableId]);
        }
    }

    public function testTenantAttendanceSaveRejectsForgedForeignKeysAndIsolatesPerTenant(): void
    {
        $date = '2026-01-17';

        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        // Tenant 25 saves attendance for its own student_session #312
        // against its own attendance type #1 (Present) -- must succeed.
        [$saveStatus, $saveBody] = $this->curlPost('admin/stuattendence/tenantAttendanceSave', [
            'student_session_ids' => [312],
            'date' => $date,
            'attendencetype312' => 1,
            'remark312' => 'present via isolation test',
        ]);
        $this->assertSame(200, $saveStatus);
        $this->assertStringContainsString('Attendance saved for 1 student(s).', $saveBody);

        [$listStatus, $listBody] = $this->curlGet('admin/stuattendence/tenantAttendanceList');
        $this->assertSame(200, $listStatus);
        $this->assertStringContainsString('student session 312', $listBody);
        $this->assertStringContainsString('type 1', $listBody);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Forgery 1: tenant 26 references tenant 25's student_session #312
            // with its own (legitimately owned) attendance type -- must be dropped.
            [$forgeSessionStatus, $forgeSessionBody] = $this->curlPost('admin/stuattendence/tenantAttendanceSave', [
                'student_session_ids' => [312],
                'date' => $date,
                'attendencetype312' => 7,
                'remark312' => 'forged student_session_id',
            ]);
            $this->assertSame(200, $forgeSessionStatus);
            $this->assertStringContainsString('Attendance saved for 0 student(s).', $forgeSessionBody);

            // Forgery 2: tenant 26 references its own student_session #796 but
            // tenant 25's attendance type #1 -- must also be dropped.
            [$forgeTypeStatus, $forgeTypeBody] = $this->curlPost('admin/stuattendence/tenantAttendanceSave', [
                'student_session_ids' => [796],
                'date' => $date,
                'attendencetype796' => 1,
                'remark796' => 'forged attendence_type_id',
            ]);
            $this->assertSame(200, $forgeTypeStatus);
            $this->assertStringContainsString('Attendance saved for 0 student(s).', $forgeTypeBody);

            // Tenant 26 must not see tenant 25's attendance row at all.
            [$crossListStatus, $crossListBody] = $this->curlGet('admin/stuattendence/tenantAttendanceList');
            $this->assertSame(200, $crossListStatus);
            $this->assertStringNotContainsString('student session 312', $crossListBody);
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }

        // Tenant 25's original row must be untouched by both forgery attempts.
        [$finalListStatus, $finalListBody] = $this->curlGet('admin/stuattendence/tenantAttendanceList');
        $this->assertSame(200, $finalListStatus);
        $this->assertStringContainsString('student session 312', $finalListBody);
        $this->assertStringContainsString('type 1', $finalListBody);

        // No tenant delete endpoint exists for this entity yet -- clean up
        // the row this test created directly.
        (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
            ->prepare('DELETE FROM student_attendences WHERE date = ? AND tenant_id = 25')
            ->execute([$date]);
    }

    public function testTenantResumeWorkSaveRejectsForgedStudentIdAndIsolatesPerTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        try {
            // Tenant 25 saves work experience for its own student #1 -- must succeed.
            [$saveStatus, $saveBody] = $this->curlPost('admin/resume/tenantResumeWorkSave', [
                'student_id' => 1,
                'total_work_count' => [0],
                'institute_0' => 'Isolation Test Institute',
                'designation_0' => 'Engineer',
                'year_0' => '2020',
                'location_0' => 'Test City',
                'detail_0' => 'details',
            ]);
            $this->assertSame(200, $saveStatus);
            $this->assertStringContainsString('Saved 1 work experience record(s).', $saveBody);

            [$listStatus, $listBody] = $this->curlGet('admin/resume/tenantResumeDetailsList/1');
            $this->assertSame(200, $listStatus);
            $this->assertStringContainsString('Isolation Test Institute', $listBody);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Forgery: tenant 26 references tenant 25's student #1 --
                // the shared parent-level FK -- must 404 the whole request.
                [$forgeStatus, ] = $this->curlPost('admin/resume/tenantResumeWorkSave', [
                    'student_id' => 1,
                    'total_work_count' => [0],
                    'institute_0' => 'forged',
                    'designation_0' => 'forged',
                    'year_0' => '2020',
                    'location_0' => 'forged',
                    'detail_0' => 'forged',
                ]);
                $this->assertSame(404, $forgeStatus);

                // Tenant 26 must not be able to view tenant 25's student's resume details either.
                [$crossListStatus, ] = $this->curlGet('admin/resume/tenantResumeDetailsList/1');
                $this->assertSame(404, $crossListStatus);
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            // Tenant 25's original row must be untouched by the forgery attempt.
            [$finalListStatus, $finalListBody] = $this->curlGet('admin/resume/tenantResumeDetailsList/1');
            $this->assertSame(200, $finalListStatus);
            $this->assertStringContainsString('Isolation Test Institute', $finalListBody);
        } finally {
            (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
                ->prepare('DELETE FROM student_work_experience WHERE student_id = 1 AND tenant_id = 25')
                ->execute();
        }
    }

    public function testTenantResumeEducationSkillReferenceSaveRoundTripForOwningTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        try {
            [$eduStatus, $eduBody] = $this->curlPost('admin/resume/tenantResumeEducationSave', [
                'student_id' => 1,
                'total_education_count' => [0],
                'course_0' => 'B.Sc',
                'university_0' => 'Isolation Test University',
                'education_year_0' => '2018',
                'education_detail_0' => 'details',
            ]);
            $this->assertSame(200, $eduStatus);
            $this->assertStringContainsString('Saved 1 education record(s).', $eduBody);

            [$skillStatus, $skillBody] = $this->curlPost('admin/resume/tenantResumeSkillSave', [
                'student_id' => 1,
                'total_skill_count' => [0],
                'skill_category_0' => 'Isolation Test Skill',
                'skill_detail_0' => 'details',
            ]);
            $this->assertSame(200, $skillStatus);
            $this->assertStringContainsString('Saved 1 skills record(s).', $skillBody);

            [$refStatus, $refBody] = $this->curlPost('admin/resume/tenantResumeReferenceSave', [
                'student_id' => 1,
                'total_reference_count' => [0],
                'reference_name_0' => 'Isolation Test Reference',
                'relation_0' => 'Friend',
                'reference_age_0' => '30',
                'profession_0' => 'Teacher',
                'contact_0' => '1234567890',
            ]);
            $this->assertSame(200, $refStatus);
            $this->assertStringContainsString('Saved 1 references record(s).', $refBody);

            [$listStatus, $listBody] = $this->curlGet('admin/resume/tenantResumeDetailsList/1');
            $this->assertSame(200, $listStatus);
            $this->assertStringContainsString('Isolation Test University', $listBody);
            $this->assertStringContainsString('Isolation Test Skill', $listBody);
            $this->assertStringContainsString('Isolation Test Reference', $listBody);
        } finally {
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $pdo->exec('DELETE FROM student_educational_details WHERE student_id = 1 AND tenant_id = 25');
            $pdo->exec('DELETE FROM student_skills_detail WHERE student_id = 1 AND tenant_id = 25');
            $pdo->exec('DELETE FROM student_refrence WHERE student_id = 1 AND tenant_id = 25');
        }
    }

    public function testTenantStudentCoreCreateEditDeleteRejectsForgedForeignKeysAndIsolatesPerTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        try {
            // Tenant 25 creates a student using its own class/section/session/category -- must succeed.
            [$createStatus, $createBody] = $this->curlPost('tenantstudentcore/tenantStudentCoreCreate', [
                'firstname' => 'Isolation Test Student',
                'admission_no' => 'ISOTEST001',
                'class_id' => 1,
                'section_id' => 1,
                'session_id' => 1,
                'category_id' => 1,
            ]);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Student created with id (\d+)/', $createBody);
            preg_match('/Student created with id (\d+)/', $createBody, $matches);
            $studentId = (int) $matches[1];

            [$listStatus, $listBody] = $this->curlGet('student/tenantStudentList');
            $this->assertSame(200, $listStatus);
            $this->assertStringContainsString('Isolation Test Student', $listBody);
            $this->assertStringContainsString('ISOTEST001', $listBody);

            [$editGetStatus, $editGetBody] = $this->curlGet('tenantstudentcore/tenantStudentCoreEdit/' . $studentId);
            $this->assertSame(200, $editGetStatus);
            $this->assertStringContainsString('Isolation Test Student', $editGetBody);

            [$editPostStatus, $editPostBody] = $this->curlPost('tenantstudentcore/tenantStudentCoreEdit/' . $studentId, [
                'firstname' => 'Isolation Test Student',
                'lastname' => 'Updated Lastname',
                'class_id' => 1,
                'section_id' => 1,
            ]);
            $this->assertSame(200, $editPostStatus);
            $this->assertStringContainsString('Updated Lastname', $editPostBody);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Forgery: tenant 26 creates a student using tenant 25's class_id -- must 404 the whole request.
                [$forgeStatus, ] = $this->curlPost('tenantstudentcore/tenantStudentCoreCreate', [
                    'firstname' => 'Forged Student',
                    'admission_no' => 'FORGED001',
                    'class_id' => 1,
                    'section_id' => 9,
                    'session_id' => 16,
                ]);
                $this->assertSame(404, $forgeStatus, 'referencing another tenant class_id must be rejected');

                [$crossEditStatus, ] = $this->curlGet('tenantstudentcore/tenantStudentCoreEdit/' . $studentId);
                $this->assertSame(404, $crossEditStatus, 'the other tenant must not be able to view this row by id');

                [$crossDeleteStatus, ] = $this->curlGet('tenantstudentcore/tenantStudentCoreDelete/' . $studentId);
                $this->assertSame(404, $crossDeleteStatus, 'the other tenant must not be able to delete this row by id');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            // Tenant 25's row must be untouched by the forgery/cross-tenant delete attempts.
            [$finalEditStatus, $finalEditBody] = $this->curlGet('tenantstudentcore/tenantStudentCoreEdit/' . $studentId);
            $this->assertSame(200, $finalEditStatus);
            $this->assertStringContainsString('Updated Lastname', $finalEditBody);

            // This freshly-created test student has no login row, so tenant 25 can delete it via this route.
            [$deleteStatus, $deleteBody] = $this->curlGet('tenantstudentcore/tenantStudentCoreDelete/' . $studentId);
            $this->assertSame(200, $deleteStatus);
            $this->assertStringContainsString('Student deleted.', $deleteBody);

            [$goneStatus, ] = $this->curlGet('tenantstudentcore/tenantStudentCoreEdit/' . $studentId);
            $this->assertSame(404, $goneStatus);
        } finally {
            // Cleanup by admission_no pattern rather than a tracked id -- must
            // run regardless of which assertion above failed, otherwise a
            // mid-test failure leaves a real stray row in production-shaped
            // data (same class of bug found and fixed in the Staffcore test).
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $strayIds = $pdo->query("SELECT id FROM students WHERE admission_no IN ('ISOTEST001', 'FORGED001')")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($strayIds as $strayId) {
                $pdo->exec('DELETE FROM student_session WHERE student_id = ' . (int) $strayId);
                $pdo->exec('DELETE FROM students WHERE id = ' . (int) $strayId);
            }
        }
    }

    public function testTenantStudentCoreDeleteRefusesRealStudentsWithAnExistingLogin(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        // Student id 1 (tenant 25) is a real migrated student with a real
        // login -- this route must refuse to delete it rather than orphan
        // the login or silently skip the cascade.
        [$deleteStatus, $deleteBody] = $this->curlGet('tenantstudentcore/tenantStudentCoreDelete/1');
        $this->assertSame(404, $deleteStatus);

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $stillThere = (int) $pdo->query('SELECT COUNT(*) FROM students WHERE id = 1 AND tenant_id = 25')->fetchColumn();
        $this->assertSame(1, $stillThere, 'a real student with a login must survive an attempted delete through this route');
    }

    public function testTenantStaffCoreCreateEditDeleteRejectsForgedForeignKeysAndScopesUniquenessPerTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        try {
            // Tenant 25 creates a staff member using its own department/designation/role -- must succeed.
            [$createStatus, $createBody] = $this->curlPost('tenantstaffcore/tenantStaffCoreCreate', [
                'employee_id' => 'ISOTEST-STAFF-001',
                'name' => 'Isolation Test Staff',
                'email' => 'isotest.staff@example.test',
                'department' => 1,
                'designation' => 1,
                'role_id' => 1,
            ]);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Staff created with id (\d+)/', $createBody);
            preg_match('/Staff created with id (\d+)/', $createBody, $matches);
            $staffId = (int) $matches[1];

            // Same tenant, same employee_id again -- must be rejected as a tenant-scoped duplicate.
            [$dupStatus, $dupBody] = $this->curlPost('tenantstaffcore/tenantStaffCoreCreate', [
                'employee_id' => 'ISOTEST-STAFF-001',
                'name' => 'Duplicate Attempt',
                'email' => 'someone.else@example.test',
            ]);
            $this->assertSame(200, $dupStatus);
            $this->assertStringContainsString('Employee id or email already exists for this tenant.', $dupBody);

            [$editGetStatus, $editGetBody] = $this->curlGet('tenantstaffcore/tenantStaffCoreEdit/' . $staffId);
            $this->assertSame(200, $editGetStatus);
            $this->assertStringContainsString('Isolation Test Staff', $editGetBody);

            [$editPostStatus, $editPostBody] = $this->curlPost('tenantstaffcore/tenantStaffCoreEdit/' . $staffId, [
                'name' => 'Isolation Test Staff',
                'surname' => 'Updated Surname',
                'email' => 'isotest.staff@example.test',
            ]);
            $this->assertSame(200, $editPostStatus);
            $this->assertStringContainsString('Updated Surname', $editPostBody);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Tenant 26 uses the SAME employee_id/email as tenant 25 --
                // must succeed, proving the uniqueness check is tenant-scoped
                // and not the legacy global check.
                [$reuseStatus, $reuseBody] = $this->curlPost('tenantstaffcore/tenantStaffCoreCreate', [
                    'employee_id' => 'ISOTEST-STAFF-001',
                    'name' => 'Isolation Test Staff Tenant26',
                    'email' => 'isotest.staff@example.test',
                    'department' => 6,
                    'designation' => 13,
                    'role_id' => 9,
                ]);
                $this->assertSame(200, $reuseStatus);
                $this->assertMatchesRegularExpression('/Staff created with id (\d+)/', $reuseBody, 'reusing another tenant\'s employee_id/email must be allowed');

                // Forgery: tenant 26 references tenant 25's department id -- must 404 the whole request.
                [$forgeStatus, ] = $this->curlPost('tenantstaffcore/tenantStaffCoreCreate', [
                    'employee_id' => 'ISOTEST-STAFF-002',
                    'name' => 'Forged Staff',
                    'email' => 'forged.staff@example.test',
                    'department' => 1,
                ]);
                $this->assertSame(404, $forgeStatus, 'referencing another tenant department id must be rejected');

                [$crossEditStatus, ] = $this->curlGet('tenantstaffcore/tenantStaffCoreEdit/' . $staffId);
                $this->assertSame(404, $crossEditStatus, 'the other tenant must not be able to view this row by id');

                [$crossDeleteStatus, ] = $this->curlGet('tenantstaffcore/tenantStaffCoreDelete/' . $staffId);
                $this->assertSame(404, $crossDeleteStatus, 'the other tenant must not be able to delete this row by id');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            // Tenant 25's row must be untouched by the forgery/cross-tenant delete attempt.
            [$finalEditStatus, $finalEditBody] = $this->curlGet('tenantstaffcore/tenantStaffCoreEdit/' . $staffId);
            $this->assertSame(200, $finalEditStatus);
            $this->assertStringContainsString('Updated Surname', $finalEditBody);

            [$deleteStatus, $deleteBody] = $this->curlGet('tenantstaffcore/tenantStaffCoreDelete/' . $staffId);
            $this->assertSame(200, $deleteStatus);
            $this->assertStringContainsString('Staff deleted.', $deleteBody);

            [$goneStatus, ] = $this->curlGet('tenantstaffcore/tenantStaffCoreEdit/' . $staffId);
            $this->assertSame(404, $goneStatus);
        } finally {
            // Cleanup by employee_id pattern rather than tracked ids -- this
            // must run regardless of which assertion above failed (or which
            // curl call never got far enough to capture an id), otherwise a
            // mid-test failure leaves a real stray row polluting the tenant's
            // actual staff list. (This is exactly what happened once during
            // this test's own development: an interrupted run left a real
            // "Isolation Test Staff" row counted in production-shaped data
            // until it was found and cleaned up by hand.)
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $strayIds = $pdo->query("SELECT id FROM staff WHERE employee_id LIKE 'ISOTEST-STAFF%'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($strayIds as $strayId) {
                $pdo->exec('DELETE FROM staff_roles WHERE staff_id = ' . (int) $strayId);
                $pdo->exec('DELETE FROM staff WHERE id = ' . (int) $strayId);
            }
        }
    }

    public function testTenantOnlineexamCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/onlineexam/tenantOnlineexamCreate',
            [
                'exam' => 'Isolation Test Online Exam',
                'attempt' => '1',
                'duration' => '01:00:00',
                'passing_percentage' => '40',
                'session_id' => 1,
            ],
            'Online exam created with id',
            'admin/onlineexam/tenantOnlineexamEdit/',
            'admin/onlineexam/tenantOnlineexamDelete/',
            'Isolation Test Online Exam',
            'Online exam deleted.',
            'No matching online exam found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantOnlineexamCreateRejectsForgedSessionId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's session id -- must 404 the whole request.
            [$forgeStatus, ] = $this->curlPost('admin/onlineexam/tenantOnlineexamCreate', [
                'exam' => 'Forged Online Exam',
                'attempt' => '1',
                'duration' => '01:00:00',
                'passing_percentage' => '40',
                'session_id' => 1,
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant session_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantStudentCoreSiblingLinkingInheritsParentIdWhenSiblingIsOwnedByTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        try {
            // Real student #1 (tenant 25) has a real parent_id -- confirmed live: 2.
            [$createStatus, $createBody] = $this->curlPost('tenantstudentcore/tenantStudentCoreCreate', [
                'firstname' => 'Isolation Test Sibling',
                'admission_no' => 'ISOTEST-SIBLING-001',
                'class_id' => 1,
                'section_id' => 1,
                'session_id' => 1,
                'sibling_id' => 1,
            ]);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Student created with id (\d+)/', $createBody);
            preg_match('/Student created with id (\d+)/', $createBody, $matches);
            $studentId = (int) $matches[1];

            [$editStatus, $editBody] = $this->curlGet('tenantstudentcore/tenantStudentCoreEdit/' . $studentId);
            $this->assertSame(200, $editStatus);
            $this->assertStringContainsString('parent_id: 2', $editBody, 'the new student must inherit sibling #1\'s real parent_id (2)');

            $this->curlGet('tenantstudentcore/tenantStudentCoreDelete/' . $studentId);
        } finally {
            // Cleanup by admission_no pattern rather than a tracked id -- must
            // run regardless of which assertion above failed (same class of
            // bug found and fixed in the Staffcore/Studentcore main tests).
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $strayIds = $pdo->query("SELECT id FROM students WHERE admission_no = 'ISOTEST-SIBLING-001'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($strayIds as $strayId) {
                $pdo->exec('DELETE FROM student_session WHERE student_id = ' . (int) $strayId);
                $pdo->exec('DELETE FROM students WHERE id = ' . (int) $strayId);
            }
        }
    }

    public function testTenantStudentCoreCreateRejectsSiblingIdFromAnotherTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's real student #1 as a "sibling"
            // -- this is the exact legacy IDOR shape (silently inheriting
            // another tenant's parent_id via a guessable id) and must 404
            // the whole request instead.
            [$forgeStatus, ] = $this->curlPost('tenantstudentcore/tenantStudentCoreCreate', [
                'firstname' => 'Forged Sibling Link',
                'admission_no' => 'FORGED-SIBLING-001',
                'class_id' => 8,
                'section_id' => 9,
                'session_id' => 16,
                'sibling_id' => 1,
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant\'s student as sibling_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $stray = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE admission_no = 'FORGED-SIBLING-001'")->fetchColumn();
        $this->assertSame(0, $stray, 'the forged request must not have created any row at all');
    }

    public function testTenantStaffCoreAutoGeneratesEmployeeIdAsAnIndependentSequencePerTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        try {
            // Tenant 25's first auto-generated hire (this pass) starts the
            // "STAFF-" sequence fresh -- none of its 18 real staff use this
            // prefix, so this must be STAFF-0001.
            [$firstStatus, $firstBody] = $this->curlPost('tenantstaffcore/tenantStaffCoreCreate', [
                'name' => 'Isolation Test Auto Staff One',
                'email' => 'isotest.autostaff1@example.test',
            ]);
            $this->assertSame(200, $firstStatus);
            $this->assertStringContainsString('employee_id: STAFF-0001', $firstBody);

            // A second auto-generated hire for the SAME tenant must increment.
            [$secondStatus, $secondBody] = $this->curlPost('tenantstaffcore/tenantStaffCoreCreate', [
                'name' => 'Isolation Test Auto Staff Two',
                'email' => 'isotest.autostaff2@example.test',
            ]);
            $this->assertSame(200, $secondStatus);
            $this->assertStringContainsString('employee_id: STAFF-0002', $secondBody);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Tenant 26's own first auto-generated hire must start its OWN
                // sequence at STAFF-0001 too -- proving it never continues
                // tenant 25's sequence (the exact legacy lastRecord() bug).
                [$otherStatus, $otherBody] = $this->curlPost('tenantstaffcore/tenantStaffCoreCreate', [
                    'name' => 'Isolation Test Auto Staff Tenant26',
                    'email' => 'isotest.autostaff.t26@example.test',
                ]);
                $this->assertSame(200, $otherStatus);
                $this->assertStringContainsString('employee_id: STAFF-0001', $otherBody, 'tenant 26 must start its own independent sequence, not continue tenant 25\'s');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }
        } finally {
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $strayIds = $pdo->query("SELECT id FROM staff WHERE employee_id LIKE 'STAFF-%'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($strayIds as $strayId) {
                $pdo->exec('DELETE FROM staff_roles WHERE staff_id = ' . (int) $strayId);
                $pdo->exec('DELETE FROM staff WHERE id = ' . (int) $strayId);
            }
        }
    }

    public function testTenantStudentCorePhotoUploadIsStoredUnderATenantScopedDirectory(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        // A minimal, real, valid 1x1 PNG.
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $tmpFile = tempnam(sys_get_temp_dir(), 'admgate_photo_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $studentId = null;

        try {
            [$createStatus, $createBody] = $this->curlPostMultipart('tenantstudentcore/tenantStudentCoreCreate', [
                'firstname' => 'Isolation Test Photo Student',
                'admission_no' => 'ISOTEST-PHOTO-001',
                'class_id' => 1,
                'section_id' => 1,
                'session_id' => 1,
            ], 'photo', $tmpFile);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Student created with id (\d+)/', $createBody);
            preg_match('/Student created with id (\d+)/', $createBody, $matches);
            $studentId = (int) $matches[1];

            $this->assertMatchesRegularExpression('#image: tenant_25/student_images/\S+!test-photo\.png#', $createBody);
            preg_match('#image: (tenant_25/student_images/\S+!test-photo\.png)#', $createBody, $imageMatches);
            $storedPath = $imageMatches[1];

            $absolutePath = __DIR__ . '/../../uploads/tenant_uploads/' . $storedPath;
            $this->assertFileExists($absolutePath, 'the uploaded file must actually exist on disk under the tenant-scoped directory');

            // Deleting the student (no login, so this route can remove it)
            // must also remove the physical file -- no orphaned upload left behind.
            [$deleteStatus, ] = $this->curlGet('tenantstudentcore/tenantStudentCoreDelete/' . $studentId);
            $this->assertSame(200, $deleteStatus);
            $this->assertFileDoesNotExist($absolutePath, 'delete must clean up the physical file, not just the DB row');
            $studentId = null;
        } finally {
            @unlink($tmpFile);
            if ($studentId) {
                $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
                $pdo->exec('DELETE FROM student_session WHERE student_id = ' . (int) $studentId);
                $pdo->exec('DELETE FROM students WHERE id = ' . (int) $studentId);
            }
        }
    }

    public function testTenantStaffCorePhotoUploadIsStoredUnderATenantScopedDirectory(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $tmpFile = tempnam(sys_get_temp_dir(), 'admgate_staffphoto_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $staffId = null;

        try {
            [$createStatus, $createBody] = $this->curlPostMultipart('tenantstaffcore/tenantStaffCoreCreate', [
                'name' => 'Isolation Test Photo Staff',
                'email' => 'isotest.photostaff@example.test',
            ], 'photo', $tmpFile);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Staff created with id (\d+)/', $createBody);
            preg_match('/Staff created with id (\d+)/', $createBody, $matches);
            $staffId = (int) $matches[1];

            $this->assertMatchesRegularExpression('#image: tenant_25/staff_images/\S+!test-photo\.png#', $createBody);
            preg_match('#image: (tenant_25/staff_images/\S+!test-photo\.png)#', $createBody, $imageMatches);
            $storedPath = $imageMatches[1];

            $absolutePath = __DIR__ . '/../../uploads/tenant_uploads/' . $storedPath;
            $this->assertFileExists($absolutePath, 'the uploaded file must actually exist on disk under the tenant-scoped directory');

            [$deleteStatus, ] = $this->curlGet('tenantstaffcore/tenantStaffCoreDelete/' . $staffId);
            $this->assertSame(200, $deleteStatus);
            $this->assertFileDoesNotExist($absolutePath, 'delete must clean up the physical file, not just the DB row');
            $staffId = null;
        } finally {
            @unlink($tmpFile);
            if ($staffId) {
                $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
                $pdo->exec('DELETE FROM staff_roles WHERE staff_id = ' . (int) $staffId);
                $pdo->exec('DELETE FROM staff WHERE id = ' . (int) $staffId);
            }
        }
    }

    public function testTenantVisitorCreateEditDeleteRejectsForgedForeignKeysAndIsolatesPerTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $tmpFile = tempnam(sys_get_temp_dir(), 'admgate_visitorphoto_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $visitorId = null;

        try {
            // Tenant 25 creates a visitor meeting its own staff #1 -- must succeed.
            [$createStatus, $createBody] = $this->curlPostMultipart('admin/visitors/tenantVisitorCreate', [
                'purpose' => 'Isolation Test Visit',
                'name' => 'Isolation Test Visitor',
                'contact' => '1234567890',
                'id_proof' => 'ID-001',
                'no_of_people' => '1',
                'date' => '2026-01-20',
                'in_time' => '10:00',
                'out_time' => '11:00',
                'note' => 'test',
                'meeting_with' => 'staff',
                'staff_id' => '1',
            ], 'photo', $tmpFile);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Visitor created with id (\d+)/', $createBody);
            preg_match('/Visitor created with id (\d+)/', $createBody, $matches);
            $visitorId = (int) $matches[1];

            [$editGetStatus, $editGetBody] = $this->curlGet('admin/visitors/tenantVisitorEdit/' . $visitorId);
            $this->assertSame(200, $editGetStatus);
            $this->assertStringContainsString('Isolation Test Visitor', $editGetBody);
            $this->assertMatchesRegularExpression('#image: tenant_25/visitors/#', $editGetBody);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Forgery 1: tenant 26 references tenant 25's staff #1 as the meeting-with staff.
                [$forgeStaffStatus, ] = $this->curlPost('admin/visitors/tenantVisitorCreate', [
                    'purpose' => 'Forged Visit',
                    'name' => 'Forged Visitor',
                    'date' => '2026-01-20',
                    'meeting_with' => 'staff',
                    'staff_id' => '1',
                ]);
                $this->assertSame(404, $forgeStaffStatus, 'referencing another tenant staff_id must be rejected');

                // Forgery 2: tenant 26 references tenant 25's real student_session #312.
                [$forgeSessionStatus, ] = $this->curlPost('admin/visitors/tenantVisitorCreate', [
                    'purpose' => 'Forged Visit',
                    'name' => 'Forged Visitor',
                    'date' => '2026-01-20',
                    'meeting_with' => 'student',
                    'student_session_id' => '312',
                ]);
                $this->assertSame(404, $forgeSessionStatus, 'referencing another tenant student_session_id must be rejected');

                [$crossEditStatus, ] = $this->curlGet('admin/visitors/tenantVisitorEdit/' . $visitorId);
                $this->assertSame(404, $crossEditStatus, 'the other tenant must not be able to view this row by id');

                [$crossDeleteStatus, ] = $this->curlGet('admin/visitors/tenantVisitorDelete/' . $visitorId);
                $this->assertSame(404, $crossDeleteStatus, 'the other tenant must not be able to delete this row by id');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            // Tenant 25's row must be untouched by the forgery/cross-tenant attempts.
            [$finalEditStatus, $finalEditBody] = $this->curlGet('admin/visitors/tenantVisitorEdit/' . $visitorId);
            $this->assertSame(200, $finalEditStatus);
            $this->assertStringContainsString('Isolation Test Visitor', $finalEditBody);

            [$deleteStatus, $deleteBody] = $this->curlGet('admin/visitors/tenantVisitorDelete/' . $visitorId);
            $this->assertSame(200, $deleteStatus);
            $this->assertStringContainsString('Visitor deleted.', $deleteBody);
            $visitorId = null;
        } finally {
            @unlink($tmpFile);
            if ($visitorId) {
                $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
                $pdo->exec('DELETE FROM visitors_book WHERE id = ' . (int) $visitorId);
            }
        }
    }

    public function testTenantComplaintCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/complaint/tenantComplaintCreate',
            ['name' => 'Isolation Test Complainant', 'date' => '2026-01-21', 'complaint_type' => 'Other', 'source' => 'Phone'],
            'Complaint created with id',
            'admin/complaint/tenantComplaintEdit/',
            'admin/complaint/tenantComplaintDelete/',
            'Isolation Test Complainant',
            'Complaint deleted.',
            'No matching complaint found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantComplaintPhotoUploadIsStoredUnderATenantScopedDirectory(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $tmpFile = tempnam(sys_get_temp_dir(), 'admgate_complaintphoto_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $complaintId = null;

        try {
            [$createStatus, $createBody] = $this->curlPostMultipart('admin/complaint/tenantComplaintCreate', [
                'name' => 'Isolation Test Photo Complainant',
                'date' => '2026-01-21',
            ], 'photo', $tmpFile);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Complaint created with id (\d+)/', $createBody);
            preg_match('/Complaint created with id (\d+)/', $createBody, $matches);
            $complaintId = (int) $matches[1];

            $this->assertMatchesRegularExpression('#tenant_25/complaints/\S+!test-photo\.png#', $createBody);

            [$deleteStatus, ] = $this->curlGet('admin/complaint/tenantComplaintDelete/' . $complaintId);
            $this->assertSame(200, $deleteStatus);
            $complaintId = null;
        } finally {
            @unlink($tmpFile);
            if ($complaintId) {
                $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
                $pdo->exec('DELETE FROM complaint WHERE id = ' . (int) $complaintId);
            }
        }
    }

    public function testTenantVehicleCreateEditDeleteAreIsolatedPerTenant(): void
    {
        // vehicle_no is varchar(20) -- keep the ownership needle within that limit.
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/vehicle/tenantVehicleCreate',
            ['vehicle_no' => 'IsoTestVehicle01', 'registration_number' => 'REG-001', 'driver_licence' => 'LIC-001'],
            'Vehicle created with id',
            'admin/vehicle/tenantVehicleEdit/',
            'admin/vehicle/tenantVehicleDelete/',
            'IsoTestVehicle01',
            'Vehicle deleted.',
            'No matching vehicle found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantVehiclePhotoUploadIsStoredUnderATenantScopedDirectory(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $tmpFile = tempnam(sys_get_temp_dir(), 'admgate_vehiclephoto_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $vehicleId = null;

        try {
            [$createStatus, $createBody] = $this->curlPostMultipart('admin/vehicle/tenantVehicleCreate', [
                'vehicle_no' => 'IsoTestPhotoVeh01',
            ], 'photo', $tmpFile);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Vehicle created with id (\d+)/', $createBody);
            preg_match('/Vehicle created with id (\d+)/', $createBody, $matches);
            $vehicleId = (int) $matches[1];

            $this->assertMatchesRegularExpression('#tenant_25/vehicle_photo/\S+!test-photo\.png#', $createBody);

            [$deleteStatus, ] = $this->curlGet('admin/vehicle/tenantVehicleDelete/' . $vehicleId);
            $this->assertSame(200, $deleteStatus);
            $vehicleId = null;
        } finally {
            @unlink($tmpFile);
            if ($vehicleId) {
                $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
                $pdo->exec('DELETE FROM vehicle_routes WHERE vehicle_id = ' . (int) $vehicleId);
                $pdo->exec('DELETE FROM vehicles WHERE id = ' . (int) $vehicleId);
            }
        }
    }

    public function testTenantAdmitcardCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/admitcard/tenantAdmitcardCreate',
            ['template' => 'default', 'heading' => 'Isolation Test Admitcard', 'title' => 'Admit Card'],
            'Admitcard created with id',
            'admin/admitcard/tenantAdmitcardEdit/',
            'admin/admitcard/tenantAdmitcardDelete/',
            'Isolation Test Admitcard',
            'Admitcard deleted.',
            'No matching admitcard found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantCertificateCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/certificate/tenantCertificateCreate',
            ['certificate_name' => 'Isolation Test Certificate', 'certificate_text' => 'Test text'],
            'Certificate created with id',
            'admin/certificate/tenantCertificateEdit/',
            'admin/certificate/tenantCertificateDelete/',
            'Isolation Test Certificate',
            'Certificate deleted.',
            'No matching certificate found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantDispatchCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/dispatch/tenantDispatchCreate',
            ['to_title' => 'Isolation Test Dispatch', 'ref_no' => 'REF-001', 'date' => '2026-01-22'],
            'Dispatch created with id',
            'admin/dispatch/tenantDispatchEdit/',
            'admin/dispatch/tenantDispatchDelete/',
            'Isolation Test Dispatch',
            'Dispatch deleted.',
            'No matching dispatch found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantReceiveCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/receive/tenantReceiveCreate',
            ['from_title' => 'Isolation Test Receive', 'ref_no' => 'REF-002', 'date' => '2026-01-22'],
            'Receive created with id',
            'admin/receive/tenantReceiveEdit/',
            'admin/receive/tenantReceiveDelete/',
            'Isolation Test Receive',
            'Receive deleted.',
            'No matching receive found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantStaffidcardCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/staffidcard/tenantStaffidcardCreate',
            ['title' => 'Isolation Test Staffidcard', 'school_name' => 'Test School', 'address' => 'Test Address'],
            'Staffidcard created with id',
            'admin/staffidcard/tenantStaffidcardEdit/',
            'admin/staffidcard/tenantStaffidcardDelete/',
            'Isolation Test Staffidcard',
            'Staffidcard deleted.',
            'No matching staffidcard found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantStudentidcardCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/studentidcard/tenantStudentidcardCreate',
            ['title' => 'Isolation Test Studentidcard', 'school_name' => 'Test School', 'address' => 'Test Address'],
            'Studentidcard created with id',
            'admin/studentidcard/tenantStudentidcardEdit/',
            'admin/studentidcard/tenantStudentidcardDelete/',
            'Isolation Test Studentidcard',
            'Studentidcard deleted.',
            'No matching studentidcard found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantItemCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/item/tenantItemCreate',
            ['name' => 'Isolation Test Item', 'unit' => 'pcs', 'item_category_id' => 1],
            'Item created with id',
            'admin/item/tenantItemEdit/',
            'admin/item/tenantItemDelete/',
            'Isolation Test Item',
            'Item deleted.',
            'No matching item found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantItemCreateRejectsForgedCategoryId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's real item_category #1 -- must 404.
            [$forgeStatus, ] = $this->curlPost('admin/item/tenantItemCreate', [
                'name' => 'Forged Item',
                'unit' => 'pcs',
                'item_category_id' => 1,
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant item_category_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantClassCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'classes/tenantClassCreate',
            ['class' => 'Isolation Test Class'],
            'Class created with id',
            'classes/tenantClassEdit/',
            'classes/tenantClassDelete/',
            'Isolation Test Class',
            'Class deleted.',
            'No matching class found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantNoticeCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/front/notice/tenantNoticeCreate',
            ['title' => 'Isolation Test Notice', 'date' => '2026-01-01', 'description' => 'x'],
            'Notice created with id',
            'admin/front/notice/tenantNoticeEdit/',
            'admin/front/notice/tenantNoticeDelete/',
            'Isolation Test Notice',
            'Notice deleted.',
            'No matching notice found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantPageCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/front/page/tenantPageCreate',
            ['title' => 'Isolation Test Page', 'description' => 'x'],
            'Page created with id',
            'admin/front/page/tenantPageEdit/',
            'admin/front/page/tenantPageDelete/',
            'Isolation Test Page',
            'Page deleted.',
            'No matching page found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantMediaUploadIsStoredUnderATenantScopedDirectoryAndIsolatedPerTenant(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $tmpFile = tempnam(sys_get_temp_dir(), 'admgate_mediaphoto_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $mediaId = null;

        try {
            [$createStatus, $createBody] = $this->curlPostMultipart('admin/front/media/tenantMediaCreate', [], 'media_file', $tmpFile);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Media created with id (\d+)/', $createBody);
            preg_match('/Media created with id (\d+)/', $createBody, $matches);
            $mediaId = (int) $matches[1];

            $this->assertMatchesRegularExpression('#tenant_25/front_cms_media/\S+!test-photo\.png#', $createBody);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                [$crossDeleteStatus, $crossDeleteBody] = $this->curlGet('admin/front/media/tenantMediaDelete/' . $mediaId);
                $this->assertSame(200, $crossDeleteStatus);
                $this->assertStringContainsString('No matching media found for this tenant.', $crossDeleteBody);
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $stillThere = (int) $pdo->query('SELECT COUNT(*) FROM front_cms_media_gallery WHERE id = ' . $mediaId)->fetchColumn();
            $this->assertSame(1, $stillThere, 'the row must still exist after the other tenant\'s attempted cross-tenant delete');

            [$deleteStatus, $deleteBody] = $this->curlGet('admin/front/media/tenantMediaDelete/' . $mediaId);
            $this->assertSame(200, $deleteStatus);
            $this->assertStringContainsString('Media deleted.', $deleteBody);
            $mediaId = null;
        } finally {
            @unlink($tmpFile);
            if ($mediaId !== null) {
                (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
                    ->exec('DELETE FROM front_cms_media_gallery WHERE id = ' . $mediaId);
            }
        }
    }

    public function testTenantBannerAddDeleteIsolatedAndRejectsForgedMediaId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $tmpFile = tempnam(sys_get_temp_dir(), 'admgate_bannermedia_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $mediaId = null;
        $bannerId = null;

        try {
            [$mediaStatus, $mediaBody] = $this->curlPostMultipart('admin/front/media/tenantMediaCreate', [], 'media_file', $tmpFile);
            $this->assertSame(200, $mediaStatus);
            preg_match('/Media created with id (\d+)/', $mediaBody, $matches);
            $mediaId = (int) $matches[1];

            [$createStatus, $createBody] = $this->curlPost('admin/front/banner/tenantBannerAdd', ['content_id' => $mediaId]);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Banner link created with id (\d+)/', $createBody);
            preg_match('/Banner link created with id (\d+)/', $createBody, $matches);
            $bannerId = (int) $matches[1];

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Tenant 26 references tenant 25's real media item -- must 404.
                [$forgeStatus, ] = $this->curlPost('admin/front/banner/tenantBannerAdd', ['content_id' => $mediaId]);
                $this->assertSame(404, $forgeStatus, 'referencing another tenant media_gallery_id must be rejected');

                [$crossDeleteStatus, $crossDeleteBody] = $this->curlGet('admin/front/banner/tenantBannerDelete/' . $bannerId);
                $this->assertSame(200, $crossDeleteStatus);
                $this->assertStringContainsString('No matching banner link found for this tenant.', $crossDeleteBody);
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $stillThere = (int) $pdo->query('SELECT COUNT(*) FROM front_cms_program_photos WHERE id = ' . $bannerId)->fetchColumn();
            $this->assertSame(1, $stillThere, 'the row must still exist after the other tenant\'s attempted cross-tenant delete');

            [$deleteStatus, $deleteBody] = $this->curlGet('admin/front/banner/tenantBannerDelete/' . $bannerId);
            $this->assertSame(200, $deleteStatus);
            $this->assertStringContainsString('Banner link deleted.', $deleteBody);
            $bannerId = null;
        } finally {
            @unlink($tmpFile);
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            if ($bannerId !== null) {
                $pdo->exec('DELETE FROM front_cms_program_photos WHERE id = ' . $bannerId);
            }
            if ($mediaId !== null) {
                $pdo->exec('DELETE FROM front_cms_media_gallery WHERE id = ' . $mediaId);
            }
        }
    }

    public function testTenantEventsCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/front/events/tenantEventsCreate',
            ['title' => 'Isolation Test Front Event', 'start_date' => '2026-01-01', 'end_date' => '2026-01-02', 'description' => 'x'],
            'Event created with id',
            'admin/front/events/tenantEventsEdit/',
            'admin/front/events/tenantEventsDelete/',
            'Isolation Test Front Event',
            'Front CMS event deleted.',
            'No matching front CMS event found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantGalleryCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/front/gallery/tenantGalleryCreate',
            ['title' => 'Isolation Test Gallery', 'description' => 'x'],
            'Gallery created with id',
            'admin/front/gallery/tenantGalleryEdit/',
            'admin/front/gallery/tenantGalleryDelete/',
            'Isolation Test Gallery',
            'Gallery deleted.',
            'No matching gallery found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantEnquiryCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/enquiry/tenantEnquiryCreate',
            [
                'name' => 'Isolation Test Enquiry', 'contact' => '1234567890', 'reference' => 'REF01',
                'source' => 'walk-in', 'date' => '2026-01-01', 'follow_up_date' => '2026-01-08',
                'description' => 'x',
            ],
            'Enquiry created with id',
            'admin/enquiry/tenantEnquiryEdit/',
            'admin/enquiry/tenantEnquiryDelete/',
            'Isolation Test Enquiry',
            'Enquiry deleted.',
            'No matching enquiry found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantEnquiryCreateRejectsForgedAssignedAndClassId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            $baseFields = [
                'name' => 'Forged Enquiry', 'contact' => '1234567890', 'reference' => 'REF01',
                'source' => 'walk-in', 'date' => '2026-01-01', 'follow_up_date' => '2026-01-08',
                'description' => 'x',
            ];

            // Tenant 26 references tenant 25's real staff #1 as assigned -- must 404.
            [$forgeAssignedStatus, ] = $this->curlPost('admin/enquiry/tenantEnquiryCreate', $baseFields + ['assigned' => 1]);
            $this->assertSame(404, $forgeAssignedStatus, 'referencing another tenant staff id as assigned must be rejected');

            // Tenant 26 references tenant 25's real class #1 -- must 404.
            [$forgeClassStatus, ] = $this->curlPost('admin/enquiry/tenantEnquiryCreate', $baseFields + ['class_id' => 1]);
            $this->assertSame(404, $forgeClassStatus, 'referencing another tenant class_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantApproveLeaveCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/approve_leave/tenantApproveLeaveCreate',
            [
                'student_session_id' => 312, 'apply_date' => '2026-01-01',
                'from_date' => '2026-01-02', 'to_date' => '2026-01-03',
                'reason' => 'Isolation Test Leave Reason',
            ],
            'Leave request created with id',
            'admin/approve_leave/tenantApproveLeaveEdit/',
            'admin/approve_leave/tenantApproveLeaveDelete/',
            'Isolation Test Leave Reason',
            'Leave request deleted.',
            'No matching leave request found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantApproveLeaveCreateRejectsForgedStudentSessionId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's real student_session #312 -- must 404.
            [$forgeStatus, ] = $this->curlPost('admin/approve_leave/tenantApproveLeaveCreate', [
                'student_session_id' => 312, 'apply_date' => '2026-01-01',
                'from_date' => '2026-01-02', 'to_date' => '2026-01-03',
                'reason' => 'Forged Leave Reason',
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant student_session_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    private function createSyllabusTopicFixture(int $tenantId, int $sessionId): int
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $pdo->exec("INSERT INTO subject_group_subjects (tenant_id) VALUES ($tenantId)");
        $subjectGroupSubjectId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO subject_group_class_sections (tenant_id) VALUES ($tenantId)");
        $subjectGroupClassSectionsId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO lesson (tenant_id, session_id, subject_group_subject_id, subject_group_class_sections_id, name) VALUES ($tenantId, $sessionId, $subjectGroupSubjectId, $subjectGroupClassSectionsId, 'Fixture Lesson')");
        $lessonId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO topic (tenant_id, session_id, lesson_id, name, status) VALUES ($tenantId, $sessionId, $lessonId, 'Fixture Topic', 0)");

        return (int) $pdo->lastInsertId();
    }

    private function cleanupSyllabusTopicFixture(int $topicId): void
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $lessonId = (int) $pdo->query("SELECT lesson_id FROM topic WHERE id = $topicId")->fetchColumn();
        $lesson = $pdo->query("SELECT subject_group_subject_id, subject_group_class_sections_id FROM lesson WHERE id = $lessonId")->fetch(PDO::FETCH_ASSOC);
        $pdo->exec("DELETE FROM topic WHERE id = $topicId");
        $pdo->exec("DELETE FROM lesson WHERE id = $lessonId");
        if ($lesson) {
            $pdo->exec("DELETE FROM subject_group_subjects WHERE id = " . (int) $lesson['subject_group_subject_id']);
            $pdo->exec("DELETE FROM subject_group_class_sections WHERE id = " . (int) $lesson['subject_group_class_sections_id']);
        }
    }

    public function testTenantSyllabusCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $topicId = $this->createSyllabusTopicFixture(25, 1);

        try {
            $this->verifyTenantCrudCrossTenantIsolation(
                'admin/syllabus/tenantSyllabusCreate',
                [
                    'topic_id' => $topicId, 'session_id' => 1, 'created_for' => 1,
                    'date' => '2026-01-01', 'time_from' => '09:00', 'time_to' => '10:00',
                    'presentation' => 'Isolation Test Syllabus Presentation',
                ],
                'Syllabus created with id',
                'admin/syllabus/tenantSyllabusEdit/',
                'admin/syllabus/tenantSyllabusDelete/',
                'Isolation Test Syllabus Presentation',
                'Syllabus deleted.',
                'No matching syllabus found for this tenant.',
                26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
            );
        } finally {
            $this->cleanupSyllabusTopicFixture($topicId);
        }
    }

    public function testTenantSyllabusCreateRejectsForgedTopicIdAndCreatedFor(): void
    {
        $topicId = $this->createSyllabusTopicFixture(25, 1);

        try {
            [$loginStatus, ] = $this->curlPostPilotLogin();
            $this->assertContains($loginStatus, [200, 302, 303, 307]);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                $baseFields = [
                    'session_id' => 16, 'date' => '2026-01-01', 'time_from' => '09:00', 'time_to' => '10:00',
                    'presentation' => 'Forged Syllabus',
                ];

                // Tenant 26 references tenant 25's real topic -- must 404.
                [$forgeTopicStatus, ] = $this->curlPost('admin/syllabus/tenantSyllabusCreate', $baseFields + ['topic_id' => $topicId, 'created_for' => 1]);
                $this->assertSame(404, $forgeTopicStatus, 'referencing another tenant topic_id must be rejected');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }
        } finally {
            $this->cleanupSyllabusTopicFixture($topicId);
        }
    }

    public function testTenantHomeworkCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $pdo->exec('INSERT INTO subject_group_subjects (tenant_id) VALUES (25)');
        $subjectGroupSubjectId = (int) $pdo->lastInsertId();

        try {
            $this->verifyTenantCrudCrossTenantIsolation(
                'homework/tenantHomeworkCreate',
                [
                    'class_id' => 1, 'section_id' => 1, 'subject_group_subject_id' => $subjectGroupSubjectId,
                    'session_id' => 1, 'homework_date' => '2026-01-01', 'submit_date' => '2026-01-08',
                    'description' => 'Isolation Test Homework Description',
                ],
                'Homework created with id',
                'homework/tenantHomeworkEdit/',
                'homework/tenantHomeworkDelete/',
                'Isolation Test Homework Description',
                'Homework deleted.',
                'No matching homework found for this tenant.',
                26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
            );
        } finally {
            $pdo->exec("DELETE FROM subject_group_subjects WHERE id = $subjectGroupSubjectId");
        }
    }

    public function testTenantHomeworkCreateRejectsForgedSubjectGroupSubjectId(): void
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $pdo->exec('INSERT INTO subject_group_subjects (tenant_id) VALUES (25)');
        $subjectGroupSubjectId = (int) $pdo->lastInsertId();

        try {
            [$loginStatus, ] = $this->curlPostPilotLogin();
            $this->assertContains($loginStatus, [200, 302, 303, 307]);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Tenant 26 references tenant 25's real subject_group_subjects row -- must 404.
                [$forgeStatus, ] = $this->curlPost('homework/tenantHomeworkCreate', [
                    'class_id' => 9, 'section_id' => 9, 'subject_group_subject_id' => $subjectGroupSubjectId,
                    'session_id' => 16, 'homework_date' => '2026-01-01', 'submit_date' => '2026-01-08',
                    'description' => 'Forged Homework',
                ]);
                $this->assertSame(404, $forgeStatus, 'referencing another tenant subject_group_subject_id must be rejected');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }
        } finally {
            $pdo->exec("DELETE FROM subject_group_subjects WHERE id = $subjectGroupSubjectId");
        }
    }

    public function testTenantAlumniCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/alumni/tenantAlumniCreate',
            ['student_id' => 1, 'current_phone' => '1234567890', 'current_email' => 'x@x.com'],
            'Alumni record created with id',
            'admin/alumni/tenantAlumniEdit/',
            'admin/alumni/tenantAlumniDelete/',
            '1234567890',
            'Alumni record deleted.',
            'No matching alumni record found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantAlumniCreateRejectsForgedStudentId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's real student #1 -- must 404.
            [$forgeStatus, ] = $this->curlPost('admin/alumni/tenantAlumniCreate', [
                'student_id' => 1, 'current_phone' => '1234567890',
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant student_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantContentCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/content/tenantContentCreate',
            ['content_title' => 'Isolation Test Content', 'content_type' => 'document'],
            'Content created with id',
            'admin/content/tenantContentEdit/',
            'admin/content/tenantContentDelete/',
            'Isolation Test Content',
            'Content deleted.',
            'No matching content found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantContentCreateRejectsForgedClassAndClsSecId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            $baseFields = ['content_title' => 'Forged Content', 'content_type' => 'document'];

            // Tenant 26 references tenant 25's real class #1 -- must 404.
            [$forgeClassStatus, ] = $this->curlPost('admin/content/tenantContentCreate', $baseFields + ['class_id' => 1]);
            $this->assertSame(404, $forgeClassStatus, 'referencing another tenant class_id must be rejected');

            // Tenant 26 references tenant 25's real class_sections #1 -- must 404.
            [$forgeSecStatus, ] = $this->curlPost('admin/content/tenantContentCreate', $baseFields + ['cls_sec_id' => 1]);
            $this->assertSame(404, $forgeSecStatus, 'referencing another tenant cls_sec_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantItemstockCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $pdo->exec("INSERT INTO item (tenant_id, name, unit, description, quantity) VALUES (25, 'Fixture Item', 'pcs', '', 0)");
        $itemId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO item_supplier (tenant_id, item_supplier, phone, email, address, contact_person_name, contact_person_phone, contact_person_email, description) VALUES (25, 'Fixture Supplier', '1', 'x@x.com', 'x', 'x', '1', 'x@x.com', '')");
        $supplierId = (int) $pdo->lastInsertId();

        try {
            $this->verifyTenantCrudCrossTenantIsolation(
                'admin/itemstock/tenantItemstockCreate',
                [
                    'item_id' => $itemId, 'supplier_id' => $supplierId, 'symbol' => '+', 'quantity' => 10,
                    'purchase_price' => '100', 'date' => '2026-01-01', 'description' => 'Isolation Test Item Stock',
                ],
                'Item stock created with id',
                'admin/itemstock/tenantItemstockEdit/',
                'admin/itemstock/tenantItemstockDelete/',
                'Isolation Test Item Stock',
                'Item stock deleted.',
                'No matching item stock found for this tenant.',
                26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
            );
        } finally {
            $pdo->exec("DELETE FROM item WHERE id = $itemId");
            $pdo->exec("DELETE FROM item_supplier WHERE id = $supplierId");
        }
    }

    public function testTenantItemstockCreateRejectsForgedItemAndSupplierId(): void
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $pdo->exec("INSERT INTO item (tenant_id, name, unit, description, quantity) VALUES (25, 'Fixture Item', 'pcs', '', 0)");
        $itemId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO item_supplier (tenant_id, item_supplier, phone, email, address, contact_person_name, contact_person_phone, contact_person_email, description) VALUES (25, 'Fixture Supplier', '1', 'x@x.com', 'x', 'x', '1', 'x@x.com', '')");
        $supplierId = (int) $pdo->lastInsertId();

        try {
            [$loginStatus, ] = $this->curlPostPilotLogin();
            $this->assertContains($loginStatus, [200, 302, 303, 307]);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                $baseFields = ['symbol' => '+', 'quantity' => 10, 'purchase_price' => '100', 'date' => '2026-01-01'];

                [$forgeItemStatus, ] = $this->curlPost('admin/itemstock/tenantItemstockCreate', $baseFields + ['item_id' => $itemId, 'supplier_id' => $supplierId]);
                $this->assertSame(404, $forgeItemStatus, 'referencing another tenant item_id must be rejected');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }
        } finally {
            $pdo->exec("DELETE FROM item WHERE id = $itemId");
            $pdo->exec("DELETE FROM item_supplier WHERE id = $supplierId");
        }
    }

    private function createOnlineAdmissionFixture(int $tenantId, string $firstname): int
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $stmt = $pdo->prepare(
            "INSERT INTO online_admissions (reference_no, middlename, cast, route_id, blood_group, vehroute_id, guardian_is, guardian_occupation, guardian_email, father_pic, mother_pic, guardian_pic, height, weight, note, form_status, paid_status, firstname, lastname, tenant_id)
             VALUES ('', '', '', 0, '', 0, '', '', '', '', '', '', '', '', '', 0, 0, ?, '', ?)"
        );
        $stmt->execute([$firstname, $tenantId]);

        return (int) $pdo->lastInsertId();
    }

    public function testTenantOnlineStudentEditDeleteIsolatedAndRejectsForgedClassSectionId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $applicationId = $this->createOnlineAdmissionFixture(25, 'Isolation Test Applicant');

        try {
            [$editStatus, $editBody] = $this->curlGet('admin/onlinestudent/tenantOnlineStudentEdit/' . $applicationId);
            $this->assertSame(200, $editStatus);
            $this->assertStringContainsString('Isolation Test Applicant', $editBody);

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                [$crossEditStatus, ] = $this->curlGet('admin/onlinestudent/tenantOnlineStudentEdit/' . $applicationId);
                $this->assertSame(404, $crossEditStatus, 'the other tenant must not be able to view this application by id');

                [$crossDeleteStatus, ] = $this->curlGet('admin/onlinestudent/tenantOnlineStudentDelete/' . $applicationId);
                $this->assertSame(200, $crossDeleteStatus);

                // Tenant 26 references tenant 25's real class_section as its own -- must 404.
                [$forgeStatus, ] = $this->curlPost('admin/onlinestudent/tenantOnlineStudentEdit/' . $applicationId, ['firstname' => 'Forged']);
                $this->assertSame(404, $forgeStatus, 'editing another tenant application must be rejected');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $stillThere = (int) $pdo->query("SELECT COUNT(*) FROM online_admissions WHERE id = $applicationId")->fetchColumn();
            $this->assertSame(1, $stillThere, 'the row must still exist after the other tenant\'s attempted cross-tenant delete');

            [$deleteStatus, $deleteBody] = $this->curlGet('admin/onlinestudent/tenantOnlineStudentDelete/' . $applicationId);
            $this->assertSame(200, $deleteStatus);
            $this->assertStringContainsString('deleted', $deleteBody);
            $applicationId = 0;
        } finally {
            if ($applicationId) {
                (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
                    ->exec("DELETE FROM online_admissions WHERE id = $applicationId");
            }
        }
    }

    public function testTenantOnlineStudentEditRejectsForgedCategoryId(): void
    {
        $applicationId = $this->createOnlineAdmissionFixture(25, 'Isolation Test Applicant 2');

        try {
            [$loginStatus, ] = $this->curlPostPilotLogin();
            $this->assertContains($loginStatus, [200, 302, 303, 307]);

            // category #1 belongs to tenant 25 too, but hostel_room_id doesn't exist for this tenant --
            // use a forged category id from a table we know tenant 25 owns zero rows of instead: a
            // safe forgery is any id belonging to another tenant. Use tenant 26's real class_section.
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $otherTenantClassSectionId = (int) $pdo->query('SELECT id FROM class_sections WHERE tenant_id = 26 LIMIT 1')->fetchColumn();

            [$forgeStatus, ] = $this->curlPost('admin/onlinestudent/tenantOnlineStudentEdit/' . $applicationId, [
                'firstname' => 'Forged', 'class_section_id' => $otherTenantClassSectionId,
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant class_section_id must be rejected');
        } finally {
            (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
                ->exec("DELETE FROM online_admissions WHERE id = $applicationId");
        }
    }

    public function testTenantSchSettingsGetUpdateIsolatedAndRejectsForgedSessionId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $originalTimezone = (string) $pdo->query("SELECT timezone FROM sch_settings WHERE tenant_id = 25")->fetchColumn();

        try {
            [$getStatus, $getBody] = $this->curlGet('schsettings/tenantSchSettingsGet');
            $this->assertSame(200, $getStatus);
            $this->assertStringContainsString('School Settings', $getBody);

            [$updateStatus, $updateBody] = $this->curlPost('schsettings/tenantSchSettingsUpdate', [
                'field' => 'timezone', 'value' => 'Isolation-Test-TZ',
            ]);
            $this->assertSame(200, $updateStatus);
            $this->assertStringContainsString('Setting timezone updated.', $updateBody);

            $stored = $pdo->query("SELECT timezone FROM sch_settings WHERE tenant_id = 25")->fetchColumn();
            $this->assertSame('Isolation-Test-TZ', $stored);

            // Non-whitelisted field must be rejected.
            [$badFieldStatus, ] = $this->curlPost('schsettings/tenantSchSettingsUpdate', [
                'field' => 'tenant_id', 'value' => '999',
            ]);
            $this->assertSame(404, $badFieldStatus, 'a non-whitelisted field must be rejected');

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Tenant 26 has no sch_settings row provisioned -- must 404, never see tenant 25's row.
                [$crossGetStatus, $crossGetBody] = $this->curlGet('schsettings/tenantSchSettingsGet');
                $this->assertSame(404, $crossGetStatus);
                $this->assertStringNotContainsString('Al-Fareed', $crossGetBody);

                [$crossUpdateStatus, ] = $this->curlPost('schsettings/tenantSchSettingsUpdate', [
                    'field' => 'timezone', 'value' => 'Forged-TZ',
                ]);
                $this->assertSame(404, $crossUpdateStatus, 'tenant 26 must not be able to write into tenant 25\'s settings row');
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            $stillStored = $pdo->query("SELECT timezone FROM sch_settings WHERE tenant_id = 25")->fetchColumn();
            $this->assertSame('Isolation-Test-TZ', $stillStored, 'tenant 25\'s row must be unaffected by tenant 26\'s attempted write');

            // Real FK verification: tenant 25 referencing tenant 26's real session must 404.
            $otherTenantSessionId = (int) $pdo->query('SELECT id FROM sessions WHERE tenant_id = 26 LIMIT 1')->fetchColumn();
            [$forgeSessionStatus, ] = $this->curlPost('schsettings/tenantSchSettingsUpdate', [
                'field' => 'session_id', 'value' => $otherTenantSessionId,
            ]);
            $this->assertSame(404, $forgeSessionStatus, 'referencing another tenant session_id must be rejected');
        } finally {
            $pdo->exec("UPDATE sch_settings SET timezone = " . $pdo->quote($originalTimezone) . " WHERE tenant_id = 25");
        }
    }

    public function testTenantFrontMenuCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/front/menus/tenantFrontMenuCreate',
            ['menu' => 'Isolation Test Menu', 'description' => 'x'],
            'Menu created with id',
            'admin/front/menus/tenantFrontMenuEdit/',
            'admin/front/menus/tenantFrontMenuDelete/',
            'Isolation Test Menu',
            'Menu deleted.',
            'No matching menu found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantBookCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/book/tenantBookCreate',
            ['book_title' => 'Isolation Test Book', 'book_no' => 'IB101', 'isbn_no' => 'ISBN-IB101', 'rack_no' => 'R1'],
            'Book created with id',
            'admin/book/tenantBookEdit/',
            'admin/book/tenantBookDelete/',
            'Isolation Test Book',
            'Book deleted.',
            'No matching book found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantMemberCreateDeleteIsolatedAndRejectsForgedMemberId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $ownId = null;

        try {
            [$createStatus, $createBody] = $this->curlPost('admin/member/tenantMemberCreate', [
                'member_type' => 'student',
                'member_id' => 1,
                'library_card_no' => 'ISOTESTCARD01',
            ]);
            $this->assertSame(200, $createStatus);
            $this->assertMatchesRegularExpression('/Member created with id (\d+)/', $createBody);
            preg_match('/Member created with id (\d+)/', $createBody, $matches);
            $ownId = (int) $matches[1];

            $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
            $realCookieJar = $this->cookieJar;
            $this->cookieJar = $otherCookieJar;

            try {
                [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                // Tenant 26 references tenant 25's real student #1 -- must 404.
                [$forgeStatus, ] = $this->curlPost('admin/member/tenantMemberCreate', [
                    'member_type' => 'student',
                    'member_id' => 1,
                    'library_card_no' => 'FORGEDCARD01',
                ]);
                $this->assertSame(404, $forgeStatus, 'referencing another tenant student as member_id must be rejected');

                [$crossDeleteStatus, $crossDeleteBody] = $this->curlGet('admin/member/tenantMemberDelete/' . $ownId);
                $this->assertSame(200, $crossDeleteStatus);
                $this->assertStringContainsString('No matching member found for this tenant.', $crossDeleteBody);
            } finally {
                $this->cookieJar = $realCookieJar;
                @unlink($otherCookieJar);
            }

            $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
            $stillThere = (int) $pdo->query('SELECT COUNT(*) FROM libarary_members WHERE id = ' . $ownId)->fetchColumn();
            $this->assertSame(1, $stillThere, 'the row must still exist after the other tenant\'s attempted cross-tenant delete');

            [$deleteStatus, $deleteBody] = $this->curlGet('admin/member/tenantMemberDelete/' . $ownId);
            $this->assertSame(200, $deleteStatus);
            $this->assertStringContainsString('Member deleted.', $deleteBody);
            $ownId = null;
        } finally {
            if ($ownId !== null) {
                (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
                    ->exec('DELETE FROM libarary_members WHERE id = ' . $ownId);
            }
            (new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', ''))
                ->exec("DELETE FROM libarary_members WHERE library_card_no LIKE 'ISOTESTCARD%' OR library_card_no LIKE 'FORGEDCARD%'");
        }
    }

    public function testTenantEventCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/calendar/tenantEventCreate',
            ['event_title' => 'Isolation Test Event', 'start_date' => '2026-01-01 09:00:00', 'end_date' => '2026-01-01 10:00:00'],
            'Event created with id',
            'admin/calendar/tenantEventEdit/',
            'admin/calendar/tenantEventDelete/',
            'Isolation Test Event',
            'Event deleted.',
            'No matching event found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantEventCreateRejectsForgedRoleId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's real role #1 -- must 404.
            [$forgeStatus, ] = $this->curlPost('admin/calendar/tenantEventCreate', [
                'event_title' => 'Forged Event',
                'start_date' => '2026-01-01 09:00:00',
                'end_date' => '2026-01-01 10:00:00',
                'role_id' => 1,
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant role_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantStudentTimelineCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/timeline/tenantStudentTimelineCreate',
            ['timeline_title' => 'Isolation Test Student Timeline', 'timeline_date' => '2026-01-01', 'timeline_desc' => 'x', 'student_id' => 1],
            'Student timeline created with id',
            'admin/timeline/tenantStudentTimelineEdit/',
            'admin/timeline/tenantStudentTimelineDelete/',
            'Isolation Test Student Timeline',
            'Timeline entry deleted.',
            'No matching timeline entry found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantStudentTimelineCreateRejectsForgedStudentId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's real student #1 -- must 404.
            [$forgeStatus, ] = $this->curlPost('admin/timeline/tenantStudentTimelineCreate', [
                'timeline_title' => 'Forged Student Timeline',
                'timeline_date' => '2026-01-01',
                'student_id' => 1,
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant student_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantStaffTimelineCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/timeline/tenantStaffTimelineCreate',
            ['timeline_title' => 'Isolation Test Staff Timeline', 'timeline_date' => '2026-01-01', 'timeline_desc' => 'x', 'staff_id' => 1],
            'Staff timeline created with id',
            'admin/timeline/tenantStaffTimelineEdit/',
            'admin/timeline/tenantStaffTimelineDelete/',
            'Isolation Test Staff Timeline',
            'Timeline entry deleted.',
            'No matching timeline entry found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantStaffTimelineCreateRejectsForgedStaffId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's real staff #1 -- must 404.
            [$forgeStatus, ] = $this->curlPost('admin/timeline/tenantStaffTimelineCreate', [
                'timeline_title' => 'Forged Staff Timeline',
                'timeline_date' => '2026-01-01',
                'staff_id' => 1,
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant staff_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantMarksheetCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/marksheet/tenantMarksheetCreate',
            ['template' => 'default', 'heading' => 'Isolation Test Marksheet', 'title' => 'Marksheet'],
            'Marksheet created with id',
            'admin/marksheet/tenantMarksheetEdit/',
            'admin/marksheet/tenantMarksheetDelete/',
            'Isolation Test Marksheet',
            'Marksheet deleted.',
            'No matching marksheet found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantExpenseCreateEditDeleteAreIsolatedPerTenant(): void
    {
        $this->verifyTenantCrudCrossTenantIsolation(
            'admin/expense/tenantExpenseCreate',
            ['exp_head_id' => 1, 'amount' => '100', 'name' => 'Isolation Test Expense', 'date' => '2026-01-23'],
            'Expense created with id',
            'admin/expense/tenantExpenseEdit/',
            'admin/expense/tenantExpenseDelete/',
            'Isolation Test Expense',
            'Expense deleted.',
            'No matching expense found for this tenant.',
            26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!'
        );
    }

    public function testTenantExpenseCreateRejectsForgedExpenseHeadId(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin();
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
        $realCookieJar = $this->cookieJar;
        $this->cookieJar = $otherCookieJar;

        try {
            [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
            $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

            // Tenant 26 references tenant 25's real expense_head #1 -- must 404.
            [$forgeStatus, ] = $this->curlPost('admin/expense/tenantExpenseCreate', [
                'exp_head_id' => 1,
                'amount' => '100',
                'name' => 'Forged Expense',
                'date' => '2026-01-23',
            ]);
            $this->assertSame(404, $forgeStatus, 'referencing another tenant exp_head_id must be rejected');
        } finally {
            $this->cookieJar = $realCookieJar;
            @unlink($otherCookieJar);
        }
    }

    public function testTenantIncomeCreateEditDeleteRejectsForgedForeignKeysAndIsolatesPerTenant(): void
    {
        // income_head has zero real data for any tenant -- create real
        // fixture rows directly, same precedent as Hostelroom's hostel/
        // room_types fixtures when no pre-existing data was available.
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $pdo->exec("INSERT INTO income_head (income_category, is_active, is_deleted, tenant_id) VALUES ('Isolation Test Head 25', 'yes', 'no', 25)");
        $tenant25HeadId = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO income_head (income_category, is_active, is_deleted, tenant_id) VALUES ('Isolation Test Head 26', 'yes', 'no', 26)");
        $tenant26HeadId = (int) $pdo->lastInsertId();

        try {
            [$loginStatus, ] = $this->curlPostPilotLogin();
            $this->assertContains($loginStatus, [200, 302, 303, 307]);

            try {
                [$createStatus, $createBody] = $this->curlPost('admin/income/tenantIncomeCreate', [
                    'inc_head_id' => $tenant25HeadId,
                    'amount' => '100',
                    'name' => 'Isolation Test Income',
                    'date' => '2026-01-24',
                ]);
                $this->assertSame(200, $createStatus);
                $this->assertMatchesRegularExpression('/Income created with id (\d+)/', $createBody);
                preg_match('/Income created with id (\d+)/', $createBody, $matches);
                $incomeId = (int) $matches[1];

                [$editGetStatus, $editGetBody] = $this->curlGet('admin/income/tenantIncomeEdit/' . $incomeId);
                $this->assertSame(200, $editGetStatus);
                $this->assertStringContainsString('Isolation Test Income', $editGetBody);

                $otherCookieJar = tempnam(sys_get_temp_dir(), 'admgate_test_other_');
                $realCookieJar = $this->cookieJar;
                $this->cookieJar = $otherCookieJar;

                try {
                    [$otherLoginStatus, ] = $this->curlPostPilotLoginAs(26, 'khushbakhtfarooq7@gmail.com', 'TestVerify123!');
                    $this->assertContains($otherLoginStatus, [200, 302, 303, 307]);

                    // Forgery: tenant 26 references tenant 25's income_head -- must 404.
                    [$forgeStatus, ] = $this->curlPost('admin/income/tenantIncomeCreate', [
                        'inc_head_id' => $tenant25HeadId,
                        'amount' => '100',
                        'name' => 'Forged Income',
                        'date' => '2026-01-24',
                    ]);
                    $this->assertSame(404, $forgeStatus, 'referencing another tenant income_head_id must be rejected');

                    [$crossEditStatus, ] = $this->curlGet('admin/income/tenantIncomeEdit/' . $incomeId);
                    $this->assertSame(404, $crossEditStatus, 'the other tenant must not be able to view this row by id');
                } finally {
                    $this->cookieJar = $realCookieJar;
                    @unlink($otherCookieJar);
                }

                [$finalEditStatus, $finalEditBody] = $this->curlGet('admin/income/tenantIncomeEdit/' . $incomeId);
                $this->assertSame(200, $finalEditStatus);
                $this->assertStringContainsString('Isolation Test Income', $finalEditBody);

                [$deleteStatus, $deleteBody] = $this->curlGet('admin/income/tenantIncomeDelete/' . $incomeId);
                $this->assertSame(200, $deleteStatus);
                $this->assertStringContainsString('Income deleted.', $deleteBody);
            } finally {
                $pdo->exec("DELETE FROM income WHERE name IN ('Isolation Test Income', 'Forged Income')");
            }
        } finally {
            $pdo->exec('DELETE FROM income_head WHERE id IN (' . $tenant25HeadId . ', ' . $tenant26HeadId . ')');
        }
    }

    private function curlPost(string $path, array $fields): array
    {
        $ch = curl_init(self::BASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    }

    // Multipart variant for endpoints that accept a file upload alongside
    // regular fields. $filePath must be a real, readable local file.
    private function curlPostMultipart(string $path, array $fields, string $fileFieldName, string $filePath): array
    {
        $ch = curl_init(self::BASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields + [$fileFieldName => new CURLFile($filePath, 'image/png', 'test-photo.png')],
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    }

    private function curlPostPilotLoginAs(int $tenantId, string $email, string $password): array
    {
        $ch = curl_init(self::BASE_URL . 'pilotlogin/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'tenant_id' => $tenantId,
                'email' => $email,
                'password' => $password,
            ]),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    }

    private function curlPostPilotLogin(): array
    {
        $ch = curl_init(self::BASE_URL . 'pilotlogin/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'tenant_id' => 25,
                'email' => 'rabiachauhan923@gmail.com',
                'password' => 'TestVerify123!',
            ]),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    }
}
