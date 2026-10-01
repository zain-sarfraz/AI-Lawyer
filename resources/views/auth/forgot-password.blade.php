<x-guest-layout>
    <div class="auth-shell">
        <section class="auth-hero">
            <div>
                <div class="auth-kicker">Account recovery</div>
                <h1>Reset access securely.</h1>
                <p>We’ll email a signed reset link so you can restore your workspace login.</p>
            </div>
        </section>
        <section class="auth-panel">
            <div class="auth-card">
                <h2>Forgot password</h2>
                <p class="lead">Enter your email and we’ll send reset instructions.</p>

                <x-auth-session-status class="mb-3" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control">
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger small" />
                    </div>
                    <button class="btn btn-legal-primary w-100">Email reset link</button>
                    <div class="text-center mt-3">
                        <a class="small" href="{{ route('login') }}">Back to sign in</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-guest-layout>
