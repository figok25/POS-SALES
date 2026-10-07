<?php

namespace App\Http\Requests\Sales;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

            // RevisiMinor #5: kondisi outlet + keterangan WAJIB di setiap
            // check-in (bukan hanya saat ada kendala), supaya Admin selalu
            // punya konteks kunjungan -- terutama saat toko tutup.
            'condition' => ['required', Rule::in(Visit::CONDITIONS)],
            'notes' => ['required', 'string', 'min:3', 'max:1000'],

            // RevisiMinor #6: info Promosi/POSM/Banner diamati saat outlet
            // normal (buka). Jika toko tutup / ada kendala, Sales tidak bisa
            // memeriksanya, jadi tidak diwajibkan.
            'has_promo' => ['required_if:condition,'.Visit::CONDITION_NORMAL, 'nullable', 'boolean'],
            'has_posm' => ['required_if:condition,'.Visit::CONDITION_NORMAL, 'nullable', 'boolean'],
            'has_banner' => ['required_if:condition,'.Visit::CONDITION_NORMAL, 'nullable', 'boolean'],
            'facility_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'condition' => 'kondisi outlet',
            'notes' => 'keterangan kondisi outlet',
            'has_promo' => 'status program promosi',
            'has_posm' => 'status POSM',
            'has_banner' => 'status banner',
            'facility_notes' => 'keterangan promosi/POSM',
        ];
    }

    public function messages(): array
    {
        return [
            'condition.required' => 'Pilih kondisi outlet (Normal, Toko Tutup, atau Kendala Lain).',
            'notes.required' => 'Keterangan kondisi outlet wajib diisi. Jelaskan situasi di lokasi (mis. toko tutup, pemilik tidak ada).',
            'notes.min' => 'Keterangan kondisi outlet terlalu singkat. Jelaskan dengan lebih detail.',
            'has_promo.required_if' => 'Pilih Ada atau Tidak ada untuk program promosi.',
            'has_posm.required_if' => 'Pilih Ada atau Tidak ada untuk POSM.',
            'has_banner.required_if' => 'Pilih Ada atau Tidak ada untuk banner.',
        ];
    }
}
