<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\Customer;

/**
 * Live Sales Field Operations - Customer Map & Customer Detail
 * (Blueprint #11 Customer Location, #12 Customer Assignment, #15 Sales
 * Map, #16 Basic Route, Fase 3 WebView Sales).
 *
 * Hanya menampilkan Customer yang di-assign ke Sales yang sedang login
 * (Blueprint #12: "Jangan menampilkan ribuan customer tanpa filter").
 */
class MapController extends Controller
{
    use ResolvesCurrentSales;

    public function index()
    {
        $sales = $this->currentSales();

        $customers = Customer::where('sales_id', $sales->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('sales.map.index', [
            'customers' => $customers,
            'customersWithLocation' => $customers->filter->hasLocation()->values(),
            'googleMapsKey' => config('services.google_maps.key'),
        ]);
    }

    public function show(Customer $customer)
    {
        $sales = $this->currentSales();

        abort_if((int) $customer->sales_id !== (int) $sales->id, 403);

        return view('sales.map.show', [
            'customer' => $customer,
            'googleMapsKey' => config('services.google_maps.key'),
        ]);
    }
}
