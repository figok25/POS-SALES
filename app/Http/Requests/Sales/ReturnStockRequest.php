<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Business Flow Update v3.1 (Blueprint #13.11): Return Stock yang
 * disubmit Sales lewat Sales Mobile. Hanya menerima product_id + quantity
 * yang mau dikembalikan; validasi apakah quantity tidak melebihi Sales
 * Stock yang benar-benar tersedia dilakukan di controller (butuh query
 * StockService, tidak praktis lewat rule statis di sini).
 */
class ReturnStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            // Nullable/min:0 karena form mengirim SEMUA produk Sales Stock
            // sekaligus (baris yang tidak diisi/0 berarti tidak diretur).
            // Filter "minimal 1 produk > 0" dilakukan di controller setelah
            // validasi, supaya pesan errornya lebih jelas untuk Sales.
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item stock yang dikembalikan.',
            'items.*.quantity.min' => 'Quantity setiap item harus lebih besar dari 0.',
            'items.*.product_id.distinct' => 'Setiap produk hanya boleh muncul sekali dalam satu retur.',
        ];
    }
}
