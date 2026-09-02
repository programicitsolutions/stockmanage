<?php

namespace App\Livewire\Catalog;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Categories')]
class Categories extends Component
{
    use WithPagination;

    public string $name = '';

    public function save(): void
    {
        abort_unless(auth()->user()?->canManageProducts(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        Category::query()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(4)),
            'is_active' => true,
        ]);

        $this->reset('name');
        session()->flash('status', 'Category added.');
    }

    public function render(): View
    {
        return view('livewire.catalog.categories', [
            'categories' => Category::query()->withCount('products')->orderBy('name')->paginate(20),
        ]);
    }
}
