<?php

namespace Database\Factories;

use App\Enums\AdjustmentStatus;
use App\Enums\TransactionType;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'direction' => TransactionType::AdjustmentIn,
            'quantity' => '1.000',
            'status' => AdjustmentStatus::Pending,
            'reason' => 'Count correction',
            'notes' => null,
            'requested_by' => User::factory(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'applied_transaction_id' => null,
        ];
    }
}
