<x-ui.modal name="create-lawyer" title="New lawyer" subtitle="Assign counsel to a firm. Phone dial country is separate from practice jurisdictions." maxWidth="2xl">
    <form
        id="create-lawyer-form"
        method="POST"
        action="{{ route('superadmin.lawyers.store') }}"
        data-turbo="false"
        onsubmit="return window.__validateLawyerForm(this)"
        x-data="{
            firmId: @js((string) old('firm_id', '')),
            byFirm: @js($servicesByFirm),
            open: false,
            selected: {{ Js::from(collect(old('service_ids', []))->map(fn ($id) => (int) $id)->values()->all()) }},
            get options() {
                return this.byFirm[this.firmId] || [];
            },
            toggle(id) {
                const value = Number(id);
                if (this.selected.includes(value)) {
                    this.selected = this.selected.filter((item) => item !== value);
                } else {
                    this.selected = [...this.selected, value];
                }
            },
            onFirmChange() {
                this.selected = [];
                this.open = false;
            },
            label() {
                const names = this.options
                    .filter((option) => this.selected.includes(Number(option.id)))
                    .map((option) => option.label || option.name);
                if (!names.length) return this.firmId ? 'Select services…' : 'Select a firm first…';
                if (names.length <= 2) return names.join(', ');
                return names.slice(0, 2).join(', ') + ' +' + (names.length - 2) + ' more';
            }
        }"
    >
        @csrf
        @if($errors->any() && old('_modal') === 'create-lawyer')
            <div class="mb-3 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">
                <p class="mb-1 font-semibold">Lawyer create failed:</p>
                <ul class="mb-0 list-disc pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-3">
            <label class="ui-label">Firm *</label>
            <select name="firm_id" class="ui-field" x-model="firmId" @change="onFirmChange()" required>
                <option value="">Select firm…</option>
                @foreach($firms as $firm)
                    <option value="{{ $firm->id }}">{{ $firm->firm_name }}</option>
                @endforeach
            </select>
            @error('firm_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @include('admin.lawyers._fields', [
            'lawyer' => $formLawyer,
            'services' => collect(),
            'selectedServices' => [],
            'activeJurisdictions' => $activeJurisdictions ?? [],
            'idPrefix' => 'create_lawyer',
            'modalName' => 'create-lawyer',
            'showServices' => false,
            'showTimezone' => false,
        ])

        <div class="mt-3">
            <label class="ui-label">Services this lawyer provides</label>
            <div class="ui-multi-dropdown" @click.outside="open = false">
                <template x-for="id in selected" :key="'svc-' + id">
                    <input type="hidden" name="service_ids[]" :value="id">
                </template>
                <button type="button" class="ui-field ui-multi-dropdown-trigger" @click="open = !open" :aria-expanded="open" :disabled="!firmId">
                    <span x-text="label()" :class="selected.length ? '' : 'ui-muted'"></span>
                </button>
                <div class="ui-multi-dropdown-menu" x-show="open && options.length" x-cloak>
                    <template x-for="option in options" :key="option.id">
                        <label class="ui-multi-dropdown-option">
                            <input type="checkbox" class="ui-check-input" :checked="selected.includes(Number(option.id))" @change="toggle(option.id)">
                            <span x-text="option.name"></span>
                        </label>
                    </template>
                </div>
                <p class="small ui-muted mb-0 mt-1" x-show="firmId && !options.length">No services for this firm yet.</p>
            </div>
        </div>

        <div class="mt-5 flex justify-end gap-2.5 border-t border-slate-200 pt-4">
            <button type="button" class="sa-modal-btn-cancel" @click="show = false">Cancel</button>
            <button type="submit" class="sa-modal-btn-submit">Save lawyer</button>
        </div>
    </form>
</x-ui.modal>
