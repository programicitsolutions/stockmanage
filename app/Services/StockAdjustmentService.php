<?php

namespace App\Services;

use App\Enums\AdjustmentStatus;
use App\Enums\TransactionType;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(private StockCalculator $calculator) {}

    /**
     * @param  array{
     *     product_id: int,
     *     direction: TransactionType|string,
     *     quantity: numeric-string|int|float,
     *     reason: string,
     *     notes?: string|null
     * }  $attributes
     */
    public function request(array $attributes, User $actor): StockAdjustment
    {
        $direction = $attributes['direction'] instanceof TransactionType
            ? $attributes['direction']
            : TransactionType::from($attributes['direction']);

        if (! in_array($direction, [TransactionType::AdjustmentIn, TransactionType::AdjustmentOut], true)) {
            throw ValidationException::withMessages([
                'direction' => 'Adjustments must be adjustment in or adjustment out.',
            ]);
        }

        $quantity = bcadd((string) $attributes['quantity'], '0', 3);

        if (bccomp($quantity, '0', 3) !== 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }

        $systemQty = array_key_exists('system_qty', $attributes) && $attributes['system_qty'] !== null
            ? bcadd((string) $attributes['system_qty'], '0', 3)
            : $this->calculator->forProductId((int) $attributes['product_id']);

        $physicalQty = array_key_exists('physical_qty', $attributes) && $attributes['physical_qty'] !== null
            ? bcadd((string) $attributes['physical_qty'], '0', 3)
            : null;

        return StockAdjustment::query()->create([
            'product_id' => $attributes['product_id'],
            'direction' => $direction,
            'quantity' => $quantity,
            'status' => AdjustmentStatus::Pending,
            'reason' => $attributes['reason'],
            'notes' => $attributes['notes'] ?? null,
            'requested_by' => $actor->id,
            'system_qty' => $systemQty,
            'physical_qty' => $physicalQty,
        ]);
    }

    public function approveAndApply(StockAdjustment $adjustment, User $actor): StockAdjustment
    {
        if ($actor->id === $adjustment->requested_by) {
            throw ValidationException::withMessages([
                'status' => 'You cannot approve or reject an adjustment you requested.',
            ]);
        }

        if ($adjustment->status !== AdjustmentStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending adjustments can be approved.',
            ]);
        }

        return DB::transaction(function () use ($adjustment, $actor) {
            $transaction = $this->calculator->record([
                'product_id' => $adjustment->product_id,
                'transaction_type' => $adjustment->direction,
                'quantity' => $adjustment->quantity,
                'created_by' => $actor->id,
                'transaction_date' => now()->toDateString(),
                'notes' => 'Approved adjustment: '.$adjustment->reason,
                'reference_number' => 'ADJ-'.$adjustment->id,
            ]);

            $adjustment->update([
                'status' => AdjustmentStatus::Applied,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'applied_transaction_id' => $transaction->id,
            ]);

            return $adjustment->refresh();
        });
    }

    public function reject(StockAdjustment $adjustment, User $actor, ?string $notes = null): StockAdjustment
    {
        if ($actor->id === $adjustment->requested_by) {
            throw ValidationException::withMessages([
                'status' => 'You cannot approve or reject an adjustment you requested.',
            ]);
        }

        if ($adjustment->status !== AdjustmentStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending adjustments can be rejected.',
            ]);
        }

        $adjustment->update([
            'status' => AdjustmentStatus::Rejected,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'notes' => $notes ?: $adjustment->notes,
        ]);

        return $adjustment->refresh();
    }
}
