<?php

namespace App\Http\Requests\Admin\Operations;

use App\Models\BkbDistribusi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Live Sales Field Operations - Sales Task / Penugasan (Blueprint #14).
 *
 * Business Flow Update v3.1 (Blueprint #13.7, #14.2): Sales Task tidak
 * lagi menerima input stock manual (product_id/quantity dari Admin).
 * Satu-satunya sumber barang adalah BKB Distribusi yang sudah Applied.
 * Admin hanya memilih BKB tersebut; item & quantity mengikuti apa adanya
 * yang tercatat pada bkb_distribusi_items.
 */
class SalesTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bkb_distribusi_id' => ['required', 'exists:bkb_distribusi,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'task_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'bkb_distribusi_id.required' => 'Pilih BKB Distribusi yang sudah Applied sebagai sumber stock Task ini.',
        ];
    }

    /**
     * Validasi tambahan yang butuh query (Blueprint #13.16 - 1 BKB hanya
     * boleh diikat 1 Sales Task aktif): dicek di sini, bukan lewat rule
     * 'unique' sederhana, karena harus digabung dengan pengecekan status
     * Applied sekaligus.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $bkbId = $this->input('bkb_distribusi_id');

            if (! $bkbId) {
                return;
            }

            $bkb = BkbDistribusi::with('salesTask')->find($bkbId);

            if (! $bkb) {
                return;
            }

            if (! $bkb->isApplied()) {
                $validator->errors()->add('bkb_distribusi_id', 'BKB Distribusi harus berstatus Applied sebelum dapat ditugaskan ke Sales.');

                return;
            }

            if ($bkb->salesTask !== null) {
                $validator->errors()->add('bkb_distribusi_id', 'BKB Distribusi ini sudah memiliki Sales Task. Satu BKB hanya boleh ditugaskan sekali.');
            }
        });
    }
}
