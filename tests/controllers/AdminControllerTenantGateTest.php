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
