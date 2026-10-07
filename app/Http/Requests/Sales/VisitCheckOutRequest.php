<?php

namespace App\Http\Requests\Sales;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;

class VisitCheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Alasan check-out WAJIB hanya bila kunjungan bermasalah dan belum
        // dijelaskan saat check-in: outlet normal (buka) tetapi TIDAK ada
        // transaksi (alasan tidak transaksi). Selain itu opsional. Disimpan
        // di check_out_notes, terpisah dari keterangan check-in.
        $visit = $this->route('visit');
        $required = $visit instanceof Visit && $visit->needsCheckOutReason();

        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => [$required ? 'required' : 'nullable', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'notes' => 'alasan tidak transaksi',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Kunjungan ini belum ada transaksi. Isi alasan tidak transaksi sebelum check-out.',
            'notes.min' => 'Alasan terlalu singkat. Jelaskan dengan lebih detail.',
        ];
    }
}
