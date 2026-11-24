@php
    $status = $member->status?->value ?? 'inactive';
    $phone = $member->lawyer?->phone;
    $role = $member->getRoleNames()->first() ?: 'Lawyer';
    $title = $member->lawyer?->title;
    $initials = strtoupper(collect(explode(' ', $member->name ?: 'U'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''));
    $jurisdictions = $member->lawyer?->jurisdictions
        ?->map(fn ($j) => $j->name.' ('.$j->code.')')
        ->implode(', ');
    $payload = [
        'id' => $member->id,
        'name' => $member->name,
        'email' => $member->email,
        'phone' => $phone,
        'country' => $member->lawyer?->country,
        'jurisdictions' => $jurisdictions ?: null,
        'title' => $title,
        'role' => $role,
        'roleMeta' => $title ? $role.' · '.$title : $role,
        'status' => $status,
        'initials' => $initials,
        'added_at' => optional($member->created_at)->format('M j, Y'),
        'cases_count' => (int) ($member->lawyer?->legal_cases_count ?? 0),
    ];
@endphp

<div class="firm-lawyer-actions" role="group" aria-label="Lawyer actions">
    <button
        type="button"
        class="firm-action is-view"
        title="View profile"
        @click='$store.firmMemberUi.openDetails(@json($payload))'
    >
        <x-ui.icon name="eye" class="h-3.5 w-3.5" />
        <span>View</span>
    </button>
    <button
        type="button"
        class="firm-action is-edit"
        title="Edit details"
        @click="$store.firmMemberUi.openEdit({{ $member->id }})"
    >
        <x-ui.icon name="pencil" class="h-3.5 w-3.5" />
        <span>Edit</span>
    </button>
</div>
