<?php

namespace App\Http\Requests\Admin\Distribution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Revisi BKB/BTB. Alasan wajib supaya setiap koreksi bisa ditelusuri.
 */
class DistributionRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'document_date' => ['nullable', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan revisi wajib diisi.',
            'items.required' => 'Minimal harus ada 1 item produk.',
            'items.*.quantity.min' => 'Quantity setiap item harus lebih besar dari 0.',
        ];
    }
}
