<?php

namespace Tests\Feature;

use App\Enums\ProductKind;
use App\Enums\TransactionType;
use App\Livewire\OnboardingTour;
use App\Livewire\Products\Form as ProductForm;
use App\Livewire\Products\Index as ProductIndex;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogAndOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeder_adds_main_and_inner_products_once(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class, CatalogSeeder::class]);

        $this->assertTrue(Product::query()->where('sku', 'MAIN-TIN-50')->exists());
        $this->assertTrue(Product::query()->where('sku', 'INN-LID-50')->exists());
        $this->assertSame(ProductKind::Main, Product::query()->where('sku', 'MAIN-TIN-50')->first()->kind);
        $this->assertSame(ProductKind::Inner, Product::query()->where('sku', 'INN-LID-50')->first()->kind);

        $openings = StockTransaction::query()->where('transaction_type', TransactionType::Opening)->count();
        $this->seed(CatalogSeeder::class);
        $this->assertSame($openings, StockTransaction::query()->where('transaction_type', TransactionType::Opening)->count());
        $this->assertSame('360.000', Product::query()->where('sku', 'MAIN-TIN-50')->first()->presentStock());
    }

    public function test_first_login_shows_walkthrough_until_completed(): void
    {
        $user = User::factory()->accountant()->create();

        Livewire::actingAs($user)
            ->test(OnboardingTour::class)
            ->assertSet('open', true)
            ->assertSee('Welcome')
            ->call('complete')
            ->assertSet('open', false);

        $this->assertNotNull($user->fresh()->onboarding_completed_at);

        Livewire::actingAs($user->fresh())
            ->test(OnboardingTour::class)
            ->assertSet('open', false);
    }

    public function test_accountant_can_create_an_inner_product(): void
    {
        $accountant = User::factory()->accountant()->create();

        Livewire::actingAs($accountant)
            ->test(ProductForm::class)
            ->set('sku', 'INN-TEST')
            ->set('name', 'Test inner lid')
            ->set('kind', 'inner')
            ->set('unit', 'pcs')
            ->set('opening_stock', '5')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('sku', 'INN-TEST')->firstOrFail();
        $this->assertSame(ProductKind::Inner, $product->kind);
        $this->assertSame('5.000', $product->presentStock());
    }

    public function test_products_index_loads_with_kind_and_status_filters(): void
    {
        $user = User::factory()->accountant()->create();

        $this->actingAs($user)->get(route('products.index'))->assertOk();

        Livewire::actingAs($user)
            ->test(ProductIndex::class)
            ->set('kind', 'inner')
            ->set('status', 'active')
            ->assertHasNoErrors()
            ->assertOk();
    }
}
