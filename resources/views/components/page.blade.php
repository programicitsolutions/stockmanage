@props(['title', 'description' => null])

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap gap-2">{{ $actions }}</div>
        @endisset
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-teal-200 bg-teal-50 text-teal-900 text-sm px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('approve') || $errors->has('status'))
        <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 text-sm px-4 py-3">
            {{ $errors->first('approve') ?: $errors->first('status') }}
        </div>
    @endif

    {{ $slot }}
</div>
