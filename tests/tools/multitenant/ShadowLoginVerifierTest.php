<?php

use PHPUnit\Framework\TestCase;

final class ShadowLoginVerifierTest extends TestCase
{
    private PDO $pdo;
    private ShadowLoginVerifier $verifier;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS shadow_verify_test');
        $admin->exec('CREATE DATABASE shadow_verify_test');

        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=shadow_verify_test;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE staff (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(200), password VARCHAR(255), tenant_id INT NOT NULL)');
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('real@example.com', 'stored-hash-25', 25), ('other-tenant@example.com', 'stored-hash-99', 99)");

        $this->verifier = new ShadowLoginVerifier($this->pdo);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS shadow_verify_test');
    }

    private function passthroughVerifier(string $expectedPassword): callable
    {
        return function (string $password, string $storedHash) use ($expectedPassword): bool {
            return $password === $expectedPassword && $storedHash === 'stored-hash-25';
        };
    }

    public function testMatchedWhenEmailTenantAndPasswordAllAgree(): void
    {
        $result = $this->verifier->verify('real@example.com', 'correct-password', 25, $this->passthroughVerifier('correct-password'));

        $this->assertSame(['matched' => true, 'reason' => 'ok'], $result);
    }

    public function testNoMatchingRowWhenEmailDoesNotExist(): void
    {
        $result = $this->verifier->verify('nobody@example.com', 'anything', 25, $this->passthroughVerifier('anything'));

        $this->assertSame(['matched' => false, 'reason' => 'no_matching_row'], $result);
    }

    public function testNoMatchingRowWhenEmailExistsOnlyUnderAnotherTenant(): void
    {
        // real@example.com exists under tenant 25 only in this fixture;
        // other-tenant@example.com exists under tenant 99 only. Asking for
        // other-tenant@example.com under tenant 25 must not match — proves
        // the WHERE clause is tenant-scoped, not just email-scoped.
        $result = $this->verifier->verify('other-tenant@example.com', 'anything', 25, $this->passthroughVerifier('anything'));

        $this->assertSame(['matched' => false, 'reason' => 'no_matching_row'], $result);
    }

    public function testPasswordMismatchWhenRowFoundButVerifierRejects(): void
    {
        $result = $this->verifier->verify('real@example.com', 'wrong-password', 25, $this->passthroughVerifier('correct-password'));

        $this->assertSame(['matched' => false, 'reason' => 'password_mismatch'], $result);
    }

    public function testPasswordVerifierReceivesThePlaintextPasswordAndTheStoredHashInThatOrder(): void
    {
        $seenArgs = null;
        $spy = function (string $password, string $storedHash) use (&$seenArgs): bool {
            $seenArgs = [$password, $storedHash];

            return true;
        };

        $this->verifier->verify('real@example.com', 'my-password', 25, $spy);

        $this->assertSame(['my-password', 'stored-hash-25'], $seenArgs);
    }
}
