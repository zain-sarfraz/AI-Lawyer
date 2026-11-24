<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Status · {{ config('app.name', 'AI Lawyer') }}</title>
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
            --gold: #d4af37;
            --ok: #059669;
            --danger: #df1b41;
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
            display: grid;
            place-items: center;
            padding: 24px 16px;
        }
        .card {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: 0 16px 48px rgba(15, 23, 42, .08);
            padding: 40px 36px;
            text-align: center;
        }
        .icon-circle {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 20px;
        }
        .icon-circle.ok {
            background: #dcfce7;
            color: var(--ok);
        }
        .icon-circle.pending {
            background: #eff6ff;
            color: var(--accent);
        }
        .icon-circle.failed {
            background: #fee2e2;
            color: var(--danger);
        }
        .icon-circle svg { width: 36px; height: 36px; }

        h1 {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 10px;
            letter-spacing: -.02em;
        }
        p {
            color: var(--muted);
            font-size: 15px;
            margin: 0 0 24px;
            line-height: 1.5;
        }

        .details-box {
            background: #f8fafc;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 24px;
            text-align: left;
            font-size: 14px;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 6px 0;
        }
        .details-row + .details-row {
            border-top: 1px solid #f1f5f9;
        }
        .details-row .lbl { color: var(--muted); }
        .details-row .val { font-weight: 600; color: var(--ink); }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(180deg, #746cff, #635bff);
            box-shadow: 0 8px 20px rgba(99,91,255,.24);
            border: 0;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-action:hover {
            filter: brightness(1.05);
            text-decoration: none;
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
            box-shadow: none;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .spinner {
            width: 32px; height: 32px;
            border: 3px solid rgba(99,91,255,.2);
            border-radius: 50%;
            border-top-color: var(--accent);
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .timeout-msg {
            display: none;
            margin-top: 16px;
            padding: 12px;
            border-radius: 8px;
            background: #fffbeb;
            color: #b45309;
            font-size: 13px;
        }
    </style>
</head>
<body>

<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></symbol>
</svg>

<div class="card">
    @if ($status === 'confirmed')
        <!-- Confirmed state -->
        <div class="icon-circle ok">
            <svg><use href="#i-check"/></svg>
        </div>
        <h1>Payment successful</h1>
        <p>Your subscription is active and your account is ready.</p>

        @if (! empty($summary))
            <div class="details-box">
                <div class="details-row">
                    <span class="lbl">Plan</span>
                    <span class="val">{{ $summary['name'] }}</span>
                </div>
                <div class="details-row">
                    <span class="lbl">Billing Interval</span>
                    <span class="val">{{ ucfirst($summary['interval']) }}</span>
                </div>
                <div class="details-row">
                    <span class="lbl">Amount</span>
                    <span class="val">{{ $summary['amount_label'] }}</span>
                </div>
                <div class="details-row">
                    <span class="lbl">Account Type</span>
                    <span class="val">{{ $summary['account_label'] }}</span>
                </div>
            </div>
        @endif

        <a href="{{ $dashboardUrl ?? route('dashboard') }}" class="btn-action">
            <span>Go to Dashboard</span>
            <span>→</span>
        </a>

    @elseif ($status === 'pending')
        <!-- Pending webhook polling state -->
        <div class="icon-circle pending">
            <div class="spinner"></div>
        </div>
        <h1 id="pendingTitle">Processing payment...</h1>
        <p id="pendingDesc">We're verifying your transaction with Stripe. This usually takes just a few seconds.</p>

        <div class="timeout-msg" id="timeoutMsg">
            Payment confirmation is taking slightly longer than usual. If Stripe charged your card, your account will activate automatically in a moment. You can safely proceed to log in.
        </div>

        <div style="margin-top:20px;">
            <a id="dashBtn" href="{{ $dashboardUrl ?? route('dashboard') }}" class="btn-action" style="display:none;">
                Go to Dashboard →
            </a>
            <a id="loginBtn" href="{{ route('login') }}" class="btn-action btn-secondary" style="display:none;">
                Go to Login
            </a>
        </div>

        <script>
        (function() {
            const sessionId = @json($sessionId);
            let elapsed = 0;
            const pollInterval = 2000;
            const maxWait = 30000;

            const timer = setInterval(function() {
                elapsed += pollInterval;

                fetch('/checkout/status?session_id=' + encodeURIComponent(sessionId), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'confirmed') {
                        clearInterval(timer);
                        window.location.reload();
                    } else if (data.status === 'failed') {
                        clearInterval(timer);
                        window.location.href = '/checkout/success?session_id=' + encodeURIComponent(sessionId);
                    }
                })
                .catch(err => console.error('Status poll error:', err));

                if (elapsed >= maxWait) {
                    clearInterval(timer);
                    document.getElementById('pendingTitle').textContent = 'Payment received';
                    document.getElementById('pendingDesc').textContent = 'Your Stripe session was registered. You may now proceed.';
                    document.getElementById('timeoutMsg').style.display = 'block';
                    document.getElementById('loginBtn').style.display = 'inline-flex';
                }
            }, pollInterval);
        })();
        </script>

    @else
        <!-- Failed state -->
        <div class="icon-circle failed">
            <svg><use href="#i-alert"/></svg>
        </div>
        <h1>Payment not completed</h1>
        <p>{{ $message ?? 'Your card was not charged or the session was cancelled.' }}</p>

        <a href="{{ url('/#pricing') }}" class="btn-action">
            Try again
        </a>
    @endif
</div>

</body>
</html>
