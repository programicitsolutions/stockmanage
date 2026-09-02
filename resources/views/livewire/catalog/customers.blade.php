<div>
    <x-page title="Customers">
        @if (auth()->user()->isAccountant())
            <form wire:submit="save" class="rounded-2xl bg-white border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <x-text-input wire:model="name" class="sm:col-span-2 w-full" placeholder="Customer name" />
                <x-text-input wire:model="code" class="w-full" placeholder="Code" />
                <div class="flex gap-2">
                    <x-text-input wire:model="phone" class="w-full" placeholder="Phone" />
                    <x-primary-button>Add</x-primary-button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('name')" />
            <x-input-error :messages="$errors->get('code')" />
        @endif

        @if ($customers->isEmpty())
            <p class="text-sm text-slate-500">No customers yet.</p>
        @else
            <ul class="rounded-2xl bg-white border border-slate-200 divide-y divide-slate-100">
                @foreach ($customers as $customer)
                    <li class="px-4 py-3">
                        <p class="font-medium">{{ $customer->name }}</p>
                        <p class="text-xs text-slate-500">{{ $customer->code ?: 'No code' }} {{ $customer->phone }}</p>
                    </li>
                @endforeach
            </ul>
            <div>{{ $customers->links() }}</div>
        @endif
    </x-page>
</div>
