<x-ui.modal
    name="add-lawyer"
    title="Add Lawyer"
    subtitle="Create a lawyer account with dial country and practice jurisdictions for case law."
    maxWidth="xl"
>
    <form method="POST" action="{{ route('firm.lawyers.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="_modal" value="add-lawyer">
        <input type="hidden" name="role" value="Lawyer">

        @if ($errors->any() && old('_modal') === 'add-lawyer')
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Full Name *</label>
                <input class="ui-field" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Email Address *</label>
                <input class="ui-field" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Title</label>
                <input class="ui-field" name="title" value="{{ old('title') }}" placeholder="e.g. Associate Counsel">
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Assigned Role *</label>
                <input class="ui-field" value="Lawyer" disabled>
            </div>

            @include('firm-admin.lawyers._practice-fields', [
                'lawyer' => null,
                'activeJurisdictions' => $activeJurisdictions ?? [],
            ])

            <div class="col-12 col-md-6">
                <label class="ui-label">Password *</label>
                <input class="ui-field" type="password" name="password" required minlength="8" autocomplete="new-password">
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Confirm Password *</label>
                <input class="ui-field" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-primary">Add Lawyer</button>
        </div>
    </form>
</x-ui.modal>
