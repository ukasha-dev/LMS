<?php

abstract class AbstractTenantMerger
{
    protected PDO $source;
    protected PDO $target;
    protected int $tenantId;

    public function __construct(PDO $source, PDO $target, int $tenantId)
    {
        $this->source = $source;
        $this->target = $target;
        $this->tenantId = $tenantId;
    }

    abstract public function run(): array;

    protected function nextId(string $table): int
    {
        $stmt = $this->target->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `{$table}`");

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
    }

    protected function fetchAll(string $sql): array
    {
        return $this->source->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function insertRow(string $table, array $row): void
    {
        $row['tenant_id'] = $this->tenantId;
        $columns = array_keys($row);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);

        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->target->prepare($sql);

        $params = [];
        foreach ($row as $column => $value) {
            $params[':' . $column] = $value;
        }
        $stmt->execute($params);
    }

    protected function inTransaction(callable $work): void
    {
        $this->target->beginTransaction();
        try {
            $work();
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }
    }

    protected function guardAgainstExistingData(string ...$tables): void
    {
        foreach ($tables as $table) {
            $stmt = $this->target->prepare("SELECT COUNT(*) AS c FROM `{$table}` WHERE tenant_id = :tenant_id");
            $stmt->execute([':tenant_id' => $this->tenantId]);
            $count = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];

            if ($count > 0) {
                throw new RuntimeException(
                    "Refusing to run: tenant {$this->tenantId} already has {$count} row(s) in `{$table}`. "
                    . 'This tool has no re-run/resume support -- re-running would duplicate data '
                    . '(this is exactly the bug that duplicated tenant 25\'s attendance rows on 2026-07-10). '
                    . "If this is intentional (e.g. recovering from a partial run), delete the tenant's "
                    . "existing rows in `{$table}` first, or extend this tool with real upsert/resume logic."
                );
            }
        }
    }
}
