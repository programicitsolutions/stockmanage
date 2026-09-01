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
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10 bg-slate-50">
            <div class="mb-6 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                    <x-application-logo class="w-10 h-10 text-teal-700" />
                    <span class="text-xl font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                </a>
                <p class="mt-2 text-sm text-slate-500">Internal stock ledger for one business</p>
            </div>

            <div class="w-full max-w-md bg-white shadow-sm border border-slate-200 rounded-2xl px-6 py-6">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
