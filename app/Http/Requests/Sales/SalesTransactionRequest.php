<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class SalesTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Baris kosong (produk & qty belum diisi) dan cangkang 0 diabaikan.
        $items = collect($this->input('items', []))
            ->filter(fn ($r) => is_array($r) && (filled($r['product_id'] ?? null) || filled($r['quantity'] ?? null)))
            ->values()->all();
        $shells = collect($this->input('shells', []))
            ->filter(fn ($r) => is_array($r) && (float) ($r['quantity'] ?? 0) > 0)
            ->values()->all();

        $this->merge(['items' => $items, 'shells' => $shells]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required_without:shells', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'shells' => ['nullable', 'array'],
            'shells.*.promo_id' => ['required', 'exists:buy_get_promos,id'],
            'shells.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
