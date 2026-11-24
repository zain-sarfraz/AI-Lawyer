<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AI Lawyer') }} — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg-cream: #F8F6F0;
            --sidebar-navy: #0C0F17;
            --gold-gradient: linear-gradient(135deg, #F5DE9B 0%, #E3BC62 50%, #CD9A35 100%);
            --gold-primary: #D4AF37;
        }
        body {
            background-color: var(--bg-cream) !important;
            color: #1E293B !important;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        }
    </style>
</head>
<body class="min-h-full antialiased" style="background-color: #F8F6F0;">
    <div class="grid min-h-screen lg:grid-cols-2">
        <!-- Left Branding Panel -->
        <aside class="relative hidden overflow-hidden p-12 text-slate-100 lg:flex lg:flex-col lg:justify-between" style="background-color: #0C0F17;">
            <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full blur-3xl pointer-events-none" style="background: radial-gradient(circle, rgba(212,175,55,0.18) 0%, transparent 70%);"></div>
            <div class="absolute bottom-10 left-10 h-64 w-64 rounded-full blur-3xl pointer-events-none" style="background: radial-gradient(circle, rgba(212,175,55,0.12) 0%, transparent 70%);"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div style="width: 42px; height: 42px; border-radius: 11px; background: linear-gradient(135deg, #F5DE9B, #D4AF37, #9E7824); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(212,175,55,0.35); flex-shrink: 0;">
                        <svg style="width: 22px; height: 22px; color: #17130A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3v18M7 21h10M5 7h14M12 5l-1-2h2z"/>
                            <path d="M5 7l-3 7a3.5 3.5 0 0 0 6 0zM19 7l-3 7a3.5 3.5 0 0 0 6 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-family: 'Fraunces', Georgia, serif; font-size: 22px; font-weight: 700; color: #FFFFFF; line-height: 1.1; letter-spacing: -0.01em;">AI Lawyer</div>
                        <div style="font-size: 11px; color: #D4AF37; font-style: italic; font-weight: 600; letter-spacing: 0.08em; margin-top: 2px;">Legal Made Smarter</div>
                    </div>
                </div>

                <h1 style="font-family: 'Fraunces', Georgia, serif; font-size: 44px; font-weight: 700; line-height: 1.1; color: #FFFFFF; letter-spacing: -0.02em; margin-top: 64px; max-width: 460px;">
                    The front desk that never puts a caller on hold.
                </h1>
                <p style="color: #94A3B8; font-size: 15px; line-height: 1.6; margin-top: 20px; max-width: 440px;">
                    Answer, qualify, and book from verified availability. The model never invents a slot.
                </p>
            </div>

            <ol class="relative z-10 space-y-4 text-sm" style="color: #CBD5E1; margin-top: 40px;">
                <li class="flex items-center gap-3">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 8px; background: rgba(212,175,55,0.15); color: #F5DE9B; font-weight: 700; font-size: 12px;">01</span>
                    <span>Synthflow carries the number and the voice agent.</span>
                </li>
                <li class="flex items-center gap-3">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 8px; background: rgba(212,175,55,0.15); color: #F5DE9B; font-weight: 700; font-size: 12px;">02</span>
                    <span>This desk owns leads, calendars, and bookings.</span>
                </li>
                <li class="flex items-center gap-3">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 8px; background: rgba(212,175,55,0.15); color: #F5DE9B; font-weight: 700; font-size: 12px;">03</span>
                    <span>One firm workspace. Datetimes stay in UTC.</span>
                </li>
            </ol>
        </aside>

        <!-- Right Main Auth Area -->
        <main class="flex items-center justify-center px-6 py-12" style="background-color: #F8F6F0;">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #F5DE9B, #D4AF37, #9E7824); display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 20px; height: 20px; color: #17130A;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M12 3v18M7 21h10M5 7h14M12 5l-1-2h2z"/>
                            <path d="M5 7l-3 7a3.5 3.5 0 0 0 6 0zM19 7l-3 7a3.5 3.5 0 0 0 6 0z"/>
                        </svg>
                    </div>
                    <span style="font-family: 'Fraunces', serif; font-size: 22px; font-weight: 700; color: #0C0F17;">AI Lawyer</span>
                </div>

                <div style="background-color: #FFFFFF; border: 1px solid #EBE5DB; border-radius: 24px; padding: 36px; box-shadow: 0 16px 40px -10px rgba(12, 15, 23, 0.08); color: #0F172A;">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>
