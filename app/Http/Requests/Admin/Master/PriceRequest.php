<?php

namespace App\Http\Requests\Admin\Master;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Price;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'price_type' => ['required', Rule::in(array_keys(Price::types()))],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_type.required' => 'Kategori harga wajib dipilih.',
            'price_type.in' => 'Kategori harga tidak valid.',
        ];
    }

    /**
     * Satu produk hanya boleh punya SATU harga aktif per kategori
     * (Retail / WS-Grosir), supaya harga yang dipakai transaksi tidak
     * ambigu. Nonaktifkan harga lama dulu sebelum mengaktifkan yang baru.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
                if (! $this->boolean('is_active') || $validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Price|null $current */
                $current = $this->route('item');

                $exists = Price::query()
                    ->where('product_id', $this->input('product_id'))
                    ->where('price_type', $this->input('price_type'))
                    ->where('is_active', true)
                    ->when($current, fn ($q) => $q->where('id', '!=', $current->id))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'price_type',
                        'Produk ini sudah punya harga '.Price::typeLabel($this->input('price_type')).' yang aktif. Nonaktifkan harga lama terlebih dahulu.'
                    );
                }
        });
    }
}
