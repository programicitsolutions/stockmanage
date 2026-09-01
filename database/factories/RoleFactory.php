<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $role = fake()->randomElement(RoleSlug::cases());

        return [
            'name' => $role->label(),
            'slug' => $role,
        ];
    }

    public function partner(): static
    {
        return $this->state(fn () => [
            'name' => RoleSlug::Partner->label(),
            'slug' => RoleSlug::Partner,
        ]);
    }

    public function accountant(): static
    {
        return $this->state(fn () => [
            'name' => RoleSlug::Accountant->label(),
            'slug' => RoleSlug::Accountant,
        ]);
    }
}
