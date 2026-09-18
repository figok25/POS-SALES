<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class VisitCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            // PERBAIKAN AUDIT (item B): dulu nullable -> Sales bisa check-in
            // tanpa GPS sama sekali. Sekarang wajib, supaya validasi radius
            // di VisitService::checkIn() selalu bisa dijalankan.
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
