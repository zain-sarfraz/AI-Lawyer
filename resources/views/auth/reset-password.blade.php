<x-guest-layout>
    <div class="mb-6">
        <h2 class="font-display text-3xl">Choose a new password</h2>
        <p class="mt-1 text-sm text-(--muted)">Use a long, unique password for the firm workspace.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <x-ui.label for="email">Email</x-ui.label>
            <x-ui.input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-ui.label for="password">Password</x-ui.label>
            <x-ui.input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-ui.label for="password_confirmation">Confirm password</x-ui.label>
            <x-ui.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <x-ui.button class="w-full">Reset password</x-ui.button>
    </form>
</x-guest-layout>
