<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0f766e">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('icon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('icon.svg') }}">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak]{display:none !important}</style>
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(900px_circle_at_0%_0%,rgba(13,148,136,0.12),transparent_45%),radial-gradient(700px_circle_at_100%_0%,rgba(14,165,233,0.10),transparent_40%)]"></div>
        <div x-data="{ sidebar: false }" class="min-h-screen lg:flex">
            <div
                x-cloak
                x-show="sidebar"
                class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
                @click="sidebar = false"
            ></div>

            <div
                class="fixed inset-y-0 left-0 z-40 w-72 transform transition-transform duration-200 lg:static lg:z-0 lg:translate-x-0 lg:h-screen lg:sticky lg:top-0"
                :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <livewire:layout.navigation />
            </div>

            <div class="flex min-w-0 flex-1 flex-col lg:pl-0">
                <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/80 backdrop-blur">
                    <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div class="flex items-center gap-3 min-w-0">
                            <button type="button" class="lg:hidden rounded-xl border border-slate-200 bg-white p-2 text-slate-600" @click="sidebar = true" aria-label="Open menu">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $title ?? config('app.name') }}</p>
                                <p class="hidden sm:block truncate text-xs text-slate-500">Ledger-backed stock · {{ now()->timezone(config('app.timezone'))->format('d M Y') }}</p>
                            </div>
                        </div>
                        @if (auth()->user()?->canEnterStock())
                            <div class="flex gap-2">
                                <a href="{{ route('stock.in') }}" wire:navigate class="hidden sm:inline-flex rounded-xl bg-teal-700 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-800">Stock in</a>
                                <a href="{{ route('stock.out') }}" wire:navigate class="inline-flex rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Stock out</a>
                            </div>
                        @endif
                    </div>
                </header>

                @if (isset($header))
                    <div class="border-b border-slate-200 bg-white">
                        <div class="px-4 py-4 sm:px-6">{{ $header }}</div>
                    </div>
                @endif

                <main class="flex-1 pb-16 sm:pb-10">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <livewire:onboarding-tour />

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').catch(() => {});
                });
            }
        </script>
    </body>
</html>
