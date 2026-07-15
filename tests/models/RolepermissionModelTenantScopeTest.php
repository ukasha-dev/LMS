<?php

use PHPUnit\Framework\TestCase;

final class RolepermissionModelTenantScopeTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS rolepermission_scope_test');
        $admin->exec('CREATE DATABASE rolepermission_scope_test');

        $this->db = new PDO('mysql:host=127.0.0.1;dbname=rolepermission_scope_test;charset=utf8mb4', 'root', '');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec(
            'CREATE TABLE roles_permissions ('
            . 'id INT AUTO_INCREMENT PRIMARY KEY, role_id INT NOT NULL, perm_cat_id INT NOT NULL, '
            . 'can_view INT DEFAULT 0, can_add INT DEFAULT 0, can_edit INT DEFAULT 0, can_delete INT DEFAULT 0, '
            . 'tenant_id INT NOT NULL)'
        );
        $this->db->exec(
            'CREATE TABLE permission_category ('
            . 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), short_code VARCHAR(100))'
        );
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS rolepermission_scope_test');
    }

    // Mirrors exactly the query Rolepermission_model::
    // getPermissionByRoleandCategory() now runs -- the `tenant_id` clause
    // only gets added when $tenantId is not null, matching the model's
    // own conditional (legacy per-branch sessions have no tenant_id
    // column on this table at all, see Rbac::hasPrivilege()'s comment).
    private function queryPermission(int $roleId, string $category, ?int $tenantId): ?array
    {
        $sql = 'SELECT roles_permissions.*, permission_category.short_code AS permission_category_code'
            . ' FROM roles_permissions'
            . ' JOIN permission_category ON permission_category.id = roles_permissions.perm_cat_id'
            . ' WHERE roles_permissions.role_id = :role_id'
            . ' AND permission_category.short_code = :category';
        if ($tenantId !== null) {
            $sql .= ' AND roles_permissions.tenant_id = :tenant_id';
        }

        $stmt = $this->db->prepare($sql);
        $params = [':role_id' => $roleId, ':category' => $category];
        if ($tenantId !== null) {
            $params[':tenant_id'] = $tenantId;
        }
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function testWithoutTenantFilterAColldingRoleIdLeaksAnotherTenantsPermission(): void
    {
        // This is the real, latent gap: two DIFFERENT tenants' roles
        // happen to share role_id=3 (globally-unique ids from the merge
        // tools are a coincidence, not a guarantee). Without the
        // tenant_id filter, whichever row the join resolver picks first
        // wins -- here, tenant 26's can_view=1 leaks to a caller who
        // actually holds tenant 25's role_id=3, which only grants
        // can_view=0.
        $this->db->exec("INSERT INTO permission_category (id, name, short_code) VALUES (1, 'Staff', 'staff')");
        $this->db->exec("INSERT INTO roles_permissions (id, role_id, perm_cat_id, can_view, tenant_id) VALUES (1, 3, 1, 0, 25)");
        $this->db->exec("INSERT INTO roles_permissions (id, role_id, perm_cat_id, can_view, tenant_id) VALUES (2, 3, 1, 1, 26)");

        $result = $this->queryPermission(3, 'staff', null);

        $this->assertNotNull($result);
        // Ambiguous by construction -- MySQL returns whichever row it
        // finds first with no ORDER BY. The point isn't which one wins;
        // it's that a result comes back at all despite genuinely
        // belonging to two different tenants. Assert on tenant_id
        // instead of can_view to make that ambiguity explicit rather
        // than asserting a specific (and fragile) row-order outcome.
        $this->assertContains((int) $result['tenant_id'], [25, 26]);
    }

    public function testWithTenantFilterTheCollidingRoleIdResolvesToTheCorrectTenantOnly(): void
    {
        $this->db->exec("INSERT INTO permission_category (id, name, short_code) VALUES (1, 'Staff', 'staff')");
        $this->db->exec("INSERT INTO roles_permissions (id, role_id, perm_cat_id, can_view, tenant_id) VALUES (1, 3, 1, 0, 25)");
        $this->db->exec("INSERT INTO roles_permissions (id, role_id, perm_cat_id, can_view, tenant_id) VALUES (2, 3, 1, 1, 26)");

        $tenant25Result = $this->queryPermission(3, 'staff', 25);
        $tenant26Result = $this->queryPermission(3, 'staff', 26);

        $this->assertSame(25, (int) $tenant25Result['tenant_id']);
        $this->assertSame(0, (int) $tenant25Result['can_view']);

        $this->assertSame(26, (int) $tenant26Result['tenant_id']);
        $this->assertSame(1, (int) $tenant26Result['can_view']);
    }

    public function testTenantFilterReturnsNullWhenThisTenantHasNoSuchRoleId(): void
    {
        $this->db->exec("INSERT INTO permission_category (id, name, short_code) VALUES (1, 'Staff', 'staff')");
        $this->db->exec("INSERT INTO roles_permissions (id, role_id, perm_cat_id, can_view, tenant_id) VALUES (1, 3, 1, 1, 26)");

        $result = $this->queryPermission(3, 'staff', 25);

        $this->assertNull($result, 'a role_id belonging only to another tenant must not resolve for this tenant');
    }
}
