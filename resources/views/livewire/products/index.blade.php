<div>
    <x-page title="Products" description="SKU is unique. Present stock is shown from the ledger and cannot be edited here.">
        <x-slot:actions>
            @if (auth()->user()->canManageProducts())
                <x-ui-link :href="route('products.create')" wire:navigate>Add product</x-ui-link>
            @endif
        </x-slot:actions>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name or SKU" class="w-full rounded-xl border-slate-300 text-sm">
            <select wire:model.live="category" class="rounded-xl border-slate-300 text-sm">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="rounded-xl border-slate-300 text-sm">
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select wire:model.live="sort" class="rounded-xl border-slate-300 text-sm">
                <option value="name">Sort by name</option>
                <option value="sku">Sort by SKU</option>
            </select>
        </div>

        @if ($products->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                No products match these filters. Add a product to post opening stock to the ledger.
            </div>
        @else
            <div class="space-y-3 md:hidden">
                @foreach ($products as $product)
                    <article class="rounded-2xl bg-white border border-slate-200 p-4">
                        <div class="flex justify-between gap-3">
                            <div>
                                <a href="{{ route('products.show', $product) }}" wire:navigate class="font-semibold text-slate-900">{{ $product->name }}</a>
                                <p class="text-xs text-slate-500">{{ $product->sku }} · {{ $product->category?->name ?? 'No category' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold">{{ $formatQty::quantity($product->present_stock_calculated) }} {{ $product->unit }}</p>
                                <x-stock-status-badge :status="$product->stock_status" />
                            </div>
                        </div>
                        <div class="mt-3 flex gap-3 text-sm">
                            <a href="{{ route('products.show', $product) }}" wire:navigate class="text-teal-800">History</a>
                            @if (auth()->user()->canManageProducts())
                                <a href="{{ route('products.edit', $product) }}" wire:navigate class="text-teal-800">Edit details</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">SKU</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Category</th>
                            <th class="px-4 py-3 font-medium text-right">Present stock</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('products.show', $product) }}" wire:navigate class="text-slate-900 hover:text-teal-800">{{ $product->name }}</a>
                                    @unless ($product->is_active)
                                        <span class="ml-1 text-xs text-slate-400">Inactive</span>
                                    @endunless
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $product->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ $formatQty::quantity($product->present_stock_calculated) }} {{ $product->unit }}</td>
                                <td class="px-4 py-3"><x-stock-status-badge :status="$product->stock_status" /></td>
                                <td class="px-4 py-3 text-right space-x-3">
                                    <a href="{{ route('products.show', $product) }}" wire:navigate class="text-teal-800">History</a>
                                    @if (auth()->user()->canManageProducts())
                                        <a href="{{ route('products.edit', $product) }}" wire:navigate class="text-teal-800">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>{{ $products->links() }}</div>
        @endif
    </x-page>
</div>
