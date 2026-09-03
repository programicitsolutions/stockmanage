<?php

namespace App\Services;

use App\Enums\ProductKind;
use App\Enums\TransactionType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductCatalog
{
    public function __construct(private StockCalculator $calculator) {}

    /**
     * @param  array{
     *     sku: string,
     *     name: string,
     *     kind?: \App\Enums\ProductKind|string,
     *     category_id?: int|null,
     *     unit: string,
     *     opening_stock?: numeric-string|int|float,
     *     minimum_stock_level?: numeric-string|int|float,
     *     default_purchase_price?: numeric-string|int|float,
     *     default_selling_price?: numeric-string|int|float,
     *     is_active?: bool
     * }  $attributes
     */
    public function create(array $attributes, User $actor): Product
    {
        return DB::transaction(function () use ($attributes, $actor) {
            $opening = bcadd((string) ($attributes['opening_stock'] ?? '0'), '0', 3);

            $product = Product::query()->create([
            'sku' => $attributes['sku'],
            'name' => $attributes['name'],
            'kind' => $attributes['kind'] ?? ProductKind::Main,
            'category_id' => $attributes['category_id'] ?? null,
                'unit' => $attributes['unit'],
                'opening_stock' => $opening,
                'minimum_stock_level' => $attributes['minimum_stock_level'] ?? '0',
                'default_purchase_price' => $attributes['default_purchase_price'] ?? '0',
                'default_selling_price' => $attributes['default_selling_price'] ?? '0',
                'is_active' => $attributes['is_active'] ?? true,
            ]);

            if (bccomp($opening, '0', 3) === 1) {
                $openingRow = $this->calculator->record([
                    'product_id' => $product->id,
                    'transaction_type' => TransactionType::Opening,
                    'quantity' => $opening,
                    'created_by' => $actor->id,
                    'transaction_date' => now()->toDateString(),
                    'notes' => $attributes['opening_notes'] ?? 'Opening stock at product setup',
                    'reference_number' => $attributes['opening_reference'] ?? null,
                    'excel_import_id' => $attributes['excel_import_id'] ?? null,
                    'unit_price' => $attributes['default_purchase_price'] ?? null,
                ]);
                app(LandingCostService::class)->attachPurchaseFromUnitPrice($openingRow);
            }

            return $product;
        });
    }

    /**
     * Opening stock cannot be changed after create. Present stock is never written here.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        if (array_key_exists('opening_stock', $attributes)
            && bccomp((string) $attributes['opening_stock'], (string) $product->opening_stock, 3) !== 0) {
            throw ValidationException::withMessages([
                'opening_stock' => 'Opening stock cannot be edited. Record an adjustment instead.',
            ]);
        }

        unset($attributes['opening_stock'], $attributes['present_stock'], $attributes['available_stock']);

        $product->update($attributes);

        return $product->refresh();
    }
}
