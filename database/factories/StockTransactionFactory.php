<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransaction>
 */
class StockTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'transaction_type' => TransactionType::StockIn,
            'quantity' => '10.000',
            'unit_price' => '100.00',
            'reference_number' => strtoupper(fake()->bothify('REF-####')),
            'supplier_id' => null,
            'customer_id' => null,
            'transaction_date' => now()->toDateString(),
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
