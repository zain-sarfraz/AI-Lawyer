@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap">
    <div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight">Users</h1>
        <p class="mt-1 text-(--muted)">Add lawyers and staff. Each user gets their own dashboard, appointments, and records.</p>
    </div>
    <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-user')">Add user</button>
</div>

<x-legal.card>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-(--line) text-left text-(--muted)">
                    <th class="py-2 pr-3">Name</th>
                    <th class="py-2 pr-3">Email</th>
                    <th class="py-2 pr-3">Role</th>
                    <th class="py-2 pr-3">Phone</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b border-(--line)/60">
                        <td class="py-3 pr-3 font-semibold">{{ $user->name }}</td>
                        <td class="py-3 pr-3">{{ $user->email }}</td>
                        <td class="py-3 pr-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->hasRole('Lawyer') ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $user->getRoleNames()->join(', ') ?: '—' }}
                            </span>
                        </td>
                        <td class="py-3 pr-3 text-(--muted)">{{ $user->lawyer?->phone ?: '—' }}</td>
                        <td class="py-3 pr-3">
                            <span class="voice-pill">{{ $user->status?->value ?? 'active' }}</span>
                        </td>
                        <td class="py-3 text-right">
                            <div class="d-inline-flex align-items-center justify-content-end gap-1 flex-wrap">
                                <button
                                    type="button"
                                    class="ui-btn ui-btn-ghost btn-sm"
                                    @click="$dispatch('open-modal', 'edit-user-{{ $user->id }}')"
                                    title="Edit user"
                                >
                                    <x-ui.icon name="pencil" class="h-3.5 w-3.5" />
                                    Edit
                                </button>

                                <form method="POST" action="{{ route('users.status', $user) }}" class="d-inline m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ ($user->status?->value ?? 'active') === 'active' ? 'inactive' : 'active' }}">
                                    <button type="submit" class="ui-btn ui-btn-ghost btn-sm" title="Toggle active status">
                                        {{ ($user->status?->value ?? 'active') === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>

                                <button
                                    type="button"
                                    class="ui-btn ui-btn-ghost btn-sm !text-rose-600 hover:!bg-rose-50"
                                    @click="$store.userUi.askDelete({ id: {{ $user->id }}, name: @js($user->name), url: @js(route('users.destroy', $user)), isLawyer: {{ $user->hasRole('Lawyer') ? 'true' : 'false' }} })"
                                    title="Delete user"
                                >
                                    <x-ui.icon name="trash" class="h-3.5 w-3.5" />
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-(--muted)">No users yet. Add a lawyer or staff member to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-legal.card>

@include('admin.users._create-modal')
@include('admin.users._delete-modal')

@foreach($users as $user)
    @include('admin.users._edit-modal', ['user' => $user])
@endforeach
@endsection
