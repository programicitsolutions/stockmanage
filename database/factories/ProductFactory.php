<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'name' => fake()->unique()->words(3, true),
            'kind' => \App\Enums\ProductKind::Main,
            'category_id' => Category::factory(),
            'unit' => fake()->randomElement(['pcs', 'kg', 'box', 'ltr']),
            'opening_stock' => '0.000',
            'minimum_stock_level' => '0.000',
            'default_purchase_price' => '0.00',
            'default_selling_price' => '0.00',
            'is_active' => true,
        ];
    }
}
