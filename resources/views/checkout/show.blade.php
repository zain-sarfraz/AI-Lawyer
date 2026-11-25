<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout · {{ $summary['name'] }} · {{ config('app.name', 'AI Lawyer') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0a2540;
            --muted: #425466;
            --line: #e6ebf1;
            --bg: #f8fafc;
            --card: #ffffff;
            --accent: #635bff;
            --accent-hover: #5348eb;
            --gold: #d4af37;
            --gold-soft: #fcf9ee;
            --gold-text: #8a6a12;
            --ok: #059669;
            --danger: #df1b41;
            --border-radius: 16px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 420px at 10% -10%, rgba(99,91,255,.08), transparent 60%),
                radial-gradient(700px 360px at 100% 0%, rgba(212,175,55,.08), transparent 55%),
                var(--bg);
            line-height: 1.5;
        }
        a { color: var(--accent); text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
        .shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 20px 60px;
        }
        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--line);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 19px;
            color: var(--ink);
            text-decoration: none;
            letter-spacing: -.02em;
        }
        .brand-mark {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, #F5DE9B, #D4AF37, #9E7824);
            display: grid; place-items: center;
            box-shadow: 0 4px 12px rgba(212,175,55,.3);
        }
        .brand-mark svg { width: 20px; height: 20px; color: #17130A; }
        .secure {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--muted); font-size: 13.5px; font-weight: 600;
        }
        .grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 28px;
            align-items: start;
        }
        @media (max-width: 880px) {
            .grid { grid-template-columns: 1fr; gap: 20px; }
        }
        .panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--border-radius);
            box-shadow: 0 10px 32px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .panel-h {
            padding: 24px 26px 20px;
            border-bottom: 1px solid var(--line);
        }
        .panel-h h1, .panel-h h2 {
            margin: 0 0 6px 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -.02em;
            color: var(--ink);
        }
        .panel-h .sub {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }
        .panel-b { padding: 26px; }

        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 12px; font-weight: 700; letter-spacing: .03em;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .badge.individual { background: rgba(99,91,255,.1); color: var(--accent); }
        .badge.firm { background: var(--gold-soft); color: var(--gold-text); border: 1px solid rgba(212,175,55,.25); }

        .price-box {
            margin-top: 8px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--line);
        }
        .price-main {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }
        .price-main .amt {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -.03em;
            color: var(--ink);
            line-height: 1;
        }
        .price-main .per {
            font-size: 18px;
            font-weight: 600;
            color: var(--muted);
        }
        .bill-note {
            margin-top: 6px;
            font-size: 13.5px;
            color: var(--muted);
            font-weight: 500;
        }

        .rate-lock-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 4px 10px;
            border-radius: 6px;
            background: #fef3c7;
            color: #92400e;
            font-size: 12.5px;
            font-weight: 600;
        }

        .list { list-style: none; margin: 18px 0 0; padding: 0; display: grid; gap: 11px; }
        .list li {
            display: grid; grid-template-columns: 20px 1fr; gap: 10px;
            align-items: start; font-size: 14px; color: #1f2937;
        }
        .list svg { width: 18px; height: 18px; color: var(--ok); margin-top: 2px; }

        .bill-box {
            margin-top: 22px; padding: 16px 18px; border-radius: 12px;
            background: #0a2540; color: #fff;
        }
        .bill-box .row { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; }
        .bill-box .row + .row { margin-top: 9px; }
        .bill-box .muted { color: rgba(255,255,255,.7); }
        .bill-box .total {
            margin-top: 14px; padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,.15);
            font-size: 17px; font-weight: 700;
        }

        .trust-box {
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            background: rgba(99,91,255,.05);
            border: 1px solid rgba(99,91,255,.12);
            font-size: 13px;
            color: var(--muted);
            display: grid;
            gap: 8px;
        }
        .trust-item { display: flex; align-items: center; gap: 8px; font-weight: 500; }
        .trust-item svg { width: 16px; height: 16px; color: var(--ok); flex-shrink: 0; }

        .change-plan {
            display: inline-block;
            margin-top: 16px;
            font-size: 13.5px;
            color: var(--muted);
        }
        .change-plan:hover { color: var(--accent); }

        /* Form styles */
        .field { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #334155; }
        .req { color: var(--danger); }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 560px) {
            .row2 { grid-template-columns: 1fr; }
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="tel"] {
            width: 100%;
            border: 1px solid #d6dce5;
            border-radius: 10px;
            padding: 12px 14px;
            font: inherit;
            color: var(--ink);
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99,91,255,.16);
        }
        .error { color: var(--danger); font-size: 12.5px; margin-top: 5px; font-weight: 500; }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13.5px;
        }
        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13.5px;
        }

        .terms {
            display: flex; gap: 10px; align-items: flex-start;
            font-size: 13px; color: var(--muted); margin: 18px 0 20px;
            cursor: pointer;
        }
        .terms input { margin-top: 3px; cursor: pointer; }

        .btn-continue {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 15px 20px;
            font: inherit;
            font-size: 15.5px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(180deg, #746cff, #635bff);
            box-shadow: 0 10px 24px rgba(99,91,255,.26);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all .2s;
        }
        .btn-continue:hover:not(:disabled) {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }
        .btn-continue:disabled {
            opacity: .65;
            cursor: not-allowed;
            transform: none;
        }

        .stripe-footer {
            margin-top: 16px;
            text-align: center;
            font-size: 12.5px;
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .stripe-footer svg { width: 14px; height: 14px; }

        .spinner {
            display: inline-block;
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
@php
    $isFirm = ($summary['account_type'] ?? '') === 'firm';
    $isPilot = ($summary['id'] ?? '') === 'pilot';
@endphp

<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></symbol>
    <symbol id="i-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></symbol>
    <symbol id="i-scale" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M7 21h10M5 7h14M12 5l-1-2h2z"/><path d="M5 7l-3 7a3.5 3.5 0 0 0 6 0zM19 7l-3 7a3.5 3.5 0 0 0 6 0z"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></symbol>
</svg>

<div class="shell">
    <header class="top">
        <a class="brand" href="{{ url('/') }}">
            <span class="brand-mark" aria-hidden="true"><svg><use href="#i-scale"/></svg></span>
            {{ config('app.name', 'AI Lawyer') }}
        </a>
        <div class="secure">
            <svg width="14" height="14"><use href="#i-lock"/></svg>
            <span>Secure Stripe Checkout</span>
        </div>
    </header>

    <main class="grid">
        <!-- Left Column: Order Summary -->
        <aside class="panel">
            <div class="panel-h">
                <span class="badge {{ $isFirm ? 'firm' : 'individual' }}">{{ $summary['account_label'] }}</span>
                <div class="price-box">
                    <div class="price-main">
                        <span class="amt">{{ $summary['amount_label'] }}</span>
                        <span class="per">{{ $summary['interval_label'] }}</span>
                    </div>
                    <div class="bill-note">{{ $summary['bill_note'] }} · {{ $summary['name'] }} plan</div>
                    @if ($isPilot || ! empty($summary['rate_locked']))
                        <div class="rate-lock-pill"><svg width="12" height="12"><use href="#i-lock"/></svg> Rate locked for 12 months</div>
                    @endif
                </div>
            </div>

            <div class="panel-b">
                <p style="margin:0 0 12px;font-weight:700;font-size:14.5px;">Included in {{ $summary['name'] }}:</p>
                <ul class="list">
                    @foreach ($summary['features'] as $feature)
                        <li><svg><use href="#i-check"/></svg><span>{{ $feature }}</span></li>
                    @endforeach
                </ul>

                <div class="bill-box">
                    <div class="row"><span class="muted">Account type</span><span>{{ $summary['account_label'] }}</span></div>
                    <div class="row"><span class="muted">Plan</span><span>{{ $summary['name'] }}</span></div>
                    <div class="row"><span class="muted">Billing cycle</span><span>{{ ucfirst($summary['interval']) }}</span></div>
                    <div class="row total"><span>Total due today</span><span>{{ $summary['amount_label'] }}</span></div>
                </div>

                <div class="trust-box">
                    <div class="trust-item">
                        <svg><use href="#i-check"/></svg>
                        <span>Cancel or switch anytime without penalties.</span>
                    </div>
                    <div class="trust-item">
                        <svg><use href="#i-shield"/></svg>
                        <span>Encrypted &amp; processed securely by Stripe.</span>
                    </div>
                </div>

                <div style="margin-top:16px;text-align:center;">
                    <a class="change-plan" href="{{ url('/#pricing') }}">← Change plan</a>
                </div>
            </div>
        </aside>

        <!-- Right Column: Account Details Form -->
        <section class="panel">
            <div class="panel-h">
                <h1>Create your account</h1>
                <p class="sub">
                    @if ($isFirm)
                        Create your law firm workspace after payment.
                    @else
                        Create your account after payment.
                    @endif
                </p>
            </div>

            <div class="panel-b">
                @if (session('error'))
                    <div class="alert-error">{{ session('error') }}</div>
                @endif
                @if (session('info'))
                    <div class="alert-info">{{ session('info') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert-error">
                        <ul style="margin:0;padding-left:18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="checkoutForm" method="POST" action="{{ route('checkout.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="plan" value="{{ $summary['id'] }}">
                    <input type="hidden" name="interval" value="{{ $summary['interval'] }}">
                    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                    <div class="field">
                        <label for="name">Full name <span class="req">*</span></label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" placeholder="e.g. Attorney Sarah Jenkins">
                        @error('name') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="email">Work email <span class="req">*</span></label>
                        <input id="email" name="email" type="email" value="{{ old('email', $pendingEmail ?? '') }}" required autocomplete="email" placeholder="sarah@firm.com">
                        @error('email') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    @if ($isFirm)
                        <div class="field">
                            <label for="firm_name">Firm name <span class="req">*</span></label>
                            <input id="firm_name" name="firm_name" type="text" value="{{ old('firm_name') }}" required autocomplete="organization" placeholder="e.g. Jenkins &amp; Partners Law">
                            @error('firm_name') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="row2">
                        <div class="field">
                            <label for="country">Country Code</label>
                            <select id="country" name="country" style="width:100%;border:1px solid var(--line);border-radius:10px;padding:11px 14px;font:inherit;font-size:14.5px;color:var(--ink);background:#fff;">
                                <option value="">Select country code…</option>
                                @foreach (($countryLabels ?? ['US' => 'United States', 'PK' => 'Pakistan', 'GB' => 'United Kingdom', 'CA' => 'Canada', 'AU' => 'Australia']) as $cCode => $cName)
                                    <option value="{{ $cCode }}" @selected(old('country') === $cCode)>{{ $cCode }} — {{ $cName }} ({{ \App\Support\Phone\PhoneNormalizer::dialCode($cCode) }})</option>
                                @endforeach
                            </select>
                            @error('country') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="phone">Phone number (optional)</label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" placeholder="3247087907 or +15550000">
                            @error('phone') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row2">
                        <div class="field">
                            <label for="password">Password <span class="req">*</span></label>
                            <input id="password" name="password" type="password" required autocomplete="new-password">
                            @error('password') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirm password <span class="req">*</span></label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                        </div>
                    </div>

                    <label class="terms">
                        <input id="accept_terms" type="checkbox" name="accept_terms" value="1" {{ old('accept_terms') ? 'checked' : '' }} required>
                        <span>I agree to the Terms of Service and Privacy Policy. I authorize billing <strong>{{ $summary['amount_label'] }}</strong> {{ strtolower($summary['interval_label']) }} until cancelled.</span>
                    </label>
                    @error('accept_terms') <div class="error" style="margin-top:-14px;margin-bottom:14px;">{{ $message }}</div> @enderror

                    <button type="submit" id="submitBtn" class="btn-continue">
                        <span id="btnText">Continue to Payment · {{ $summary['amount_label'] }}</span>
                    </button>

                    <div class="stripe-footer">
                        <svg><use href="#i-lock"/></svg>
                        <span>You will be redirected to Stripe to enter your payment details securely.</span>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('checkoutForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');

    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            return;
        }

        // Disable button to prevent double submissions
        submitBtn.disabled = true;
        btnText.innerHTML = '<span class="spinner"></span> Redirecting to Stripe...';
    });
});
</script>
</body>
</html>
