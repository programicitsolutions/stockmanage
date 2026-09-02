<?php

namespace App\Livewire\Catalog;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Suppliers')]
class Suppliers extends Component
{
    use WithPagination;

    public string $name = '';

    public string $code = '';

    public string $phone = '';

    public function save(): void
    {
        abort_unless(auth()->user()?->isAccountant(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', 'unique:suppliers,code'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        Supplier::query()->create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'is_active' => true,
        ]);

        $this->reset('name', 'code', 'phone');
        session()->flash('status', 'Supplier added.');
    }

    public function render(): View
    {
        return view('livewire.catalog.suppliers', [
            'suppliers' => Supplier::query()->orderBy('name')->paginate(20),
        ]);
    }
}
