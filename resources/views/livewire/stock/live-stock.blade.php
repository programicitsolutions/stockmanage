<div>
    <x-page title="Live stock" description="These figures are calculated from the ledger. There is no cell to edit them.">
        <div class="flex flex-col sm:flex-row gap-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name or SKU" class="w-full rounded-xl border-slate-300 text-sm">
            <select wire:model.live="filter" class="rounded-xl border-slate-300 text-sm">
                <option value="all">All products</option>
                <option value="low">Low / critical</option>
                <option value="out">Out of stock</option>
            </select>
        </div>

        @if ($products->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                No stock to show yet. Add a product, then post stock in.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($products as $product)
                    <article class="rounded-2xl bg-white border border-slate-200 p-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                            <p class="text-xs text-slate-500">{{ $product->sku }} · min {{ $formatQty::quantity($product->minimum_stock_level) }} {{ $product->unit }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-semibold {{ ($product->is_low ?? false) ? 'text-red-700' : 'text-slate-900' }}">
                                {{ $formatQty::quantity($product->present_stock_calculated) }}
                            </p>
                            <p class="text-xs text-slate-500">{{ $product->unit }}</p>
                            <x-stock-status-badge :status="$product->stock_status ?? 'healthy'" />
                        </div>
                    </article>
                @endforeach
            </div>
            <div>{{ $products->links() }}</div>
        @endif
    </x-page>
</div>
