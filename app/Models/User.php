<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'branch_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // PERBAIKAN AUDIT (item C): HasApiTokens dibutuhkan supaya
    // createToken() (dipanggil dari Sales\NativeTokenController) tersedia.
    // Pastikan `composer require laravel/sanctum` sudah dijalankan.
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Multi Branch/Depo - relasi otorisasi inti User -> Branch.
     * NULL untuk super_admin (global), wajib terisi untuk admin/sales.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Admin atau Sales (role yang terikat tepat satu Branch), beda dengan
     * Super Admin yang global. Dipakai BranchContext untuk menentukan
     * apakah user WAJIB dipaksa ke branch_id miliknya sendiri.
     */
    public function isBranchScoped(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('sales');
    }

    /**
     * Boleh mengakses Branch tertentu? Super Admin selalu boleh (termasuk
     * ke Branch manapun, dan ke "semua"); Admin/Sales hanya boleh ke
     * branch_id miliknya sendiri.
     */
    public function canAccessBranch(?int $branchId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $branchId !== null && (int) $this->branch_id === $branchId;
    }
}
