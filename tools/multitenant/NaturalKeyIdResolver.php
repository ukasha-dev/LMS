<?php

final class NaturalKeyIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId, string $table, string $naturalKeyColumn): array
    {
        $sourceMap = $this->buildKeyToIdMap($source, $table, $naturalKeyColumn, null, null);
        $targetMap = $this->buildKeyToIdMap($target, $table, $naturalKeyColumn, 'tenant_id', $tenantId);

        $oldToNew = [];
        foreach ($targetMap as $key => $newId) {
            if (isset($sourceMap[$key])) {
                $oldToNew[$sourceMap[$key]] = $newId;
            }
        }

        return $oldToNew;
    }

    /**
     * @param mixed $whereValue
     */
    private function buildKeyToIdMap(PDO $pdo, string $table, string $naturalKeyColumn, ?string $whereColumn, $whereValue): array
    {
        $hasActiveColumn = $this->hasColumn($pdo, $table, 'is_active');

        $sql = "SELECT id, `{$naturalKeyColumn}` AS natural_key" . ($hasActiveColumn ? ', is_active' : '') . " FROM `{$table}`";
        if ($whereColumn !== null) {
            $sql .= " WHERE `{$whereColumn}` = :where_value";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($whereColumn !== null ? [':where_value' => $whereValue] : []);

        $candidatesByKey = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = $row['natural_key'];
            if ($key === null || $key === '') {
                continue;
            }
            $candidatesByKey[$key][] = [
                'id' => (int) $row['id'],
                'active' => $hasActiveColumn ? $this->isActiveValue($row['is_active']) : null,
            ];
        }

        $map = [];
        foreach ($candidatesByKey as $key => $candidates) {
            $distinctIds = array_values(array_unique(array_column($candidates, 'id')));
            if (count($distinctIds) === 1) {
                $map[$key] = $distinctIds[0];
                continue;
            }

            // Multiple distinct ids share this natural key. Real school data
            // has duplicate admission numbers/emails from data-entry mistakes
            // that get corrected by deactivating the stale row rather than
            // deleting it. If the table has an is_active column and EXACTLY
            // ONE of the duplicates is active, that's almost certainly the
            // genuine record -- prefer it and silently drop the inactive
            // sibling(s) from the map (their own rows simply won't resolve
            // downstream, which every caller already treats as a normal,
            // warned skip -- see MergeFeeData/MergeAttendanceData's "could
            // not be resolved" counters). If none are active, there is no
            // live record this key could mean, so the key is dropped
            // entirely rather than guessed -- same "unmatched, simply
            // absent" outcome as a key with no match at all. Only genuine,
            // dangerous ambiguity (no is_active signal at all, or more than
            // one active candidate) still throws, forcing manual review.
            if ($hasActiveColumn) {
                $activeIds = array_values(array_unique(array_column(
                    array_filter($candidates, static fn (array $c): bool => $c['active'] === true),
                    'id'
                )));
                if (count($activeIds) === 1) {
                    $map[$key] = $activeIds[0];
                    continue;
                }
                if (count($activeIds) === 0) {
                    continue;
                }
            }

            throw new RuntimeException(
                "Ambiguous natural key: multiple distinct ids share the value \"{$key}\" in column \"{$naturalKeyColumn}\" of table \"{$table}\" — cannot safely resolve. Manual investigation required."
            );
        }

        return $map;
    }

    private function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns '
            . 'WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
        );
        $stmt->execute([':table' => $table, ':column' => $column]);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    private function isActiveValue($value): bool
    {
        if (is_string($value)) {
            return strtolower(trim($value)) === 'yes' || trim($value) === '1';
        }

        return ((int) $value) === 1;
    }
}
