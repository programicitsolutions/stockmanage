<div>
    <x-page
        :title="$mode === 'in' ? 'Stock in' : 'Stock out'"
        :description="$mode === 'in' ? 'Add every product on the invoice, review new stock, then confirm once. Each line posts STOCK_IN.' : 'Add every product issued, review remaining stock, then confirm once. Each line posts STOCK_OUT and cannot go below zero.'"
    >
        @if (! $reviewed)
            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
                <form wire:submit="addLine" class="xl:col-span-3 saas-card p-5 space-y-4">
                    <h2 class="font-semibold">Add a line</h2>
                    <p class="text-xs text-slate-500">Type a SKU and press Add, or search by name. Same product on two adds is combined.</p>
                    <div x-data="stockScanner">
                        <div class="flex items-center justify-between gap-2">
                            <x-input-label for="productSearch" value="SKU or name" />
                            <button type="button" @click="toggle($wire)" class="text-xs font-semibold text-teal-800 hover:text-teal-950">Scan barcode</button>
                        </div>
                        <x-text-input wire:model.live.debounce.200ms="productSearch" wire:keydown.enter.prevent="pickExactSku" id="productSearch" class="block mt-1 w-full" placeholder="Scan or type MAIN-TIN-50" autocomplete="off" />
                        <p class="mt-1 text-[11px] text-slate-500">Camera scan, USB scanner, or type the SKU and press Enter.</p>
                        <div x-cloak x-show="open" class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                            <video x-ref="video" class="h-48 w-full object-cover" playsinline></video>
                            <div class="flex items-center justify-between gap-2 px-3 py-2 text-xs text-slate-200">
                                <span x-text="status"></span>
                                <button type="button" class="font-semibold text-teal-200" @click="stop()">Close camera</button>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                        @if ($selectedProduct)
                            <p class="mt-2 text-sm">Selected: <span class="font-semibold">{{ $selectedProduct->sku }} — {{ $selectedProduct->name }}</span>
                                <button type="button" wire:click="$set('product_id', null)" class="text-teal-800 text-xs ml-2">Change</button>
                            </p>
                        @elseif ($productSearch !== '')
                            <ul class="mt-2 max-h-48 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50">
                                @forelse ($products as $product)
                                    <li>
                                        <button type="button" wire:click="selectProduct({{ $product->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-white">
                                            <span class="font-medium">{{ $product->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $product->sku }} · {{ $product->kind?->label() }}</span>
                                        </button>
                                    </li>
                                @empty
                                    <li class="px-3 py-2 text-sm text-slate-500">No match. Check the SKU.</li>
                                @endforelse
                            </ul>
                        @endif
                        @if ($present !== null)
                            <p class="mt-2 text-sm text-slate-500">Current stock: <span class="font-semibold text-slate-800">{{ $present }}</span> {{ $selectedProduct?->unit }}</p>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="quantity" value="Quantity" />
                            <x-text-input wire:model="quantity" id="quantity" type="number" step="0.001" min="0.001" class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="unit_price" :value="$mode === 'in' ? 'Price / unit' : 'Price / unit'" />
                            <x-text-input wire:model="unit_price" id="unit_price" type="number" step="0.01" min="0" class="block mt-1 w-full" />
                        </div>
                    </div>
                    <x-primary-button>Add line</x-primary-button>
                </form>

                <div class="xl:col-span-2 saas-card p-5 space-y-3">
                    <h2 class="font-semibold">Bill header</h2>
                    <div>
                        <x-input-label for="reference_number" :value="$mode === 'in' ? 'Invoice / GRN' : 'Invoice / issue no.'" />
                        <x-text-input wire:model="reference_number" id="reference_number" class="block mt-1 w-full" />
                    </div>
                    @if ($mode === 'in')
                        <div>
                            <x-input-label for="supplier_id" value="Supplier" />
                            <select wire:model="supplier_id" id="supplier_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">None</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div>
                            <x-input-label for="customer_id" value="Customer" />
                            <select wire:model="customer_id" id="customer_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">None</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <x-input-label for="transaction_date" value="Date" />
                        <x-text-input wire:model="transaction_date" id="transaction_date" type="date" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="notes" value="Remarks" />
                        <textarea wire:model="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                    </div>
                    @if ($mode === 'in')
                        <div class="border-t border-slate-100 pt-3 space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bill landing extras</p>
                            <p class="text-[11px] text-slate-500">Split across this bill by line value. Landing = purchase + extras. Does not change present stock.</p>
                            <div>
                                <x-input-label for="freight" value="Transport / freight" />
                                <x-text-input wire:model.blur="freight" id="freight" type="number" step="0.01" min="0" class="block mt-1 w-full" />
                            </div>
                            <div>
                                <x-input-label for="loading_unloading" value="Loading / unloading" />
                                <x-text-input wire:model.blur="loading_unloading" id="loading_unloading" type="number" step="0.01" min="0" class="block mt-1 w-full" />
                            </div>
                            <div>
                                <x-input-label for="other_charges" value="Other charges" />
                                <x-text-input wire:model.blur="other_charges" id="other_charges" type="number" step="0.01" min="0" class="block mt-1 w-full" />
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="saas-card p-5">
                <h2 class="font-semibold mb-3">Lines on this bill ({{ count($previewLines) }})</h2>
                @if ($previewLines === [])
                    <p class="text-sm text-slate-500">No lines yet. Add at least one product. A single product still works: add one line, then Review.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-slate-500">
                                <tr>
                                    <th class="py-2">Product</th>
                                    <th class="py-2 text-right">Qty</th>
                                    <th class="py-2 text-right">Current</th>
                                    <th class="py-2 text-right">{{ $mode === 'in' ? 'New' : 'Remaining' }}</th>
                                    @if ($mode === 'in')
                                        <th class="py-2 text-right">Landing / unit</th>
                                    @endif
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($previewLines as $i => $line)
                                    <tr>
                                        <td class="py-2">{{ $line['sku'] }} · {{ $line['name'] }}</td>
                                        <td class="py-2 text-right font-semibold">{{ $mode === 'in' ? '+' : '−' }}{{ $formatQty::quantity($line['quantity']) }}</td>
                                        <td class="py-2 text-right">{{ $formatQty::quantity($line['current']) }}</td>
                                        <td class="py-2 text-right">{{ $formatQty::quantity($line['projected']) }}</td>
                                        @if ($mode === 'in')
                                            <td class="py-2 text-right">{{ $formatMoney::money($line['landing_unit'] ?? '0') }}</td>
                                        @endif
                                        <td class="py-2 text-right"><button type="button" wire:click="removeLine({{ $i }})" class="text-xs text-red-700">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <form wire:submit="review" class="mt-4">
                        <x-primary-button>Review bill</x-primary-button>
                    </form>
                @endif
            </div>
        @else
            <div class="saas-card p-5 max-w-3xl space-y-4">
                <h2 class="font-semibold">Confirm {{ $mode === 'in' ? 'stock in' : 'stock out' }}</h2>
                @if ($reference_number)
                    <p class="text-sm text-slate-600">Reference: {{ $reference_number }}</p>
                @endif
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-slate-500">
                            <tr>
                                <th class="py-2">Product</th>
                                <th class="py-2 text-right">Current</th>
                                <th class="py-2 text-right">{{ $mode === 'in' ? 'In' : 'Out' }}</th>
                                <th class="py-2 text-right">{{ $mode === 'in' ? 'New' : 'Remaining' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            @foreach ($previewLines as $line)
                                <tr>
                                    <td class="py-2 font-sans">{{ $line['name'] }}</td>
                                    <td class="py-2 text-right">{{ $formatQty::quantity($line['current']) }}</td>
                                    <td class="py-2 text-right">{{ $mode === 'in' ? '+' : '−' }}{{ $formatQty::quantity($line['quantity']) }}</td>
                                    <td class="py-2 text-right font-semibold">{{ $formatQty::quantity($line['projected']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <form wire:submit="save" class="flex gap-2">
                    <x-primary-button wire:loading.attr="disabled" :disabled="$saving">
                        <span wire:loading.remove wire:target="save">Confirm and save</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </x-primary-button>
                    <button type="button" wire:click="editEntry" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Back</button>
                </form>
            </div>
        @endif
    </x-page>
</div>
