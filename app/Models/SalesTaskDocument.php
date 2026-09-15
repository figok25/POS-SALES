<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Live Sales Field Operations - Task Documents (Blueprint #13.5).
 */
class SalesTaskDocument extends Model
{
    public const TYPE_SURAT_JALAN = 'surat_jalan';
    public const TYPE_BARANG_KELUAR = 'barang_keluar';
    public const TYPE_DAFTAR_STOCK = 'daftar_stock';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'sales_task_id', 'type', 'title', 'file_path',
        'reference_type', 'reference_id', 'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    public function salesTask(): BelongsTo
    {
        return $this->belongsTo(SalesTask::class);
    }

    /**
     * Dokumen sumber opsional (mis. BkbDistribusi, DeliveryOrder) agar
     * data dokumen tidak diduplikasi (Blueprint #40 - jangan duplikasi
     * business logic/data antar modul).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isDownloaded(): bool
    {
        return $this->downloaded_at !== null;
    }
}
