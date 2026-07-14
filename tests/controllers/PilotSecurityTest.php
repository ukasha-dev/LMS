<?php

use PHPUnit\Framework\TestCase;

final class PilotSecurityTest extends TestCase
{
    private const BASE_URL = 'http://localhost/web-app/';

    private string $cookieJar;

    protected function setUp(): void
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'pilot_security_test_');
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

    private function curlPostPilotLogin(string $email, string $password): array
    {
        $ch = curl_init(self::BASE_URL . 'pilotlogin/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'tenant_id' => 25,
                'email' => $email,
                'password' => $password,
            ]),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    }

    public function testFailedPilotLoginDoesNotLeavePilotTenantIdUsable(): void
    {
        // application/views/pilot_students.php's actual heading, confirmed
        // by direct read: "<h1>Pilot Students (tenant_id = ...)</h1>". Its
        // absence proves index() did not render a normal successful page
        // (Tenant_Model::currentTenantId() throws when pilot_tenant_id is
        // unset, so this should error out rather than list real students).
        [, $loginBody] = $this->curlPostPilotLogin('totally-bogus@example.invalid', 'wrong');
        $this->assertStringContainsString('Invalid email or password', $loginBody);

        [, $indexBody] = $this->curlGet('pilotstudents/index');
        $this->assertStringNotContainsString('Pilot Students', $indexBody);
    }

    public function testLoginAsBackdoorNoLongerExists(): void
    {
        [$status, ] = $this->curlGet('pilotstudents/login_as/25');
        $this->assertNotSame(200, $status);
    }

    public function testLegitimatePilotLoginStillReachesRealStudentData(): void
    {
        [$loginStatus, ] = $this->curlPostPilotLogin('rabiachauhan923@gmail.com', 'TestVerify123!');
        $this->assertContains($loginStatus, [200, 302, 303, 307]);

        [$indexStatus, $indexBody] = $this->curlGet('pilotstudents/index');
        $this->assertSame(200, $indexStatus);
        $this->assertStringContainsString('Pilot Students', $indexBody);
    }
}
