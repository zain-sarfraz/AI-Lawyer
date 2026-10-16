@extends('layouts.superadmin')

@section('title', 'Admins')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap">
    <div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight">Admins</h1>
        <p class="mt-1 text-(--muted)">Add or edit firm admins. Each admin only sees their own firm dashboard.</p>
    </div>
    <button type="button" class="ui-btn ui-btn-brass" @click="$dispatch('open-modal', 'add-admin')">Add admin</button>
</div>

<x-legal.card>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-(--line) text-left text-(--muted)">
                    <th class="py-2 pr-3">Name</th>
                    <th class="py-2 pr-3">Email</th>
                    <th class="py-2 pr-3">Firm</th>
                    <th class="py-2 pr-3">Role</th>
                    <th class="py-2 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    @php
                        $role = $admin->getRoleNames()->first() ?: 'Admin';
                        $status = $admin->status?->value ?? (string) $admin->status ?: 'active';
                    @endphp
                    <tr class="border-b border-(--line)/60">
                        <td class="py-3 pr-3 font-semibold">{{ $admin->name }}</td>
                        <td class="py-3 pr-3">{{ $admin->email }}</td>
                        <td class="py-3 pr-3">
                            @if($admin->firm)
                                <a class="text-(--ink)" href="{{ route('superadmin.firms.show', $admin->firm) }}">{{ $admin->firm->firm_name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 pr-3">{{ $admin->getRoleNames()->join(', ') ?: '—' }}</td>
                        <td class="py-3 text-end">
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-(--muted)">No firm admins yet. Add one to an existing firm.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $admins->links() }}</div>
</x-legal.card>
@endsection
