<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Phase 2 - Master Data: Sales (Blueprint #32).
 * Phase 6 - ditambahkan relasi user_id agar Sales App dapat menemukan
 * "current sales" dari user yang sedang login (Blueprint #15 - User
 * relation) tanpa backend terpisah.
 */
class Sales extends Model
{
    protected $table = 'sales';

    protected $fillable = ['branch_id', 'user_id', 'code', 'name', 'phone', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function customerTaggings(): HasMany
    {
        return $this->hasMany(CustomerTagging::class);
    }

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }

    /**
     * Live Sales Field Operations (Blueprint #14, #21, #22, #47).
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(SalesTask::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(SalesDevice::class);
    }

    public function trackingSessions(): HasMany
    {
        return $this->hasMany(SalesTrackingSession::class);
    }

    public function currentLocation(): HasOne
    {
        return $this->hasOne(SalesCurrentLocation::class);
    }

    public function locationHistories(): HasMany
    {
        return $this->hasMany(SalesLocationHistory::class);
    }

    /**
     * Ambil record Sales milik user yang sedang login. Dipakai oleh
     * seluruh controller Sales App untuk resolve "current sales"
     * (Blueprint #38 - Sales hanya boleh mengakses data miliknya sendiri).
     */
    public static function currentForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)->first();
    }
}
