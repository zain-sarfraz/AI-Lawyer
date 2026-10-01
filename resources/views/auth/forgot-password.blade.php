<x-guest-layout>
    <div class="mb-6">
        <h2 class="font-display text-3xl">Reset access</h2>
        <p class="mt-1 text-sm text-(--muted)">We’ll email a signed link so you can restore the workspace login.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <x-ui.label for="email">Email</x-ui.label>
            <x-ui.input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <x-ui.button class="w-full">Email reset link</x-ui.button>
        <p class="text-center text-sm"><a class="font-semibold text-[#8a6b32]" href="{{ route('login') }}">Back to sign in</a></p>
    </form>
</x-guest-layout>
