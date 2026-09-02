<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'role_id' => fn () => $this->roleId(RoleSlug::Partner),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function partner(): static
    {
        return $this->state(fn () => [
            'role_id' => $this->roleId(RoleSlug::Partner),
        ]);
    }

    public function accountant(): static
    {
        return $this->state(fn () => [
            'role_id' => $this->roleId(RoleSlug::Accountant),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role_id' => $this->roleId(RoleSlug::Admin),
        ]);
    }

    private function roleId(RoleSlug $role): int
    {
        return Role::query()->firstOrCreate(
            ['slug' => $role->value],
            ['name' => $role->label()],
        )->id;
    }
}
