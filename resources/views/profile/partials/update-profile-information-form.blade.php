<section>
    <h2 class="crm-panel-title">Profile information</h2>
    <p class="ui-muted small mb-3">
        @if ($canEditPractice ?? false)
            Update your name, email, country code, and which case law jurisdictions are shown to you.
        @else
            Update your account name and email address.
        @endif
    </p>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label class="ui-label" for="name">Name</label>
            <input id="name" name="name" type="text" class="ui-field" value="{{ old('name', $user->name) }}" required autocomplete="name">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <label class="ui-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="ui-field" value="{{ old('email', $user->email) }}" required autocomplete="username">
            <x-input-error class="mt-2 text-danger small" :messages="$errors->get('email')" />
        </div>

        @if ($canEditPractice ?? false)
            @php
                $activeJurisdictions = $activeJurisdictions ?? [];
                $phoneCountry = strtoupper((string) old('phone_country', $lawyer?->country ?: 'US'));
                if (! in_array($phoneCountry, ['PK', 'GB', 'US', 'CA', 'AU'], true)) {
                    $phoneCountry = 'US';
                }
                $split = \App\Support\Phone\PhoneNormalizer::split(old('phone_local') ? null : $lawyer?->phone);
                $phoneLocal = old('phone_local') !== null ? old('phone_local') : ($split['local'] ?? '');
                $selectedJurisdictionIds = collect(old('jurisdiction_ids', $lawyer?->jurisdictions?->pluck('id')->all() ?? []))
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
                $practiceCountries = \App\Support\Phone\PhoneNormalizer::countryLabels();
            @endphp

            <style>
                .lp-practice-divider {
                    margin-top: 22px;
                    padding-top: 20px;
                    border-top: 1px solid var(--line, #e2e8f0);
                }
                .lp-phone-row {
                    display: grid;
                    grid-template-columns: minmax(210px, 240px) minmax(0, 1fr);
                    gap: 10px;
                    align-items: center;
                }
                .lp-country-select {
                    height: 42px !important;
                    width: 100%;
                    margin: 0;
                    border-radius: 10px !important;
                    font-size: 13.5px !important;
                    font-weight: 500;
                }
                .lp-phone-group {
                    display: flex;
                    align-items: stretch;
                    height: 42px;
                    border: 1px solid var(--line, #cbd5e1);
                    border-radius: 10px;
                    background: var(--surface-2, #ffffff);
                    overflow: hidden;
                    transition: border-color .15s ease, box-shadow .15s ease;
                }
                .lp-phone-group:focus-within {
                    border-color: #0d9488;
                    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14);
                }
                .lp-dial-prefix {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 58px;
                    padding: 0 12px;
                    background: var(--surface, #f8fafc);
                    border-right: 1px solid var(--line, #e2e8f0);
                    font-size: 13.5px;
                    font-weight: 700;
                    color: var(--ink, #0f172a);
                    user-select: none;
                    font-variant-numeric: tabular-nums;
                }
                .lp-phone-input {
                    flex: 1 1 auto;
                    min-width: 0;
                    border: 0 !important;
                    outline: none !important;
                    box-shadow: none !important;
                    background: transparent;
                    padding: 0 14px;
                    font-size: 14px;
                    color: var(--ink, #0f172a);
                }
                .lp-preview-line {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 8px;
                    margin-top: 8px;
                    font-size: 12px;
                    color: var(--muted, #64748b);
                }
                .lp-preview-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 2px 10px;
                    border-radius: 999px;
                    background: var(--surface, #f1f5f9);
                    border: 1px solid var(--line, #e2e8f0);
                    font-size: 11.5px;
                    font-weight: 600;
                    color: var(--ink, #0f172a);
                    font-variant-numeric: tabular-nums;
                }
                .lp-cases-head {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    margin-bottom: 12px;
                    flex-wrap: wrap;
                }
                .lp-country-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 4px 10px;
                    border-radius: 999px;
                    background: rgba(13, 148, 136, 0.1);
                    border: 1px solid rgba(13, 148, 136, 0.25);
                    color: #0f766e;
                    font-size: 11.5px;
                    font-weight: 700;
                }
                .lp-jur-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                    gap: 10px;
                }
                .lp-jur-card {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    padding: 11px 14px;
                    border-radius: 10px;
                    border: 1px solid var(--line, #e2e8f0);
                    background: var(--surface-2, #ffffff);
                    cursor: pointer;
                    margin: 0;
                    transition: all 0.15s ease;
                }
                .lp-jur-card:hover {
                    border-color: #94a3b8;
                }
                .lp-jur-card.is-checked {
                    border-color: rgba(13, 148, 136, 0.55);
                    background: rgba(13, 148, 136, 0.06);
                }
                .lp-jur-left {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    min-width: 0;
                }
                .lp-jur-name {
                    font-size: 13.5px;
                    font-weight: 600;
                    color: var(--ink, #0f172a);
                    line-height: 1.3;
                }
                .lp-jur-code {
                    flex-shrink: 0;
                    padding: 2px 7px;
                    border-radius: 6px;
                    background: var(--surface, #f1f5f9);
                    color: var(--muted, #475569);
                    font-size: 11px;
                    font-weight: 600;
                    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                }
                @media (max-width: 640px) {
                    .lp-phone-row {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <div
                class="lp-practice-divider"
                x-data="{
                    ...lawyerPracticeForm(@js($phoneCountry), @js($phoneLocal), @js($activeJurisdictions), @js($selectedJurisdictionIds)),
                    onCountrySwitch() {
                        this.onPhoneCountryChange();
                        this.practiceCountry = this.phoneCountry;
                        const items = this.jurisdictionsByCountry[this.phoneCountry] || [];
                        this.selectedJurisdictions = items.map((item) => Number(item.id));
                    }
                }"
            >
                <div style="margin-bottom: 20px;">
                    <label class="ui-label" style="margin-bottom: 8px; display: block;">Country Code &amp; Phone *</label>
                    <div class="lp-phone-row">
                        <select name="phone_country" class="ui-field lp-country-select" x-model="phoneCountry" @change="onCountrySwitch()" required>
                            @foreach ($practiceCountries as $cCode => $cName)
                                <option value="{{ $cCode }}">{{ $cCode }} — {{ $cName }} ({{ \App\Support\Phone\PhoneNormalizer::dialCode($cCode) }})</option>
                            @endforeach
                        </select>
                        <div class="lp-phone-group">
                            <span class="lp-dial-prefix" x-text="dialCode || '—'"></span>
                            <input
                                type="tel"
                                name="phone_local"
                                class="lp-phone-input"
                                x-model="phoneLocal"
                                @input="sanitizeLocal()"
                                placeholder="3247087907 (optional)"
                                inputmode="numeric"
                                autocomplete="tel-national"
                            >
                        </div>
                    </div>
                    <div class="lp-preview-line">
                        <span>Selecting a country automatically scopes your case law to that country.</span>
                        <span class="lp-preview-chip">
                            <span style="opacity:.65;font-weight:500;">Preview:</span>
                            <span x-text="previewE164 || '—'"></span>
                        </span>
                    </div>
                    @error('phone_country')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('phone_local')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lp-practice-divider" style="margin-top: 18px; padding-top: 18px;">
                    <div class="lp-cases-head">
                        <div>
                            <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink, #0f172a);">Cases Shown To You</h3>
                            <p style="margin: 2px 0 0; font-size: 12px; color: var(--muted, #64748b);">
                                Automatically matched to your selected practice country.
                            </p>
                        </div>
                        <span class="lp-country-pill">
                            <span>Country:</span>
                            <span x-text="phoneCountry"></span>
                        </span>
                    </div>

                    <div class="lp-jur-grid">
                        @forelse ($activeJurisdictions as $countryCode => $items)
                            @foreach ($items as $jur)
                                <label
                                    class="lp-jur-card"
                                    :class="selectedJurisdictions.includes({{ (int) $jur['id'] }}) ? 'is-checked' : ''"
                                    x-show="phoneCountry === @js($countryCode)"
                                >
                                    <span class="lp-jur-left">
                                        <input
                                            type="checkbox"
                                            class="ui-check-input"
                                            name="jurisdiction_ids[]"
                                            value="{{ $jur['id'] }}"
                                            :checked="selectedJurisdictions.includes({{ (int) $jur['id'] }})"
                                            @change="toggleJurisdiction({{ (int) $jur['id'] }})"
                                        >
                                        <span class="lp-jur-name">{{ $jur['name'] }}</span>
                                    </span>
                                    <span class="lp-jur-code">{{ $jur['code'] }}</span>
                                </label>
                            @endforeach
                        @empty
                            <p class="mb-0 text-sm text-(--muted)">No active jurisdictions available yet.</p>
                        @endforelse
                    </div>
                    <p
                        class="mb-0 rounded-lg border border-dashed border-(--line) p-3 text-sm text-(--muted)"
                        x-show="!(jurisdictionsByCountry[phoneCountry] && jurisdictionsByCountry[phoneCountry].length)"
                    >
                        No active jurisdictions seeded for this country yet.
                    </p>
                    @error('jurisdiction_ids')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        <div class="pt-2">
            <button class="ui-btn ui-btn-primary">Save</button>
            @if (session('status') === 'profile-updated')
                <span class="small ui-muted ms-2">Saved.</span>
            @endif
        </div>
    </form>
</section>
