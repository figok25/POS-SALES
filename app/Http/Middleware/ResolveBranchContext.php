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
    /** Kunci session: pilihan Depo terakhir Super Admin ('all' atau id Depo). */
    public const SESSION_KEY = 'branch_context';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $requested = $request->query('branch');
            $hasSession = $request->hasSession();

            if ($user->isSuperAdmin() && $hasSession) {
                if ($requested !== null && $requested !== '') {
                    // Super Admin memilih Depo (atau "all"): ingat pilihannya supaya tidak
                    // kembali ke "Semua Depo" saat membuka detail/edit/hapus/simpan.
                    $context = BranchContext::resolveForUser($user, (string) $requested);
                    $request->session()->put(self::SESSION_KEY, $context->isAll() ? BranchContext::ALL : (string) $context->branchId());
                } else {
                    $stored = $request->session()->get(self::SESSION_KEY);
                    $context = BranchContext::resolveForUser($user, is_scalar($stored) ? (string) $stored : null);

                    // Depo tersimpan sudah tidak valid/aktif -> kembali ke Semua Depo.
                    if ($context->isAll() && $stored !== null && $stored !== BranchContext::ALL) {
                        $request->session()->forget(self::SESSION_KEY);
                    }
                }

                BranchContext::setCurrent($context);

                return $next($request);
            }

            BranchContext::setCurrent(BranchContext::resolveForUser($user, $requested));
        }

        return $next($request);
    }
}
