<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\StockTransaction;
use App\Services\StockCalculator;

class StockTransactionObserver
{
    public function created(StockTransaction $transaction): void
    {
        $transaction->loadMissing('product');

        $after = app(StockCalculator::class)->forProductId((int) $transaction->product_id);
        $before = bcsub($after, $transaction->signedQuantity(), 3);

        ActivityLog::query()->create([
            'user_id' => $transaction->created_by,
            'action' => 'stock_transaction.created',
            'subject_type' => $transaction->getMorphClass(),
            'subject_id' => $transaction->id,
            'properties' => [
                'product_id' => $transaction->product_id,
                'product_name' => $transaction->product?->name,
                'transaction_type' => $transaction->transaction_type->value,
                'quantity' => (string) $transaction->quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'unit_price' => $transaction->unit_price !== null ? (string) $transaction->unit_price : null,
                'reference_number' => $transaction->reference_number,
                'notes' => $transaction->notes,
                'transaction_date' => $transaction->transaction_date?->toDateString(),
                'created_by' => $transaction->created_by,
            ],
        ]);
    }
}
