<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;

/**
 * Fase 9 - Sales App: Pengaturan Printer Bluetooth Thermal (Blueprint #12.7).
 *
 * Halaman ini adalah tempat Sales menghubungkan (pair) printer thermal
 * Bluetooth miliknya SEKALI, mengetes cetak, dan mengatur lebar kertas.
 * Koneksi disimpan di localStorage browser/WebView perangkat itu sendiri
 * (lihat resources/views/sales/printer/_script.blade.php) supaya Cetak
 * Struk (Transaksi) dan Cetak Stock bisa langsung pakai printer yang sama
 * berulang kali tanpa pairing ulang setiap saat.
 */
class PrinterController extends Controller
{
    use ResolvesCurrentSales;

    public function index()
    {
        $sales = $this->currentSales()->load('branch.company');

        return view('sales.printer.index', compact('sales'));
    }
}
