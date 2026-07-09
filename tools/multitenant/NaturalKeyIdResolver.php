<?php

final class NaturalKeyIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId, string $table, string $naturalKeyColumn): array
    {
        $sourceMap = [];
        $sourceStmt = $source->query("SELECT id, `{$naturalKeyColumn}` AS natural_key FROM `{$table}`");
        foreach ($sourceStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = $row['natural_key'];
            if ($key === null || $key === '') {
                continue;
            }
            $id = (int) $row['id'];
            if (isset($sourceMap[$key]) && $sourceMap[$key] !== $id) {
                throw new RuntimeException(
                    "Ambiguous natural key: multiple distinct ids share the value \"{$key}\" in column \"{$naturalKeyColumn}\" of table \"{$table}\" — cannot safely resolve. Manual investigation required."
                );
            }
            $sourceMap[$key] = $id;
        }

        $targetStmt = $target->prepare(
            "SELECT id, `{$naturalKeyColumn}` AS natural_key FROM `{$table}` WHERE tenant_id = :tenant_id"
        );
        $targetStmt->execute([':tenant_id' => $tenantId]);

        $targetMap = [];
        foreach ($targetStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = $row['natural_key'];
            if ($key === null || $key === '') {
                continue;
            }
            $id = (int) $row['id'];
            if (isset($targetMap[$key]) && $targetMap[$key] !== $id) {
                throw new RuntimeException(
                    "Ambiguous natural key: multiple distinct ids share the value \"{$key}\" in column \"{$naturalKeyColumn}\" of table \"{$table}\" — cannot safely resolve. Manual investigation required."
                );
            }
            $targetMap[$key] = $id;
        }

        $oldToNew = [];
        foreach ($targetMap as $key => $newId) {
            if (isset($sourceMap[$key])) {
                $oldToNew[$sourceMap[$key]] = $newId;
            }
        }

        return $oldToNew;
    }
}
