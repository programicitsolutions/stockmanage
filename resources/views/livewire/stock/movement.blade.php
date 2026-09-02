<div>
    <x-page title="Stock movement" description="Historical ledger. Entries cannot be edited or deleted.">
        <div class="flex flex-col sm:flex-row gap-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search product, SKU, or reference" class="w-full rounded-xl border-slate-300 text-sm">
            <select wire:model.live="type" class="rounded-xl border-slate-300 text-sm">
                <option value="">All types</option>
                @foreach ($types as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="mine" class="rounded-xl border-slate-300 text-sm">
                <option value="">Everyone’s entries</option>
                <option value="1">My entries</option>
            </select>
        </div>

        @if ($movements->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                No movements yet.
            </div>
        @else
            <div class="space-y-3 md:hidden">
                @foreach ($movements as $row)
                    <article class="rounded-2xl bg-white border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ $row->transaction_type->label() }} · {{ $row->transaction_date->toDateString() }}</p>
                        <p class="font-semibold text-slate-900">{{ $row->product->name }}</p>
                        <p class="text-sm mt-1">
                            {{ $row->transaction_type->increasesStock() ? '+' : '−' }}{{ $formatQty::quantity($row->quantity) }} {{ $row->product->unit }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">{{ $row->createdBy->name }} · {{ $row->reference_number ?: 'No reference' }}</p>
                    </article>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Product</th>
                            <th class="px-4 py-3 font-medium text-right">Qty</th>
                            <th class="px-4 py-3 font-medium">Reference</th>
                            <th class="px-4 py-3 font-medium">Entered by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($movements as $row)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $row->transaction_date->toDateString() }}</td>
                                <td class="px-4 py-3">{{ $row->transaction_type->label() }}</td>
                                <td class="px-4 py-3">{{ $row->product->sku }} · {{ $row->product->name }}</td>
                                <td class="px-4 py-3 text-right font-semibold">
                                    {{ $row->transaction_type->increasesStock() ? '+' : '−' }}{{ $formatQty::quantity($row->quantity) }}
                                </td>
                                <td class="px-4 py-3">{{ $row->reference_number ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $row->createdBy->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $movements->links() }}</div>
        @endif
    </x-page>
</div>
