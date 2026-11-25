@php
    $modalName = 'edit-lawyer-'.$lawyer->id;
    $selectedServices = $lawyer->services->pluck('id')->all();
    $firmServices = collect($servicesByFirm[$lawyer->firm_id] ?? [])->map(fn ($s) => (object) $s);
    $isIndivModal = ($lawyer->user?->isIndividual() ?? false) || ($lawyer->firm?->isIndividualAccount() ?? false) || ($lawyer->firm_id === null);
    $modalSubFirm = $isIndivModal ? 'Individual Lawyer' : ($lawyer->firm?->firm_name ?: 'Firm');
@endphp

<x-ui.modal
    :name="$modalName"
    title="Edit lawyer"
    :subtitle="$lawyer->displayName().' · '.$modalSubFirm"
    maxWidth="2xl"
>
    <form id="{{ $modalName }}-form" method="POST" action="{{ route('superadmin.lawyers.update', $lawyer->id) }}" data-turbo="false">
        @csrf
        @method('PUT')
        <input type="hidden" name="firm_id" value="{{ $lawyer->firm_id }}">
        @include('admin.lawyers._fields', [
            'lawyer' => $lawyer,
            'services' => $firmServices,
            'selectedServices' => old('_modal') === $modalName ? old('service_ids', $selectedServices) : $selectedServices,
            'activeJurisdictions' => $activeJurisdictions ?? [],
            'idPrefix' => 'edit_lawyer_'.$lawyer->id,
            'modalName' => $modalName,
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="sa-modal-btn-cancel" @click="show = false">Cancel</button>
        <button type="submit" form="{{ $modalName }}-form" class="sa-modal-btn-submit">Save changes</button>
    </x-slot:footer>
</x-ui.modal>
