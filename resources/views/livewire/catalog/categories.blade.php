<div>
    <x-page title="Categories" description="Group products. Excel mapping is not part of this phase.">
        @if (auth()->user()->canManageProducts())
            <form wire:submit="save" class="rounded-2xl bg-white border border-slate-200 p-4 flex flex-col sm:flex-row gap-3">
                <x-text-input wire:model="name" class="w-full" placeholder="Category name" />
                <x-primary-button>Add</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('name')" />
        @endif

        @if ($categories->isEmpty())
            <p class="text-sm text-slate-500">No categories yet.</p>
        @else
            <ul class="rounded-2xl bg-white border border-slate-200 divide-y divide-slate-100">
                @foreach ($categories as $category)
                    <li class="px-4 py-3 flex justify-between">
                        <span>{{ $category->name }}</span>
                        <span class="text-sm text-slate-500">{{ $category->products_count }} products</span>
                    </li>
                @endforeach
            </ul>
            <div>{{ $categories->links() }}</div>
        @endif
    </x-page>
</div>
