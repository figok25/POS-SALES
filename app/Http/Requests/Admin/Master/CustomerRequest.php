<?php

namespace App\Http\Requests\Admin\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_id' => ['nullable', 'exists:sales,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('customers', 'code')->ignore($this->route('item'))],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:255'],
            // Live Sales Field Operations (Blueprint #11): koordinat yang
            // dipakai Peta Customer di Sales App. Normalnya terisi otomatis
            // lewat approve Tagging Toko, tapi Admin tetap perlu bisa
            // mengoreksi/mengisi manual (mis. data lama sebelum fitur ini ada,
            // atau titik GPS tagging kurang akurat).
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ];
    }
}
