<section>
    <h2 class="crm-panel-title">Profile information</h2>
    <p class="ui-muted small mb-3">Update your account name and email address.</p>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label class="ui-label" for="name">Name</label>
            <input id="name" name="name" type="text" class="ui-field" value="{{ old('name', $user->name) }}" required autocomplete="name">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <label class="ui-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="ui-field" value="{{ old('email', $user->email) }}" required autocomplete="username">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->get('email')" />
        </div>

        <button class="ui-btn ui-btn-primary">Save</button>
        @if (session('status') === 'profile-updated')
            <span class="small ui-muted ms-2">Saved.</span>
        @endif
    </form>
</section>
