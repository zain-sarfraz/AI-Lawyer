@extends('layouts.app')

@section('content')
@php
    $duration = $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '—';
    $transcript = trim((string) $call->transcript);
    $lines = [];
    if ($transcript !== '') {
        foreach (preg_split("/\r\n|\n|\r/", $transcript) as $raw) {
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }
            if (preg_match('/^(AI|Assistant|Receptionist)\s*[:\-]\s*(.+)$/i', $raw, $m)) {
                $lines[] = ['role' => 'ai', 'text' => $m[2]];
            } elseif (preg_match('/^(Caller|User|Human|Client)\s*[:\-]\s*(.+)$/i', $raw, $m)) {
                $lines[] = ['role' => 'caller', 'text' => $m[2]];
            } else {
                $lines[] = ['role' => 'caller', 'text' => $raw];
            }
        }
    }
@endphp

<x-legal.page-header title="Call detail" :subtitle="$call->caller_phone . ' · ' . ($call->started_at?->format('M j, Y g:i A') ?: 'Unknown time')" />

<div class="row g-3">
    <div class="col-lg-4">
        <x-legal.card title="Caller information">
            <div class="legal-meta-grid" style="grid-template-columns:1fr">
                <div class="legal-meta-item">
                    <label>Phone</label>
                    <div>{{ $call->caller_phone }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Status</label>
                    <div><x-legal.badge>{{ $call->status }}</x-legal.badge></div>
                </div>
                <div class="legal-meta-item">
                    <label>Outcome</label>
                    <div>{{ $call->outcome ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Intent</label>
                    <div>{{ $call->intent ?: '—' }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Duration</label>
                    <div>{{ $duration }}</div>
                </div>
                <div class="legal-meta-item">
                    <label>Handled by</label>
                    <div><x-legal.badge tone="brass">AI Receptionist</x-legal.badge></div>
                </div>
                @if($call->lead)
                    <div class="legal-meta-item">
                        <label>Lead</label>
                        <div><a href="{{ route('leads.show', $call->lead) }}">{{ $call->lead->name ?: $call->lead->phone }}</a></div>
                    </div>
                @endif
                @if($call->client)
                    <div class="legal-meta-item">
                        <label>Client</label>
                        <div><a href="{{ route('clients.show', $call->client) }}">{{ $call->client->name }}</a></div>
                    </div>
                @endif
                @if($call->recording_url)
                    <div class="legal-meta-item">
                        <label>Recording</label>
                        <div><a href="{{ $call->recording_url }}" target="_blank" rel="noopener">Open recording</a></div>
                    </div>
                @endif
            </div>
        </x-legal.card>

        <x-legal.card title="AI summary" class="mt-3">
            <p class="mb-0" style="color:var(--ink);line-height:1.55">{{ $call->summary ?: 'No summary yet.' }}</p>
        </x-legal.card>
    </div>

    <div class="col-lg-8">
        <x-legal.card title="Transcript">
            @if(count($lines))
                <div class="legal-transcript">
                    @foreach($lines as $line)
                        <div class="legal-transcript-line {{ $line['role'] === 'ai' ? 'is-ai' : 'is-caller' }}">
                            <div class="legal-transcript-speaker">
                                {{ $line['role'] === 'ai' ? 'AI Receptionist' : 'Caller' }}
                            </div>
                            <div class="legal-transcript-text">{{ $line['text'] }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="legal-empty py-4">No transcript yet.</div>
            @endif
        </x-legal.card>

        <x-legal.card title="AI tool activity" class="mt-3">
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Tool</th>
                            <th>Status</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($call->aiSession?->toolCalls ?? [] as $tool)
                        <tr>
                            <td class="fw-semibold">{{ $tool->tool_name }}</td>
                            <td>
                                <x-legal.badge :tone="$tool->status === 'ok' || $tool->status === 'success' ? 'success' : 'soft'">
                                    {{ $tool->status }}
                                </x-legal.badge>
                            </td>
                            <td>{{ $tool->duration_ms ? $tool->duration_ms . ' ms' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="legal-empty">No tool calls recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-legal.card>
    </div>
</div>
@endsection
