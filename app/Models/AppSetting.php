<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * System - Settings (singleton, selalu 1 baris). Lihat migration
 * `create_app_settings_table` untuk cakupan & alasan.
 */
class AppSetting extends Model
{
    protected $fillable = ['app_name', 'logo_path'];

    /**
     * Ambil baris setting (selalu id=1), buat otomatis kalau belum ada
     * (mis. langsung setelah migrate, sebelum Admin pernah membuka
     * halaman Settings).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
