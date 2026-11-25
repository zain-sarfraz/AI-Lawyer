@extends('layouts.superadmin')

@section('title', $firm->firm_name)

@section('content')
@php
    $status = $firm->status ?? 'active';
    $isActive = $status === 'active';
    $initials = collect(preg_split('/\s+/', trim((string) $firm->firm_name)) ?: [])
        ->filter()
        ->map(fn ($p) => mb_substr($p, 0, 1))
        ->take(2)
        ->implode('');
    $initials = $initials !== '' ? strtoupper($initials) : 'F';
    $adminCount = $admins->count();
    $lawyerCount = $admins->filter(fn ($u) => $u->hasRole('Lawyer'))->count();
    $indivUser = $admins->first(fn ($u) => $u->isIndividual());
    $isIndiv = $firm->isIndividualAccount() || $indivUser !== null;
    $heroName = $isIndiv ? ($indivUser?->name ?: ($firm->owner?->name ?: $firm->firm_name)) : $firm->firm_name;
@endphp

<div class="sa-firm-show">
    <div class="mb-3">
        <a href="{{ route('superadmin.firms.index') }}" class="sa-firm-back">
            <x-ui.icon name="chevron-left" class="h-4 w-4" />
            All firms
        </a>
    </div>

    <section class="sa-firm-hero">
        <div class="sa-firm-hero-main">
            <div class="sa-firm-avatar" aria-hidden="true">{{ $initials }}</div>
            <div class="min-w-0">
                <div class="sa-firm-kicker">{{ $isIndiv ? 'Individual Lawyer' : 'Firm workspace' }}</div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h1 class="sa-firm-title">{{ $heroName }}</h1>
                    @if($isIndiv)
                        <span class="sa-firm-status" style="background: #EEF2FF; color: #3730A3;">Individual</span>
                    @endif
                    <span class="sa-firm-status {{ $isActive ? 'is-active' : 'is-suspended' }}">
                        {{ $isActive ? 'Active' : 'Suspended' }}
                    </span>
                </div>
                <div class="sa-firm-meta-row">
                    <span>{{ $firm->email ?: 'No email' }}</span>
                    <span class="sa-dot">·</span>
                    <span>{{ $firm->timezone ?: '—' }}</span>
                    <span class="sa-dot">·</span>
                    <span>{{ $firm->currency ?: '—' }}</span>
                </div>
            </div>
        </div>
        <div class="sa-firm-hero-actions">
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
                        status: @js($status),
                    });
                    $dispatch('open-modal', 'edit-firm');
                "
            >Edit firm</button>
            <form method="POST" action="{{ route('superadmin.firms.status', $firm) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $isActive ? 'suspended' : 'active' }}">
                <button type="submit" class="ui-btn {{ $isActive ? 'ui-btn-ghost' : 'ui-btn-brass' }} btn-sm">
                    {{ $isActive ? 'Suspend firm' : 'Activate firm' }}
                </button>
            </form>
            <button
                type="button"
                class="ui-btn ui-btn-ghost btn-sm text-rose-600 border-rose-200 hover:bg-rose-50"
                @click="
                    $dispatch('fill-delete-firm', {
                        action: @js(route('superadmin.firms.destroy', $firm)),
                        name: @js($firm->firm_name),
                    });
                    $dispatch('open-modal', 'delete-firm');
                "
            >Delete firm</button>
        </div>
    </section>

    <div class="sa-firm-stats">
        <div class="sa-firm-stat">
            <span class="sa-firm-stat-label">Users</span>
            <strong>{{ $adminCount }}</strong>
        </div>
        <div class="sa-firm-stat">
            <span class="sa-firm-stat-label">Lawyers</span>
            <strong>{{ $lawyerCount }}</strong>
        </div>
        <div class="sa-firm-stat">
            <span class="sa-firm-stat-label">Created</span>
            <strong class="sa-firm-stat-date">{{ optional($firm->created_at)->format('M j, Y') ?: '—' }}</strong>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <section class="sa-firm-panel">
                <div class="sa-firm-panel-head">
                    <h2>Firm details</h2>
                    <p>Platform metadata only — CRM data stays in the firm workspace.</p>
                </div>
                <dl class="sa-firm-dl">
                    <div>
                        <dt>Status</dt>
                        <dd>
                            <span class="sa-firm-status {{ $isActive ? 'is-active' : 'is-suspended' }}">
                                {{ $isActive ? 'Active' : 'Suspended' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd>{{ $firm->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd>{{ $firm->email ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Timezone</dt>
                        <dd>{{ $firm->timezone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Currency</dt>
                        <dd>{{ $firm->currency ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Slug</dt>
                        <dd><code class="sa-firm-code">{{ $firm->slug }}</code></dd>
                    </div>
                    <div>
                        <dt>Created</dt>
                        <dd>{{ optional($firm->created_at)->format('D, M j, Y g:i A') ?: '—' }}</dd>
                    </div>
                </dl>
                <p class="sa-firm-footnote">
                    Superadmin cannot open this firm’s leads, clients, appointments, or calls.
                </p>
            </section>
        </div>

        <div class="col-12 col-lg-8">
            <section class="sa-firm-panel">
                <div class="sa-firm-panel-head sa-firm-panel-head-row">
                    <div>
                        <h2>Lawyers on this firm</h2>
                        <p>{{ $adminCount }} user{{ $adminCount === 1 ? '' : 's' }} in this workspace.</p>
                    </div>
                    <a
                        href="{{ route('superadmin.lawyers.index', ['open' => 'create-lawyer', 'firm_id' => $firm->id]) }}"
                        class="ui-btn ui-btn-brass btn-sm"
                    >Add lawyer</a>
                </div>

                <div class="sa-firm-people">
                    @forelse($admins as $admin)
                        @php
                            $rolesLabel = $admin->displayRoleLabel();
                            $userInitials = collect(preg_split('/\s+/', trim((string) $admin->name)) ?: [])
                                ->filter()
                                ->map(fn ($p) => mb_substr($p, 0, 1))
                                ->take(2)
                                ->implode('');
                            $userInitials = $userInitials !== '' ? strtoupper($userInitials) : '?';
                        @endphp
                        <article class="sa-firm-person">
                            <div class="sa-firm-person-main">
                                <div class="sa-firm-person-avatar">{{ $userInitials }}</div>
                                <div class="min-w-0">
                                    <div class="sa-firm-person-name">{{ $admin->name }}</div>
                                    <div class="sa-firm-person-email">{{ $admin->email }}</div>
                                </div>
                            </div>
                            <div class="sa-firm-person-meta">
                                <span class="sa-role-pill is-lawyer">{{ $rolesLabel }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="sa-firm-empty">
                            <strong>No lawyers yet</strong>
                            <p>Add a lawyer from the Lawyers page so this firm can sign in.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
