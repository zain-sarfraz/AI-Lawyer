<section>
    <h2 class="crm-panel-title">Profile information</h2>
    <p class="text-muted small mb-3">Update your account name and email address.</p>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label class="form-label" for="name">Name</label>
            <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autocomplete="name">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->get('email')" />
        </div>

        <button class="btn btn-legal-primary">Save</button>
        @if (session('status') === 'profile-updated')
            <span class="small text-muted ms-2">Saved.</span>
        @endif
    </form>
</section>
