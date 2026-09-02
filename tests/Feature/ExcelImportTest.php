<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Livewire\Admin\ImportExcel;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\ProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_import(): void
    {
        $this->actingAs(User::factory()->accountant()->create())
            ->get(route('import.excel'))
            ->assertForbidden();

        $this->actingAs(User::factory()->partner()->create())
            ->get(route('import.excel'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('import.excel'))
            ->assertOk();
    }

    public function test_validate_previews_without_writing_products(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->workbook([
            ['Product Name', 'STOCK', 'INPUT', 'OUT', 'AVAILABLE', 'SALE', 'PURCHASE', 'Total Value', 'PROFIT/UNIT'],
            ['NEOSEAL', '', '', '', '', '', '', '', ''],
            ['Sealant 100ml', 50, 60, 10, 100, 20, 12, 1200, 8],
        ]);

        Livewire::actingAs($admin)
            ->test(ImportExcel::class)
            ->set('file', $file)
            ->call('validateFile')
            ->assertSet('step', 'preview')
            ->assertSet('canImport', true)
            ->assertSee('Sealant 100ml')
            ->assertSee('NEOSEAL');

        $this->assertSame(0, Product::query()->count());
        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_confirm_imports_available_as_opening_stock_and_skips_section_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->workbook([
            ['Product Name', 'STOCK', 'INPUT', 'OUT', 'AVAILABLE', 'SALE', 'PURCHASE'],
            ['NEOSEAL', '', '', '', '', '', ''],
            ['Sealant 100ml', 50, 60, 10, 100, 20, 12],
            ['', '', '', '', '', '', ''],
        ]);

        Livewire::actingAs($admin)
            ->test(ImportExcel::class)
            ->set('file', $file)
            ->call('validateFile')
            ->call('confirmImport')
            ->assertSet('step', 'done');

        $this->assertSame(1, Product::query()->count());
        $product = Product::query()->first();
        $this->assertSame('Sealant 100ml', $product->name);
        $this->assertSame('NEOSEAL', $product->category->name);
        $this->assertSame('100.000', $product->presentStock());
        $this->assertSame('12.00', (string) $product->default_purchase_price);
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'transaction_type' => TransactionType::Opening->value,
            'quantity' => '100.000',
        ]);
        $this->assertSame(1, StockTransaction::query()->count());
    }

    public function test_second_import_of_the_same_file_does_not_double_opening_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $rows = [
            ['Product Name', 'AVAILABLE', 'PURCHASE'],
            ['Widget A', 40, 5],
        ];

        Livewire::actingAs($admin)
            ->test(ImportExcel::class)
            ->set('file', $this->workbook($rows))
            ->call('validateFile')
            ->call('confirmImport');

        $this->assertSame('40.000', Product::query()->first()->presentStock());

        Livewire::actingAs($admin)
            ->test(ImportExcel::class)
            ->set('file', $this->workbook($rows))
            ->call('validateFile')
            ->assertSet('canImport', false)
            ->call('confirmImport');

        $this->assertSame(1, Product::query()->count());
        $this->assertSame(1, StockTransaction::query()->where('transaction_type', TransactionType::Opening)->count());
        $this->assertSame('40.000', Product::query()->first()->presentStock());
    }

    public function test_existing_product_is_flagged_and_not_given_a_second_opening(): void
    {
        $admin = User::factory()->admin()->create();
        app(ProductCatalog::class)->create([
            'sku' => 'SKU-OLD',
            'name' => 'Widget A',
            'unit' => 'pcs',
            'opening_stock' => '10',
        ], $admin);

        Livewire::actingAs($admin)
            ->test(ImportExcel::class)
            ->set('file', $this->workbook([
                ['Product Name', 'AVAILABLE'],
                ['Widget A', 99],
            ]))
            ->call('validateFile')
            ->assertSet('canImport', false)
            ->call('confirmImport');

        $this->assertSame('10.000', Product::query()->where('name', 'Widget A')->first()->presentStock());
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function workbook(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'stk').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return UploadedFile::fake()->createWithContent('stock.xlsx', (string) file_get_contents($path));
    }
}
