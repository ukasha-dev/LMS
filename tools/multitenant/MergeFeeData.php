<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/StudentSessionIdResolver.php';

final class MergeFeeData extends AbstractTenantMerger
{
    public function run(): array
    {
        $feetypes = $this->fetchAll(
            'SELECT id, is_system, type, code, is_active, description, session_id, nature, created_at, updated_at FROM feetype'
        );
        $feesDiscounts = $this->fetchAll(
            'SELECT id, session_id, name, code, type, percentage, amount, discount_limit, expire_date, description, is_active, created_at, updated_at'
            . ' FROM fees_discounts'
        );
        $fsgRows = $this->fetchAll('SELECT id, fee_groups_id, session_id, is_active, created_at, updated_at FROM fee_session_groups');
        $fgfRows = $this->fetchAll(
            'SELECT id, fee_session_group_id, fee_groups_id, feetype_id, session_id, amount, fine_type, due_date,'
            . ' fine_percentage, fine_amount, fine_per_day, is_active, created_at, updated_at FROM fee_groups_feetype'
        );

        $referencedSessionIds = [];
        foreach ([$feetypes, $feesDiscounts, $fsgRows, $fgfRows] as $rowSet) {
            foreach ($rowSet as $row) {
                if ($row['session_id'] !== null) {
                    $referencedSessionIds[] = (int) $row['session_id'];
                }
            }
        }
        $sessionMap = $this->resolveReferencedSessionIds($referencedSessionIds);

        $feetypeRemap = new IdRemapper($this->nextId('feetype'));
        $feetypeSourceTotal = count($feetypes);
        $feetypeSkipped = 0;
        $feetypeRowsToInsert = [];
        foreach ($feetypes as $row) {
            $oldId = (int) $row['id'];
            $oldSessionId = $row['session_id'] !== null ? (int) $row['session_id'] : null;
            if ($oldSessionId !== null && !isset($sessionMap[$oldSessionId])) {
                $feetypeSkipped++;
                continue;
            }
            $feetypeRemap->remapId($oldId);
            $row['id'] = $feetypeRemap->getMapping($oldId);
            $row['session_id'] = $oldSessionId !== null ? $sessionMap[$oldSessionId] : null;
            $feetypeRowsToInsert[$oldId] = $row;
        }

        $feeGroupRemap = new IdRemapper($this->nextId('fee_groups'));
        $feeGroups = $this->fetchAll('SELECT id, name, is_system, description, nature, is_active, created_at, updated_at FROM fee_groups');
        foreach ($feeGroups as $row) {
            $feeGroupRemap->remapId((int) $row['id']);
        }

        $feesDiscountRemap = new IdRemapper($this->nextId('fees_discounts'));
        $feesDiscountSourceTotal = count($feesDiscounts);
        $feesDiscountSkipped = 0;
        $feesDiscountRowsToInsert = [];
        foreach ($feesDiscounts as $row) {
            $oldId = (int) $row['id'];
            $oldSessionId = $row['session_id'] !== null ? (int) $row['session_id'] : null;
            if ($oldSessionId !== null && !isset($sessionMap[$oldSessionId])) {
                $feesDiscountSkipped++;
                continue;
            }
            $feesDiscountRemap->remapId($oldId);
            $row['id'] = $feesDiscountRemap->getMapping($oldId);
            $row['session_id'] = $oldSessionId !== null ? $sessionMap[$oldSessionId] : null;
            $feesDiscountRowsToInsert[$oldId] = $row;
        }

        $fsgRemap = new IdRemapper($this->nextId('fee_session_groups'));
        $fsgSourceTotal = count($fsgRows);
        $fsgSkipped = 0;
        $fsgRowsToInsert = [];
        foreach ($fsgRows as $row) {
            $oldId = (int) $row['id'];
            $oldSessionId = $row['session_id'] !== null ? (int) $row['session_id'] : null;
            $oldFeeGroupId = $row['fee_groups_id'] !== null ? (int) $row['fee_groups_id'] : null;
            if ($oldSessionId !== null && !isset($sessionMap[$oldSessionId])) {
                $fsgSkipped++;
                continue;
            }
            $fsgRemap->remapId($oldId);
            $row['id'] = $fsgRemap->getMapping($oldId);
            $row['fee_groups_id'] = $oldFeeGroupId !== null ? $feeGroupRemap->getMapping($oldFeeGroupId) : null;
            $row['session_id'] = $oldSessionId !== null ? $sessionMap[$oldSessionId] : null;
            $fsgRowsToInsert[$oldId] = $row;
        }

        $fgfRemap = new IdRemapper($this->nextId('fee_groups_feetype'));
        $fgfSourceTotal = count($fgfRows);
        $fgfSkipped = 0;
        $fgfRowsToInsert = [];
        foreach ($fgfRows as $row) {
            $oldId = (int) $row['id'];
            $oldFsgId = $row['fee_session_group_id'] !== null ? (int) $row['fee_session_group_id'] : null;
            $oldFeeGroupId = $row['fee_groups_id'] !== null ? (int) $row['fee_groups_id'] : null;
            $oldFeetypeId = $row['feetype_id'] !== null ? (int) $row['feetype_id'] : null;
            $oldSessionId = $row['session_id'] !== null ? (int) $row['session_id'] : null;
            if (($oldFsgId !== null && !isset($fsgRowsToInsert[$oldFsgId]))
                || ($oldFeetypeId !== null && !isset($feetypeRowsToInsert[$oldFeetypeId]))
                || ($oldSessionId !== null && !isset($sessionMap[$oldSessionId]))
            ) {
                $fgfSkipped++;
                continue;
            }
            $fgfRemap->remapId($oldId);
            $row['id'] = $fgfRemap->getMapping($oldId);
            $row['fee_session_group_id'] = $oldFsgId !== null ? $fsgRemap->getMapping($oldFsgId) : null;
            $row['fee_groups_id'] = $oldFeeGroupId !== null ? $feeGroupRemap->getMapping($oldFeeGroupId) : null;
            $row['feetype_id'] = $oldFeetypeId !== null ? $feetypeRemap->getMapping($oldFeetypeId) : null;
            $row['session_id'] = $oldSessionId !== null ? $sessionMap[$oldSessionId] : null;
            $fgfRowsToInsert[$oldId] = $row;
        }

        $reminderRemap = new IdRemapper($this->nextId('fees_reminder'));
        $reminders = $this->fetchAll('SELECT id, reminder_type, day, is_active, created_at, updated_at FROM fees_reminder');
        foreach ($reminders as $row) {
            $reminderRemap->remapId((int) $row['id']);
        }

        $studentSessionResolver = new StudentSessionIdResolver();
        $studentSessionMap = $studentSessionResolver->resolve($this->source, $this->target, $this->tenantId);

        $sfmRemap = new IdRemapper($this->nextId('student_fees_master'));
        $sfmRows = $this->fetchAll(
            'SELECT id, is_system, student_session_id, fee_session_group_id, amount, pre_discount, is_active, created_at, updated_at'
            . ' FROM student_fees_master'
        );
        $sfmSourceTotal = count($sfmRows);
        $sfmSkipped = 0;
        $sfmRowsToInsert = [];
        foreach ($sfmRows as $row) {
            $oldId = (int) $row['id'];
            $oldStudentSessionId = $row['student_session_id'] !== null ? (int) $row['student_session_id'] : null;
            $oldFsgId = $row['fee_session_group_id'] !== null ? (int) $row['fee_session_group_id'] : null;
            if (($oldStudentSessionId !== null && !isset($studentSessionMap[$oldStudentSessionId]))
                || ($oldFsgId !== null && !isset($fsgRowsToInsert[$oldFsgId]))
            ) {
                $sfmSkipped++;
                continue;
            }
            $sfmRemap->remapId($oldId);
            $row['id'] = $sfmRemap->getMapping($oldId);
            $row['student_session_id'] = $oldStudentSessionId !== null ? $studentSessionMap[$oldStudentSessionId] : null;
            $row['fee_session_group_id'] = $oldFsgId !== null ? $fsgRemap->getMapping($oldFsgId) : null;
            $sfmRowsToInsert[$oldId] = $row;
        }

        $sfDiscRemap = new IdRemapper($this->nextId('student_fees_discounts'));
        $sfDiscRows = $this->fetchAll(
            'SELECT id, student_session_id, fees_discount_id, status, payment_id, description, is_active, created_at, updated_at'
            . ' FROM student_fees_discounts'
        );
        $sfDiscSourceTotal = count($sfDiscRows);
        $sfDiscSkipped = 0;
        $sfDiscRowsToInsert = [];
        foreach ($sfDiscRows as $row) {
            $oldId = (int) $row['id'];
            $oldStudentSessionId = $row['student_session_id'] !== null ? (int) $row['student_session_id'] : null;
            $oldFeesDiscountId = $row['fees_discount_id'] !== null ? (int) $row['fees_discount_id'] : null;
            if (($oldStudentSessionId !== null && !isset($studentSessionMap[$oldStudentSessionId]))
                || ($oldFeesDiscountId !== null && !isset($feesDiscountRowsToInsert[$oldFeesDiscountId]))
            ) {
                $sfDiscSkipped++;
                continue;
            }
            $sfDiscRemap->remapId($oldId);
            $row['id'] = $sfDiscRemap->getMapping($oldId);
            $row['student_session_id'] = $oldStudentSessionId !== null ? $studentSessionMap[$oldStudentSessionId] : null;
            $row['fees_discount_id'] = $oldFeesDiscountId !== null ? $feesDiscountRemap->getMapping($oldFeesDiscountId) : null;
            $sfDiscRowsToInsert[$oldId] = $row;
        }

        $sfDepositeRemap = new IdRemapper($this->nextId('student_fees_deposite'));
        $sfDepositeRows = $this->fetchAll(
            'SELECT id, student_fees_master_id, fee_groups_feetype_id, student_transport_fee_id, amount_detail, is_active, created_at, updated_at'
            . ' FROM student_fees_deposite'
        );
        $sfDepositeSourceTotal = count($sfDepositeRows);
        $sfDepositeSkipped = 0;
        $sfDepositeRowsToInsert = [];
        foreach ($sfDepositeRows as $row) {
            $oldId = (int) $row['id'];
            $oldMasterId = $row['student_fees_master_id'] !== null ? (int) $row['student_fees_master_id'] : null;
            $oldFgfId = $row['fee_groups_feetype_id'] !== null ? (int) $row['fee_groups_feetype_id'] : null;
            if (($oldMasterId !== null && !isset($sfmRowsToInsert[$oldMasterId]))
                || ($oldFgfId !== null && !isset($fgfRowsToInsert[$oldFgfId]))
            ) {
                $sfDepositeSkipped++;
                continue;
            }
            $sfDepositeRemap->remapId($oldId);
            $row['id'] = $sfDepositeRemap->getMapping($oldId);
            $row['student_fees_master_id'] = $oldMasterId !== null ? $sfmRemap->getMapping($oldMasterId) : null;
            $row['fee_groups_feetype_id'] = $oldFgfId !== null ? $fgfRemap->getMapping($oldFgfId) : null;
            // student_transport_fee_id intentionally passed through unresolved --
            // always NULL in real data (verified during planning); transport
            // fees are out of this stage's scope.
            $sfDepositeRowsToInsert[$oldId] = $row;
        }

        $sadRemap = new IdRemapper($this->nextId('student_applied_discounts'));
        $sadRows = $this->fetchAll(
            'SELECT id, student_fees_deposite_id, student_fees_discount_id, date, invoice_id, sub_invoice_id, created_at, updated_at'
            . ' FROM student_applied_discounts'
        );
        $sadSourceTotal = count($sadRows);
        $sadSkipped = 0;
        $sadRowsToInsert = [];
        foreach ($sadRows as $row) {
            $oldId = (int) $row['id'];
            $oldDepositeId = $row['student_fees_deposite_id'] !== null ? (int) $row['student_fees_deposite_id'] : null;
            $oldDiscountId = $row['student_fees_discount_id'] !== null ? (int) $row['student_fees_discount_id'] : null;
            if (($oldDepositeId !== null && !isset($sfDepositeRowsToInsert[$oldDepositeId]))
                || ($oldDiscountId !== null && !isset($sfDiscRowsToInsert[$oldDiscountId]))
            ) {
                $sadSkipped++;
                continue;
            }
            $sadRemap->remapId($oldId);
            $row['id'] = $sadRemap->getMapping($oldId);
            $row['student_fees_deposite_id'] = $oldDepositeId !== null ? $sfDepositeRemap->getMapping($oldDepositeId) : null;
            $row['student_fees_discount_id'] = $oldDiscountId !== null ? $sfDiscRemap->getMapping($oldDiscountId) : null;
            $sadRowsToInsert[$oldId] = $row;
        }

        $this->inTransaction(function () use (
            $feetypeRowsToInsert, $feeGroups, $feesDiscountRowsToInsert, $fsgRowsToInsert, $fgfRowsToInsert, $reminders,
            $feeGroupRemap, $reminderRemap, $sfmRowsToInsert, $sfDiscRowsToInsert, $sfDepositeRowsToInsert, $sadRowsToInsert
        ) {
            foreach ($feetypeRowsToInsert as $row) {
                $this->insertRow('feetype', $row);
            }
            foreach ($feeGroups as $row) {
                $row['id'] = $feeGroupRemap->getMapping((int) $row['id']);
                $this->insertRow('fee_groups', $row);
            }
            foreach ($feesDiscountRowsToInsert as $row) {
                $this->insertRow('fees_discounts', $row);
            }
            foreach ($fsgRowsToInsert as $row) {
                $this->insertRow('fee_session_groups', $row);
            }
            foreach ($fgfRowsToInsert as $row) {
                $this->insertRow('fee_groups_feetype', $row);
            }
            foreach ($reminders as $row) {
                $row['id'] = $reminderRemap->getMapping((int) $row['id']);
                $this->insertRow('fees_reminder', $row);
            }
            foreach ($sfmRowsToInsert as $row) {
                $this->insertRow('student_fees_master', $row);
            }
            foreach ($sfDiscRowsToInsert as $row) {
                $this->insertRow('student_fees_discounts', $row);
            }
            foreach ($sfDepositeRowsToInsert as $row) {
                $this->insertRow('student_fees_deposite', $row);
            }
            foreach ($sadRowsToInsert as $row) {
                $this->insertRow('student_applied_discounts', $row);
            }
        });

        return [
            'feetype_migrated' => count($feetypeRowsToInsert),
            'feetype_source_total' => $feetypeSourceTotal,
            'feetype_skipped' => $feetypeSkipped,
            'fee_groups_migrated' => count($feeGroups),
            'fees_discounts_migrated' => count($feesDiscountRowsToInsert),
            'fees_discounts_source_total' => $feesDiscountSourceTotal,
            'fees_discounts_skipped' => $feesDiscountSkipped,
            'fee_session_groups_migrated' => count($fsgRowsToInsert),
            'fee_session_groups_source_total' => $fsgSourceTotal,
            'fee_session_groups_skipped' => $fsgSkipped,
            'fee_groups_feetype_migrated' => count($fgfRowsToInsert),
            'fee_groups_feetype_source_total' => $fgfSourceTotal,
            'fee_groups_feetype_skipped' => $fgfSkipped,
            'fees_reminder_migrated' => count($reminders),
            'student_fees_master_migrated' => count($sfmRowsToInsert),
            'student_fees_master_source_total' => $sfmSourceTotal,
            'student_fees_master_skipped' => $sfmSkipped,
            'student_fees_discounts_migrated' => count($sfDiscRowsToInsert),
            'student_fees_discounts_source_total' => $sfDiscSourceTotal,
            'student_fees_discounts_skipped' => $sfDiscSkipped,
            'student_fees_deposite_migrated' => count($sfDepositeRowsToInsert),
            'student_fees_deposite_source_total' => $sfDepositeSourceTotal,
            'student_fees_deposite_skipped' => $sfDepositeSkipped,
            'student_applied_discounts_migrated' => count($sadRowsToInsert),
            'student_applied_discounts_source_total' => $sadSourceTotal,
            'student_applied_discounts_skipped' => $sadSkipped,
        ];
    }

