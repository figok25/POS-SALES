<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sales;
use App\Models\SalesCurrentLocation;
use App\Models\SalesLocationHistory;
use App\Services\LiveMonitoringService;
use App\Support\BranchContext;
use Illuminate\Http\Request;

/**
 * KHUSUS SUPER ADMIN (route dikunci role:super_admin). Status efektif (diam/istirahat/dll)
 * diturunkan LiveMonitoringService.
 *
 * Live Sales Field Operations - Admin Live Monitoring (Blueprint #28,
 * #29, #38, Fase 2 & Fase 7):
 *
 *   GET /admin/operations/live-monitoring        (index  - halaman peta)
 *   GET /api/admin/live-sales                    (liveSales - JSON polling)
 *   GET /api/admin/sales/{id}/locations          (locations - JSON history)
 *
 * MVP polling (Blueprint #31): Admin/JS map poll endpoint ini secara
 * berkala, bukan WebSocket.
 *
 * Multi Branch/Depo (PERBAIKAN KEAMANAN): sebelumnya `?branch_id=` pada
 * liveSales() dipercaya mentah-mentah dari query string TANPA validasi
 * otorisasi - Admin Branch mana pun bisa melihat Sales Branch lain hanya
 * dengan mengubah URL. Sekarang branch_id SELALU diturunkan dari
 * BranchContext (yang untuk Admin memaksa branch_id miliknya sendiri,
 * mengabaikan ?branch_id= sepenuhnya); dan locations() memvalidasi bahwa
 * Sales yang diminta memang berada pada Branch yang diizinkan sebelum
 * mengembalikan histori lokasinya (anti-IDOR).
 */
class LiveSalesController extends Controller
{
    public function __construct(private readonly LiveMonitoringService $monitoring)
    {
    }

    /**
     * Halaman peta Live Monitoring: menampilkan seluruh Sales yang
     * sedang tracking di satu peta MapLibre, auto-refresh lewat polling
     * ke liveSales() di atas (Blueprint #28, sebelumnya belum ada
     * halaman-nya sama sekali -- baru JSON API-nya).
     */
    public function index(Request $request)
    {
        // Dropdown filter Branch pada halaman HANYA relevan/boleh dipakai
        // Super Admin - lihat BranchContext::resolveForUser(). Tetap
        // dikirim ke view supaya Blade bisa merender selector kalau
        // auth()->user()->isSuperAdmin(), dan menyembunyikannya (tampilkan
        // Branch sendiri sebagai teks read-only) untuk Admin biasa.
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $mapStyleUrl = config('services.maps.style_url');
        $refreshSeconds = config('services.maps.admin_live_refresh_seconds', 15);

        return view('admin.operations.live-monitoring.index', compact('branches', 'mapStyleUrl', 'refreshSeconds'));
    }

    public function liveSales(Request $request)
    {
        $branchContext = BranchContext::current();

        $locations = $branchContext->applyTo(
            SalesCurrentLocation::with(['sales', 'branch'])
        )->orderByDesc('last_seen_at')->get();

        $data = $this->monitoring->enrich($locations);

        return response()->json([
            'success' => true,
            'data' => $data,
            'idle_alert_count' => collect($data)->where('idle_alert', true)->count(),
            'idle_minutes_threshold' => (int) config('monitoring.idle_minutes'),
        ]);
    }

    public function locations(Request $request, Sales $sales)
    {
        // Anti-IDOR (Multi Branch/Depo Scenario E): Admin Branch A tidak
        // boleh membaca histori lokasi Sales Branch B hanya karena tahu
        // ID-nya. 403, bukan diam-diam mengembalikan array kosong, supaya
        // kesalahan akses jelas kelihatan (bukan disalahartikan sebagai
        // "Sales ini memang tidak punya histori").
        if (! BranchContext::current()->allows($sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke data tracking Sales ini.');
        }

        $validated = $request->validate([
            'tracking_session_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        // Tanpa filter tanggal/sesi = jejak HARI INI saja. Sebelumnya semua
        // hari diambil urut terlama & dipotong 500 titik, sehingga setelah
        // beberapa jam titik terbaru terpotong dan jejak "hilang".
        $hasRange = ! empty($validated['from']) || ! empty($validated['to']) || ! empty($validated['tracking_session_id']);
        $limit = $validated['limit'] ?? 5000;

        // Ambil titik TERBARU sebanyak $limit (desc), lalu dibalik ke urutan waktu.
        $histories = SalesLocationHistory::where('sales_id', $sales->id)
            ->when($validated['tracking_session_id'] ?? null, fn ($q, $v) => $q->where('tracking_session_id', $v))
            ->when($validated['from'] ?? null, fn ($q, $v) => $q->whereDate('recorded_at', '>=', $v))
            ->when($validated['to'] ?? null, fn ($q, $v) => $q->whereDate('recorded_at', '<=', $v))
            ->when(! $hasRange, fn ($q) => $q->where('recorded_at', '>=', now()->startOfDay()))
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json(['success' => true, 'data' => $histories]);
    }
}
