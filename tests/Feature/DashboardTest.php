<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_home_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_partners_and_accountants_can_view_the_dashboard(): void
    {
        $partner = User::factory()->partner()->create();
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($partner)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Present stock is calculated from the ledger')
            ->assertSee('Partner');

        $this->actingAs($accountant)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Accountant');
    }

    public function test_inactive_users_cannot_access_the_dashboard(): void
    {
        $user = User::factory()->partner()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }
}
