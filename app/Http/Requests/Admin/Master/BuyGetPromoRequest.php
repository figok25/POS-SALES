<?php

namespace App\Http\Requests\Admin\Master;

use Illuminate\Foundation\Http\FormRequest;

class BuyGetPromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:buy_get,shell_exchange'],
            'buy_product_id' => ['required', 'exists:products,id'],
            'buy_qty' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'free_product_id' => ['required', 'exists:products,id'],
            'free_qty' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama program',
            'buy_product_id' => 'produk yang dibeli',
            'buy_qty' => 'jumlah beli',
            'free_product_id' => 'produk gratis',
            'free_qty' => 'jumlah gratis',
            'starts_on' => 'tanggal mulai',
            'ends_on' => 'tanggal selesai',
            'branch_ids' => 'Depo',
            'type' => 'jenis program',
        ];
    }
}
