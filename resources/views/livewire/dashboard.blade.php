<div>
    <x-page title="Dashboard" description="Present stock is calculated from the ledger. It cannot be typed into a cell.">
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            <a href="{{ route('products.index') }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Products</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $productCount }}</p>
            </a>
            <a href="{{ route('stock.movement') }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Ledger entries</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $transactionCount }}</p>
            </a>
            <a href="{{ route('adjustments.index') }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4 col-span-2 lg:col-span-1">
                <p class="text-xs uppercase tracking-wide text-slate-500">Pending adjustments</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $pendingAdjustments }}</p>
            </a>
        </div>

        <section class="rounded-2xl bg-teal-800 text-white p-5">
            <h2 class="font-semibold">Stock principle</h2>
            <p class="mt-2 font-mono text-sm leading-7 text-teal-50">
                Opening + Stock IN − Stock OUT ± approved adjustments = Present stock
            </p>
        </section>

        @if (auth()->user()->isAccountant())
            <div class="grid grid-cols-2 gap-3">
                <x-ui-link :href="route('stock.in')" wire:navigate>Stock in</x-ui-link>
                <x-ui-link :href="route('stock.out')" variant="secondary" wire:navigate>Stock out</x-ui-link>
            </div>
        @endif

        <section class="rounded-2xl bg-white border border-slate-200 p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-slate-900">Below minimum stock</h2>
                <a href="{{ route('stock.live', ['filter' => 'low']) }}" wire:navigate class="text-sm text-teal-800">View live stock</a>
            </div>
            @if ($lowStock->isEmpty())
                <p class="mt-3 text-sm text-slate-500">No products are below their minimum level, or no products have been added yet.</p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($lowStock as $product)
                        <li class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->sku }}</p>
                            </div>
                            <p class="text-sm font-semibold text-red-700">
                                {{ $formatQty::quantity($stock[$product->id] ?? '0') }} {{ $product->unit }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </x-page>
</div>
