@php
    $idPrefix = $idPrefix ?? 'lawyer';
    $selectedServices = $selectedServices ?? [];
    $activeJurisdictions = $activeJurisdictions ?? collect();
    $defaultJurisdictionIds = ($lawyer->exists ?? false)
        ? $lawyer->jurisdictions()->pluck('jurisdictions.id')->all()
        : [];
    $selectedJurisdictionIds = collect(old('jurisdiction_ids', $defaultJurisdictionIds))
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    $phoneCountry = strtoupper((string) old('phone_country', $lawyer->country));
    if (! in_array($phoneCountry, ['PK', 'GB'], true)) {
        $phoneCountry = '';
    }
    $split = \App\Support\Phone\PhoneNormalizer::split(old('phone_local') ? null : $lawyer->phone);
    if (old('phone_local') !== null) {
        $phoneLocal = old('phone_local');
    } else {
        $phoneLocal = $split['local'] ?? '';
        if ($phoneCountry === '' && ($split['country'] ?? '') !== '') {
            $phoneCountry = $split['country'];
        }
    }
@endphp

<input type="hidden" name="_modal" value="{{ $modalName ?? '' }}">

<div
    class="legal-form-section !border-0 !pb-0 !mb-0"
    x-data="lawyerPhoneForm(@js($phoneCountry), @js($phoneLocal))"
>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="ui-label">Name</label>
            <input name="name" class="ui-field" value="{{ old('name', $lawyer->user?->name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="ui-label">Email</label>
            <input type="email" name="email" class="ui-field" value="{{ old('email', $lawyer->user?->email) }}" required>
        </div>

        <div class="col-12">
            <label class="ui-label">Phone (formatting only)</label>
            <div class="grid gap-2 sm:grid-cols-[11rem_7rem_1fr]">
                <select name="phone_country" class="ui-field" x-model="phoneCountry" @change="onCountryChange()" required>
                    <option value="">Dial country</option>
                    <option value="PK">PK — Pakistan (+92)</option>
                    <option value="GB">GB — United Kingdom (+44)</option>
                </select>
                <input type="text" class="ui-field bg-(--surface)" :value="dialCode" readonly tabindex="-1" aria-label="Dial code">
                <input
                    type="tel"
                    name="phone_local"
                    class="ui-field"
                    x-model="phoneLocal"
                    @input="sanitizeLocal()"
                    placeholder="3247087907"
                    inputmode="numeric"
                    autocomplete="tel-national"
                >
            </div>
            <p class="mt-1.5 text-xs text-(--muted)">
                Enter the national number without a leading 0. Preview:
                <span class="font-medium text-(--ink)" x-text="previewE164 || '—'"></span>
                · Phone country is <em>not</em> the practice jurisdiction.
            </p>
            @error('phone_local')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-12">
            <label class="ui-label">Jurisdictions <span class="text-red-500">*</span></label>
            <p class="mb-2 text-xs text-(--muted)">Relevant case law is loaded from these jurisdictions only (independent of phone country).</p>
            <div class="space-y-3 rounded-xl border border-(--line) p-3">
                @forelse($activeJurisdictions as $countryCode => $items)
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-(--muted)">{{ $countryCode }}</div>
                        <div class="flex flex-wrap gap-3">
                            @foreach($items as $j)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        class="ui-check-input"
                                        name="jurisdiction_ids[]"
                                        value="{{ $j->id }}"
                                        @checked(in_array((int) $j->id, $selectedJurisdictionIds, true))
                                    >
                                    <span>{{ $j->name }} <span class="text-(--muted)">({{ $j->code }})</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-(--muted) mb-0">No active jurisdictions. Run <code>php artisan db:seed --class=JurisdictionSeeder</code>.</p>
                @endempty
            </div>
            @error('jurisdiction_ids')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="ui-label">Title</label>
            <input name="title" class="ui-field" value="{{ old('title', $lawyer->title) }}" placeholder="Partner, Associate…">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Experience</label>
            <input name="experience" class="ui-field" value="{{ old('experience', $lawyer->experience) }}" placeholder="e.g. 8 years family law">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Password</label>
            <input type="password" name="password" class="ui-field" placeholder="{{ $lawyer->exists ? 'Leave blank to keep current' : 'Default: password' }}" minlength="8">
        </div>
        <div class="col-md-6">
            <label class="ui-label">Status</label>
            <select name="status" class="ui-field">
                <option value="active" @selected(old('status', $lawyer->status?->value ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $lawyer->status?->value) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="ui-label">Timezone</label>
            <input name="timezone" class="ui-field" value="{{ old('timezone', $lawyer->timezone) }}" placeholder="Asia/Karachi">
        </div>

        <div class="col-12">
            <label class="ui-label">Services this lawyer provides</label>
            @if($services->isNotEmpty())
                @php
                    $serviceOptions = $services->map(fn ($service) => [
                        'id' => $service->id,
                        'label' => $service->name,
                    ])->values()->all();
                    $selectedServiceIds = collect(old('service_ids', $selectedServices))->map(fn ($id) => (int) $id)->values()->all();
                @endphp
                <div
                    class="ui-multi-dropdown"
                    x-data="{
                        open: false,
                        options: {{ Js::from($serviceOptions) }},
                        selected: {{ Js::from($selectedServiceIds) }},
                        toggle(id) {
                            const value = Number(id);
                            if (this.selected.includes(value)) {
                                this.selected = this.selected.filter((item) => item !== value);
                            } else {
                                this.selected = [...this.selected, value];
                            }
                        },
                        label() {
                            const names = this.options
                                .filter((option) => this.selected.includes(Number(option.id)))
                                .map((option) => option.label);
                            if (!names.length) return 'Select services…';
                            if (names.length <= 2) return names.join(', ');
                            return names.slice(0, 2).join(', ') + ' +' + (names.length - 2) + ' more';
                        }
                    }"
                    @click.outside="open = false"
                >
                    <template x-for="id in selected" :key="'svc-' + id">
                        <input type="hidden" name="service_ids[]" :value="id">
                    </template>

                    <button type="button" class="ui-field ui-multi-dropdown-trigger" @click="open = !open" :aria-expanded="open">
                        <span x-text="label()" :class="selected.length ? '' : 'ui-muted'"></span>
                        <svg class="ui-multi-dropdown-caret" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="ui-multi-dropdown-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                        <template x-for="option in options" :key="option.id">
                            <label class="ui-multi-dropdown-option">
                                <input type="checkbox" class="ui-check-input" :checked="selected.includes(Number(option.id))" @change="toggle(option.id)">
                                <span x-text="option.label"></span>
                            </label>
                        </template>
                    </div>
                </div>
            @else
                <p class="small ui-muted mb-0">Pehle Services page se service add karein — phir yahan dropdown mein dikhengi.</p>
            @endif
        </div>
        <div class="col-12">
            <label class="ui-label">Bio / notes</label>
            <textarea name="bio" class="ui-field" rows="3">{{ old('bio', $lawyer->bio) }}</textarea>
        </div>
    </div>
</div>