    private function resolveReferencedSessionIds(array $oldSessionIds): array
    {
        $oldSessionIds = array_values(array_unique($oldSessionIds));
        if ($oldSessionIds === []) {
            return [];
        }

        $sourceRows = $this->fetchAll('SELECT id, session FROM sessions');
        $sourceNameById = [];
        foreach ($sourceRows as $row) {
            $sourceNameById[(int) $row['id']] = $row['session'];
        }

        $targetStmt = $this->target->prepare('SELECT id, session FROM sessions WHERE tenant_id = :tenant_id');
        $targetStmt->execute([':tenant_id' => $this->tenantId]);
        $targetRows = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

        $oldToNew = [];
        foreach ($oldSessionIds as $oldId) {
            $name = $sourceNameById[$oldId] ?? null;
            if ($name === null) {
                continue;
            }
            $matches = array_values(array_filter($targetRows, static fn ($row) => $row['session'] === $name));
            if (count($matches) > 1) {
                throw new RuntimeException(
                    "Ambiguous natural key: multiple distinct ids share the value \"{$name}\" in column \"session\""
                    . " of table \"sessions\" — cannot safely resolve. Manual investigation required."
                );
            }
            if (count($matches) === 1) {
                $oldToNew[$oldId] = (int) $matches[0]['id'];
            }
        }

        return $oldToNew;
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeFeeData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeFeeData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['feetype_migrated']} fee types, {$result['fee_groups_migrated']} fee groups,"
        . " {$result['fees_discounts_migrated']} fee discounts, {$result['fee_session_groups_migrated']} fee session groups,"
        . " {$result['fee_groups_feetype_migrated']} fee group pricing rows, {$result['fees_reminder_migrated']} fee reminders,"
        . " {$result['student_fees_master_migrated']} student fee assignments, {$result['student_fees_deposite_migrated']} student fee deposits,"
        . " {$result['student_fees_discounts_migrated']} student fee discounts, and {$result['student_applied_discounts_migrated']} applied discounts"
        . " for tenant {$tenantId}.\n";

    $skipChecks = [
        'feetype' => [$result['feetype_skipped'], $result['feetype_source_total']],
        'fees_discounts' => [$result['fees_discounts_skipped'], $result['fees_discounts_source_total']],
        'fee_session_groups' => [$result['fee_session_groups_skipped'], $result['fee_session_groups_source_total']],
        'fee_groups_feetype' => [$result['fee_groups_feetype_skipped'], $result['fee_groups_feetype_source_total']],
        'student_fees_master' => [$result['student_fees_master_skipped'], $result['student_fees_master_source_total']],
        'student_fees_discounts' => [$result['student_fees_discounts_skipped'], $result['student_fees_discounts_source_total']],
        'student_fees_deposite' => [$result['student_fees_deposite_skipped'], $result['student_fees_deposite_source_total']],
        'student_applied_discounts' => [$result['student_applied_discounts_skipped'], $result['student_applied_discounts_source_total']],
    ];
    foreach ($skipChecks as $label => [$skipped, $sourceTotal]) {
        if ($skipped > 0) {
            fwrite(
                STDERR,
                "WARNING: {$skipped} of {$sourceTotal} {$label} rows could not be resolved and were skipped."
                . " Investigate before trusting this migration.\n"
            );
        }
    }
}
