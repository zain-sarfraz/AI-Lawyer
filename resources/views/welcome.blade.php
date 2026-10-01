<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'LEGALAI') }} — AI receptionist for law firms</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,640&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-navy-950 text-stone-100 antialiased">
    <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
        <div class="flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-gold-500 font-semibold text-navy-950">LA</div>
            <div>
                <div class="font-display text-xl">LEGALAI</div>
                <div class="text-[10px] uppercase tracking-[0.18em] text-gold-400">FineTuner voice desk</div>
            </div>
        </div>
        <a href="{{ route('login') }}" class="ui-btn ui-btn-brass">Sign in</a>
    </header>

    <main class="mx-auto grid max-w-6xl gap-16 px-6 pb-20 pt-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">For law firms</p>
            <h1 class="font-display mt-4 text-5xl leading-[1.05] sm:text-6xl">A receptionist that books only what the calendar can keep.</h1>
            <p class="mt-6 max-w-xl text-lg text-stone-300">FineTuner answers the phone. This desk captures the lead, checks real availability, and writes the appointment. No invented slots. No legal advice from the model.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="ui-btn ui-btn-brass">Open the workspace</a>
                <a href="{{ route('login') }}" class="ui-btn ui-btn-ghost border-white/20 text-stone-100">Staff sign in</a>
            </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <div class="text-xs uppercase tracking-[0.16em] text-gold-400">Live line</div>
            <div class="font-display mt-2 text-3xl">+1 · your FineTuner number</div>
            <p class="mt-2 text-sm text-stone-400">Attach an agent, then watch calls, leads, and bookings land in one place.</p>
            <dl class="mt-6 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl bg-black/20 p-3">
                    <dt class="text-[11px] uppercase tracking-wide text-stone-400">Answer</dt>
                    <dd class="font-display mt-1 text-2xl">24/7</dd>
                </div>
                <div class="rounded-2xl bg-black/20 p-3">
                    <dt class="text-[11px] uppercase tracking-wide text-stone-400">Book</dt>
                    <dd class="font-display mt-1 text-2xl">UTC</dd>
                </div>
                <div class="rounded-2xl bg-black/20 p-3">
                    <dt class="text-[11px] uppercase tracking-wide text-stone-400">Firm</dt>
                    <dd class="font-display mt-1 text-2xl">1</dd>
                </div>
            </dl>
        </div>
    </main>

    <section class="border-t border-white/10">
        <div class="mx-auto grid max-w-6xl gap-6 px-6 py-14 md:grid-cols-3">
            <article>
                <h2 class="font-display text-2xl">Voice line</h2>
                <p class="mt-2 text-sm text-stone-400">FineTuner is the display brand. The adapter speaks Synthflow v2 when the voice phase lands.</p>
            </article>
            <article>
                <h2 class="font-display text-2xl">Conflict-safe booking</h2>
                <p class="mt-2 text-sm text-stone-400">Staff and the receptionist share one availability check. The database decides the truth.</p>
            </article>
            <article>
                <h2 class="font-display text-2xl">Firm workspace</h2>
                <p class="mt-2 text-sm text-stone-400">Leads, clients, calls, lawyers, and knowledge stay in the same navy desk.</p>
            </article>
        </div>
    </section>
</body>
</html>
