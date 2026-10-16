<x-ui.modal
    name="edit-firm"
    title="Edit firm"
    subtitle="Update firm workspace details."
    maxWidth="xl"
>
    <form
        method="POST"
        class="space-y-4"
        x-data="{
            action: '',
            firm_name: '',
            email: '',
            phone: '',
            timezone: 'America/New_York',
            currency: 'USD',
            status: 'active',
            fill(detail) {
                this.action = detail.action;
                this.firm_name = detail.firm_name || '';
                this.email = detail.email || '';
                this.phone = detail.phone || '';
                this.timezone = detail.timezone || 'America/New_York';
                this.currency = detail.currency || 'USD';
                this.status = detail.status || 'active';
            }
        }"
        x-on:fill-edit-firm.window="fill($event.detail)"
        x-bind:action="action"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="_modal" value="edit-firm">

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Firm name *</label>
                <input class="ui-field" name="firm_name" x-model="firm_name" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Firm email</label>
                <input class="ui-field" type="email" name="email" x-model="email">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Phone</label>
                <input class="ui-field" name="phone" x-model="phone">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Timezone</label>
                <input class="ui-field" name="timezone" x-model="timezone">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Currency</label>
                <input class="ui-field" name="currency" x-model="currency" maxlength="3">
            </div>
            <div class="col-12 col-md-4">
                <label class="ui-label">Status</label>
                <select class="ui-field" name="status" x-model="status">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="$dispatch('close-modal', 'edit-firm')">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-brass">Save firm</button>
        </div>
    </form>
</x-ui.modal>
