<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            // Hanya 2 role yang benar-benar dikenali sistem (route gate
            // 'role:admin'/'role:sales' di routes/web.php, dan
            // RolePermissionSeeder) -- role lain tidak akan membuka menu
            // apapun, jadi sengaja dibatasi di sini.
            'role' => ['required', Rule::in(['admin', 'sales'])],
            // Wajib diisi saat membuat user baru; opsional saat edit
            // (kosongkan = password tidak berubah).
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ];
    }
}
