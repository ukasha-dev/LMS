<?php

final class NaturalKeyIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId, string $table, string $naturalKeyColumn): array
    {
        $sourceMap = [];
        $sourceStmt = $source->query("SELECT id, `{$naturalKeyColumn}` AS natural_key FROM `{$table}`");
        foreach ($sourceStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['natural_key'] !== null && $row['natural_key'] !== '') {
                $sourceMap[$row['natural_key']] = (int) $row['id'];
            }
        }

        $targetStmt = $target->prepare(
            "SELECT id, `{$naturalKeyColumn}` AS natural_key FROM `{$table}` WHERE tenant_id = :tenant_id"
        );
        $targetStmt->execute([':tenant_id' => $tenantId]);

        $oldToNew = [];
        foreach ($targetStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = $row['natural_key'];
            if ($key !== null && $key !== '' && isset($sourceMap[$key])) {
                $oldToNew[$sourceMap[$key]] = (int) $row['id'];
            }
        }

        return $oldToNew;
    }
}
