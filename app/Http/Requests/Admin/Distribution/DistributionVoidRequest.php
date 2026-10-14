<?php

namespace App\Http\Requests\Admin\Distribution;

use Illuminate\Foundation\Http\FormRequest;

class DistributionVoidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:500']];
    }

    public function messages(): array
    {
        return ['reason.required' => 'Alasan pembatalan wajib diisi.'];
    }
}
