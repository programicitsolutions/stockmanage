<?php

namespace Tests\Feature;

use App\Enums\AdjustmentStatus;
use App\Enums\TransactionType;
use App\Livewire\Adjustments;
use App\Livewire\Products\Form as ProductForm;
use App\Livewire\Stock\Entry as StockEntry;
use App\Livewire\Stock\LiveStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\ProductCatalog;
use App\Services\StockCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseTwoStockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_product_posts_opening_stock_to_the_ledger(): void
    {
        $accountant = User::factory()->accountant()->create();

        Livewire::actingAs($accountant)
            ->test(ProductForm::class)
            ->set('sku', 'SKU-200')
            ->set('name', 'Widget')
            ->set('unit', 'pcs')
            ->set('opening_stock', '10')
            ->set('minimum_stock_level', '2')
            ->set('default_purchase_price', '5')
            ->set('default_selling_price', '9')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product = Product::query()->where('sku', 'SKU-200')->firstOrFail();

        $this->assertSame('10.000', $product->presentStock());
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'transaction_type' => TransactionType::Opening->value,
            'quantity' => '10.000',
        ]);
    }

    public function test_partners_cannot_open_stock_entry_screens(): void
    {
        $partner = User::factory()->partner()->create();

        $this->actingAs($partner)->get(route('stock.in'))->assertForbidden();
        $this->actingAs($partner)->get(route('stock.out'))->assertForbidden();
        $this->actingAs($partner)->get(route('products.create'))->assertForbidden();
    }

    public function test_accountant_stock_in_and_out_update_calculated_present_stock(): void
    {
        $accountant = User::factory()->accountant()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'SKU-300',
            'name' => 'Bolt',
            'unit' => 'pcs',
            'opening_stock' => '20',
        ], $accountant);

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'in')
            ->set('product_id', $product->id)
            ->set('quantity', '5')
            ->set('unit_price', '1.50')
            ->set('reference_number', 'PO-9')
            ->set('transaction_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('25.000', $product->fresh()->presentStock());

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'out')
            ->set('product_id', $product->id)
            ->set('quantity', '4')
            ->set('unit_price', '3.00')
            ->set('reference_number', 'INV-9')
            ->set('transaction_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('21.000', $product->fresh()->presentStock());
    }

    public function test_stock_out_form_rejects_quantity_above_present_stock(): void
    {
        $accountant = User::factory()->accountant()->create();
        $product = Product::factory()->create();

        Livewire::actingAs($accountant)
            ->test(StockEntry::class)
            ->set('mode', 'out')
            ->set('product_id', $product->id)
            ->set('quantity', '1')
            ->set('transaction_date', now()->toDateString())
            ->call('save')
            ->assertHasErrors(['quantity']);
    }

    public function test_live_stock_page_shows_calculated_quantity_not_an_editor(): void
    {
        $user = User::factory()->partner()->create();
        $accountant = User::factory()->accountant()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'SKU-400',
            'name' => 'Washer',
            'unit' => 'pcs',
            'opening_stock' => '7',
        ], $accountant);

        Livewire::actingAs($user)
            ->test(LiveStock::class)
            ->assertSee('Washer')
            ->assertSee('7')
            ->assertDontSee('name="present_stock"')
            ->assertSee('There is no cell to edit them');

        $this->assertSame('7.000', app(StockCalculator::class)->forProductIds([$product->id])[$product->id]);
    }

    public function test_partner_approval_posts_adjustment_to_the_ledger(): void
    {
        $accountant = User::factory()->accountant()->create();
        $partner = User::factory()->partner()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'SKU-500',
            'name' => 'Nut',
            'unit' => 'pcs',
            'opening_stock' => '8',
        ], $accountant);

        Livewire::actingAs($accountant)
            ->test(Adjustments::class)
            ->set('product_id', $product->id)
            ->set('direction', TransactionType::AdjustmentOut->value)
            ->set('quantity', '2')
            ->set('reason', 'Damaged in store')
            ->call('requestAdjustment')
            ->assertHasNoErrors();

        $adjustment = StockAdjustment::query()->firstOrFail();
        $this->assertSame(AdjustmentStatus::Pending, $adjustment->status);
        $this->assertSame('8.000', $product->fresh()->presentStock());

        Livewire::actingAs($partner)
            ->test(Adjustments::class)
            ->call('approve', $adjustment->id)
            ->assertHasNoErrors();

        $this->assertSame('6.000', $product->fresh()->presentStock());
        $this->assertSame(AdjustmentStatus::Applied, $adjustment->fresh()->status);
    }

    public function test_opening_stock_cannot_be_changed_on_update(): void
    {
        $accountant = User::factory()->accountant()->create();
        $product = app(ProductCatalog::class)->create([
            'sku' => 'SKU-600',
            'name' => 'Gasket',
            'unit' => 'pcs',
            'opening_stock' => '3',
        ], $accountant);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ProductCatalog::class)->update($product, ['opening_stock' => '99']);
    }
}
