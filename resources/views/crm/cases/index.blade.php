@extends('layouts.app')

@section('content')
@php
    $statusLabels = [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'on_hold' => 'On hold',
        'closed' => 'Closed',
    ];
@endphp

<div x-data="liveTableSearch(@js($filters['q'] ?? ''), @js($filters['status'] ?? ''))">
    <x-legal.page-header title="Cases Management" subtitle="Track legal matters, assigned counsel, and case status">
        <x-slot:actions>
            @can('cases.manage')
                <button type="button" class="ui-btn ui-btn-primary" @click="$dispatch('open-modal', 'create-case')">New case</button>
            @endcan
        </x-slot:actions>
    </x-legal.page-header>

    @if(session('success'))
        <div class="ui-alert ui-alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="ui-alert ui-alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="legal-toolbar">
        <div class="legal-toolbar-search">
            <input
                type="search"
                class="ui-field"
                placeholder="Search case number, title, status"
                autocomplete="off"
                x-model="query"
                @input="apply()"
            >
        </div>
    </div>

    <x-legal.card>
        <div class="ui-table-wrap">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th>Case no.</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Lawyer</th>
                        <th>Opened</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="tbody">
                @forelse($cases as $case)
                    @php
                        $status = $case->status ?: 'open';
                        $search = strtolower(trim(($case->case_number.' '.$case->title.' '.$status.' '.$case->client?->name)));
                        $files = $case->documents->map(function ($doc) {
                            $mime = (string) ($doc->mime_type ?: '');
                            $isImage = str_starts_with($mime, 'image/')
                                || preg_match('/\.(png|jpe?g|gif|webp|bmp)$/i', (string) $doc->original_name);

                            return [
                                'name' => $doc->original_name,
                                'size' => $doc->humanSize(),
                                'is_image' => (bool) $isImage,
                                'preview_url' => $isImage ? route('crm.documents.preview', $doc) : null,
                                'download_url' => route('crm.documents.download', $doc),
                            ];
                        })->values()->all();
                        $detail = [
                            'id' => $case->id,
                            'title' => $case->title,
                            'case_number' => $case->case_number,
                            'client' => $case->client?->name ?: '—',
                            'lawyer' => $case->lawyer?->displayName() ?: '—',
                            'status' => $status,
                            'opened_on' => $case->opened_on?->format('Y-m-d') ?: '—',
                            'description' => (string) ($case->description ?? ''),
                            'files' => $files,
                            'update_url' => route('cases.notes', $case),
                            'upload_url' => route('cases.attachments', $case),
                        ];
                    @endphp
                    <tr data-search="{{ $search }}" data-status="{{ $status }}">
                        <td class="fw-semibold">{{ $case->case_number ?: '—' }}</td>
                        <td>{{ $case->title }}</td>
                        <td>{{ $case->client?->name ?: '—' }}</td>
                        <td>{{ $case->lawyer?->displayName() ?: '—' }}</td>
                        <td>{{ $case->opened_on?->format('Y-m-d') ?: '—' }}</td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <x-legal.badge :tone="$status === 'closed' ? 'soft' : ($status === 'on_hold' ? 'pending' : 'teal')">
                                    {{ $statusLabels[$status] ?? str_replace('_', ' ', $status) }}
                                </x-legal.badge>
                                @if($case->documents->count() > 0)
                                    <span class="text-xs text-(--muted)">{{ $case->documents->count() }} file(s)</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="table-icon-actions">
                                <button
                                    type="button"
                                    class="table-icon-btn is-edit"
                                    title="View details"
                                    @click="$store.caseUi.openDetails(@js($detail))"
                                >
                                    <x-ui.icon name="eye" class="h-4 w-4" />
                                </button>
                                @can('cases.manage')
                                    <button
                                        type="button"
                                        class="table-icon-btn is-edit"
                                        title="Notes"
                                        @click="$store.caseUi.openNotes(@js($detail))"
                                    >
                                        <x-ui.icon name="clipboard" class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="table-icon-btn is-edit"
                                        title="Edit"
                                        @click="$dispatch('open-modal', 'edit-case-{{ $case->id }}')"
                                    >
                                        <x-ui.icon name="pencil" class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="table-icon-btn is-delete"
                                        title="Delete"
                                        @click="$store.caseUi.askDelete({ name: @js($case->title), url: @js(route('cases.destroy', $case)) })"
                                    >
                                        <x-ui.icon name="trash" class="h-4 w-4" />
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr x-ref="empty">
                        <td colspan="7" class="legal-empty py-5">
                            <h3>No cases yet</h3>
                            <p class="mb-0">Create a case to track matters for your clients.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3" x-ref="pager">{{ $cases->links() }}</div>
    </x-legal.card>
</div>

@include('crm.cases._details-modal')
@can('cases.manage')
    @include('crm.cases._notes-modal')
    @include('crm.cases._create-modal')
    @foreach($cases as $case)
        @include('crm.cases._edit-modal', ['case' => $case])
    @endforeach
    @include('crm.cases._delete-modal')
@endcan
@endsection
