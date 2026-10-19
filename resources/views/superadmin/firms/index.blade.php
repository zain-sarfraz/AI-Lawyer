@extends('layouts.superadmin')

@section('title', 'Firm')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap">
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight">Firm</h1>
        <p class="mt-1 text-(--muted)">Each firm is an isolated admin workspace.</p>
    </div>
    <button type="button" class="ui-btn ui-btn-brass" @click="$dispatch('open-modal', 'create-firm')">Create firm</button>
</div>

<x-legal.card>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-(--line) text-left text-(--muted)">
                    <th class="py-2 pr-3">Firm</th>
                    <th class="py-2 pr-3">Users</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($firms as $firm)
                    @php $isActive = ($firm->status ?? 'active') === 'active'; @endphp
                    <tr class="border-b border-(--line)/60">
                        <td class="py-3 pr-3">
                            <div class="font-semibold">{{ $firm->firm_name }}</div>
                            <div class="text-xs text-(--muted)">{{ $firm->email }}</div>
                        </td>
                        <td class="py-3 pr-3">{{ $firm->users_count }}</td>
                        <td class="py-3 pr-3">
                            <span class="status-pill {{ $isActive ? 'is-active' : 'is-suspended' }}">
                                {{ $isActive ? 'Active' : 'Suspended' }}
                            </span>
                        </td>
                        <td class="py-3 text-end">
                            <div class="inline-flex items-center gap-1">
                                <button
                                    type="button"
                                    class="table-icon-btn is-edit"
                                    title="Edit firm"
                                    aria-label="Edit firm"
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
                                >
                                    <x-ui.icon name="pencil" class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    class="table-icon-btn is-delete"
                                    title="Delete firm"
                                    aria-label="Delete firm"
                                    @click="
                                        $dispatch('fill-delete-firm', {
                                            action: @js(route('superadmin.firms.destroy', $firm)),
                                            name: @js($firm->firm_name),
                                        });
                                        $dispatch('open-modal', 'delete-firm');
                                    "
                                >
                                    <x-ui.icon name="trash" class="h-4 w-4" />
                                </button>
                                <a href="{{ route('superadmin.firms.show', $firm) }}" class="ui-btn ui-btn-primary btn-sm">Open</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $firms->links() }}</div>
</x-legal.card>
@endsection
