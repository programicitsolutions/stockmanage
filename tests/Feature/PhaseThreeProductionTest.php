<?php

namespace Tests\Feature;

use App\Enums\AdjustmentStatus;
use App\Enums\TransactionType;
use App\Livewire\Adjustments;
use App\Livewire\Admin\Users;
use App\Livewire\Dashboard;
use App\Livewire\Products\Show as ProductShow;
use App\Livewire\Reports;
use App\Livewire\Stock\Entry as StockEntry;
use App\Mail\DailyStockSummaryMail;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\ProductCatalog;
use App\Services\StockCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseThreeProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_post_stock_in_and_partners_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = User::factory()->partner()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'P3-1',
            'name' => 'Tin cans',
            'unit' => 'pcs',
            'opening_stock' => '360',
        ], $admin);

        $this->actingAs($partner)->get(route('stock.in'))->assertForbidden();
        $this->actingAs($admin)->get(route('stock.in'))->assertOk();

        Livewire::actingAs($admin)
            ->test(StockEntry::class)
            ->set('mode', 'in')
            ->set('product_id', $product->id)
            ->set('quantity', '100')
            ->set('transaction_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('460.000', $product->fresh()->presentStock());
    }

    public function test_physical_count_adjustment_cannot_be_self_approved(): void
    {
        $accountant = User::factory()->accountant()->create();
        $admin = User::factory()->admin()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'P3-2',
            'name' => 'PVC 50 ML',
            'unit' => 'pcs',
            'opening_stock' => '360',
        ], $accountant);

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'in')
            ->set('product_id', $product->id)
            ->set('quantity', '100')
            ->set('transaction_date', now()->toDateString())
            ->call('save');

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'out')
            ->set('product_id', $product->id)
            ->set('quantity', '80')
            ->set('transaction_date', now()->toDateString())
            ->call('save');

        $this->assertSame('380.000', $product->fresh()->presentStock());

        Livewire::actingAs($accountant)
            ->test(Adjustments::class)
            ->set('product_id', $product->id)
            ->set('physical_qty', '375')
            ->set('reason', 'Physical shortage')
            ->call('requestAdjustment')
            ->assertHasNoErrors();

        $adjustment = StockAdjustment::query()->firstOrFail();
        $this->assertSame('380.000', (string) $adjustment->system_qty);
        $this->assertSame('375.000', (string) $adjustment->physical_qty);
        $this->assertSame(TransactionType::AdjustmentOut, $adjustment->direction);
        $this->assertSame('5.000', (string) $adjustment->quantity);

        Livewire::actingAs($accountant)
            ->test(Adjustments::class)
            ->call('approve', $adjustment->id)
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(Adjustments::class)
            ->call('approve', $adjustment->id)
            ->assertHasNoErrors();

        $this->assertSame(AdjustmentStatus::Applied, $adjustment->fresh()->status);
        $this->assertSame('375.000', $product->fresh()->presentStock());
    }

    public function test_admin_cannot_approve_own_adjustment(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = User::factory()->partner()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'P3-3',
            'name' => 'Gasket',
            'unit' => 'pcs',
            'opening_stock' => '10',
        ], $admin);

        Livewire::actingAs($admin)
            ->test(Adjustments::class)
            ->set('product_id', $product->id)
            ->set('direction', TransactionType::AdjustmentOut->value)
            ->set('quantity', '1')
            ->set('reason', 'Damaged')
            ->call('requestAdjustment')
            ->assertHasNoErrors();

        $adjustment = StockAdjustment::query()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(Adjustments::class)
            ->call('approve', $adjustment->id)
            ->assertHasErrors(['approve']);

        $this->assertSame('10.000', $product->fresh()->presentStock());

        Livewire::actingAs($partner)
            ->test(Adjustments::class)
            ->call('approve', $adjustment->id)
            ->assertHasNoErrors();

        $this->assertSame('9.000', $product->fresh()->presentStock());
    }

    public function test_product_history_matches_ledger_math(): void
    {
        $accountant = User::factory()->accountant()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'P3-4',
            'name' => 'History item',
            'unit' => 'pcs',
            'opening_stock' => '360',
        ], $accountant);

        $calculator = app(StockCalculator::class);
        $calculator->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::StockIn,
            'quantity' => '100',
            'created_by' => $accountant->id,
            'transaction_date' => now()->toDateString(),
        ]);
        $calculator->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::StockOut,
            'quantity' => '80',
            'created_by' => $accountant->id,
            'transaction_date' => now()->toDateString(),
        ]);
        $calculator->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::AdjustmentOut,
            'quantity' => '5',
            'created_by' => $accountant->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $summary = $calculator->ledgerSummary($product->id);
        $this->assertSame('360.000', $summary['opening']);
        $this->assertSame('100.000', $summary['stock_in']);
        $this->assertSame('80.000', $summary['stock_out']);
        $this->assertSame('-5.000', $summary['adjustments']);
        $this->assertSame('375.000', $summary['present']);
        $this->assertSame('375.000', $product->fresh()->presentStock());

        Livewire::actingAs($accountant)
            ->test(ProductShow::class, ['product' => $product])
            ->assertSee('History item')
            ->assertSee('Current stock');
    }

    public function test_dashboard_and_reports_use_real_ledger_counts(): void
    {
        $partner = User::factory()->partner()->create();
        $accountant = User::factory()->accountant()->create();
        app(ProductCatalog::class)->create([
            'sku' => 'P3-5',
            'name' => 'Dashboard can',
            'unit' => 'pcs',
            'opening_stock' => '12',
            'minimum_stock_level' => '20',
            'default_purchase_price' => '2',
        ], $accountant);

        Livewire::actingAs($partner)
            ->test(Dashboard::class)
            ->assertSee('Dashboard can')
            ->assertSee('Total products')
            ->assertSee('12');

        $this->actingAs($partner)->get(route('reports.index'))->assertOk();

        Livewire::actingAs($partner)
            ->test(Reports::class)
            ->set('report', 'current')
            ->assertSee('Dashboard can');
    }

    public function test_accountant_cannot_open_user_admin(): void
    {
        $accountant = User::factory()->accountant()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($accountant)->get(route('users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('name', 'New clerk')
            ->set('email', 'clerk@stock.local')
            ->set('password', 'password12')
            ->set('role_slug', 'accountant')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'clerk@stock.local']);
    }

    public function test_daily_summary_command_mails_opted_in_users(): void
    {
        Mail::fake();
        $partner = User::factory()->partner()->create(['receives_daily_summary' => true]);
        User::factory()->accountant()->create(['receives_daily_summary' => false]);

        Artisan::call('stock:daily-summary');

        Mail::assertSent(DailyStockSummaryMail::class, function (DailyStockSummaryMail $mail) use ($partner) {
            return $mail->hasTo($partner->email);
        });
    }
}
