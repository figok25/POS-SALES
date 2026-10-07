<?php

namespace App\Http\Requests\Admin\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('promo_items', 'name')->ignore($this->route('item'))],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama item',
            'sort_order' => 'urutan',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama item ini sudah ada.',
        ];
    }
}
