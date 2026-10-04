<?php

namespace App\Http\Requests\Admin\Distribution;

use App\Models\Sales;
use App\Models\Warehouse;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Phase 5 - BKB Distribusi: Warehouse -> Sales.
 */
class BkbDistribusiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_request_id' => ['nullable', 'exists:stock_requests,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'sales_id' => ['required', 'exists:sales,id'],
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
     * Multi Branch/Depo:
     * 1. Anti-IDOR - Admin tidak boleh membuat BKB dari Warehouse Branch lain.
     * 2. Konsistensi bisnis - Warehouse & Sales WAJIB satu Branch yang sama.
     *    Tanpa ini, Admin Branch A bisa "menyuntikkan" stok ke Sales Branch
     *    B lewat BKB biasa (jalur yang seharusnya hanya lewat Branch
     *    Transfer yang resmi, dengan approval source & destination).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $warehouseId = $this->input('warehouse_id');
            $salesId = $this->input('sales_id');

            if (! $warehouseId || ! $salesId) {
                return;
            }

            $warehouse = Warehouse::find($warehouseId);
            $sales = Sales::find($salesId);

            if ($warehouse && ! BranchContext::current()->allows($warehouse->branch_id)) {
                $validator->errors()->add('warehouse_id', 'Warehouse yang dipilih berada di Branch lain.');

                return;
            }

            if ($warehouse && $sales && (int) $warehouse->branch_id !== (int) $sales->branch_id) {
                $validator->errors()->add('sales_id', 'Sales harus berada di Branch yang sama dengan Warehouse. Gunakan Branch Transfer untuk pengiriman lintas-Branch.');
            }
        });
    }
}
