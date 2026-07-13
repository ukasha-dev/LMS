<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';
require_once __DIR__ . '/StudentSessionIdResolver.php';

final class MergeFeeData extends AbstractTenantMerger
{
    public function run(): array
    {
        $sessionResolver = new NaturalKeyIdResolver();
        $sessionMap = $sessionResolver->resolve($this->source, $this->target, $this->tenantId, 'sessions', 'session');

        $feetypeRemap = new IdRemapper($this->nextId('feetype'));
        $feetypes = $this->fetchAll(
            'SELECT id, is_system, type, code, is_active, description, session_id, nature, created_at, updated_at FROM feetype'
        );
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
        $feesDiscounts = $this->fetchAll(
            'SELECT id, session_id, name, code, type, percentage, amount, discount_limit, expire_date, description, is_active, created_at, updated_at'
            . ' FROM fees_discounts'
        );
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
        $fsgRows = $this->fetchAll('SELECT id, fee_groups_id, session_id, is_active, created_at, updated_at FROM fee_session_groups');
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
        $fgfRows = $this->fetchAll(
            'SELECT id, fee_session_group_id, fee_groups_id, feetype_id, session_id, amount, fine_type, due_date,'
            . ' fine_percentage, fine_amount, fine_per_day, is_active, created_at, updated_at FROM fee_groups_feetype'
        );
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

        $this->inTransaction(function () use (
            $feetypeRowsToInsert, $feeGroups, $feesDiscountRowsToInsert, $fsgRowsToInsert, $fgfRowsToInsert, $reminders,
            $feeGroupRemap, $reminderRemap
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
        ];
    }
}
