<div>
    <x-page :title="$product->name" description="Current stock is explained from the existing ledger. This page does not calculate a second stock figure.">
        <x-slot:actions>
            @if (auth()->user()->canManageProducts())
                <x-ui-link :href="route('products.edit', $product)" variant="secondary" wire:navigate>Edit details</x-ui-link>
            @endif
            @if (auth()->user()->canEnterStock())
                <x-ui-link :href="route('stock.in')" wire:navigate>Stock in</x-ui-link>
            @endif
        </x-slot:actions>

        <p class="text-sm text-slate-500">{{ $product->sku }} · {{ $product->kind?->label() }} · {{ $product->category?->name ?? 'No category' }} · {{ $product->unit }}
            <x-stock-status-badge :status="$status" />
        </p>

        <dl class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Opening stock</dt>
                <dd class="mt-1 text-xl font-semibold">{{ $formatQty::quantity($summary['opening']) }}</dd>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Stock in</dt>
                <dd class="mt-1 text-xl font-semibold text-teal-800">+{{ $formatQty::quantity($summary['stock_in']) }}</dd>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Stock out</dt>
                <dd class="mt-1 text-xl font-semibold text-slate-900">−{{ $formatQty::quantity($summary['stock_out']) }}</dd>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Adjustments</dt>
                <dd class="mt-1 text-xl font-semibold">{{ bccomp($summary['adjustments'], '0', 3) === 1 ? '+' : '' }}{{ $formatQty::quantity($summary['adjustments']) }}</dd>
            </div>
            <div class="rounded-2xl bg-teal-800 text-white p-4 col-span-2 lg:col-span-1">
                <dt class="text-xs uppercase tracking-wide text-teal-100">Current stock</dt>
                <dd class="mt-1 text-xl font-semibold">{{ $formatQty::quantity($summary['present']) }} {{ $product->unit }}</dd>
            </div>
        </dl>

        @if ($summary['movements'] === [])
            <p class="text-sm text-slate-500">No ledger movements yet for this product.</p>
        @else
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium text-right">Quantity</th>
                            <th class="px-4 py-3 font-medium">User</th>
                            <th class="px-4 py-3 font-medium">Reference</th>
                            <th class="px-4 py-3 font-medium text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (array_reverse($summary['movements']) as $row)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="px-4 py-3">{{ $row['type']->label() }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ bccomp($row['quantity'], '0', 3) === 1 ? '+' : '' }}{{ $formatQty::quantity($row['quantity']) }}</td>
                                <td class="px-4 py-3">{{ $row['user'] ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $row['reference'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $formatQty::quantity($row['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-page>
</div>
