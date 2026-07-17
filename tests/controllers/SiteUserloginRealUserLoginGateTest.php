<?php

use PHPUnit\Framework\TestCase;

final class SiteUserloginRealUserLoginGateTest extends TestCase
{
    private const BASE_URL = 'http://localhost/web-app/index.php/site/userlogin';

    public function testFailedLoginForNonExistentAccountIsUnaffectedAndLogsNoNewException(): void
    {
        $logFile = __DIR__ . '/../../application/logs/log-' . date('Y-m-d') . '.php';
        $logSizeBefore = file_exists($logFile) ? filesize($logFile) : 0;

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
        // ('invalid_username_or_password') is never what's rendered; confirmed
        // live via curl during implementation that userlogin()'s failure path
        // renders the identical text as login()'s.
        $this->assertStringContainsString('Invalid Username Or Password', $body);

        if (file_exists($logFile)) {
            $newLogContent = file_get_contents($logFile);
            $newLogContent = substr($newLogContent, $logSizeBefore);
            $this->assertStringNotContainsString('RealUserLoginGate', $newLogContent, 'a login matching no real account must never trigger RealUserLoginGate logging');
        }
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

    // Phase 4 Stage 3: RealUserLoginGate's pre-loop check now also covers
    // tenants 26-29 (in addition to tenant 25, Stage 2). Field names/BASE_URL
    // match this file's existing convention above -- no adjustment needed,
    // userlogin() posts 'username'/'password' directly (confirmed by reading
    // Site.php::userlogin() before writing this).
    public function testFailedLoginWithWrongPasswordForEachNewTenantStudentIsUnaffected(): void
    {
        $newTenantUsernames = ['std504', 'std144', 'std178', 'std1782']; // tenants 26-29

        foreach ($newTenantUsernames as $username) {
            $ch = curl_init(self::BASE_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'username' => $username,
                    'password' => 'definitely-wrong-password-' . bin2hex(random_bytes(4)),
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $this->assertSame(200, $status, "username {$username} should return 200");
            $this->assertStringContainsString('Invalid Username Or Password', $body, "username {$username} should show the standard failure message");
        }
    }

    public function testTenant30ShapedLoginAttemptFallsThroughToLegacyLoopUnaffected(): void
    {
        // Tenant 30 (Smart School) is deliberately never in this stage's
        // loop array. A login attempt with a smart_school-shaped identifier
        // must behave identically to any other non-matching login -- it
        // reaches the legacy loop, which the pre-loop gate never touches
        // for tenant 30.
        $ch = curl_init(self::BASE_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => 'definitely-not-a-smart-school-account-' . bin2hex(random_bytes(4)),
                'password' => 'definitely-wrong-password',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        $this->assertStringContainsString('Invalid Username Or Password', $body);
    }
}
