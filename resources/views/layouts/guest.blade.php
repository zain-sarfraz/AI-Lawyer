<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AI Lawyer') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-(--canvas) text-(--ink) antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        <aside class="relative hidden overflow-hidden bg-navy-900 p-12 text-slate-100 lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-teal-500/20 blur-3xl"></div>
            <div class="absolute bottom-10 left-10 h-40 w-40 rounded-full bg-teal-400/10 blur-2xl"></div>
            <div>
                <div class="flex items-center gap-3">
                    <div class="grid h-11 w-11 place-items-center rounded-full bg-teal-500 text-sm font-extrabold text-navy-950">AL</div>
                    <div>
                        <div class="text-xl font-extrabold tracking-tight text-white">AI Lawyer</div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-400">Voice operations</div>
                    </div>
                </div>
                <h1 class="mt-16 max-w-md text-5xl font-extrabold leading-[1.05] tracking-tight text-white">The front desk that never puts a caller on hold.</h1>
                <p class="mt-5 max-w-md text-slate-300">Answer, qualify, and book from verified availability. The model never invents a slot.</p>
            </div>
            <ol class="space-y-4 text-sm text-slate-300">
                <li><span class="font-bold text-teal-400">01</span> Synthflow carries the number and the voice agent.</li>
                <li><span class="font-bold text-teal-400">02</span> This desk owns leads, calendars, and bookings.</li>
                <li><span class="font-bold text-teal-400">03</span> One firm workspace. Datetimes stay in UTC.</li>
            </ol>
        </aside>
        <main class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="grid h-10 w-10 place-items-center rounded-full bg-teal-500 font-extrabold text-navy-950">AL</div>
                    <div class="text-2xl font-extrabold tracking-tight">AI Lawyer</div>
                </div>
                <div class="ui-card p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>
