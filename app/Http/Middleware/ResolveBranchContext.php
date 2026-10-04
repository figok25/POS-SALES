<?php

namespace App\Http\Middleware;

use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Multi Branch/Depo - resolve BranchContext sekali di awal request (dari
 * query string `branch` untuk Super Admin, atau dari auth()->user()->
 * branch_id untuk Admin/Sales), lalu simpan lewat BranchContext::setCurrent()
 * supaya seluruh controller/service di request ini tinggal memanggil
 * BranchContext::current() tanpa mengulang logika resolusi sendiri-sendiri.
 *
 * Dipasang SETELAH middleware 'auth' (butuh auth()->user()) pada group
 * route Admin (routes/web.php). Sales App tidak memakainya secara
 * langsung - Sales sudah mendapatkan Branch lewat Sales::currentForUser(),
 * bukan lewat BranchContext (lihat ResolvesCurrentSales).
 */
class ResolveBranchContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $requested = $request->query('branch');
            BranchContext::setCurrent(BranchContext::resolveForUser($user, $requested));
        }

        return $next($request);
    }
}
