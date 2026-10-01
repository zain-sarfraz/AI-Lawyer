@extends('layouts.app')

@section('content')
<x-legal.page-header title="Knowledge Base" subtitle="Approved firm knowledge the AI receptionist may cite" />

<div class="row g-3">
    <div class="col-lg-5">
        <x-legal.card title="Add knowledge item">
            <form method="POST" action="{{ route('knowledge.store') }}">
                @csrf
                <div class="legal-form-section">
                    <h3>Content</h3>
                    <p>Only active, AI-visible items are available to the receptionist.</p>
                    <div class="mb-3">
                        <label class="ui-label">Title</label>
                        <input name="title" class="ui-field" placeholder="e.g. Consultation pricing policy" required>
                    </div>
                    <div class="mb-3">
                        <label class="ui-label">Category</label>
                        <select name="type" class="ui-field">
                            <option value="faq">FAQ</option>
                            <option value="policy">Policy</option>
                            <option value="office">Office</option>
                            <option value="practice">Practice area</option>
                            <option value="pricing">Pricing</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="ui-label">Body</label>
                        <textarea name="content" class="ui-field" rows="6" required placeholder="Clear, factual content for AI answers"></textarea>
                    </div>
                </div>
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label class="ui-label">Status</label>
                        <select name="status" class="ui-field">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6 ui-check mt-4">
                        <input type="checkbox" class="ui-check-input" name="ai_visible" value="1" id="ai_visible" checked>
                        <label class="ui-check-label" for="ai_visible">AI visible</label>
                    </div>
                </div>
                <button class="ui-btn ui-btn-primary mt-3">Add item</button>
            </form>
        </x-legal.card>
    </div>

    <div class="col-lg-7">
        <x-legal.card title="Published items">
            @forelse($items as $item)
                <div class="legal-list-item">
                    <div>
                        <div class="fw-semibold">{{ $item->title }}</div>
                        <div class="small ui-muted mb-1">
                            Updated {{ $item->updated_at?->diffForHumans() ?? '—' }}
                        </div>
                        <div class="small" style="color:var(--ink);max-width:52ch">{{ \Illuminate\Support\Str::limit($item->content, 160) }}</div>
                    </div>
                    <div class="text-end">
                        <x-legal.badge>{{ $item->type }}</x-legal.badge>
                        <div class="mt-2">
                            <x-legal.badge :tone="$item->ai_visible ? 'brass' : 'soft'">
                                {{ $item->ai_visible ? 'AI on' : 'AI off' }}
                            </x-legal.badge>
                        </div>
                    </div>
                </div>
            @empty
                <div class="legal-empty">
                    <h3>No knowledge yet</h3>
                    <p>Add FAQs, policies, and office details for approved AI answers.</p>
                </div>
            @endforelse
            <div class="mt-3">{{ $items->links() }}</div>
        </x-legal.card>
    </div>
</div>
@endsection
