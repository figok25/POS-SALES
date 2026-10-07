<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class VisitCheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // RevisiMinor #5: keterangan check-out WAJIB (hasil kunjungan /
            // kondisi outlet saat selesai). Disimpan di check_out_notes,
            // terpisah dari keterangan check-in.
            'notes' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'notes' => 'keterangan check-out',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Keterangan check-out wajib diisi (hasil kunjungan atau kondisi outlet saat selesai).',
            'notes.min' => 'Keterangan check-out terlalu singkat. Jelaskan dengan lebih detail.',
        ];
    }
}
