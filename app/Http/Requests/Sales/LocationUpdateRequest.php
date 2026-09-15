<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Live Sales Field Operations - Location Update (Blueprint #24, #25, #38).
 * location_event_id adalah idempotency key (uuid) dari client, wajib agar
 * retry dari Offline Location Queue tidak menghasilkan duplikasi.
 */
class LocationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_event_id' => ['required', 'uuid'],
            'tracking_session_id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'recorded_at' => ['required', 'date'],
        ];
    }
}
