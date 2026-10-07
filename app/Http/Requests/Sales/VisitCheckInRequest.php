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

            // Kondisi outlet selalu dipilih. Keterangan/alasan WAJIB hanya bila
            // bermasalah (toko tutup / kendala lain); outlet normal tidak perlu.
            'condition' => ['required', Rule::in(Visit::CONDITIONS)],
            'notes' => [
                Rule::requiredIf(fn () => Visit::conditionNeedsReason($this->input('condition'))),
                'nullable', 'string', 'min:3', 'max:1000',
            ],

            // Promosi/POSM: Sales cukup MENCENTANG item yang ADA; item yang tidak
            // dicentang dianggap "Tidak ada". Berlaku untuk SEMUA kondisi
            // (toko tutup pun POSM-nya tetap dicatat). Daftar item dikelola Admin.
            'promo_items' => ['nullable', 'array'],
            'promo_items.*' => ['integer', Rule::exists('promo_items', 'id')->where('is_active', true)],
            'facility_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'condition' => 'kondisi outlet',
            'notes' => 'keterangan kondisi outlet',
            'promo_items' => 'item promosi/POSM',
            'facility_notes' => 'keterangan promosi/POSM',
        ];
    }

    public function messages(): array
    {
        return [
            'condition.required' => 'Pilih kondisi outlet (Normal, Toko Tutup, atau Kendala Lain).',
            'notes.required' => 'Jelaskan alasan/kondisi di lokasi (mis. toko tutup, pemilik tidak ada).',
            'notes.min' => 'Keterangan terlalu singkat. Jelaskan dengan lebih detail.',
            'promo_items.*.exists' => 'Ada item promosi/POSM yang tidak valid atau sudah dinonaktifkan. Muat ulang halaman lalu coba lagi.',
        ];
    }
}
