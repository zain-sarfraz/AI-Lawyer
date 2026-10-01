<section>
    <h2 class="crm-panel-title">Delete account</h2>
    <p class="ui-muted small mb-3">This permanently removes your user account. Firm CRM data is not bulk-deleted by this action.</p>

    <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Delete your account permanently?')">
        @csrf
        @method('delete')

        <div class="mb-3" style="max-width:320px">
            <label class="ui-label" for="password">Confirm with password</label>
            <input id="password" name="password" type="password" class="ui-field" placeholder="Password">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->userDeletion->get('password')" />
        </div>

        <button class="ui-btn ui-btn-danger">Delete account</button>
    </form>
</section>
