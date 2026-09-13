<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sales;
use App\Services\CustomerAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Phase 6 Hardening - Customer Assignment (Blueprint baris #729, #1008
 * Developer C, "Customer Assignment" sebagai menu tersendiri di Sales
 * Management - bukan cuma field di form Master Data > Customer).
 *
 * Modul ini murni untuk melihat & mengubah SIAPA Sales yang menangani
 * suatu Customer, lengkap dengan riwayatnya - beda dari Master Data
 * Customer yang mengelola data toko itu sendiri (nama, alamat, dst).
 */
class CustomerAssignmentController extends Controller
{
    public function __construct(protected CustomerAssignmentService $service) {}

    public function index(Request $request)
    {
        $search = $request->query('q');
        $salesFilter = $request->query('sales_id');
        $unassignedOnly = $request->boolean('unassigned');

        $items = Customer::query()
            ->with(['sales'])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($salesFilter, fn ($q) => $q->where('sales_id', $salesFilter))
            ->when($unassignedOnly, fn ($q) => $q->whereNull('sales_id'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $saless = Sales::where('is_active', true)->orderBy('name')->get();

        return view('admin.sales.customer-assignments.index', compact('items', 'saless', 'search', 'salesFilter', 'unassignedOnly'));
    }

    public function edit(Customer $customer)
    {
        $customer->load(['sales']);
        $saless = Sales::where('is_active', true)->orderBy('name')->get();
        $history = $customer->assignments()->with(['sales', 'assignedBy'])->limit(20)->get();

        return view('admin.sales.customer-assignments.edit', compact('customer', 'saless', 'history'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'sales_id' => ['nullable', Rule::exists('sales', 'id')],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['sales_id'] ?? null) {
            $this->service->assign($customer, (int) $data['sales_id'], auth()->id(), $data['reason'] ?? null);
            $message = 'Customer berhasil di-assign.';
        } else {
            $this->service->unassign($customer, auth()->id(), $data['reason'] ?? null);
            $message = 'Customer dilepas dari Sales sebelumnya (tidak ada Sales yang menangani sekarang).';
        }

        return redirect()->route('admin.sales.customer-assignments.index')->with('status', $message);
    }
}
