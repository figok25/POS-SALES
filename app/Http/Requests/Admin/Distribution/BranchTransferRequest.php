<?php

namespace App\Http\Requests\Admin\Distribution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Phase 5 - Branch Transfer (BKB Cabang / BTB Cabang, Blueprint #11).
 */
class BranchTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_warehouse_id.different' => 'Warehouse asal dan tujuan tidak boleh sama.',
            'items.required' => 'Minimal harus ada 1 item produk.',
            'items.*.quantity.min' => 'Quantity setiap item harus lebih besar dari 0.',
        ];
    }
}
