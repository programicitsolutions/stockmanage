<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\StockTransaction;

class StockTransactionObserver
{
    public function created(StockTransaction $transaction): void
    {
        ActivityLog::query()->create([
            'user_id' => $transaction->created_by,
            'action' => 'stock_transaction.created',
            'subject_type' => $transaction->getMorphClass(),
            'subject_id' => $transaction->id,
            'properties' => [
                'product_id' => $transaction->product_id,
                'transaction_type' => $transaction->transaction_type->value,
                'quantity' => (string) $transaction->quantity,
                'unit_price' => $transaction->unit_price !== null ? (string) $transaction->unit_price : null,
                'reference_number' => $transaction->reference_number,
                'notes' => $transaction->notes,
                'transaction_date' => $transaction->transaction_date?->toDateString(),
                'created_by' => $transaction->created_by,
            ],
        ]);
    }
}
