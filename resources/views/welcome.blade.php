<!DOCTYPE html>
<html lang="en" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'AI Lawyer') }} — Voice receptionist for law firms</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
<body class="min-h-full bg-navy-900 text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-teal-500/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/4 h-64 w-64 rounded-full bg-teal-400/10 blur-3xl"></div>
    </div>

    <header class="relative mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
        <div class="flex items-center gap-3">
            <div class="grid h-11 w-11 place-items-center rounded-full bg-teal-500 text-sm font-extrabold text-navy-950">AL</div>
            <div>
                <div class="text-xl font-extrabold tracking-tight text-white">AI Lawyer</div>
                <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-teal-400">Voice operations</div>
            </div>
        </div>
        <a href="{{ route('login') }}" class="ui-btn ui-btn-brass">Sign in</a>
        </header>

    <main class="relative mx-auto grid max-w-6xl gap-16 px-6 pb-20 pt-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-400">For law firms</p>
            <h1 class="mt-4 text-5xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-6xl">AI Lawyer — the receptionist that books only what the calendar can keep.</h1>
            <p class="mt-6 max-w-xl text-lg text-slate-300">Synthflow answers the phone. This desk captures the lead, checks real availability, and writes the appointment. No invented slots. No legal advice from the model.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="ui-btn ui-btn-brass">Open the workspace</a>
                <a href="{{ route('login') }}" class="ui-btn ui-btn-ghost border-white/20 text-slate-100">Staff sign in</a>
            </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-400">Live line</div>
            <div class="mt-2 text-3xl font-extrabold tracking-tight">+1 609 780 9610</div>
            <p class="mt-2 text-sm text-slate-400">Attach an agent, then watch calls, leads, and bookings land in one place.</p>
            <dl class="mt-6 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl bg-teal-500/10 p-3">
                    <dt class="text-[11px] uppercase tracking-wide text-slate-400">Answer</dt>
                    <dd class="mt-1 text-2xl font-extrabold">24/7</dd>
                </div>
                <div class="rounded-2xl bg-teal-500/10 p-3">
                    <dt class="text-[11px] uppercase tracking-wide text-slate-400">Book</dt>
                    <dd class="mt-1 text-2xl font-extrabold">UTC</dd>
                </div>
                <div class="rounded-2xl bg-teal-500/10 p-3">
                    <dt class="text-[11px] uppercase tracking-wide text-slate-400">Firm</dt>
                    <dd class="mt-1 text-2xl font-extrabold">1</dd>
                </div>
            </dl>
                </div>
            </main>

    <section class="relative border-t border-white/10">
        <div class="mx-auto grid max-w-6xl gap-6 px-6 py-14 md:grid-cols-3">
            <article>
                <h2 class="text-2xl font-extrabold tracking-tight">Voice line</h2>
                <p class="mt-2 text-sm text-slate-400">Synthflow answers callers with your greeting, fees, lawyers, and booking tools.</p>
            </article>
            <article>
                <h2 class="text-2xl font-extrabold tracking-tight">Conflict-safe booking</h2>
                <p class="mt-2 text-sm text-slate-400">Staff and the receptionist share one availability check. The database decides the truth.</p>
            </article>
            <article>
                <h2 class="text-2xl font-extrabold tracking-tight">Firm workspace</h2>
                <p class="mt-2 text-sm text-slate-400">Leads, clients, calls, lawyers, and calendars stay in one teal desk.</p>
            </article>
        </div>
    </section>
    </body>
</html>
