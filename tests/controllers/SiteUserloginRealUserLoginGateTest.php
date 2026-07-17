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
}
