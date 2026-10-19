<x-ui.modal
    name="create-firm"
    title="Create firm"
    subtitle="New isolated workspace. Add admins separately from the Admins page."
    maxWidth="xl"
>
    <form method="POST" action="{{ route('superadmin.firms.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="_modal" value="create-firm">

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Firm name *</label>
                <input class="ui-field" name="firm_name" value="{{ old('firm_name') }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Firm email</label>
                <input class="ui-field" type="email" name="email" value="{{ old('email') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Phone</label>
                <input class="ui-field" name="phone" value="{{ old('phone') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Timezone</label>
                <input class="ui-field" name="timezone" value="{{ old('timezone', 'Asia/Karachi') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Currency</label>
                <input class="ui-field" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-brass">Create firm</button>
        </div>
    </form>
</x-ui.modal>
