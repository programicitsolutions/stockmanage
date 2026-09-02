<div>
    <x-page title="Stock adjustments" description="Enter the physical count. The difference is requested, then a manager or admin posts ADJUSTMENT_IN or ADJUSTMENT_OUT. History is never edited.">
        @if (auth()->user()->canRequestAdjustments())
            <form wire:submit="requestAdjustment" class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4 max-w-xl">
                <h2 class="font-semibold">Physical count</h2>
                <div>
                    <x-input-label for="productSearch" value="Search product" />
                    <x-text-input wire:model.live.debounce.250ms="productSearch" id="productSearch" class="block mt-1 w-full" placeholder="Name or SKU" autocomplete="off" />
                    <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                    @if ($selectedProduct)
                        <p class="mt-2 text-sm">Selected: <span class="font-semibold">{{ $selectedProduct->sku }} — {{ $selectedProduct->name }}</span>
                            <button type="button" wire:click="$set('product_id', null)" class="text-teal-800 text-xs ml-2">Change</button>
                        </p>
                    @elseif ($productSearch !== '')
                        <ul class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50 max-h-56 overflow-y-auto">
                            @forelse ($products as $product)
                                <li>
                                    <button type="button" wire:click="selectProduct({{ $product->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-white">
                                        {{ $product->name }} <span class="text-xs text-slate-500">{{ $product->sku }}</span>
                                    </button>
                                </li>
                            @empty
                                <li class="px-3 py-2 text-sm text-slate-500">No matching products.</li>
                            @endforelse
                        </ul>
                    @endif
                </div>

                @if ($systemQty !== null)
                    <dl class="rounded-xl bg-slate-50 p-4 space-y-1 text-sm font-mono">
                        <div class="flex justify-between"><dt>System stock</dt><dd>{{ $formatQty::quantity($systemQty) }}</dd></div>
                        @if ($difference !== null)
                            <div class="flex justify-between"><dt>Difference</dt><dd>{{ bccomp($difference, '0', 3) === 1 ? '+' : '' }}{{ $formatQty::quantity($difference) }}</dd></div>
                        @endif
                    </dl>
                @endif

                <div>
                    <x-input-label for="physical_qty" value="Physical count" />
                    <x-text-input wire:model.live.debounce.200ms="physical_qty" id="physical_qty" type="number" step="0.001" min="0" class="block mt-1 w-full" placeholder="Counted quantity" />
                    <x-input-error :messages="$errors->get('physical_qty')" class="mt-2" />
                    <p class="mt-1 text-xs text-slate-500">Leave blank only if you need the older increase/decrease quantity form below.</p>
                </div>

                <div>
                    <x-input-label for="reason" value="Reason" />
                    <x-text-input wire:model="reason" id="reason" class="block mt-1 w-full" placeholder="Physical shortage, damaged, found extra…" />
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>
                <textarea wire:model="notes" rows="2" class="block w-full rounded-md border-gray-300" placeholder="Notes (optional)"></textarea>

                <details class="text-sm text-slate-600">
                    <summary class="cursor-pointer">Or enter direction and quantity instead</summary>
                    <div class="mt-3 space-y-3">
                        <select wire:model="direction" class="block w-full rounded-md border-gray-300">
                            <option value="ADJUSTMENT_IN">Increase stock (found extra)</option>
                            <option value="ADJUSTMENT_OUT">Decrease stock (missing / damaged)</option>
                        </select>
                        <x-text-input wire:model="quantity" type="number" step="0.001" min="0.001" class="w-full" placeholder="Quantity" />
                        <x-input-error :messages="$errors->get('quantity')" />
                    </div>
                </details>

                <x-primary-button wire:loading.attr="disabled" :disabled="$saving">Submit adjustment request</x-primary-button>
            </form>
        @endif

        @if ($adjustments->isEmpty())
            <p class="text-sm text-slate-500">No adjustment requests yet.</p>
        @else
            <div class="space-y-3">
                @foreach ($adjustments as $adjustment)
                    <article class="rounded-2xl bg-white border border-slate-200 p-4">
                        <div class="flex justify-between gap-3">
                            <p class="font-semibold">{{ $adjustment->product->name }}</p>
                            <span class="text-xs uppercase tracking-wide text-slate-500">{{ $adjustment->status->label() }}</span>
                        </div>
                        <p class="text-sm mt-1">{{ $adjustment->direction->label() }} · {{ $formatQty::quantity($adjustment->quantity) }} {{ $adjustment->product->unit }}</p>
                        @if ($adjustment->system_qty !== null)
                            <p class="text-sm text-slate-600 mt-1 font-mono">
                                System {{ $formatQty::quantity($adjustment->system_qty) }}
                                @if ($adjustment->physical_qty !== null)
                                    → physical {{ $formatQty::quantity($adjustment->physical_qty) }}
                                @endif
                            </p>
                        @endif
                        <p class="text-sm text-slate-600 mt-1">{{ $adjustment->reason }}</p>
                        <p class="text-xs text-slate-500 mt-2">
                            Requested by {{ $adjustment->requestedBy->name }}
                            · {{ $adjustment->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        </p>
                        @if ($adjustment->reviewedBy)
                            <p class="text-xs text-slate-500">
                                {{ $adjustment->status->label() }} by {{ $adjustment->reviewedBy->name }}
                                @if ($adjustment->reviewed_at)
                                    · {{ $adjustment->reviewed_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                @endif
                            </p>
                        @endif
                        @if (
                            auth()->user()->canApproveAdjustments()
                            && $adjustment->status->value === 'pending'
                            && $adjustment->requested_by !== auth()->id()
                        )
                            <div class="mt-3 flex gap-2">
                                <button wire:click="approve({{ $adjustment->id }})" wire:confirm="Post this adjustment to the ledger?" class="rounded-lg bg-teal-700 text-white text-sm px-3 py-1.5">Approve & post</button>
                                <button wire:click="reject({{ $adjustment->id }})" class="rounded-lg border border-slate-300 text-sm px-3 py-1.5">Reject</button>
                            </div>
                        @elseif (auth()->user()->canApproveAdjustments() && $adjustment->status->value === 'pending' && $adjustment->requested_by === auth()->id())
                            <p class="mt-2 text-xs text-amber-700">You requested this adjustment. Another manager or admin must approve it.</p>
                        @endif
                    </article>
                @endforeach
            </div>
            <div>{{ $adjustments->links() }}</div>
        @endif
    </x-page>
</div>
