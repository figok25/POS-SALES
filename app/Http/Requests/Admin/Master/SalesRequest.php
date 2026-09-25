<?php

namespace App\Http\Requests\Admin\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Sales|null $salesItem */
        $salesItem = $this->route('item');
        $userId = $salesItem?->user_id;

        // Password wajib diisi HANYA kalau ini akan membuat akun login baru:
        // (a) form Tambah Sales dan Email diisi, atau
        // (b) form Edit Sales, Sales ini belum punya akun login, dan Email
        //     baru saja diisi sekarang.
        // Di luar itu (akun sudah ada), password opsional -- kosongkan
        // berarti tidak diubah.
        $isCreatingNewLogin = $this->filled('email') && ! $userId;

        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('sales', 'code')->ignore($salesItem)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],

            // Manajemen Akun Sales (Email & Password login Sales App).
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$isCreatingNewLogin ? 'required' : 'nullable', 'string', 'min:4', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'password.required' => 'Password wajib diisi untuk membuat akun login baru.',
            'password.min' => 'Password minimal 4 karakter.',
        ];
    }
}
