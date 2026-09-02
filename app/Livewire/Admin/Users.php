<?php

namespace App\Livewire\Admin;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Users')]
class Users extends Component
{
    use WithPagination;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role_slug = 'accountant';

    public bool $receives_daily_summary = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_slug' => ['required', Rule::in(['partner', 'accountant', 'admin'])],
            'receives_daily_summary' => ['boolean'],
        ]);

        $role = Role::query()->where('slug', $validated['role_slug'])->firstOrFail();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $role->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'receives_daily_summary' => $validated['receives_daily_summary'],
        ]);

        $this->reset('name', 'email', 'password', 'receives_daily_summary');
        $this->role_slug = 'accountant';
        session()->flash('status', 'User created. They can sign in immediately.');
    }

    public function toggleActive(int $userId): void
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);

        $user = User::query()->findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('status', 'You cannot deactivate your own account.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
    }

    public function toggleSummary(int $userId): void
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);

        $user = User::query()->findOrFail($userId);
        $user->update(['receives_daily_summary' => ! $user->receives_daily_summary]);
    }

    public function render(): View
    {
        return view('livewire.admin.users', [
            'users' => User::query()->with('role')->orderBy('name')->paginate(20),
            'roles' => RoleSlug::cases(),
        ]);
    }
}
