<x-ui.modal name="create-lawyer" title="New lawyer" subtitle="Phone dial country is separate from practice jurisdictions" maxWidth="lg">
    <form
        id="create-lawyer-form"
        method="POST"
        action="{{ route('lawyers.store') }}"
        data-turbo="false"
        onsubmit="return window.__validateLawyerForm(this)"
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
        @include('admin.lawyers._fields', [
            'lawyer' => $formLawyer,
            'services' => $formServices,
            'selectedServices' => [],
            'activeJurisdictions' => $activeJurisdictions ?? [],
            'idPrefix' => 'create_lawyer',
            'modalName' => 'create-lawyer',
        ])
        <div class="mt-4 flex justify-end gap-2 border-t border-(--line) pt-4">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-primary">Save lawyer</button>
        </div>
    </form>
</x-ui.modal>
