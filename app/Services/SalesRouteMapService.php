<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SalesVisitPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Route Toko Sesuai Hari/Minggu: dipakai bersama oleh
 * Sales\MapController (WebView Sales, "Peta Customer") dan
 * Admin\Sales\MapController (WebView Admin, "Rute Toko per Sales").
 *
 * Sengaja diekstrak ke service supaya kedua sisi (Sales & Admin) selalu
 * konsisten memakai definisi "Rute Kanvas hari ini" yang sama, tanpa
 * duplikasi logic dan tanpa menyentuh RouteController (mobile native)
 * sama sekali -- service ini hanya dipakai oleh dua controller WebView
 * di atas.
 */
class SalesRouteMapService
{
    /**
     * Normalisasi input hari (1=Senin..7=Minggu). Null atau di luar
     * rentang -> default hari ini.
     */
    public function resolveSelectedDay(?int $day): int
    {
        if ($day === null || $day < 1 || $day > 7) {
            return now()->dayOfWeekIso;
        }

        return $day;
    }

    /**
     * Daftar Customer untuk 1 Sales pada 1 hari tertentu, mengikuti
     * Rute Kanvas (SalesVisitPlan). Kalau Rute Kanvas hari itu masih
     * kosong, fallback ke seluruh Customer yang di-assign ke Sales
     * tsb (diurutkan nama) supaya peta/list tidak kosong.
     *
     * @return array{0: Collection<int, Customer>, 1: bool} [$customers, $usingFallback]
     */
    public function customersForDay(int $salesId, int $dayOfWeek): array
    {
        $planCustomerIds = SalesVisitPlan::where('sales_id', $salesId)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('sequence')
            ->pluck('customer_id');

        $baseQuery = Customer::where('sales_id', $salesId)->where('is_active', true);

        if ($planCustomerIds->isNotEmpty()) {
            $customers = (clone $baseQuery)->whereIn('id', $planCustomerIds)->get()
                ->sortBy(fn ($c) => $planCustomerIds->search($c->id))
                ->values();

            return [$customers, false];
        }

        $customers = (clone $baseQuery)->orderBy('name')->get();

        return [$customers, true];
    }

    /**
     * 7 opsi hari (Senin-Minggu) minggu berjalan, masing-masing dengan
     * tanggal aslinya, supaya "Senin" jelas maksudnya tanggal berapa --
     * bukan cuma nama hari lepas dari konteks minggu.
     *
     * @return array<int, array{day: int, label: string, date: Carbon, isToday: bool, isSelected: bool}>
     */
    public function weekDayOptions(int $selectedDay): array
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $labels = ['Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu', 'Minggu'];

        $options = [];
        for ($day = 1; $day <= 7; $day++) {
            $date = $startOfWeek->copy()->addDays($day - 1);

            $options[] = [
                'day' => $day,
                'label' => $labels[$day - 1],
                'date' => $date,
                'isToday' => $date->isToday(),
                'isSelected' => $day === $selectedDay,
            ];
        }

        return $options;
    }
}
