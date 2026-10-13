<?php

namespace Tests\Feature\Sales;

use App\Models\SalesTransaction;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Riwayat Transaksi & Kunjungan Sales difilter per hari (default hari ini).
 */
class HistoryDateFilterTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function trx($sales, $customer, $when, float $total = 10000): SalesTransaction
    {
        static $n = 0;
        $n++;

        $trx = SalesTransaction::create([
            'code' => "TRX-HIST-{$n}", 'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'subtotal' => $total, 'discount' => 0, 'tax' => 0, 'total' => $total,
            'status' => SalesTransaction::STATUS_COMPLETED,
        ]);
        $trx->forceFill(['created_at' => $when, 'updated_at' => $when])->saveQuietly();

        return $trx;
    }

    private function visit($sales, $customer, $when): Visit
    {
        return Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'check_in_at' => $when, 'check_out_at' => (clone $when)->addMinutes(10),
            'check_in_condition' => Visit::CONDITION_NORMAL, 'status' => Visit::STATUS_COMPLETED,
        ]);
    }

    public function test_transaction_history_defaults_to_today_and_can_pick_a_date_or_all(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);

        $today = $this->trx($sales, $customer, now(), 15000);
        $old = $this->trx($sales, $customer, now()->subDays(3), 7000);

        $this->actingAs($user)->get(route('sales.transactions.index'))
            ->assertOk()
            ->assertSee($today->code)
            ->assertDontSee($old->code)
            ->assertViewHas('summary', fn ($s) => $s['count'] === 1 && $s['total'] === 15000.0);

        $this->actingAs($user)->get(route('sales.transactions.index', ['date' => now()->subDays(3)->toDateString()]))
            ->assertOk()
            ->assertSee($old->code)
            ->assertDontSee($today->code);

        $this->actingAs($user)->get(route('sales.transactions.index', ['date' => 'all']))
            ->assertOk()
            ->assertSee($today->code)
            ->assertSee($old->code);
    }

    public function test_transaction_history_only_lists_own_transactions_and_ignores_bad_dates(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        [, $other] = $this->makeSalesUser();
        $mine = $this->trx($sales, $this->makeCustomer($sales->id), now());
        $theirs = $this->trx($other, $this->makeCustomer($other->id), now());

        // Tanggal ngawur & tanggal masa depan -> jatuh ke hari ini.
        foreach (['bukan-tanggal', now()->addDays(5)->toDateString()] as $bad) {
            $this->actingAs($user)->get(route('sales.transactions.index', ['date' => $bad]))
                ->assertOk()->assertSee($mine->code)->assertDontSee($theirs->code);
        }
    }

    public function test_visit_history_defaults_to_today_and_can_pick_a_date_or_all(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales); // menu Kunjungan hanya bisa dibuka Sales yang punya tugas aktif
        $todayStore = $this->makeCustomer($sales->id);
        $oldStore = $this->makeCustomer($sales->id);
        $todayStore->forceFill(['name' => 'Toko Hari Ini Jaya'])->save();
        $oldStore->forceFill(['name' => 'Toko Lama Sentosa'])->save();

        $this->visit($sales, $todayStore, now());
        $this->visit($sales, $oldStore, now()->subDays(2));

        $this->actingAs($user)->get(route('sales.visits.index'))
            ->assertOk()->assertSee('Toko Hari Ini Jaya')->assertDontSee('Toko Lama Sentosa');

        $this->actingAs($user)->get(route('sales.visits.index', ['date' => now()->subDays(2)->toDateString()]))
            ->assertOk()->assertSee('Toko Lama Sentosa')->assertDontSee('Toko Hari Ini Jaya');

        $this->actingAs($user)->get(route('sales.visits.index', ['date' => 'all']))
            ->assertOk()->assertSee('Toko Hari Ini Jaya')->assertSee('Toko Lama Sentosa');
    }

    public function test_empty_day_shows_a_friendly_message(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales);

        $this->actingAs($user)->get(route('sales.transactions.index'))->assertOk()->assertSee('Tidak ada transaksi pada tanggal ini');
        $this->actingAs($user)->get(route('sales.visits.index'))->assertOk()->assertSee('Tidak ada kunjungan pada tanggal ini');
    }
}
