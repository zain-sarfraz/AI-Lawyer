@php
    $modalFirms = $modalFirms ?? collect();
    $selectedFirmId = $selectedFirmId ?? (old('firm_id') ?: request('firm_id'));
@endphp

<x-ui.modal
    name="add-admin"
    title="Add admin"
    subtitle="Login for an existing firm. That admin only sees their own firm data."
    maxWidth="xl"
>
    @if($modalFirms->isEmpty())
        <p class="text-sm text-(--muted)">No active firms yet. Create a firm first, then add more admins.</p>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
            <button type="button" class="ui-btn ui-btn-brass" @click="show = false; $dispatch('open-modal', 'create-firm')">Create firm</button>
        </div>
    @else
        <form method="POST" action="{{ route('superadmin.admins.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_modal" value="add-admin">

            <div class="row g-3">
                <div class="col-12">
                    <label class="ui-label">Firm *</label>
                    <select id="add-admin-firm-id" class="ui-field" name="firm_id" required>
                        <option value="">Select firm…</option>
                        @foreach($modalFirms as $firm)
                            <option value="{{ $firm->id }}" @selected((int) $selectedFirmId === (int) $firm->id)>
                                {{ $firm->firm_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="ui-label">Admin name *</label>
                    <input class="ui-field" name="admin_name" value="{{ old('admin_name') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="ui-label">Admin email *</label>
                    <input class="ui-field" type="email" name="admin_email" value="{{ old('admin_email') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="ui-label">Role</label>
                    <select class="ui-field" name="admin_role">
                        <option value="Admin" @selected(old('admin_role', 'Admin') === 'Admin')>Admin</option>
                        <option value="Owner" @selected(old('admin_role') === 'Owner')>Owner</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="ui-label">Password *</label>
                    <input class="ui-field" type="password" name="admin_password" required minlength="8">
                </div>
                <div class="col-12 col-md-4">
                    <label class="ui-label">Confirm password *</label>
                    <input class="ui-field" type="password" name="admin_password_confirmation" required minlength="8">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
                <button type="submit" class="ui-btn ui-btn-brass">Add admin</button>
            </div>
        </form>
    @endif
</x-ui.modal>
