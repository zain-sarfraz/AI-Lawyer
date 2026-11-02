@extends('layouts.app')

@section('title', $record->title)

@section('content')
<x-legal.page-header :title="$record->title" :subtitle="$record->citation ?: 'Case law record'">
    <x-slot:actions>
        <a href="{{ route('lawyers.relevant-cases', $lawyer) }}" class="ui-btn ui-btn-ghost">Back to cases</a>
    </x-slot:actions>
</x-legal.page-header>

<p class="mb-4 text-xs text-(--muted)">
    AI summaries and evidence must be verified by a qualified lawyer before reliance.
</p>

<div class="grid gap-4 lg:grid-cols-3">
    <x-legal.card class="lg:col-span-2">
        <h3 class="font-display text-lg mb-3">AI insights</h3>
        @if($record->ai_status !== 'done')
            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Processing</span>
            <p class="mt-3 text-sm text-(--muted)">Summary and evidence are not ready (status: {{ $record->ai_status }}).</p>
        @else
            <dl class="lawyer-detail-list">
                <div>
                    <dt>Summary</dt>
                    <dd style="white-space:pre-wrap">{{ $record->ai_summary ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Outcome</dt>
                    <dd>{{ $record->dispositionLabel() ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Plaintiff evidence</dt>
                    <dd>
                        <ul class="mb-0 list-disc pl-5">
                            @forelse(($record->evidence_json['plaintiff'] ?? []) as $item)
                                <li>{{ $item }}</li>
                            @empty
                                <li class="text-(--muted)">None recorded</li>
                            @endforelse
                        </ul>
                    </dd>
                </div>
                <div>
                    <dt>Defendant evidence</dt>
                    <dd>
                        <ul class="mb-0 list-disc pl-5">
                            @forelse(($record->evidence_json['defendant'] ?? []) as $item)
                                <li>{{ $item }}</li>
                            @empty
                                <li class="text-(--muted)">None recorded</li>
                            @endforelse
                        </ul>
                    </dd>
                </div>
                @if($record->ai_confidence !== null)
                    <div>
                        <dt>AI confidence</dt>
                        <dd>{{ number_format((float) $record->ai_confidence * 100, 1) }}%</dd>
                    </div>
                @endif
            </dl>
        @endif
    </x-legal.card>

    <x-legal.card>
        <h3 class="font-display text-lg mb-3">Metadata</h3>
        <dl class="lawyer-detail-list">
            <div><dt>Jurisdiction</dt><dd>{{ $record->jurisdiction?->name }}</dd></div>
            <div><dt>Court</dt><dd>{{ $record->court?->name ?: '—' }}</dd></div>
            <div><dt>Decision date</dt><dd>{{ optional($record->decision_date)->format('Y-m-d') ?: '—' }}</dd></div>
            <div><dt>Source</dt><dd>{{ $record->source }} / {{ $record->external_id }}</dd></div>
            @if($record->source_url)
                <div><dt>Source URL</dt><dd><a class="text-teal-700 underline" href="{{ $record->source_url }}" target="_blank" rel="noopener">Open</a></dd></div>
            @endif
        </dl>
    </x-legal.card>
</div>
@endsection
