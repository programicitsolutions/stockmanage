<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Exceptions\ImmutableStockTransactionException;
use App\Exceptions\InsufficientStockException;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\StockCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_present_stock_is_calculated_from_the_ledger_only(): void
    {
        $user = User::factory()->accountant()->create();
        $product = Product::factory()->create(['opening_stock' => '999.000']);
        $calculator = app(StockCalculator::class);

        $calculator->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::Opening,
            'quantity' => '100',
            'created_by' => $user->id,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Opening',
        ]);

        $calculator->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::StockIn,
            'quantity' => '40',
            'unit_price' => '12.50',
            'reference_number' => 'PO-1',
            'created_by' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $calculator->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::StockOut,
            'quantity' => '25',
            'unit_price' => '18.00',
            'reference_number' => 'INV-1',
            'created_by' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertSame('115.000', $product->fresh()->presentStock());
        $this->assertSame('115.000', $calculator->forProductId($product->id));
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'opening_stock' => '115.000',
        ]);
    }

    public function test_stock_out_cannot_make_present_stock_negative(): void
    {
        $user = User::factory()->accountant()->create();
        $product = Product::factory()->create();
        $calculator = app(StockCalculator::class);

        $calculator->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::StockIn,
            'quantity' => '5',
            'created_by' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->expectException(InsufficientStockException::class);

        $calculator->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::StockOut,
            'quantity' => '6',
            'created_by' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);
    }

    public function test_historical_stock_transactions_cannot_be_updated_or_deleted(): void
    {
        $transaction = StockTransaction::factory()->create(['quantity' => '3.000']);

        try {
            $transaction->update(['quantity' => '99.000']);
            $this->fail('Expected an immutability exception when updating.');
        } catch (ImmutableStockTransactionException) {
            $this->assertSame('3.000', (string) $transaction->fresh()->quantity);
        }

        $this->expectException(ImmutableStockTransactionException::class);
        $transaction->delete();
    }

    public function test_creating_a_stock_transaction_writes_an_activity_log(): void
    {
        $user = User::factory()->accountant()->create();
        $product = Product::factory()->create();

        $transaction = app(StockCalculator::class)->record([
            'product_id' => $product->id,
            'transaction_type' => TransactionType::StockIn,
            'quantity' => '8',
            'unit_price' => '10.00',
            'reference_number' => 'PO-88',
            'notes' => 'Received',
            'created_by' => $user->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'stock_transaction.created',
            'subject_id' => $transaction->id,
        ]);

        $log = ActivityLog::query()->first();
        $this->assertSame('STOCK_IN', $log->properties['transaction_type']);
        $this->assertSame('8.000', $log->properties['quantity']);
        $this->assertSame('PO-88', $log->properties['reference_number']);
        $this->assertSame('Received', $log->properties['notes']);
    }

    public function test_product_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'SKU-100']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Product::factory()->create(['sku' => 'SKU-100']);
    }

    public function test_product_table_has_no_present_stock_column(): void
    {
        $columns = collect(DB::select('PRAGMA table_info(products)'))->pluck('name');

        $this->assertFalse($columns->contains('present_stock'));
        $this->assertFalse($columns->contains('available_stock'));
        $this->assertTrue($columns->contains('opening_stock'));
    }
}
