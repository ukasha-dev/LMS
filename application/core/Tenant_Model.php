<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . '../tools/multitenant/TenantScope.php';

class Tenant_Model extends MY_Model
{
    protected TenantScope $tenantScope;

    public function __construct()
    {
        parent::__construct();

        $pdo = new PDO(
            'mysql:host=' . $this->db->hostname . ';dbname=' . $this->db->database . ';charset=utf8mb4',
            $this->db->username,
            $this->db->password
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->tenantScope = new TenantScope($pdo);
    }

    protected function currentTenantId(): int
    {
        $tenantId = $this->session->userdata('pilot_tenant_id');
        if (empty($tenantId)) {
            throw new RuntimeException('Tenant_Model: no pilot_tenant_id in session');
        }

        return (int) $tenantId;
    }

    public function tenantGetAll(string $table, array $where = []): array
    {
        return $this->tenantScope->selectAll($table, $where, $this->currentTenantId());
    }

    public function tenantInsert(string $table, array $data): int
    {
        return $this->tenantScope->insert($table, $data, $this->currentTenantId());
    }

    public function tenantUpdate(string $table, array $data, array $where): int
    {
        return $this->tenantScope->update($table, $data, $where, $this->currentTenantId());
    }

    public function tenantDelete(string $table, array $where): int
    {
        return $this->tenantScope->delete($table, $where, $this->currentTenantId());
    }

    public function tenantCount(string $table, array $where = []): int
    {
        return $this->tenantScope->count($table, $where, $this->currentTenantId());
    }
}
