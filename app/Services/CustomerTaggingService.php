<?php

namespace App\Services;

use App\Models\Customer;
use App\Services\AuditLogger;
use App\Models\CustomerTagging;
use App\Models\SalesVisitPlan;
use App\Support\DocumentCode;
use Illuminate\Support\Collection;
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
    public function __construct(protected CustomerAssignmentService $assignmentService) {}

    /**
     * Sales mengirim data tagging dari lapangan.
     */
    public function submit(int $salesId, array $data): CustomerTagging
    {
        $tagging = CustomerTagging::create([
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

        AuditLogger::log('create', 'Sales', CustomerTagging::class, $tagging->id, null, $tagging->toArray());

        return $tagging;
    }

    /**
     * Kemungkinan duplikasi berdasarkan nama toko + nomor telepon yang
     * sama pada customer yang sudah ada. Dipakai untuk peringatan di UI
     * sebelum submit, bukan hard-block (validasi akhir tetap di Admin).
     */
    public function findPossibleDuplicates(string $name, ?string $phone): Collection
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
                    'code' => 'TEMP',
                    'name' => $tagging->name,
                    'address' => $tagging->address,
                    'phone' => $tagging->phone,
                    // Business Flow fix: koordinat hasil GPS Sales di lapangan
                    // WAJIB ikut disalin ke Customer, bukan cuma tersimpan di
                    // customer_taggings. Tanpa ini, Customer::hasLocation()
                    // selalu false dan Customer TIDAK PERNAH muncul di Peta
                    // Customer (Sales\MapController::index() memfilter hanya
                    // customer yang punya latitude/longitude), walau tagging-nya
                    // sendiri sudah Approved dan koordinatnya valid.
                    'latitude' => $tagging->latitude,
                    'longitude' => $tagging->longitude,
                    // 'verified' karena koordinat ini sudah melalui proses
                    // review Admin (approve tagging), bukan sekadar submit
                    // mentah dari Sales yang belum divalidasi siapa pun.
                    'location_status' => $tagging->latitude !== null ? 'verified' : null,
                    'is_active' => true,
                ]);
                $customer->update(['code' => DocumentCode::make('CUST', $customer->id)]);

                // Customer Assignment (Blueprint #729): tercatat sebagai
                // assignment resmi pertama, bukan sekadar kolom sales_id.
                $this->assignmentService->assign(
                    $customer,
                    $tagging->sales_id,
                    $reviewerId,
                    "Tagging Toko #{$tagging->id} disetujui",
                );

                $tagging->customer_id = $customer->id;

                // Otomasi Visit Plan "Rute Kanvas" (penyempurnaan Tagging
                // Toko): outlet yang di-tag pada hari X otomatis ikut jadi
                // bagian Rute Kanvas hari X untuk Sales tsb, supaya setiap
                // minggu berikutnya toko ini otomatis kebentuk di rute --
                // tidak menunggu Admin isi manual di Visit Plan.
                //
                // Sengaja HANYA menulis ke sales_visit_plans (template
                // mingguan). Tidak menyentuh SalesTaskCustomer/SalesRoute/
                // RouteStop -- itu tetap murni urusan SalesTaskController::
                // store() (fallback SalesVisitPlan::forDay() yang sudah ada)
                // dan RouteController (mobile), tidak diubah sama sekali.
                $this->autoAddToVisitPlan($customer->id, $tagging->sales_id, $tagging->tagged_at);
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

    /**
     * Sisipkan outlet ke Rute Kanvas (sales_visit_plans) pada hari sesuai
     * $referenceDate (dipakai dengan $tagging->tagged_at -- hari toko
     * DITEMUKAN di lapangan, bukan hari Admin approve). Idempotent lewat
     * firstOrCreate: aman dipanggil ulang (mis. tagging yang sama entah
     * bagaimana diproses dua kali) karena constraint unik
     * uniq_visit_plan_slot (sales_id, day_of_week, customer_id) tetap
     * berlaku sebagai pengaman terakhir.
     */
    private function autoAddToVisitPlan(int $customerId, int $salesId, \DateTimeInterface $referenceDate): void
    {
        $dayOfWeekIso = \Illuminate\Support\Carbon::parse($referenceDate)->dayOfWeekIso;

        $nextSequence = 1 + (int) SalesVisitPlan::where('sales_id', $salesId)
            ->where('day_of_week', $dayOfWeekIso)
            ->max('sequence');

        SalesVisitPlan::firstOrCreate(
            ['sales_id' => $salesId, 'day_of_week' => $dayOfWeekIso, 'customer_id' => $customerId],
            ['sequence' => $nextSequence, 'source' => 'tagging'],
        );
    }
}
