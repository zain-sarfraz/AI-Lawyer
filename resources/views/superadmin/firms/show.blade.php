@extends('layouts.superadmin')

@section('title', $firm->firm_name)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 gap-3 flex-wrap">
    <div>
        <div class="studio-kicker">Firm workspace</div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight">{{ $firm->firm_name }}</h1>
        <p class="mt-1 text-(--muted)">{{ $firm->email }} · {{ $firm->timezone }} · {{ $firm->currency }}</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <button
            type="button"
            class="ui-btn ui-btn-brass btn-sm"
            @click="
                $dispatch('fill-edit-firm', {
                    action: @js(route('superadmin.firms.update', $firm)),
                    firm_name: @js($firm->firm_name),
                    email: @js($firm->email),
                    phone: @js($firm->phone),
                    timezone: @js($firm->timezone),
                    currency: @js($firm->currency),
                    status: @js($firm->status ?? 'active'),
                });
                $dispatch('open-modal', 'edit-firm');
            "
        >Edit firm</button>
        <form method="POST" action="{{ route('superadmin.firms.status', $firm) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="{{ ($firm->status ?? 'active') === 'active' ? 'suspended' : 'active' }}">
            <button type="submit" class="ui-btn ui-btn-ghost btn-sm">
                {{ ($firm->status ?? 'active') === 'active' ? 'Suspend firm' : 'Activate firm' }}
            </button>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <x-legal.card>
            <h2 class="legal-card-title">Firm metadata</h2>
            <div class="small mt-3 space-y-2">
                <div><strong>Status:</strong> {{ $firm->status ?? 'active' }}</div>
                <div><strong>Phone:</strong> {{ $firm->phone ?: '—' }}</div>
                <div><strong>Slug:</strong> {{ $firm->slug }}</div>
                <div><strong>Created:</strong> {{ optional($firm->created_at)->toDayDateTimeString() }}</div>
            </div>
            <p class="mt-4 text-xs text-(--muted)">Superadmin cannot open this firm's leads, clients, appointments, or calls.</p>
        </x-legal.card>
    </div>
    <div class="col-12 col-lg-7">
        <x-legal.card>
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h2 class="legal-card-title mb-0">Admins on this firm</h2>
                <button
                    type="button"
                    class="ui-btn ui-btn-brass btn-sm"
                    @click="
                        const el = document.getElementById('add-admin-firm-id');
                        if (el) el.value = '{{ $firm->id }}';
                        $dispatch('open-modal', 'add-admin');
                    "
                >Add admin</button>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-(--line) text-left text-(--muted)">
                            <th class="py-2 pr-3">Name</th>
                            <th class="py-2 pr-3">Email</th>
                            <th class="py-2 pr-3">Role</th>
                            <th class="py-2 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admins as $admin)
                            @php
                                $role = $admin->getRoleNames()->first() ?: 'Admin';
                                $status = $admin->status?->value ?? (string) $admin->status ?: 'active';
                                $isFirmAdmin = $admin->hasAnyRole(['Owner', 'Admin']);
                            @endphp
                            <tr class="border-b border-(--line)/60">
                                <td class="py-3 pr-3 font-semibold">{{ $admin->name }}</td>
                                <td class="py-3 pr-3">{{ $admin->email }}</td>
                                <td class="py-3 pr-3">{{ $admin->getRoleNames()->join(', ') ?: '—' }}</td>
                                <td class="py-3 text-end">
                                    @if($isFirmAdmin)
                                        <button
                                            type="button"
                                            class="table-icon-btn is-edit"
                                            title="Edit admin"
                                            aria-label="Edit admin"
                                            @click="
                                                $dispatch('fill-edit-admin', {
                                                    action: @js(route('superadmin.admins.update', $admin->id)),
                                                    firm_id: @js($admin->firm_id),
                                                    admin_name: @js($admin->name),
                                                    admin_email: @js($admin->email),
                                                    admin_role: @js($role),
                                                    status: @js($status),
                                                });
                                                $dispatch('open-modal', 'edit-admin');
                                            "
                                        >
                                            <x-ui.icon name="pencil" class="h-4 w-4" />
                                        </button>
                                    @else
                                        <span class="text-xs text-(--muted)">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-(--muted)">No users on this firm.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-legal.card>
    </div>
</div>
@endsection
