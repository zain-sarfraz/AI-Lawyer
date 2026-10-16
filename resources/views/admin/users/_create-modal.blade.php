<x-ui.modal
    name="create-user"
    title="Add user"
    subtitle="They will sign in to their own dashboard and see their appointments and records."
    maxWidth="lg"
>
    <form
        method="POST"
        action="{{ route('users.store') }}"
        class="space-y-4"
        x-data="{ role: @js(old('role', 'Lawyer')) }"
    >
        @csrf
        <input type="hidden" name="_modal" value="create-user">

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Name *</label>
                <input class="ui-field" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Email *</label>
                <input class="ui-field" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Role *</label>
                <select class="ui-field" name="role" x-model="role" required>
                    <option value="Lawyer">Lawyer</option>
                    <option value="Staff">Staff</option>
                </select>
            </div>
            <div class="col-12 col-md-6" x-show="role === 'Lawyer'" x-cloak>
                <label class="ui-label">Title</label>
                <input class="ui-field" name="title" value="{{ old('title') }}" placeholder="e.g. Associate Counsel">
            </div>
            <div class="col-12 col-md-6" x-show="role === 'Lawyer'" x-cloak>
                <label class="ui-label">Phone</label>
                <input class="ui-field" name="phone" value="{{ old('phone') }}">
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Password *</label>
                <input class="ui-field" type="password" name="password" required minlength="8">
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Confirm password *</label>
                <input class="ui-field" type="password" name="password_confirmation" required minlength="8">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-primary">Add user</button>
        </div>
    </form>
</x-ui.modal>
