<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">Dashboard</h1>
                <p class="text-sm text-slate-500">Present stock is calculated from the ledger. It cannot be typed into a cell.</p>
            </div>
            <span class="inline-flex self-start rounded-full bg-teal-50 text-teal-800 text-xs font-medium px-3 py-1 border border-teal-100">
                {{ auth()->user()->role?->name ?? 'Staff' }}
            </span>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <section class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Hello, {{ auth()->user()->name }}</h2>
            <p class="mt-2 text-slate-800 text-base leading-relaxed">
                This is Phase 1: foundation only. You can sign in, and the database is ready to record stock as a transaction ledger.
                Live stock screens, purchases, sales, and Excel import come in later phases.
            </p>
        </section>

        <section class="rounded-2xl bg-teal-800 text-white p-5 sm:p-6">
            <h2 class="text-lg font-semibold">Stock principle</h2>
            <p class="mt-3 font-mono text-sm sm:text-base leading-7 text-teal-50">
                Opening stock<br>
                + Stock IN<br>
                − Stock OUT<br>
                ± Approved adjustments<br>
                = Present stock
            </p>
            <p class="mt-4 text-sm text-teal-100">
                Present stock is never stored as an editable field. Changing a number in a spreadsheet cannot happen here.
            </p>
        </section>

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ([
                ['Live stock', 'See calculated present stock per product. Not yet available.'],
                ['Stock movement', 'Ledger of IN, OUT, opening, and adjustments. Not yet available.'],
                ['Purchases & sales', 'Accountant entry screens. Not yet available.'],
                ['Adjustments', 'Correction requests with later partner approval. Table is ready; UI is later.'],
                ['Reports', 'Profit and exports. Landing price structure is in the database, not in the UI yet.'],
                ['Audit history', 'Every ledger entry already records who, when, product, qty, price, and notes.'],
            ] as [$title, $body])
                <article class="rounded-2xl bg-white border border-slate-200 p-5">
                    <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $body }}</p>
                    <p class="mt-4 text-xs font-medium text-slate-400 uppercase tracking-wide">Coming in a later phase</p>
                </article>
            @endforeach
        </section>
    </div>
</x-app-layout>
