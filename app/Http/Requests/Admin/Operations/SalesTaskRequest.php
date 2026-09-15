<?php

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Live Sales Field Operations - Sales Task / Penugasan (Blueprint #14).
 */
class SalesTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_id' => ['required', 'exists:sales,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'task_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'stocks' => ['required', 'array', 'min:1'],
            'stocks.*.product_id' => ['required', 'exists:products,id'],
            'stocks.*.quantity_assigned' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'stocks.required' => 'Minimal harus ada 1 item stock yang ditugaskan.',
            'stocks.*.quantity_assigned.min' => 'Quantity setiap item harus lebih besar dari 0.',
        ];
    }
}
