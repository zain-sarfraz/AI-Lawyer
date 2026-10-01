<x-guest-layout>
    <div class="auth-shell">
        <section class="auth-hero">
            <div>
                <div class="auth-kicker">Secure reset</div>
                <h1>Choose a new password.</h1>
                <p>Pick something long and unique for your firm workspace.</p>
            </div>
        </section>
        <section class="auth-panel">
            <div class="auth-card">
                <h2>Reset password</h2>
                <p class="lead">Create your new credentials below.</p>

                <form method="POST" action="{{ route('password.store') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="form-control">
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger small" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password" class="form-control">
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger small" />
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control">
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-danger small" />
                    </div>

                    <button class="btn btn-legal-primary w-100">Reset password</button>
                </form>
            </div>
        </section>
    </div>
</x-guest-layout>
