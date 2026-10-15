<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerTagging;
use App\Models\Sales;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pulihkan data master yang terhapus tidak sengaja, dari snapshot "before"
 * pada Audit Log aksi `delete`.
 *
 * Saat ini yang didukung: Customer. Customer hanya BISA terhapus bila belum
 * punya kunjungan / transaksi / invoice / penugasan (kunci database menolak
 * sisanya), jadi data inti yang dipulihkan memang lengkap di snapshot.
 *
 * Yang ikut dipulihkan: baris Customer (ID & kode sama), penugasan ke Sales
 * (customer_assignments), dan tautan Tagging Toko asalnya. Yang TIDAK ikut
 * (ikut terhapus bersama Customer & tidak punya snapshot): keanggotaan Rute
 * distribusi dan Rencana Kunjungan harian -- dikembalikan lewat daftar catatan.
 */
class AuditRestoreService
{
    /** document_type => metode pemulih */
    private const HANDLERS = [
        Customer::class => 'restoreCustomer',
    ];

    public function __construct(protected CustomerAssignmentService $assignments) {}

    /** Apakah jenis log ini pernah bisa dipulihkan (untuk menampilkan panel). */
    public function supports(AuditLog $log): bool
    {
        return $log->action === 'delete'
            && isset(self::HANDLERS[(string) $log->document_type])
            && is_array($log->before) && $log->before !== []
            && $log->document_id;
    }

    /** null = bisa dipulihkan sekarang; selain itu alasan penolakannya. */
    public function blockedReason(AuditLog $log): ?string
    {
        if (! $this->supports($log)) {
            return 'Log ini bukan penghapusan data yang bisa dipulihkan.';
        }

        $class = $log->document_type;
        $before = $log->before;

        if (! BranchContext::current()->allows(isset($before['branch_id']) ? (int) $before['branch_id'] : null)) {
            return 'Anda tidak memiliki akses ke Depo data ini.';
        }

        if ($class === Customer::class) {
            if (Customer::query()->whereKey($log->document_id)->exists()) {
                return 'Data ini sudah ada kembali (sudah dipulihkan atau dibuat ulang).';
            }

            if (! empty($before['code']) && Customer::query()->where('code', $before['code'])->exists()) {
                return "Kode {$before['code']} sudah dipakai Customer lain, tidak bisa dipulihkan otomatis.";
            }
        }

        return null;
    }

    /**
     * @return array{record: \Illuminate\Database\Eloquent\Model, notes: array<int, string>}
     *
     * @throws \DomainException
     */
    public function restore(AuditLog $log, User $actor): array
    {
        if ($reason = $this->blockedReason($log)) {
            throw new \DomainException($reason);
        }

        $method = self::HANDLERS[$log->document_type];

        try {
            return DB::transaction(fn () => $this->{$method}($log, $actor));
        } catch (QueryException $e) {
            throw new \DomainException('Data gagal dipulihkan karena bentrok dengan data yang sudah ada. Muat ulang halaman dan coba lagi.');
        }
    }

    private function restoreCustomer(AuditLog $log, User $actor): array
    {
        $before = $log->before;

        $columns = Schema::getColumnListing('customers');
        $attributes = Arr::only($before, $columns);
        $attributes['id'] = (int) $log->document_id;

        $customer = new Customer();
        $customer->forceFill($attributes);
        $customer->save();

        $notes = [];

        // Penugasan ke Sales (tercatat resmi di customer_assignments).
        $salesId = ! empty($before['sales_id']) ? (int) $before['sales_id'] : null;
        if ($salesId && Sales::query()->whereKey($salesId)->exists()) {
            $this->assignments->assign($customer, $salesId, $actor->id, "Dipulihkan dari Audit Log #{$log->id}");
        } elseif ($salesId) {
            $notes[] = 'Sales sebelumnya sudah tidak ada, jadi Customer belum ditugaskan ke Sales mana pun.';
        }

        // Tautan Tagging Toko asal: hanya bila tepat satu yang cocok (nama & Sales sama, sudah disetujui, tertaut kosong).
        if ($salesId) {
            $taggings = CustomerTagging::query()
                ->whereNull('customer_id')
                ->where('sales_id', $salesId)
                ->where('name', $customer->name)
                ->where('status', CustomerTagging::STATUS_APPROVED)
                ->get();

            if ($taggings->count() === 1) {
                $taggings->first()->update(['customer_id' => $customer->id]);
            }
        }

        $notes[] = 'Keanggotaan Rute distribusi dan Rencana Kunjungan harian Customer ini tidak ikut kembali; tambahkan lagi bila diperlukan.';

        AuditLogger::log(
            action: 'restore',
            module: 'Master Data',
            documentType: Customer::class,
            documentId: $customer->id,
            before: null,
            after: $customer->fresh()->toArray() + ['restored_from_audit_log_id' => $log->id],
            userId: $actor->id,
        );

        return ['record' => $customer, 'notes' => $notes];
    }
}
