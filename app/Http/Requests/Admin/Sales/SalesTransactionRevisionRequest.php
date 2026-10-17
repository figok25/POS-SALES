<?php

namespace App\Http\Requests\Admin\Sales;

use App\Models\Price;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edit transaksi penjualan oleh Admin/Superadmin. Alasan koreksi wajib.
 */
class SalesTransactionRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'consumer_name' => ['nullable', 'string', 'max:120'],
            'transaction_date' => ['nullable', 'date', 'before_or_equal:today'],
            'price_type' => ['nullable', Rule::in(array_keys(Price::types()))],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'free' => ['nullable', 'array'],
            'free.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'items.*.quantity.min' => 'Quantity setiap item harus lebih besar dari 0.',
        ];
    }
}
