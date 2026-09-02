<div>
    @if ($open)
        <div class="fixed inset-0 z-[50]">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-[2px]"></div>
            <div class="relative z-10 flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200">
                    <div class="bg-gradient-to-br from-teal-700 via-teal-800 to-slate-900 px-6 py-5 text-white">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-teal-100">Stock Ledger tour</p>
                        <h2 class="mt-2 text-2xl font-semibold">{{ $current['title'] }}</h2>
                        <p class="mt-1 text-xs text-teal-100">Step {{ $step + 1 }} of {{ $total }}</p>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm leading-6 text-slate-600">{{ $current['body'] }}</p>
                        <div class="mt-4 flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="rounded-full bg-teal-600" style="width: {{ (($step + 1) / max($total, 1)) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" wire:click="complete" class="text-sm font-medium text-slate-500 hover:text-slate-800">Skip</button>
                        <div class="flex gap-2">
                            @if ($step > 0)
                                <button type="button" wire:click="back" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</button>
                            @endif
                            @if ($step === 0)
                                <button type="button" wire:click="startTour" class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Start walkthrough</button>
                            @elseif ($step < $total - 1)
                                <button type="button" wire:click="next" class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Next</button>
                            @else
                                <button type="button" wire:click="complete" class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Let’s go</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
