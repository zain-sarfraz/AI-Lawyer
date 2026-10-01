<x-guest-layout>
    <div class="mb-6">
        <h2 class="font-display text-3xl">Welcome back</h2>
        <p class="mt-1 text-sm text-(--muted)">Sign in to the firm workspace.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <x-ui.label for="email">Email</x-ui.label>
            <x-ui.input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-ui.label for="password">Password</x-ui.label>
            <x-ui.input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="remember" class="rounded border-(--line) text-navy-900 focus:ring-gold-500">
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a class="font-semibold text-[#8a6b32]" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>
        <x-ui.button class="w-full">Sign in</x-ui.button>
    </form>
</x-guest-layout>
