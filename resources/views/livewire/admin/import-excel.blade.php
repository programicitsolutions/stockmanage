<div>
    <x-page title="Import Excel" description="Upload the client stock workbook, review every row, then confirm. Nothing is saved until you confirm. Opening stock is posted to the existing ledger.">
        @if ($step === 'upload' || $step === 'preview')
            <form wire:submit="validateFile" class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4 max-w-xl">
                <div>
                    <x-input-label for="file" value="Excel file (.xlsx)" />
                    <input wire:model="file" id="file" type="file" accept=".xlsx,.xls" class="mt-1 block w-full text-sm">
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    <p class="mt-2 text-xs text-slate-500">Validation and preview run first. Confirm Import is the only step that writes products or opening stock.</p>
                </div>
                <x-primary-button>Validate & preview</x-primary-button>
            </form>
        @endif

        @if ($step === 'preview')
            @if ($previousImport)
                <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-950 text-sm px-4 py-3">
                    This file was imported on {{ $previousImport->confirmed_at?->toDayDateTimeString() }}.
                    Confirming again will skip existing products and will not add a second opening balance.
                </div>
            @endif

            @foreach ($messages as $message)
                <p class="text-sm text-slate-600">{{ $message }}</p>
            @endforeach

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ([
                    'Total rows' => $summary['total_rows'] ?? 0,
                    'Valid / new' => $summary['new_products'] ?? 0,
                    'Invalid' => $summary['invalid_rows'] ?? 0,
                    'Existing' => $summary['existing_products'] ?? 0,
                    'Section rows' => $summary['section_rows'] ?? 0,
                    'Empty rows' => $summary['empty_rows'] ?? 0,
                    'Duplicates in file' => $summary['duplicate_in_file'] ?? 0,
                    'Needs attention' => $summary['attention_rows'] ?? 0,
                ] as $label => $value)
                    <div class="rounded-2xl bg-white border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-3 py-2 font-medium">Product Name</th>
                            <th class="px-3 py-2 font-medium">Category</th>
                            <th class="px-3 py-2 font-medium text-right">Stock</th>
                            <th class="px-3 py-2 font-medium text-right">Purchase Price</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($previewRows as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row['name'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $row['category'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-right">{{ $row['kind'] === 'product' ? $row['opening_qty'] : '—' }}</td>
                                <td class="px-3 py-2 text-right">{{ $row['kind'] === 'product' ? $row['purchase'] : '—' }}</td>
                                <td class="px-3 py-2">
                                    <span class="capitalize">{{ str_replace('_', ' ', $row['status']) }}</span>
                                    @if ($row['issues'])
                                        <p class="text-xs text-slate-500">{{ implode(' ', $row['issues']) }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-slate-500">No product rows to preview.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($previewTotal > 100)
                <p class="text-xs text-slate-500">Showing the first 100 of {{ $previewTotal }} preview rows. Totals above include the full file.</p>
            @endif

            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    wire:click="confirmImport"
                    wire:loading.attr="disabled"
                    wire:target="confirmImport"
                    @disabled(! $canImport)
                    class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="confirmImport">Confirm Import</span>
                    <span wire:loading wire:target="confirmImport">Importing…</span>
                </button>
                <button type="button" wire:click="startOver" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
            </div>
            @if (! $canImport)
                <p class="text-sm text-red-700">Nothing new can be imported from this file. Existing products will not receive a second opening balance.</p>
            @endif
        @endif

        @if ($step === 'done' && $result)
            <div class="rounded-2xl border border-teal-200 bg-teal-50 p-5 space-y-2">
                <p class="font-semibold text-teal-950">Import completed successfully</p>
                <ul class="text-sm text-teal-950 space-y-1">
                    <li>Products imported: {{ $result['products_imported'] ?? 0 }}</li>
                    <li>Opening stock posted: {{ $result['opening_posted'] ?? 0 }}</li>
                    <li>Existing products skipped: {{ $result['existing_skipped'] ?? 0 }}</li>
                    <li>Rows skipped: {{ $result['rows_skipped'] ?? 0 }}</li>
                    <li>Errors: {{ $result['errors'] ?? 0 }}</li>
                </ul>
                <div class="flex gap-3 pt-2">
                    <x-ui-link :href="route('products.index')" wire:navigate>View products</x-ui-link>
                    <x-ui-link :href="route('stock.live')" variant="secondary" wire:navigate>View live stock</x-ui-link>
                    <button type="button" wire:click="startOver" class="text-sm text-teal-900 underline">Import another file</button>
                </div>
            </div>
        @endif
    </x-page>
</div>
