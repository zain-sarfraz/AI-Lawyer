<x-guest-layout>
    <div class="auth-shell">
        <section class="auth-hero">
            <div>
                <div class="auth-kicker">
                    <span style="width:8px;height:8px;border-radius:50%;background:#c6a15b;display:inline-block"></span>
                    AI Legal Receptionist
                </div>
                <h1>Your firm’s front desk, always on.</h1>
                <p>Answer calls, capture leads, and book consultations from verified availability — without double-booking.</p>

                <div class="auth-points">
                    <div class="auth-point">
                        <div style="color:#c6a15b;font-weight:700">01</div>
                        <div>
                            <strong>Realtime voice intake</strong>
                            <span>Twilio + OpenAI receptionist with controlled CRM tools.</span>
                        </div>
                    </div>
                    <div class="auth-point">
                        <div style="color:#c6a15b;font-weight:700">02</div>
                        <div>
                            <strong>Conflict-safe booking</strong>
                            <span>Laravel transactions decide truth — not the model.</span>
                        </div>
                    </div>
                    <div class="auth-point">
                        <div style="color:#c6a15b;font-weight:700">03</div>
                        <div>
                            <strong>One firm workspace</strong>
                            <span>Leads, clients, calls, and schedules in one place.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="small" style="color:rgba(232,240,237,.55)">Horizon Legal Group · Single-organization CRM</div>
        </section>

        <section class="auth-panel">
            <div class="auth-card">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="legal-brand-mark">LA</div>
                    <div>
                        <div style="font-weight:700;color:#18212B">Welcome back</div>
                        <div class="small text-muted">Sign in to the firm workspace</div>
                    </div>
                </div>

                <x-auth-session-status class="mb-3" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control">
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger small" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control">
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger small" />
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <label class="d-flex align-items-center gap-2 small mb-0">
                            <input type="checkbox" name="remember" class="form-check-input m-0">
                            Remember me
                        </label>
                        @if (Route::has('password.request'))
                            <a class="small" href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>

                    <button class="btn btn-legal-primary w-100">Sign in</button>
                </form>
            </div>
        </section>
    </div>
</x-guest-layout>
