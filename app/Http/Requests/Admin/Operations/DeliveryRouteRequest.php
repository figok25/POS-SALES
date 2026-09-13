<?php

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

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
            'code' => ['required', 'string', 'max:50', 'unique:routes,code,'.$id],
            'name' => ['required', 'string', 'max:150'],
            'area' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
