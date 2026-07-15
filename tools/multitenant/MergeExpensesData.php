<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergeExpensesData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('expenses');

        $expenses = $this->fetchAll(
            'SELECT id, exp_head_id, name, invoice_no, date, amount, documents, note, is_active, is_deleted,'
            . ' created_at, updated_at FROM expenses'
        );
        $sourceTotal = count($expenses);

        $referencedExpenseHeadIds = array_values(array_unique(array_filter(
            array_map(static fn ($row) => $row['exp_head_id'] !== null ? (int) $row['exp_head_id'] : null, $expenses)
        )));
        $expenseHeadMap = $this->resolveReferencedExpenseHeadIds($referencedExpenseHeadIds);

        $skipped = 0;
        $remap = new IdRemapper($this->nextId('expenses'));
        $rowsToInsert = [];
        foreach ($expenses as $row) {
            $oldExpenseHeadId = $row['exp_head_id'] !== null ? (int) $row['exp_head_id'] : null;
            if ($oldExpenseHeadId !== null && !isset($expenseHeadMap[$oldExpenseHeadId])) {
                $skipped++;
                continue;
            }
            $oldId = (int) $row['id'];
            $remap->remapId($oldId);
            $row['id'] = $remap->getMapping($oldId);
            $row['exp_head_id'] = $oldExpenseHeadId !== null ? $expenseHeadMap[$oldExpenseHeadId] : null;
            $rowsToInsert[] = $row;
        }

        $this->inTransaction(function () use ($rowsToInsert) {
            foreach ($rowsToInsert as $row) {
                $this->insertRow('expenses', $row);
            }
        });

        return [
            'expenses_migrated' => count($rowsToInsert),
            'expenses_source_total' => $sourceTotal,
            'expenses_skipped' => $skipped,
        ];
    }

    // expense_head was migrated earlier (MergeStandaloneTenantData) with
    // freshly assigned ids -- source and target exp_head_id values do not
    // match, so exp_head_id must be resolved by natural key (exp_category).
    // Deliberately lazy (only resolves ids actually referenced by the
    // expenses being migrated), same as MergeFeeData::
    // resolveReferencedSessionIds -- an unrelated duplicate exp_category
    // elsewhere in the table must not block a migration that never
    // references it. Same is_active tiebreak as every other resolver in
    // this migration: prefer the one active match, skip (not throw) when
    // none are active, only throw on genuine multi-active ambiguity.
    private function resolveReferencedExpenseHeadIds(array $oldExpenseHeadIds): array
    {
        if ($oldExpenseHeadIds === []) {
            return [];
        }

        $sourceRows = $this->fetchAll('SELECT id, exp_category FROM expense_head');
        $sourceCategoryById = [];
        foreach ($sourceRows as $row) {
            $sourceCategoryById[(int) $row['id']] = $row['exp_category'];
        }

        $targetStmt = $this->target->prepare('SELECT id, exp_category, is_active FROM expense_head WHERE tenant_id = :tenant_id');
        $targetStmt->execute([':tenant_id' => $this->tenantId]);
        $targetRows = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

        $oldToNew = [];
        foreach ($oldExpenseHeadIds as $oldId) {
            $category = $sourceCategoryById[$oldId] ?? null;
            if ($category === null) {
                continue;
            }
            $matches = array_values(array_filter($targetRows, static fn ($row) => $row['exp_category'] === $category));
            if (count($matches) === 1) {
                $oldToNew[$oldId] = (int) $matches[0]['id'];
                continue;
            }
            if (count($matches) > 1) {
                $activeMatches = array_values(array_filter(
                    $matches,
                    fn ($row) => $this->isActiveValue($row['is_active'])
                ));
                if (count($activeMatches) === 1) {
                    $oldToNew[$oldId] = (int) $activeMatches[0]['id'];
                    continue;
                }
                if (count($activeMatches) === 0) {
                    continue;
                }

                throw new RuntimeException(
                    "Ambiguous natural key: multiple distinct ids share the value \"{$category}\" in column \"exp_category\""
                    . " of table \"expense_head\" — cannot safely resolve. Manual investigation required."
                );
            }
        }

        return $oldToNew;
    }

    private function isActiveValue($value): bool
    {
        if (is_string($value)) {
            return strtolower(trim($value)) === 'yes' || trim($value) === '1';
        }

        return ((int) $value) === 1;
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeExpensesData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeExpensesData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['expenses_migrated']} expenses for tenant {$tenantId}.\n";

    if ($result['expenses_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['expenses_skipped']} of {$result['expenses_source_total']} expenses"
            . " could not be resolved and were skipped. Investigate before trusting this migration.\n"
        );
    }
}
