<div>
    <x-page title="Stock slip" description="Print this for the store copy. Figures already posted to the ledger.">
        <x-slot:actions>
            <button type="button" onclick="window.print()" class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white print:hidden">Print</button>
            <x-ui-link :href="route('stock.movement')" variant="secondary" wire:navigate class="print:hidden">Back to movement</x-ui-link>
        </x-slot:actions>

        <div class="saas-card p-6 print:border-0 print:shadow-none">
            <div class="flex justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ ($payload['mode'] ?? 'in') === 'in' ? 'Goods received note' : 'Goods issue note' }}</p>
                    <h2 class="text-xl font-semibold">{{ config('app.name') }}</h2>
                </div>
                <div class="text-sm text-right">
                    <p>Date: {{ $payload['date'] ?? now()->toDateString() }}</p>
                    <p>Reference: {{ $payload['reference'] ?: '—' }}</p>
                    <p>Entered by: {{ auth()->user()->name }}</p>
                </div>
            </div>

            <table class="mt-6 min-w-full text-sm">
                <thead class="text-left text-slate-500 border-b">
                    <tr>
                        <th class="py-2">SKU</th>
                        <th class="py-2">Product</th>
                        <th class="py-2 text-right">Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="py-2 font-mono text-xs">{{ $row->product->sku }}</td>
                            <td class="py-2">{{ $row->product->name }}</td>
                            <td class="py-2 text-right font-semibold">
                                {{ $row->transaction_type->increasesStock() ? '+' : '−' }}{{ $formatQty::quantity($row->quantity) }} {{ $row->product->unit }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if (! empty($payload['notes']))
                <p class="mt-4 text-sm text-slate-600">Remarks: {{ $payload['notes'] }}</p>
            @endif
            <p class="mt-8 text-xs text-slate-400">Opening + IN − OUT ± approved adjustments = present stock. This slip is a copy of ledger rows, not a second stock figure.</p>
        </div>
    </x-page>
</div>
