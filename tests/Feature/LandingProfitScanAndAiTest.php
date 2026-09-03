<?php

namespace Tests\Feature;

use App\Enums\CostType;
use App\Enums\TransactionType;
use App\Livewire\HelpChat;
use App\Livewire\Reports;
use App\Livewire\Stock\Entry as StockEntry;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\LandingCostService;
use App\Services\ProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LandingProfitScanAndAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_freight_raises_landing_and_stock_out_computes_profit(): void
    {
        $accountant = User::factory()->accountant()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'CAN-1',
            'name' => 'Tin can',
            'unit' => 'pcs',
            'opening_stock' => '0',
            'default_purchase_price' => '10',
            'default_selling_price' => '25',
        ], $accountant);

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'in')
            ->set('product_id', $product->id)
            ->set('quantity', '10')
            ->set('unit_price', '10')
            ->set('freight', '50')
            ->set('transaction_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $in = StockTransaction::query()->where('product_id', $product->id)->where('transaction_type', TransactionType::StockIn)->firstOrFail();
        $this->assertTrue($in->costs()->where('cost_type', CostType::Purchase)->exists());
        $this->assertTrue($in->costs()->where('cost_type', CostType::Transport)->exists());
        $this->assertEquals(50.0, (float) $in->costs()->where('cost_type', CostType::Transport)->sum('amount'));

        $landing = app(LandingCostService::class)->averageUnitLanding($product->fresh());
        $this->assertSame('15.00', $landing);

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'out')
            ->set('product_id', $product->id)
            ->set('quantity', '2')
            ->set('unit_price', '25')
            ->set('transaction_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'transaction_type' => TransactionType::StockOut->value,
            'quantity' => '2.000',
        ]);

        $profit = app(LandingCostService::class)->periodProfit(now()->toDateString(), now()->toDateString(), (int) $product->id);
        $this->assertSame('50.00', $profit['revenue']);
        $this->assertSame('30.00', $profit['cogs']);
        $this->assertSame('20.00', $profit['profit']);
        $this->assertSame('8.000', $product->fresh()->presentStock());
    }

    public function test_scanned_sku_selects_the_product_on_stock_in(): void
    {
        $accountant = User::factory()->accountant()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'MAIN-TIN-50',
            'name' => '100 PVC 50 ML TIN CANS',
            'unit' => 'pcs',
            'opening_stock' => '5',
        ], $accountant);

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'in')
            ->call('applyScannedCode', 'main-tin-50')
            ->assertSet('product_id', $product->id)
            ->assertSee('Scan barcode');
    }

    public function test_profit_report_page_loads(): void
    {
        $user = User::factory()->partner()->create();

        Livewire::actingAs($user)
            ->test(Reports::class)
            ->set('report', 'profit')
            ->assertSee('Gross profit');
    }

    public function test_assistant_uses_openai_when_a_key_is_configured(): void
    {
        config(['services.openai.key' => 'sk-test', 'services.openai.model' => 'gpt-4o-mini']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'MAIN-TIN-50 has 360 on the ledger after landing is applied.',
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->accountant()->create();

        Livewire::actingAs($user)
            ->test(HelpChat::class)
            ->set('message', 'What is on MAIN-TIN-50?')
            ->call('send')
            ->assertSee('360 on the ledger');
    }
}
