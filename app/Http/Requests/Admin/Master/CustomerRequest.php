<?php

namespace App\Http\Requests\Admin\Master;

use App\Models\Sales;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_id' => ['nullable', 'exists:sales,id'],
            // Multi Branch/Depo: dipaksa/di-override server-side di
            // CustomerController untuk Admin biasa (selalu Branch
            // miliknya sendiri) - field ini hanya benar-benar "bebas
            // dipilih" untuk Super Admin.
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('is_active', true)],
            'code' => ['required', 'string', 'max:255', Rule::unique('customers', 'code')->ignore($this->route('item'))],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:255'],
            // Live Sales Field Operations (Blueprint #11): koordinat yang
            // dipakai Peta Customer di Sales App. Normalnya terisi otomatis
            // lewat approve Tagging Toko, tapi Admin tetap perlu bisa
            // mengoreksi/mengisi manual (mis. data lama sebelum fitur ini ada,
            // atau titik GPS tagging kurang akurat).
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Anti-IDOR (Multi Branch/Depo): Admin Branch A tidak boleh
     * menugaskan Customer ke Sales milik Branch B hanya karena tahu
     * sales_id-nya - walau branch_id Customer itu sendiri nanti dipaksa
     * benar di controller, assignment Sales lintas-Branch tetap harus
     * ditolak di sini supaya tidak ada data yang Customer Branch A tapi
     * Sales-nya Branch B.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $salesId = $this->input('sales_id');

            if (! $salesId) {
                return;
            }

            $sales = Sales::find($salesId);

            if ($sales && ! BranchContext::current()->allows($sales->branch_id)) {
                $validator->errors()->add('sales_id', 'Sales yang dipilih berada di Branch lain.');
            }
        });
    }
}
