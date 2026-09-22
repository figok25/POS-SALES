<?php

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('item')?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('routes', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:150'],
            'route_type' => ['nullable', 'string', 'max:50'],
            'sales_id' => ['nullable', 'integer', 'exists:sales,id'],
            'area' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
