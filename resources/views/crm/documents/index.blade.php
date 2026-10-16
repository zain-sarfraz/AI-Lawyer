@extends('layouts.app')

@section('content')
<div x-data="liveTableSearch(@js($filters['q'] ?? ''), @js($filters['status'] ?? ''))">
    <x-legal.page-header title="CRM" subtitle="Documents grouped by client — open View to see all files and images">
        <x-slot:actions>
            @can('documents.manage')
                <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'upload-document')">Upload document</button>
            @endcan
        </x-slot:actions>
    </x-legal.page-header>

    <div class="legal-toolbar">
        <div class="legal-toolbar-search">
            <input
                type="search"
                class="ui-field"
                placeholder="Search client, file name..."
                autocomplete="off"
                x-model="query"
                @input="apply()"
            >
        </div>
        <select class="ui-field" style="max-width: 160px" x-model="status" @change="apply()">
            <option value="">All statuses</option>
            <option value="open">Open</option>
            <option value="closed">Closed</option>
        </select>
    </div>

    <x-legal.card>
        <div class="ui-table-wrap">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th>Client name</th>
                        <th>Files</th>
                        <th>Case</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="tbody">
                @forelse($groups as $group)
                    <tr data-search="{{ $group['search'] }}" data-status="{{ $group['status'] }}">
                        <td class="fw-semibold">{{ $group['client'] }}</td>
                        <td>
                            <div class="text-sm">{{ $group['files_count'] }} total</div>
                            <div class="text-xs text-(--muted)">
                                {{ $group['image_count'] }} image(s) · {{ $group['file_count'] }} file(s)
                            </div>
                        </td>
                        <td>{{ $group['cases'] ? implode(', ', $group['cases']) : '—' }}</td>
                        <td>
                            <x-ui.badge :tone="$group['status'] === 'closed' ? 'default' : 'success'">
                                {{ ucfirst($group['status']) }}
                            </x-ui.badge>
                        </td>
                        <td>{{ $group['uploaded'] }}</td>
                        <td class="text-end">
                            <div class="table-icon-actions">
                                <button
                                    type="button"
                                    class="table-icon-btn is-edit"
                                    title="View files"
                                    aria-label="View client documents"
                                    @click="$store.documentUi.openGallery(@js($group['gallery']))"
                                >
                                    <x-ui.icon name="eye" class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr x-ref="empty">
                        <td colspan="6" class="legal-empty py-5">
                            <h3>No documents yet</h3>
                            <p class="mb-0">Upload contracts, IDs, and matter files for your CRM.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3" x-ref="pager">{{ $groups->links() }}</div>
    </x-legal.card>
</div>

@include('crm.documents._gallery-modal')
@include('crm.documents._details-modal')
@can('documents.manage')
    @include('crm.documents._upload-modal')
    @include('crm.documents._delete-modal')
@endcan
@endsection
