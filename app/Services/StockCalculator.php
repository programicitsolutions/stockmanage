<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockQuantityException;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockCalculator
{
    public function forProduct(Product $product): string
    {
        return $this->forProductId((int) $product->id);
    }

    public function forProductId(int $productId): string
    {
        return $this->forProductIds([$productId])[$productId];
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, string>
     */
    public function forProductIds(array $productIds): array
    {
        $scale = (int) config('stock.quantity_scale', 3);
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        $stock = [];

        foreach ($ids as $id) {
            $stock[$id] = $this->normalize('0', $scale);
        }

        if ($ids === []) {
            return $stock;
        }

        $rows = StockTransaction::query()
            ->whereIn('product_id', $ids)
            ->get(['product_id', 'transaction_type', 'quantity']);

        foreach ($rows as $row) {
            $id = (int) $row->product_id;
            $stock[$id] = bcadd($stock[$id], $row->signedQuantity(), $scale);
        }

        return $stock;
    }

    /**
     * Opening + IN − OUT ± adjustments from the same ledger used for present stock.
     *
     * @return array{
     *     opening: string,
     *     stock_in: string,
     *     stock_out: string,
     *     adjustments: string,
     *     present: string,
     *     movements: list<array<string, mixed>>
     * }
     */
    public function ledgerSummary(int $productId): array
    {
        $scale = (int) config('stock.quantity_scale', 3);
        $opening = $this->normalize('0', $scale);
        $stockIn = $this->normalize('0', $scale);
        $stockOut = $this->normalize('0', $scale);
        $adjustments = $this->normalize('0', $scale);
        $balance = $this->normalize('0', $scale);
        $movements = [];

        $rows = StockTransaction::query()
            ->with('createdBy')
            ->where('product_id', $productId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $signed = $row->signedQuantity();
            $balance = bcadd($balance, $signed, $scale);

            match ($row->transaction_type) {
                TransactionType::Opening => $opening = bcadd($opening, (string) $row->quantity, $scale),
                TransactionType::StockIn => $stockIn = bcadd($stockIn, (string) $row->quantity, $scale),
                TransactionType::StockOut => $stockOut = bcadd($stockOut, (string) $row->quantity, $scale),
                TransactionType::AdjustmentIn, TransactionType::AdjustmentOut => $adjustments = bcadd($adjustments, $signed, $scale),
            };

            $movements[] = [
                'id' => $row->id,
                'date' => $row->transaction_date?->toDateString(),
                'type' => $row->transaction_type,
                'quantity' => $signed,
                'user' => $row->createdBy?->name,
                'reference' => $row->reference_number,
                'notes' => $row->notes,
                'balance' => $balance,
            ];
        }

        return [
            'opening' => $opening,
            'stock_in' => $stockIn,
            'stock_out' => $stockOut,
            'adjustments' => $adjustments,
            'present' => $balance,
            'movements' => $movements,
        ];
    }

    /**
     * Persist a ledger row. Present stock is never written to products.
     *
     * @param  array{
     *     product_id: int,
     *     transaction_type: TransactionType|string,
     *     quantity: numeric-string|int|float,
     *     created_by: int,
     *     transaction_date: \DateTimeInterface|string,
     *     unit_price?: numeric-string|int|float|null,
     *     reference_number?: string|null,
     *     supplier_id?: int|null,
     *     customer_id?: int|null,
     *     notes?: string|null
     * }  $attributes
     */
    public function record(array $attributes): StockTransaction
    {
        $scale = (int) config('stock.quantity_scale', 3);
        $type = $attributes['transaction_type'] instanceof TransactionType
            ? $attributes['transaction_type']
            : TransactionType::from($attributes['transaction_type']);

        $quantity = $this->normalize((string) $attributes['quantity'], $scale);

        if (bccomp($quantity, '0', $scale) !== 1) {
            throw new InvalidStockQuantityException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($attributes, $type, $quantity, $scale) {
            $product = Product::query()->lockForUpdate()->findOrFail($attributes['product_id']);

            if ($type === TransactionType::Opening) {
                $existingOpening = StockTransaction::query()
                    ->where('product_id', $product->id)
                    ->where('transaction_type', TransactionType::Opening)
                    ->lockForUpdate()
                    ->exists();

                if ($existingOpening) {
                    throw ValidationException::withMessages([
                        'transaction_type' => 'Opening stock has already been recorded for this product.',
                    ]);
                }
            }

            $before = $this->forProduct($product);

            if (! $type->increasesStock() && ! config('stock.allow_negative_stock')) {
                $projected = bcsub($before, $quantity, $scale);

                if (bccomp($projected, '0', $scale) === -1) {
                    throw InsufficientStockException::forProduct(
                        $product->name,
                        $before,
                        $quantity,
                    );
                }
            }

            $transaction = StockTransaction::query()->create([
                'product_id' => $product->id,
                'transaction_type' => $type,
                'quantity' => $quantity,
                'unit_price' => $attributes['unit_price'] ?? null,
                'reference_number' => $attributes['reference_number'] ?? null,
                'supplier_id' => $attributes['supplier_id'] ?? null,
                'customer_id' => $attributes['customer_id'] ?? null,
                'transaction_date' => $attributes['transaction_date'],
                'notes' => $attributes['notes'] ?? null,
                'created_by' => $attributes['created_by'],
                'excel_import_id' => $attributes['excel_import_id'] ?? null,
            ]);

            return $transaction;
        });
    }

    private function normalize(string $value, int $scale): string
    {
        return bcadd($value, '0', $scale);
    }
}
