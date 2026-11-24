@php
    $lawyer = $lawyer ?? null;
    $activeJurisdictions = $activeJurisdictions ?? [];

    $phoneCountry = strtoupper((string) old('phone_country', $lawyer?->country));
    if (! in_array($phoneCountry, ['PK', 'GB', 'US', 'CA', 'AU'], true)) {
        $phoneCountry = '';
    }

    $split = \App\Support\Phone\PhoneNormalizer::split(old('phone_local') ? null : $lawyer?->phone);
    if (old('phone_local') !== null) {
        $phoneLocal = old('phone_local');
    } else {
        $phoneLocal = $split['local'] ?? '';
        if ($phoneCountry === '' && ($split['country'] ?? '') !== '') {
            $phoneCountry = $split['country'];
        }
    }

    $selectedJurisdictionIds = collect(old('jurisdiction_ids', $lawyer?->jurisdictions?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    $practiceCountries = [
        'PK' => 'Pakistan',
        'GB' => 'United Kingdom',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'US' => 'United States',
    ];
@endphp

<div
    class="col-12 space-y-4"
    x-data="lawyerPracticeForm(@js($phoneCountry), @js($phoneLocal), @js($activeJurisdictions), @js($selectedJurisdictionIds))"
>
    <div>
        <label class="ui-label">Phone *</label>
        <div class="grid gap-2 sm:grid-cols-[11rem_7rem_1fr]">
            <select name="phone_country" class="ui-field" x-model="phoneCountry" @change="onPhoneCountryChange()" required>
                <option value="">Dial country</option>
                <option value="PK">PK — Pakistan (+92)</option>
                <option value="GB">GB — United Kingdom (+44)</option>
                <option value="US">US — United States (+1)</option>
                <option value="CA">CA — Canada (+1)</option>
                <option value="AU">AU — Australia (+61)</option>
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
                required
            >
        </div>
        <p class="mt-1.5 text-xs text-(--muted)">
            National number without leading 0. Preview:
            <span class="font-medium text-(--ink)" x-text="previewE164 || '—'"></span>
        </p>
        @error('phone_country')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
        @error('phone_local')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="border-t border-(--line) pt-3">
        <h3 class="mb-1 text-sm font-semibold text-(--ink)">Cases / jurisdictions *</h3>
        <p class="mb-3 text-xs text-(--muted)">
            Practice country choose karein, phir jurisdictions select karein — yahi decide karti hain ke lawyer ko konsi case law dikhegi.
            Phone dial country alag hai.
        </p>

        @error('jurisdiction_ids')
            <div class="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        <div class="mb-3">
            <label class="ui-label">Practice country</label>
            <select class="ui-field max-w-md" x-model="practiceCountry" @change="onPracticeCountryChange()">
                <option value="">All countries</option>
                @foreach ($practiceCountries as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <label class="ui-label">Active jurisdictions</label>
        <div class="mt-1 max-h-48 space-y-2 overflow-y-auto rounded-md border border-(--line) p-3">
            @forelse ($activeJurisdictions as $countryCode => $items)
                @foreach ($items as $jur)
                    <label
                        class="flex items-center gap-2 text-sm text-(--ink)"
                        x-show="!practiceCountry || practiceCountry === @js($countryCode)"
                    >
                        <input
                            type="checkbox"
                            class="ui-check-input"
                            name="jurisdiction_ids[]"
                            value="{{ $jur['id'] }}"
                            @checked(in_array((int) $jur['id'], $selectedJurisdictionIds, true))
                        >
                        <span>{{ $jur['name'] }} <span class="text-(--muted)">({{ $jur['code'] }})</span></span>
                    </label>
                @endforeach
            @empty
                <p class="mb-0 text-sm text-(--muted)">No active jurisdictions seeded yet.</p>
            @endforelse
            <p class="mb-0 text-sm text-(--muted)" x-show="practiceCountry && visibleJurisdictions().length === 0">
                No active jurisdictions for this country yet.
            </p>
        </div>
    </div>
</div>
