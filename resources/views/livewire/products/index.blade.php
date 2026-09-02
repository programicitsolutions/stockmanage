<div>
    <x-page title="Products" description="SKU is unique. Present stock is shown from the ledger and cannot be edited here.">
        <x-slot:actions>
            @if (auth()->user()->isAccountant())
                <x-ui-link :href="route('products.create')" wire:navigate>Add product</x-ui-link>
            @endif
        </x-slot:actions>

        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name or SKU" class="w-full rounded-xl border-slate-300 text-sm">

        @if ($products->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                No products yet. Add a product to post opening stock to the ledger.
            </div>
        @else
            <div class="space-y-3 md:hidden">
                @foreach ($products as $product)
                    <article class="rounded-2xl bg-white border border-slate-200 p-4">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->sku }} · {{ $product->category?->name ?? 'No category' }}</p>
                            </div>
                            <p class="text-sm font-semibold">{{ $formatQty::quantity($stock[$product->id] ?? '0') }} {{ $product->unit }}</p>
                        </div>
                        @if (auth()->user()->isAccountant())
                            <a href="{{ route('products.edit', $product) }}" wire:navigate class="mt-3 inline-block text-sm text-teal-800">Edit details</a>
                        @endif
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
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                                <td class="px-4 py-3">{{ $product->name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $product->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ $formatQty::quantity($stock[$product->id] ?? '0') }} {{ $product->unit }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if (auth()->user()->isAccountant())
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
