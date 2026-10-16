@php
    $modalName = 'edit-user-'.$user->id;
    $roleName = $user->hasRole('Lawyer') ? 'Lawyer' : ($user->hasRole('Staff') ? 'Staff' : 'Lawyer');
    $statusValue = $user->status?->value ?? 'active';
@endphp

<x-ui.modal
    :name="$modalName"
    title="Edit user"
    :subtitle="$user->name"
    maxWidth="lg"
>
    <form
        id="{{ $modalName }}-form"
        method="POST"
        action="{{ route('users.update', $user) }}"
        class="space-y-4"
        x-data="{ role: @js(old('_modal') === $modalName ? old('role', $roleName) : $roleName) }"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="_modal" value="{{ $modalName }}">

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Name *</label>
                <input class="ui-field" name="name" value="{{ old('_modal') === $modalName ? old('name', $user->name) : $user->name }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Email *</label>
                <input class="ui-field" type="email" name="email" value="{{ old('_modal') === $modalName ? old('email', $user->email) : $user->email }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Role *</label>
                <select class="ui-field" name="role" x-model="role" required>
                    <option value="Lawyer">Lawyer</option>
                    <option value="Staff">Staff</option>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Status</label>
                <select class="ui-field" name="status">
                    <option value="active" @selected((old('_modal') === $modalName ? old('status', $statusValue) : $statusValue) === 'active')>Active</option>
                    <option value="inactive" @selected((old('_modal') === $modalName ? old('status', $statusValue) : $statusValue) === 'inactive')>Inactive</option>
                    <option value="suspended" @selected((old('_modal') === $modalName ? old('status', $statusValue) : $statusValue) === 'suspended')>Suspended</option>
                </select>
            </div>
            <div class="col-12 col-md-6" x-show="role === 'Lawyer'" x-cloak>
                <label class="ui-label">Title</label>
                <input class="ui-field" name="title" value="{{ old('_modal') === $modalName ? old('title', $user->lawyer?->title) : $user->lawyer?->title }}" placeholder="Associate, Partner…">
            </div>
            <div class="col-12 col-md-6" x-show="role === 'Lawyer'" x-cloak>
                <label class="ui-label">Phone</label>
                <input class="ui-field" name="phone" value="{{ old('_modal') === $modalName ? old('phone', $user->lawyer?->phone) : $user->lawyer?->phone }}" placeholder="+1 (555) 000-0000">
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">New password</label>
                <input class="ui-field" type="password" name="password" minlength="8" placeholder="Leave blank to keep current">
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Confirm new password</label>
                <input class="ui-field" type="password" name="password_confirmation" minlength="8" placeholder="Leave blank to keep current">
            </div>
        </div>
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="{{ $modalName }}-form" class="ui-btn ui-btn-primary">Save changes</button>
    </x-slot:footer>
</x-ui.modal>
