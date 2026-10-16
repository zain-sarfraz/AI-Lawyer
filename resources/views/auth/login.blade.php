<x-guest-layout>
    <div x-data="{
        fill(email, password) {
            const emailEl = document.getElementById('email');
            const passwordEl = document.getElementById('password');
            if (emailEl) emailEl.value = email;
            if (passwordEl) passwordEl.value = password;
            emailEl?.focus();
        }
    }">
        <div class="mb-6">
            <h2 class="font-display text-3xl">Welcome back</h2>
            <p class="mt-1 text-sm text-(--muted)">Sign in to the platform portal.</p>
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
                    <input type="checkbox" name="remember" class="rounded border-(--line) text-teal-600 focus:ring-teal-500">
                    Remember me
                </label>
                @if (Route::has('password.request'))
                    <a class="font-semibold text-teal-700" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <x-ui.button class="w-full">Sign in</x-ui.button>
        </form>

        <button
            type="button"
            @click="fill('admin@admin.com', '11223344')"
            class="mt-5 w-full rounded-xl border border-teal-500/40 bg-teal-500/5 px-4 py-3 text-left transition hover:border-teal-500 hover:bg-teal-500/10 focus:outline-none focus:ring-2 focus:ring-teal-500/40"
        >
            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-teal-700">Superadmin</div>
            <div class="mt-1.5 text-sm text-(--ink)">
                <span class="font-semibold">admin@admin.com</span>
                <span class="mx-1.5 text-(--muted)">·</span>
                <span class="font-semibold tracking-wide">11223344</span>
            </div>
            <div class="mt-1 text-xs text-(--muted)">Click to fill — create firms &amp; admins from the portal</div>
        </button>
    </div>
</x-guest-layout>
