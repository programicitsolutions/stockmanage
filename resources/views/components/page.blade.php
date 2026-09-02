@props(['title', 'description' => null])

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-500">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap gap-2">{{ $actions }}</div>
        @endisset
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-teal-200 bg-teal-50 text-teal-950 text-sm px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('approve') || $errors->has('status'))
        <div class="rounded-2xl border border-red-200 bg-red-50 text-red-800 text-sm px-4 py-3">
            {{ $errors->first('approve') ?: $errors->first('status') }}
        </div>
    @endif

    {{ $slot }}
</div>
