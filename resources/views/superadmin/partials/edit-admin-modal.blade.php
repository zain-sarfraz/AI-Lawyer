@php
    $modalFirms = $modalFirms ?? collect();
@endphp

<x-ui.modal
    name="edit-admin"
    title="Edit admin"
    subtitle="Update firm admin login, role, firm, or status."
    maxWidth="xl"
>
    <form
        method="POST"
        class="space-y-4"
        x-data="{
            action: '',
            firm_id: '',
            admin_name: '',
            admin_email: '',
            admin_role: 'Admin',
            status: 'active',
            fill(detail) {
                this.action = detail.action;
                this.firm_id = String(detail.firm_id || '');
                this.admin_name = detail.admin_name || '';
                this.admin_email = detail.admin_email || '';
                this.admin_role = detail.admin_role || 'Admin';
                this.status = detail.status || 'active';
            }
        }"
        x-on:fill-edit-admin.window="fill($event.detail)"
        x-bind:action="action"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="_modal" value="edit-admin">

        <div class="row g-3">
            <div class="col-12">
                <label class="ui-label">Firm *</label>
                <select class="ui-field" name="firm_id" x-model="firm_id" required>
                    <option value="">Select firm…</option>
                    @foreach($modalFirms as $firm)
                        <option value="{{ $firm->id }}">{{ $firm->firm_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Admin name *</label>
                <input class="ui-field" name="admin_name" x-model="admin_name" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Admin email *</label>
                <input class="ui-field" type="email" name="admin_email" x-model="admin_email" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Role</label>
                <select class="ui-field" name="admin_role" x-model="admin_role">
                    <option value="Admin">Admin</option>
                    <option value="Owner">Owner</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Status</label>
                <select class="ui-field" name="status" x-model="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">New password</label>
                <input class="ui-field" type="password" name="admin_password" minlength="8" placeholder="Leave blank to keep">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Confirm password</label>
                <input class="ui-field" type="password" name="admin_password_confirmation" minlength="8" placeholder="Only if changing">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="$dispatch('close-modal', 'edit-admin')">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-brass">Save admin</button>
        </div>
    </form>
</x-ui.modal>
