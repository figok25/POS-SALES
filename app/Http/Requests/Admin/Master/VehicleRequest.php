<?php

namespace App\Http\Requests\Admin\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('vehicles', 'code')->ignore($this->route('item'))],
            'name' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:255', Rule::unique('vehicles', 'plate_number')->ignore($this->route('item'))],
            'type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
