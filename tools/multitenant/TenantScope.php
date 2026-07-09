<?php

final class TenantScope
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function selectAll(string $table, array $where, int $tenantId): array
    {
        [$whereSql, $params] = $this->buildWhere($where, $tenantId);
        $stmt = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE {$whereSql}");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(string $table, array $data, int $tenantId): int
    {
        $data['tenant_id'] = $tenantId;
        $columns = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);

        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);

        $params = [];
        foreach ($data as $column => $value) {
            $params[':' . $column] = $value;
        }
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where, int $tenantId): int
    {
        $setParts = [];
        $setParams = [];
        foreach ($data as $column => $value) {
            $placeholder = ':set_' . $column;
            $setParts[] = "`{$column}` = {$placeholder}";
            $setParams[$placeholder] = $value;
        }

        [$whereSql, $whereParams] = $this->buildWhere($where, $tenantId);
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($setParams + $whereParams);

        return $stmt->rowCount();
    }

    public function delete(string $table, array $where, int $tenantId): int
    {
        [$whereSql, $params] = $this->buildWhere($where, $tenantId);
        $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE {$whereSql}");
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function count(string $table, array $where, int $tenantId): int
    {
        [$whereSql, $params] = $this->buildWhere($where, $tenantId);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE {$whereSql}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function buildWhere(array $where, int $tenantId): array
    {
        $conditions = ['`tenant_id` = :tenant_id'];
        $params = [':tenant_id' => $tenantId];

        foreach ($where as $column => $value) {
            $placeholder = ':where_' . $column;
            $conditions[] = "`{$column}` = {$placeholder}";
            $params[$placeholder] = $value;
        }

        return [implode(' AND ', $conditions), $params];
    }
}
