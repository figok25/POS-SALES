<?php

namespace App\Http\Requests\Admin\Inventory;

use App\Models\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Stock Adjustment: dua jenis input lewat field `mode`.
 *  - single : satu produk -> satu draft tunggal (tanpa batch_code).
 *  - batch  : paket berisi beberapa produk (min. 2) -> banyak draft dengan
 *             batch_code yang sama, bisa di-Apply/dihapus sebagai satu paket.
 *
 * location_type/location_id/type/reason berlaku sama untuk seluruh baris
 * dalam satu submit (satu lokasi & satu arah in/out per paket -- kalau Admin
 * perlu lokasi/arah berbeda, itu jadi paket terpisah).
 */
class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isBatch = $this->input('mode') === 'batch';

        return [
            'mode' => ['required', Rule::in(['single', 'batch'])],

            'location_type' => ['required', Rule::in([Stock::LOCATION_WAREHOUSE, Stock::LOCATION_SALES])],
            'location_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', Rule::in(['in', 'out'])],
            'reason' => ['nullable', 'string'],

            'items' => ['required', 'array', $isBatch ? 'min:2' : 'size:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal 1 produk.',
            'items.min' => 'Paket harus berisi minimal 2 produk. Untuk satu produk, pilih "Satu produk".',
            'items.size' => 'Mode "Satu produk" hanya menerima 1 produk. Pilih "Paket" untuk banyak produk.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh ditambahkan dua kali dalam satu paket.',
        ];
    }
}
