<?php

namespace App\Http\Controllers\Sales\Concerns;

use App\Models\Sales;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Setiap controller Sales App perlu tahu "current sales" (baris master
 * Sales yang terhubung ke user yang sedang login) untuk membatasi
 * seluruh query ke data miliknya sendiri (Blueprint #38 - Sales hanya
 * boleh mengakses Own Dashboard, Own Customer, Own Visit, Own
 * Transaction, Own Stock).
 */
trait ResolvesCurrentSales
{
    protected function currentSales(): Sales
    {
        $sales = Sales::currentForUser(Auth::id());

        if (! $sales) {
            throw new HttpException(403, 'Akun Anda belum terhubung ke data Sales. Hubungi Administrator.');
        }

        return $sales;
    }
}
