<?php

namespace App\Services;

use App\Models\CashLedger;
use App\Support\DocumentCode;

/**
 * Phase 7 - Income & Expense (Blueprint #37): pembukuan umum
 * perusahaan di luar transaksi penjualan.
 */
class CashLedgerService
{
    public function create(array $data, ?int $createdByUserId): CashLedger
    {
        $entry = CashLedger::create([
            'code' => 'TEMP',
            'type' => $data['type'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'date' => $data['date'],
            'description' => $data['description'] ?? null,
            'created_by' => $createdByUserId,
        ]);
        $entry->update(['code' => DocumentCode::make(strtoupper(substr($data['type'], 0, 3)), $entry->id)]);

        AuditLogger::log(
            action: 'create',
            module: 'Finance',
            documentType: CashLedger::class,
            documentId: $entry->id,
            after: $entry->fresh()->toArray(),
            userId: $createdByUserId,
        );

        return $entry;
    }

    public function delete(CashLedger $entry, ?int $byUserId): void
    {
        $before = $entry->toArray();
        $entry->delete();

        AuditLogger::log(
            action: 'delete',
            module: 'Finance',
            documentType: CashLedger::class,
            documentId: $entry->id,
            before: $before,
            userId: $byUserId,
        );
    }
}
