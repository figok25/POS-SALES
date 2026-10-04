<?php

namespace App\Http\Requests\Admin\Distribution;

use App\Models\Sales;
use App\Models\Warehouse;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    /**
     * Multi Branch/Depo: sama seperti BkbDistribusiRequest - anti-IDOR
     * (Sales yang dipilih harus berada di Branch yang diizinkan) + Sales
     * & Warehouse WAJIB satu Branch yang sama (retur lintas-Branch bukan
     * lewat BTB biasa).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $salesId = $this->input('sales_id');
            $warehouseId = $this->input('warehouse_id');

            if (! $salesId || ! $warehouseId) {
                return;
            }

            $sales = Sales::find($salesId);
            $warehouse = Warehouse::find($warehouseId);

            if ($sales && ! BranchContext::current()->allows($sales->branch_id)) {
                $validator->errors()->add('sales_id', 'Sales yang dipilih berada di Branch lain.');

                return;
            }

            if ($sales && $warehouse && (int) $sales->branch_id !== (int) $warehouse->branch_id) {
                $validator->errors()->add('warehouse_id', 'Warehouse harus berada di Branch yang sama dengan Sales.');
            }
        });
    }
}
