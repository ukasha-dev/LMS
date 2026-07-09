<?php

use PHPUnit\Framework\TestCase;

final class TenantScopeTest extends TestCase
{
    private PDO $pdo;
    private TenantScope $scope;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS tenant_scope_test');
        $admin->exec('CREATE DATABASE tenant_scope_test');

        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=tenant_scope_test;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE widgets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            name VARCHAR(100) NOT NULL
        )');

        $this->scope = new TenantScope($this->pdo);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS tenant_scope_test');
    }

    public function testInsertStampsTenantId(): void
    {
        $id = $this->scope->insert('widgets', ['name' => 'Widget A'], 7);
        $rows = $this->scope->selectAll('widgets', [], 7);

        $this->assertCount(1, $rows);
        // assertEquals (not assertSame) because this PHP 8.1/mysqlnd PDO driver
        // returns native int types for INT columns rather than strings; the
        // brief's exact assertion assumed string-typed fetches. See task-3-report.md.
        $this->assertEquals(7, $rows[0]['tenant_id']);
        $this->assertSame($id, (int) $rows[0]['id']);
    }

    public function testSelectAllOnlyReturnsMatchingTenant(): void
    {
        $this->scope->insert('widgets', ['name' => 'Tenant 1 Widget'], 1);
        $this->scope->insert('widgets', ['name' => 'Tenant 2 Widget'], 2);

        $tenant1Rows = $this->scope->selectAll('widgets', [], 1);

        $this->assertCount(1, $tenant1Rows);
        $this->assertSame('Tenant 1 Widget', $tenant1Rows[0]['name']);
    }

    public function testUpdateOnlyAffectsMatchingTenant(): void
    {
        $idTenant1 = $this->scope->insert('widgets', ['name' => 'Original'], 1);

        $affected = $this->scope->update('widgets', ['name' => 'Renamed'], ['id' => $idTenant1], 1);

        $this->assertSame(1, $affected);
        $rows = $this->scope->selectAll('widgets', [], 1);
        $this->assertSame('Renamed', $rows[0]['name']);
    }

    public function testUpdateDoesNotAffectOtherTenantsRowEvenWithMatchingId(): void
    {
        $idTenant1 = $this->scope->insert('widgets', ['name' => 'Original'], 1);

        $affected = $this->scope->update('widgets', ['name' => 'Hacked'], ['id' => $idTenant1], 2);

        $this->assertSame(0, $affected);
        $rows = $this->scope->selectAll('widgets', [], 1);
        $this->assertSame('Original', $rows[0]['name']);
    }

    public function testDeleteOnlyAffectsMatchingTenant(): void
    {
        $idTenant1 = $this->scope->insert('widgets', ['name' => 'ToDelete'], 1);

        $deletedByWrongTenant = $this->scope->delete('widgets', ['id' => $idTenant1], 2);
        $this->assertSame(0, $deletedByWrongTenant);

        $deletedByRightTenant = $this->scope->delete('widgets', ['id' => $idTenant1], 1);
        $this->assertSame(1, $deletedByRightTenant);
    }

    public function testCountOnlyCountsMatchingTenant(): void
    {
        $this->scope->insert('widgets', ['name' => 'A'], 5);
        $this->scope->insert('widgets', ['name' => 'B'], 5);
        $this->scope->insert('widgets', ['name' => 'C'], 6);

        $this->assertSame(2, $this->scope->count('widgets', [], 5));
    }

    public function testUpdateCannotReassignRowToAnotherTenant(): void
    {
        $id = $this->scope->insert('widgets', ['name' => 'Original'], 1);

        $affected = $this->scope->update(
            'widgets',
            ['tenant_id' => 2, 'name' => 'Renamed'],
            ['id' => $id],
            1
        );

        $this->assertSame(1, $affected);

        $rows = $this->scope->selectAll('widgets', [], 1);
        $this->assertCount(1, $rows);
        $this->assertSame('Renamed', $rows[0]['name']);
        $this->assertEquals(1, $rows[0]['tenant_id']);
    }

    public function testUpdateCannotReassignRowToAnotherTenantViaCasedKey(): void
    {
        $id = $this->scope->insert('widgets', ['name' => 'Original'], 1);

        $affected = $this->scope->update(
            'widgets',
            ['TENANT_ID' => 2, 'name' => 'Renamed'],
            ['id' => $id],
            1
        );

        $this->assertSame(1, $affected);

        $rows = $this->scope->selectAll('widgets', [], 1);
        $this->assertCount(1, $rows);
        $this->assertSame('Renamed', $rows[0]['name']);
        $this->assertEquals(1, $rows[0]['tenant_id']);
    }

    public function testInsertRejectsInvalidColumnName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->scope->insert('widgets', ['name; DROP TABLE widgets; --' => 'x'], 1);
    }

    public function testSelectAllRejectsInvalidTableName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->scope->selectAll('widgets; DROP TABLE widgets; --', [], 1);
    }

    public function testUpdateRejectsInvalidWhereColumnName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->scope->update('widgets', ['name' => 'x'], ['id; DROP TABLE widgets; --' => 1], 1);
    }
}
