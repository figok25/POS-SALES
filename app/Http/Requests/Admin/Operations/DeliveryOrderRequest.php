<?php

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Phase 8 - Delivery Order (Blueprint #38).
 */
class DeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_transaction_id' => ['required', 'exists:sales_transactions,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'exists:employees,id'],
            'route_id' => ['nullable', 'exists:routes,id'],
            'scheduled_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
