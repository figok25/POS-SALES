<?php

namespace App\Http\Requests\Admin\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['counts_as_ec' => $this->boolean('counts_as_ec')]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('categories', 'code')->ignore($this->route('item'))],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'counts_as_ec' => ['boolean'],
        ];
    }
}
