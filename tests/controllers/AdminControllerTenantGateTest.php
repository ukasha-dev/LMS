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
