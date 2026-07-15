<?php

defined('BASEPATH') or exit('No direct script access allowed');

class MY_Model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->library('user_agent');
    }

    public function log($message = null, $record_id = null, $action = null) {
        $user_id = $this->customlib->getStaffID();

        $ip = $this->input->ip_address();

        if ($this->agent->is_browser()) {
            $agent = $this->agent->browser() . ' ' . $this->agent->version();
        } elseif ($this->agent->is_robot()) {
            $agent = $this->agent->robot();
        } elseif ($this->agent->is_mobile()) {

            $agent = $this->agent->mobile();
        } else {
            $agent = 'Unidentified User Agent';
        }

        $platform = $this->agent->platform(); // Platform info (Windows, Linux, Mac, etc.)

        $insert = array(
            'message' => $message,
            'user_id' => $user_id,
            'record_id' => $record_id,
            'ip_address' => $ip,
            'platform' => $platform,
            'agent' => $agent,
            'action' => $action,
            'time' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('logs', $insert);
    }

    // The 5 methods below are generic tenant-safe CRUD helpers, available
    // to every model since they all extend MY_Model. Every read/update/
    // delete filters explicitly by tenant_id; every insert injects it.
    // `id` is never trusted alone -- a tenant session can never read,
    // modify, or delete another tenant's row even if it tampers with the
    // id in a request. Built to avoid hand-writing this same defensive
    // shape once per table (see Grade_model's tenantScopedAdd/
    // tenantScopedGrade/tenantScopedDelete, the original proof-of-concept
    // this generalizes). Only for tables that actually have a tenant_id
    // column -- do not use these against global reference tables.

    public function tenantScopedFind(string $table, int $tenantId, int $id): ?array
    {
        $row = $this->db->where('id', $id)->where('tenant_id', $tenantId)->get($table)->row_array();

        return $row ?: null;
    }

    public function tenantScopedList(string $table, int $tenantId, array $where = []): array
    {
        $this->db->where('tenant_id', $tenantId);
        foreach ($where as $column => $value) {
            $this->db->where($column, $value);
        }

        return $this->db->get($table)->result_array();
    }

    public function tenantScopedInsert(string $table, int $tenantId, array $data): int
    {
        unset($data['id'], $data['tenant_id']);
        $data['tenant_id'] = $tenantId;
        $this->db->insert($table, $data);

        return (int) $this->db->insert_id();
    }

    public function tenantScopedUpdate(string $table, int $tenantId, int $id, array $data): bool
    {
        unset($data['id'], $data['tenant_id']);
        $this->db->where('id', $id)->where('tenant_id', $tenantId)->update($table, $data);

        return $this->db->affected_rows() > 0;
    }

    public function tenantScopedDelete(string $table, int $tenantId, int $id): bool
    {
        $this->db->where('id', $id)->where('tenant_id', $tenantId)->delete($table);

        return $this->db->affected_rows() > 0;
    }

    // Batch analogue of tenantScopedFind, for FK-ownership checks against a
    // whole posted set at once (e.g. a batch-attendance save) instead of one
    // query per row. Returns an id-keyed map so callers can do O(1)
    // membership tests; any id not present in the map is not owned by this
    // tenant and must be dropped, not guessed at.
    public function tenantScopedBatchFind(string $table, int $tenantId, string $idColumn, array $ids): array
    {
        $ids = array_unique(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        $rows = $this->db->where('tenant_id', $tenantId)
            ->where_in($idColumn, $ids)
            ->get($table)->result_array();

        return array_column($rows, null, $idColumn);
    }

}
