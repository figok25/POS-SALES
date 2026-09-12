<?php

namespace App\Http\Requests\Admin\Inventory;

use App\Models\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'location_type' => ['required', Rule::in([Stock::LOCATION_WAREHOUSE, Stock::LOCATION_SALES])],
            'location_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
