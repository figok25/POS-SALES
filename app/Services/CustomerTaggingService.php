<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerTagging;
use App\Support\DocumentCode;
use Illuminate\Support\Facades\DB;

/**
 * Phase 6 - Tagging Toko (Blueprint #12.3).
 *
 * Submit tidak langsung membuat Customer. Admin harus approve agar
 * data yang masuk ke master Customer sudah divalidasi, mencegah
 * duplikasi ("Tagging tidak boleh menyebabkan duplikasi customer
 * tanpa validasi").
 */
class CustomerTaggingService
{
    /**
     * Sales mengirim data tagging dari lapangan.
     */
    public function submit(int $salesId, array $data): CustomerTagging
    {
        return CustomerTagging::create([
            'sales_id' => $salesId,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'customer_type' => $data['customer_type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => CustomerTagging::STATUS_PENDING,
            'tagged_at' => now(),
        ]);
    }

    /**
     * Kemungkinan duplikasi berdasarkan nama toko + nomor telepon yang
     * sama pada customer yang sudah ada. Dipakai untuk peringatan di UI
     * sebelum submit, bukan hard-block (validasi akhir tetap di Admin).
     */
    public function findPossibleDuplicates(string $name, ?string $phone): \Illuminate\Support\Collection
    {
        return Customer::query()
            ->where(function ($q) use ($name, $phone) {
                $q->where('name', 'like', "%{$name}%");
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->limit(5)
            ->get();
    }

    /**
     * Admin menyetujui tagging: membuat (atau menautkan) Customer resmi.
     */
    public function approve(CustomerTagging $tagging, int $reviewerId, ?string $reviewNotes = null): CustomerTagging
    {
        return DB::transaction(function () use ($tagging, $reviewerId, $reviewNotes) {
            if (! $tagging->customer_id) {
                $customer = Customer::create([
                    'sales_id' => $tagging->sales_id,
                    'code' => 'TEMP',
                    'name' => $tagging->name,
                    'address' => $tagging->address,
                    'phone' => $tagging->phone,
                    'is_active' => true,
                ]);
                $customer->update(['code' => DocumentCode::make('CUST', $customer->id)]);

                $tagging->customer_id = $customer->id;
            }

            $tagging->status = CustomerTagging::STATUS_APPROVED;
            $tagging->reviewed_by = $reviewerId;
            $tagging->reviewed_at = now();
            $tagging->review_notes = $reviewNotes;
            $tagging->save();

            return $tagging;
        });
    }

    public function reject(CustomerTagging $tagging, int $reviewerId, ?string $reviewNotes = null): CustomerTagging
    {
        $tagging->update([
            'status' => CustomerTagging::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $reviewNotes,
        ]);

        return $tagging;
    }
}
