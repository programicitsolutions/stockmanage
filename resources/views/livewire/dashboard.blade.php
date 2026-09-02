<div>
    <x-page title="Dashboard" description="Present stock is calculated from the ledger. It cannot be typed into a cell.">
        @if ($pendingAdjustments > 0 || $outCount > 0 || $lowCount > 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 space-y-1">
                @if ($pendingAdjustments > 0)
                    <p><a href="{{ route('adjustments.index') }}" wire:navigate class="font-medium underline">{{ $pendingAdjustments }} pending adjustment{{ $pendingAdjustments === 1 ? '' : 's' }}</a> waiting for approval.</p>
                @endif
                @if ($outCount > 0)
                    <p>{{ $outCount }} product{{ $outCount === 1 ? '' : 's' }} out of stock.</p>
                @endif
                @if ($lowCount > 0)
                    <p>{{ $lowCount }} product{{ $lowCount === 1 ? '' : 's' }} at or below the configured minimum (critical included).</p>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="{{ route('products.index') }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total products</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $productCount }}</p>
            </a>
            <a href="{{ route('stock.live') }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Available stock</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $formatQty::quantity($totalQty) }}</p>
            </a>
            <a href="{{ route('reports.index') }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Stock value</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $formatMoney::money($stockValue) }}</p>
                <p class="text-[11px] text-slate-400 mt-1">Qty × default purchase price</p>
            </a>
            <a href="{{ route('adjustments.index') }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Pending adjustments</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $pendingAdjustments }}</p>
            </a>
            <a href="{{ route('stock.live', ['filter' => 'low']) }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Low / critical</p>
                <p class="mt-1 text-2xl font-semibold text-amber-800">{{ $lowCount }}</p>
            </a>
            <a href="{{ route('stock.live', ['filter' => 'out']) }}" wire:navigate class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Out of stock</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $outCount }}</p>
            </a>
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Today’s stock in</p>
                <p class="mt-1 text-2xl font-semibold text-teal-800">+{{ $formatQty::quantity($todayIn) }}</p>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Today’s stock out</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">−{{ $formatQty::quantity($todayOut) }}</p>
            </div>
        </div>

        <section class="rounded-2xl bg-teal-800 text-white p-5">
            <h2 class="font-semibold">Stock principle</h2>
            <p class="mt-2 font-mono text-sm leading-7 text-teal-50">
                Opening + Stock IN − Stock OUT ± approved adjustments = Present stock
            </p>
        </section>

        @if (auth()->user()->canEnterStock())
            <div class="grid grid-cols-2 gap-3">
                <x-ui-link :href="route('stock.in')" wire:navigate>Stock in</x-ui-link>
                <x-ui-link :href="route('stock.out')" variant="secondary" wire:navigate>Stock out</x-ui-link>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <section class="rounded-2xl bg-white border border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Stock in vs stock out</h2>
                <canvas id="trendChart" class="mt-3 w-full h-56" height="220"></canvas>
            </section>
            <section class="rounded-2xl bg-white border border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Stock by category</h2>
                @if ($categoryBars === [])
                    <p class="mt-3 text-sm text-slate-500">No category stock yet.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($categoryBars as $bar)
                            <li class="text-sm flex justify-between gap-3">
                                <span class="text-slate-600 truncate">{{ $bar['label'] }}</span>
                                <span class="font-medium">{{ $formatQty::quantity($bar['qty']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
            <section class="rounded-2xl bg-white border border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Top products by stock</h2>
                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse ($topProducts as $row)
                        <li class="py-2 flex justify-between gap-3 text-sm">
                            <a href="{{ route('products.show', $row['id']) }}" wire:navigate class="truncate">{{ $row['name'] }}</a>
                            <span class="font-medium">{{ $formatQty::quantity($row['qty']) }} {{ $row['unit'] }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No products yet.</li>
                    @endforelse
                </ul>
            </section>
            <section class="rounded-2xl bg-white border border-slate-200 p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-slate-900">Needs attention</h2>
                    <a href="{{ route('stock.live', ['filter' => 'low']) }}" wire:navigate class="text-sm text-teal-800">Live stock</a>
                </div>
                @if ($lowList->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">No products are below their minimum level, or no minimums are configured.</p>
                @else
                    <ul class="mt-4 divide-y divide-slate-100">
                        @foreach ($lowList as $row)
                            <li class="py-3 flex items-center justify-between gap-3">
                                <div>
                                    <a href="{{ route('products.show', $row['id']) }}" wire:navigate class="font-medium text-slate-900">{{ $row['name'] }}</a>
                                    <p class="text-xs text-slate-500">{{ $row['sku'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-red-700">{{ $formatQty::quantity($row['qty']) }} {{ $row['unit'] }}</p>
                                    <x-stock-status-badge :status="$row['status']" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        @if (auth()->user()->isAccountant() && $recentMine->isNotEmpty())
            <section class="rounded-2xl bg-white border border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Your recent entries</h2>
                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($recentMine as $row)
                        <li class="py-2 text-sm flex justify-between gap-3">
                            <span>{{ $row->transaction_type->label() }} · {{ $row->product?->name }}</span>
                            <span class="font-medium">{{ $row->transaction_type->increasesStock() ? '+' : '−' }}{{ $formatQty::quantity($row->quantity) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </x-page>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const el = document.getElementById('trendChart');
            if (!el || typeof Chart === 'undefined') return;
            const labels = @json($trendLabels);
            new Chart(el, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: 'Stock in', data: @json($trendIn), borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,0.12)', tension: 0.3, fill: true },
                        { label: 'Stock out', data: @json($trendOut), borderColor: '#334155', backgroundColor: 'rgba(51,65,85,0.08)', tension: 0.3, fill: true },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true } },
                },
            });
        })();
    </script>
</div>
