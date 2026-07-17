<?php

use PHPUnit\Framework\TestCase;

final class SiteLoginRealLoginGateTest extends TestCase
{
    private const BASE_URL = 'http://localhost/web-app/';

    public function testFailedLoginForNonExistentAccountIsUnaffectedAndLogsNoDriftOrException(): void
    {
        $logFile = __DIR__ . '/../../application/logs/log-' . date('Y-m-d') . '.php';
        $logSizeBefore = file_exists($logFile) ? filesize($logFile) : 0;

        $ch = curl_init(self::BASE_URL . 'site/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => 'definitely-not-a-real-account@example.invalid',
                'password' => 'wrong',
            ]),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        // The lang line 'invalid_username_or_password' renders as this exact
        // text (Site.php's real, unmodified error branch, confirmed live);
        // asserting the literal lang key (as an earlier draft did) never
        // matches actual output.
        $this->assertStringContainsString('Invalid Username Or Password', $body, 'unauthenticated failure message must be present, unchanged');

        if (file_exists($logFile)) {
            $newLogContent = file_get_contents($logFile);
            $newLogContent = substr($newLogContent, $logSizeBefore);
            $this->assertStringNotContainsString('RealLoginGate', $newLogContent, 'a login matching no real account must never trigger RealLoginGate logging');
        }
    }

    public function testLoginFormRendersIdenticallyOnGet(): void
    {
        $ch = curl_init(self::BASE_URL . 'site/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status);
        $this->assertStringContainsString('name="username"', $body);
        $this->assertStringContainsString('name="password"', $body);
    }
}
