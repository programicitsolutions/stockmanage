<?php

namespace Tests\Feature;

use App\Livewire\Adjustments;
use App\Livewire\HelpChat;
use App\Livewire\Stock\Entry as StockEntry;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\ProductCatalog;
use App\Services\StockAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyEaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_line_stock_in_posts_one_ledger_row_per_product(): void
    {
        $accountant = User::factory()->accountant()->create();
        $catalog = app(ProductCatalog::class);
        $a = $catalog->create(['sku' => 'A-1', 'name' => 'Can A', 'unit' => 'pcs', 'opening_stock' => '10'], $accountant);
        $b = $catalog->create(['sku' => 'B-1', 'name' => 'Lid B', 'unit' => 'pcs', 'opening_stock' => '20'], $accountant);

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'in')
            ->set('transaction_date', now()->toDateString())
            ->set('reference_number', 'GRN-77')
            ->set('product_id', $a->id)
            ->set('quantity', '5')
            ->call('addLine')
            ->set('product_id', $b->id)
            ->set('quantity', '3')
            ->call('addLine')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('15.000', $a->fresh()->presentStock());
        $this->assertSame('23.000', $b->fresh()->presentStock());
    }

    public function test_count_sheet_creates_a_pending_adjustment_per_line(): void
    {
        $accountant = User::factory()->accountant()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'C-1',
            'name' => 'Counted can',
            'unit' => 'pcs',
            'opening_stock' => '50',
        ], $accountant);

        Livewire::actingAs($accountant)
            ->test(Adjustments::class)
            ->set('product_id', $product->id)
            ->set('physical_qty', '48')
            ->set('reason', 'Store count')
            ->call('addCountLine')
            ->call('submitCountSheet')
            ->assertHasNoErrors();

        $this->assertSame(1, StockAdjustment::query()->count());
        $this->assertSame('50.000', $product->fresh()->presentStock());
    }

    public function test_assistant_answers_with_live_stock_for_a_sku(): void
    {
        $user = User::factory()->accountant()->create();
        app(ProductCatalog::class)->create([
            'sku' => 'MAIN-TIN-50',
            'name' => '100 PVC 50 ML TIN CANS',
            'unit' => 'pcs',
            'opening_stock' => '360',
        ], $user);

        $reply = app(StockAssistant::class)->reply('MAIN-TIN-50', $user);

        $this->assertStringContainsString('360', $reply['text']);
        $this->assertStringContainsString('100 PVC 50 ML TIN CANS', $reply['text']);

        Livewire::actingAs($user)
            ->test(HelpChat::class)
            ->call('toggle')
            ->set('message', 'how do I stock in?')
            ->call('send')
            ->assertSee('Stock in');
    }
}
