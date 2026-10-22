<?php

namespace App\Http\Requests\Admin\Distribution;

use App\Models\Warehouse;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            // to_warehouse_id SENGAJA tidak dibatasi Branch - inti Branch
            // Transfer memang mengirim ke Branch lain.
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * Anti-IDOR (Multi Branch/Depo): Admin hanya boleh MENGIRIM dari
     * Warehouse Branch-nya sendiri - tidak boleh membuat dokumen yang
     * sumbernya Branch lain walau tahu ID warehouse-nya.
     *
     * PERBAIKAN: rule `different:to_warehouse_id` di atas HANYA mengecek ID
     * Warehouse-nya beda, BUKAN Branch-nya. Kalau satu Depo punya lebih
     * dari satu Warehouse, Admin bisa pilih from & to yang keduanya masih
     * Warehouse Depo yang sama (mis. Blitar -> Blitar) dan tetap lolos --
     * padahal itu bukan Branch Transfer sungguhan. Sekarang ditolak secara
     * eksplisit: Warehouse asal & tujuan WAJIB beda Branch.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $fromWarehouseId = $this->input('from_warehouse_id');
            $toWarehouseId = $this->input('to_warehouse_id');

            if (! $fromWarehouseId) {
                return;
            }

            $warehouse = Warehouse::find($fromWarehouseId);

            if ($warehouse && ! BranchContext::current()->allows($warehouse->branch_id)) {
                $validator->errors()->add('from_warehouse_id', 'Warehouse asal harus berada di Branch Anda sendiri.');
            }

            if ($warehouse && $toWarehouseId) {
                $toWarehouse = Warehouse::find($toWarehouseId);

                if ($toWarehouse && $toWarehouse->branch_id === $warehouse->branch_id) {
                    $validator->errors()->add('to_warehouse_id', 'Warehouse tujuan harus di Branch/Depo lain, bukan Branch yang sama dengan Warehouse asal.');
                }
            }
        });
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
