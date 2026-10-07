<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sales;
use App\Services\AuditLogger;
use App\Models\CustomerTagging;
use App\Models\SalesVisitPlan;
use App\Support\DocumentCode;
use App\Support\Geo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phase 6 - Tagging Toko (Blueprint #12.3).
 *
 * Tagging TIDAK perlu approval Admin lagi, kecuali terindikasi duplikat:
 *  - Tidak duplikat  -> langsung menjadi Customer resmi (assignment ke Sales
 *    yang men-tagging + masuk Rute Kanvas), ditandai auto_approved.
 *  - Terindikasi duplikat -> ditahan (pending) dengan alasannya, menunggu
 *    keputusan Admin (approve / reject), sehingga tetap memenuhi aturan
 *    "Tagging tidak boleh menyebabkan duplikasi customer tanpa validasi".
 *
 * Definisi duplikat (sengaja ketat supaya tidak banyak salah tahan):
 *  1. Nomor telepon sama persis (setelah dirapikan: hanya angka, +62 = 0)
 *     dengan Customer di Depo yang sama atau tagging lain yang masih menunggu.
 *  2. Nama mirip (>= 85%) DAN berjarak <= 100 meter dari Customer di Depo
 *     yang sama.
 */
class CustomerTaggingService
{
    /** Nomor telepon yang lebih pendek dari ini diabaikan (bukan nomor sungguhan). */
    private const MIN_PHONE_DIGITS = 8;

    private const DUPLICATE_RADIUS_METERS = 100;

    private const NAME_SIMILARITY_PERCENT = 85;

    public function __construct(protected CustomerAssignmentService $assignmentService) {}

    /**
     * Sales mengirim data tagging dari lapangan. Tidak duplikat -> langsung
     * jadi Customer; duplikat -> ditahan untuk review Admin.
     */
    public function submit(int $salesId, array $data): CustomerTagging
    {
        return DB::transaction(function () use ($salesId, $data) {
            $duplicate = $this->detectDuplicate($salesId, $data);

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
                'duplicate_customer_id' => $duplicate['customer_id'] ?? null,
                'duplicate_reason' => $duplicate['reason'] ?? null,
                'tagged_at' => now(),
            ]);

            AuditLogger::log('create', 'Sales', CustomerTagging::class, $tagging->id, null, $tagging->toArray());

            if ($duplicate === null) {
                $tagging = $this->autoApprove($tagging);
            }

            return $tagging->fresh();
        });
    }

    /**
     * Setujui otomatis oleh sistem (tanpa Admin): sama persis dengan approve()
     * biasa, hanya tanpa reviewer dan ditandai auto_approved.
     */
    public function autoApprove(CustomerTagging $tagging): CustomerTagging
    {
        $approved = $this->approve($tagging, null, 'Disetujui otomatis: tidak terindikasi duplikat.');
        $approved->update(['auto_approved' => true]);

        AuditLogger::log('auto_approve', 'Sales', CustomerTagging::class, $approved->id, null, $approved->toArray());

        return $approved;
    }

    /**
     * Tandai tagging pending sebagai terindikasi duplikat (dipakai pemrosesan
     * pending lama). Tetap pending, menunggu Admin.
     *
     * @param  array{customer_id: ?int, reason: string}  $duplicate
     */
    public function flagDuplicate(CustomerTagging $tagging, array $duplicate): CustomerTagging
    {
        $tagging->update([
            'duplicate_customer_id' => $duplicate['customer_id'] ?? null,
            'duplicate_reason' => $duplicate['reason'],
        ]);

        return $tagging;
    }

    /**
     * Deteksi duplikat (lihat aturan di docblock kelas). Mengembalikan null bila
     * tidak ada indikasi duplikat.
     *
     * $ignoreTaggingId: dipakai saat memproses tagging yang SUDAH tersimpan --
     * tagging itu sendiri dan tagging pending yang LEBIH BARU tidak dihitung,
     * sehingga yang lebih lama menang dan yang lebih baru yang ditahan.
     *
     * @return array{customer_id: ?int, reason: string}|null
     */
    public function detectDuplicate(int $salesId, array $data, ?int $ignoreTaggingId = null): ?array
    {
        $branchId = Sales::find($salesId)?->branch_id;

        // 1) Nomor telepon sama persis.
        $digits = $this->phoneDigits($data['phone'] ?? null);

        if (strlen($digits) >= self::MIN_PHONE_DIGITS) {
            $customer = Customer::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereNotNull('phone')
                ->get(['id', 'code', 'name', 'phone'])
                ->first(fn ($c) => $this->phoneDigits($c->phone) === $digits);

            if ($customer) {
                return [
                    'customer_id' => $customer->id,
                    'reason' => "Nomor telepon sama dengan {$customer->code} - {$customer->name}",
                ];
            }

            $pending = CustomerTagging::query()
                ->where('status', CustomerTagging::STATUS_PENDING)
                ->whereNotNull('phone')
                ->when($ignoreTaggingId, fn ($q) => $q->where('id', '<', $ignoreTaggingId))
                ->when($branchId, fn ($q) => $q->whereHas('sales', fn ($s) => $s->where('branch_id', $branchId)))
                ->get(['id', 'name', 'phone'])
                ->first(fn ($t) => $this->phoneDigits($t->phone) === $digits);

            if ($pending) {
                return [
                    'customer_id' => null,
                    'reason' => "Nomor telepon sama dengan tagging lain yang masih menunggu ({$pending->name})",
                ];
            }
        }

        // 2) Nama mirip dan berdekatan (<= 100 m).
        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;

        if (is_numeric($lat) && is_numeric($lng)) {
            $lat = (float) $lat;
            $lng = (float) $lng;
            $delta = 0.0015; // ~165 m, batas kasar sebelum jarak sebenarnya dihitung

            $candidates = Customer::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereBetween('latitude', [$lat - $delta, $lat + $delta])
                ->whereBetween('longitude', [$lng - $delta, $lng + $delta])
                ->get(['id', 'code', 'name', 'latitude', 'longitude']);

            foreach ($candidates as $candidate) {
                $distance = Geo::distanceMeters($lat, $lng, (float) $candidate->latitude, (float) $candidate->longitude);

                if ($distance <= self::DUPLICATE_RADIUS_METERS
                    && $this->nameSimilarity((string) $data['name'], $candidate->name) >= self::NAME_SIMILARITY_PERCENT) {
                    return [
                        'customer_id' => $candidate->id,
                        'reason' => sprintf('Nama mirip dan hanya %d m dari %s - %s', round($distance), $candidate->code, $candidate->name),
                    ];
                }
            }
        }

        return null;
    }

    /** Hanya angka; awalan 62 (kode negara) disamakan dengan 0. */
    private function phoneDigits(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }

    /** Huruf kecil, tanpa tanda baca, tanpa kata umum (toko/warung/kios/dst). */
    private function normalizeName(string $name): string
    {
        $name = mb_strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/u', ' ', $name);
        $name = preg_replace('/\b(toko|warung|warkop|kios|kedai|depot|ud|cv|pt)\b/u', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /** Kemiripan nama 0-100. Nama yang saling memuat (>= 5 huruf) dianggap 100. */
    private function nameSimilarity(string $a, string $b): float
    {
        $a = $this->normalizeName($a);
        $b = $this->normalizeName($b);

        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 100.0;
        }

        [$short, $long] = strlen($a) <= strlen($b) ? [$a, $b] : [$b, $a];

        if (strlen($short) >= 5 && str_contains($long, $short)) {
            return 100.0;
        }

        similar_text($a, $b, $percent);

        return (float) $percent;
    }

    /**
     * Kemungkinan duplikasi berdasarkan nama toko + nomor telepon yang
     * sama pada customer yang sudah ada. Dipakai untuk peringatan di layar
     * review Admin (longgar, hanya petunjuk); keputusan duplikat otomatis
     * memakai detectDuplicate() yang lebih ketat.
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
     * Menyetujui tagging: membuat (atau menautkan) Customer resmi. Dipanggil
     * Admin untuk tagging yang ditahan karena terindikasi duplikat, atau
     * otomatis oleh sistem ($reviewerId null) lewat autoApprove().
     */
    public function approve(CustomerTagging $tagging, ?int $reviewerId, ?string $reviewNotes = null): CustomerTagging
    {
        return DB::transaction(function () use ($tagging, $reviewerId, $reviewNotes) {
            if (! $tagging->customer_id) {
                $customer = Customer::create([
                    'code' => 'TEMP',
                    // Multi Branch/Depo: Customer baru WAJIB mewarisi
                    // branch_id dari Sales yang men-tagging-nya. Tanpa ini,
                    // Customer hasil approve Tagging Toko (jalur UTAMA
                    // penambahan toko baru) akan punya branch_id NULL dan
                    // langsung tidak terlihat sama sekali begitu
                    // BranchContext scoping aktif di sisi Admin.
                    'branch_id' => Sales::find($tagging->sales_id)?->branch_id,
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
                    // validasi (tidak terindikasi duplikat / direview Admin),
                    // bukan sekadar submit mentah yang belum divalidasi siapa pun.
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
