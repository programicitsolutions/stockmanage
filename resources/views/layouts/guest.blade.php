<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0f766e">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('icon.svg') }}" type="image/svg+xml">

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen grid lg:grid-cols-2">
            <div class="relative hidden lg:flex flex-col justify-between bg-slate-950 p-10 text-white overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(45,212,191,0.25),transparent_40%),radial-gradient(circle_at_80%_80%,rgba(56,189,248,0.18),transparent_40%)]"></div>
                <div class="relative">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                        <x-application-logo class="w-10 h-10 text-teal-400" />
                        <span class="text-xl font-semibold tracking-tight">{{ config('app.name') }}</span>
                    </a>
                    <p class="mt-10 max-w-sm text-3xl font-semibold leading-tight">Stock that always matches the ledger.</p>
                    <p class="mt-4 max-w-sm text-sm leading-6 text-slate-300">Opening + Stock in − Stock out ± approved adjustments. Accountants enter. Managers approve. Nobody types present stock.</p>
                </div>
                <p class="relative text-xs text-slate-500">Internal workspace for one business</p>
            </div>
            <div class="flex flex-col justify-center px-4 py-10 sm:px-10 bg-slate-50">
                <div class="lg:hidden mb-8 text-center">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                        <x-application-logo class="w-10 h-10 text-teal-700" />
                        <span class="text-xl font-semibold">{{ config('app.name') }}</span>
                    </a>
                </div>
                <div class="mx-auto w-full max-w-md rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/50">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
