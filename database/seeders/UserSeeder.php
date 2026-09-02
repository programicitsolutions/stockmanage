<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $partnerRole = Role::query()->where('slug', RoleSlug::Partner)->firstOrFail();
        $accountantRole = Role::query()->where('slug', RoleSlug::Accountant)->firstOrFail();
        $adminRole = Role::query()->where('slug', RoleSlug::Admin)->firstOrFail();

        foreach (range(1, 5) as $number) {
            User::query()->updateOrCreate(
                ['email' => "partner{$number}@stock.local"],
                [
                    'name' => "Partner {$number}",
                    'password' => 'password',
                    'role_id' => $partnerRole->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'receives_daily_summary' => true,
                ],
            );
        }

        User::query()->updateOrCreate(
            ['email' => 'accountant@stock.local'],
            [
                'name' => 'Accountant',
                'password' => 'password',
                'role_id' => $accountantRole->id,
                'email_verified_at' => now(),
                'is_active' => true,
                'receives_daily_summary' => false,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@stock.local'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
                'is_active' => true,
                'receives_daily_summary' => true,
            ],
        );
    }
}
