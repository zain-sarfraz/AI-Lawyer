<section>
    <h2 class="crm-panel-title">Update password</h2>
    <p class="ui-muted small mb-3">Use a long, unique password to keep the workspace secure.</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <label class="ui-label" for="update_password_current_password">Current password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="ui-field" autocomplete="current-password">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="mb-3">
            <label class="ui-label" for="update_password_password">New password</label>
            <input id="update_password_password" name="password" type="password" class="ui-field" autocomplete="new-password">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="mb-3">
            <label class="ui-label" for="update_password_password_confirmation">Confirm password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="ui-field" autocomplete="new-password">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <button class="ui-btn ui-btn-primary">Save</button>
        @if (session('status') === 'password-updated')
            <span class="small ui-muted ms-2">Saved.</span>
        @endif
    </form>
</section>
