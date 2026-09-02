<div>
    <x-page title="Reports" description="Every figure comes from the ledger. Export is a CSV of the same query you see on screen.">
        <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 rounded-2xl bg-white border border-slate-200 p-4" wire:submit.prevent="$refresh">
            <select wire:model.live="report" class="rounded-xl border-slate-300 text-sm">
                <option value="current">Current stock</option>
                <option value="in">Stock in</option>
                <option value="out">Stock out</option>
                <option value="movement">Stock movement</option>
                <option value="adjustments">Adjustments</option>
                <option value="low">Low stock</option>
                <option value="out_of_stock">Out of stock</option>
                <option value="history">Product history</option>
                <option value="value">Stock value</option>
                <option value="daily">Daily summary</option>
            </select>
            <input wire:model.live="from" type="date" class="rounded-xl border-slate-300 text-sm">
            <input wire:model.live="to" type="date" class="rounded-xl border-slate-300 text-sm">
            <input wire:model.live.debounce.250ms="product_search" type="search" placeholder="Find product by SKU or name" class="rounded-xl border-slate-300 text-sm">
            <select wire:model.live="product_id" class="rounded-xl border-slate-300 text-sm">
                <option value="">All products</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="category_id" class="rounded-xl border-slate-300 text-sm">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="type" class="rounded-xl border-slate-300 text-sm">
                <option value="">All types</option>
                @foreach ($types as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="user_id" class="rounded-xl border-slate-300 text-sm">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
            <div class="sm:col-span-2 lg:col-span-1 flex items-end">
                <x-primary-button type="button" wire:click="export">Export CSV</x-primary-button>
            </div>
        </form>

        @if ($summary)
            <section class="rounded-2xl bg-teal-800 text-white p-5">
                <h2 class="font-semibold">Daily stock summary · {{ $summary['date'] }}</h2>
                <ul class="mt-3 grid grid-cols-2 gap-2 text-sm text-teal-50">
                    <li>Total products: {{ $summary['productCount'] }}</li>
                    <li>Available stock: {{ $formatQty::quantity($summary['totalQty']) }}</li>
                    <li>Stock in today: +{{ $formatQty::quantity($summary['todayIn']) }}</li>
                    <li>Stock out today: −{{ $formatQty::quantity($summary['todayOut']) }}</li>
                    <li>Low stock: {{ $summary['lowCount'] }}</li>
                    <li>Out of stock: {{ $summary['outCount'] }}</li>
                    <li>Pending adjustments: {{ $summary['pendingAdjustments'] }}</li>
                </ul>
            </section>
        @endif

        @if ($table['rows'] === [])
            <p class="text-sm text-slate-500">No rows for these filters.</p>
        @else
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            @foreach ($table['headers'] as $header)
                                <th class="px-4 py-3 font-medium whitespace-nowrap">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($table['rows'] as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td class="px-4 py-2 whitespace-nowrap">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-slate-400">Showing up to 200 rows on screen. CSV export includes the full filtered set.</p>
        @endif
    </x-page>
</div>
