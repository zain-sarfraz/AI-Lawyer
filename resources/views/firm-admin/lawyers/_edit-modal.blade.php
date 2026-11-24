@php
    $modalName = 'edit-firm-member-'.$member->id;
    $useOld = old('_modal') === $modalName;
    $status = $member->status?->value ?? 'inactive';
    $canToggleStatus = ! $member->isFirmAdmin() && $member->id !== auth()->id();
@endphp

<x-ui.modal
    name="{{ $modalName }}"
    title="Edit lawyer"
    subtitle="Update profile, dial country, jurisdictions{{ $canToggleStatus ? ', or account status' : '' }}"
    maxWidth="xl"
>
    <form method="POST" action="{{ route('firm.lawyers.update', $member) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="_modal" value="{{ $modalName }}">

        @if ($errors->any() && $useOld)
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="ui-label">Full name *</label>
                <input class="ui-field" name="name" value="{{ $useOld ? old('name') : $member->name }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Email *</label>
                <input class="ui-field" type="email" name="email" value="{{ $useOld ? old('email') : $member->email }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Title</label>
                <input class="ui-field" name="title" value="{{ $useOld ? old('title') : $member->lawyer?->title }}">
            </div>

            @include('firm-admin.lawyers._practice-fields', [
                'lawyer' => $member->lawyer,
                'activeJurisdictions' => $activeJurisdictions ?? [],
            ])

            <div class="col-12 col-md-6">
                <label class="ui-label">New password</label>
                <input class="ui-field" type="password" name="password" minlength="8" autocomplete="new-password">
            </div>
            <div class="col-12 col-md-6">
                <label class="ui-label">Confirm password</label>
                <input class="ui-field" type="password" name="password_confirmation" minlength="8" autocomplete="new-password">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-primary">Save changes</button>
        </div>
    </form>

    @if ($canToggleStatus)
        <div class="mt-5 border-t border-(--line) pt-4">
            <h3 class="mb-1 text-sm font-semibold">Account status</h3>
            <p class="mb-3 text-xs text-(--muted)">Deactivate to block sign-in without deleting records.</p>
            @if ($status === 'active')
                <form method="POST" action="{{ route('firm.lawyers.status', $member) }}" onsubmit="return confirm('Deactivate {{ $member->name }}?')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="inactive">
                    <button type="submit" class="ui-btn ui-btn-danger">Deactivate account</button>
                </form>
            @else
                <form method="POST" action="{{ route('firm.lawyers.status', $member) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="ui-btn ui-btn-primary">Reactivate account</button>
                </form>
            @endif
        </div>
    @endif
</x-ui.modal>
