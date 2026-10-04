<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Multi Branch/Depo: route pembuat/pengubah User sekarang Super
 * Admin-only (lihat routes/admin_system.php, gate 'role:super_admin'),
 * sehingga aman memperbolehkan role 'super_admin' dipilih di sini - yang
 * BISA membuat/mengelola user hanya Super Admin itu sendiri.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('item'))],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'sales'])],
            // super_admin: branch_id WAJIB kosong (global). admin/sales:
            // branch_id WAJIB terisi & harus Branch yang benar-benar ada
            // dan aktif - divalidasi server, bukan dipercaya buta dari form.
            'branch_id' => [
                Rule::requiredIf(fn () => in_array($this->input('role'), ['admin', 'sales'], true)),
                Rule::prohibitedIf(fn () => $this->input('role') === 'super_admin'),
                'nullable',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            // Wajib diisi saat membuat user baru; opsional saat edit
            // (kosongkan = password tidak berubah).
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch wajib dipilih untuk role Admin/Sales.',
            'branch_id.prohibited' => 'Super Admin bersifat global dan tidak boleh diikat ke satu Branch.',
            'branch_id.exists' => 'Branch yang dipilih tidak ditemukan atau sudah nonaktif.',
        ];
    }
}
