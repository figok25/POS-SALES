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
     * Rute Kanvas (SalesVisitPlan).
     *
     * PERBAIKAN: sebelumnya begitu Rute Kanvas HARI INI kosong, service
     * ini fallback menampilkan SELURUH Customer yang di-assign ke Sales
     * (lintas hari) -- akibatnya toko-toko jadwal Senin ikut nongol pas
     * hari Rabu, dan toko hasil Tagging yang baru terdaftar di hari lain
     * ikut kebawa juga. "Rute Kanvas per hari" jadi tidak berarti apa-apa.
     *
     * Sekarang fallback ke SEMUA customer HANYA terjadi kalau Sales ini
     * belum PERNAH punya Rute Kanvas sama sekali (di hari manapun) --
     * supaya Sales yang baru dan belum di-setup Admin tetap bisa lihat
     * customer-nya. Begitu Sales sudah punya jadwal di satu hari saja,
     * hari-hari lain yang memang kosong akan tampil KOSONG (bukan
     * fallback), sesuai maksud "Rute Kanvas hari ini".
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

        $hasAnyPlanAnyDay = SalesVisitPlan::where('sales_id', $salesId)->exists();

        if ($hasAnyPlanAnyDay) {
            // Sales SUDAH punya Rute Kanvas (di hari lain) - hari ini memang
            // tidak dijadwalkan, jadi tampilkan kosong, JANGAN fallback.
            return [new Collection(), false];
        }

        // Sales belum pernah punya Rute Kanvas di hari manapun -> fallback
        // ke semua customer supaya tidak buntu total sebelum Admin sempat setup.
        $customers = (clone $baseQuery)->orderBy('name')->get();

        return [$customers, true];
    }

    /**
     * Sales yang punya Rute Kanvas terjadwal pada hari tertentu --
     * dipakai untuk menyaring dropdown "Sales" di Peta Rute Admin dan
     * BKB assignable di Sales Task, supaya keduanya hanya menampilkan
     * Sales yang benar-benar "aktif bertugas" hari itu (Penyaringan
     * Daftar Sales), bukan seluruh Sales aktif tanpa pandang jadwal.
     */
    public function salesIdsWithRouteOnDay(int $dayOfWeek): Collection
    {
        return SalesVisitPlan::where('day_of_week', $dayOfWeek)->distinct()->pluck('sales_id');
    }

    /**
     * 7 opsi hari (Senin-Minggu), masing-masing dengan tanggal aslinya,
     * supaya "Senin" jelas maksudnya tanggal berapa -- bukan cuma nama
     * hari lepas dari konteks minggu.
     *
     * $anchorDate menentukan MINGGU mana yang ditampilkan: default hari
     * ini (browsing biasa), tapi kalau dipanggil dari tautan Sales Task
     * ("Lihat di Peta Rute" dengan tanggal tugas tsb), anchor-nya adalah
     * tanggal task itu -- supaya tab yang tampil adalah minggu ASLI
     * task itu dibuat, bukan selalu minggu berjalan saat ini.
     *
     * @return array<int, array{day: int, label: string, date: Carbon, isToday: bool, isSelected: bool}>
     */
    public function weekDayOptions(int $selectedDay, ?Carbon $anchorDate = null): array
    {
        $startOfWeek = ($anchorDate ?? Carbon::now())->copy()->startOfWeek(Carbon::MONDAY);
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
