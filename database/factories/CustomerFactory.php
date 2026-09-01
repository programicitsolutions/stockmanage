<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->bothify('CUS-####')),
            'contact_name' => fake()->name(),
            'phone' => fake()->numerify('##########'),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
