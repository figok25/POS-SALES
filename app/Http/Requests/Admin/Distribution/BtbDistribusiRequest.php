<?php

namespace App\Http\Requests\Admin\Distribution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Phase 5 - BTB Distribusi: Sales -> Warehouse (pengembalian).
 */
class BtbDistribusiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bkb_distribusi_id' => ['nullable', 'exists:bkb_distribusi,id'],
            'sales_id' => ['required', 'exists:sales,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item produk.',
            'items.*.quantity.min' => 'Quantity setiap item harus lebih besar dari 0.',
        ];
    }
}
