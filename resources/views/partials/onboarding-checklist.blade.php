@php
    $authUser = auth()->user();
@endphp

@if ($authUser && ! $authUser->isSuperAdmin() && ! $authUser->isOnboardingDismissed())
@php
    $isFirmAdmin = $authUser->isFirmAdmin();

    $steps = $isFirmAdmin ? [
        [
            'key' => 'firm_profile',
            'title' => 'Complete Firm Profile',
            'desc' => 'Add your legal name, timezone, contact info, and branding.',
            'url' => route('settings.edit'),
            'label' => 'Edit Firm',
        ],
        [
            'key' => 'invite_lawyer',
            'title' => 'Add or Invite First Lawyer',
            'desc' => 'Invite lawyers to your shared multi-seat workspace.',
            'url' => Route::has('firm.lawyers.index') ? route('firm.lawyers.index') : '#',
            'label' => 'Manage Lawyers',
        ],
        [
            'key' => 'ai_receptionist',
            'title' => 'Set Up AI Receptionist',
            'desc' => 'Configure your AI phone answering, greeting, and call hours.',
            'url' => route('ai.show'),
            'label' => 'Configure AI',
        ],
        [
            'key' => 'billing',
            'title' => 'Review Subscription & Billing',
            'desc' => 'Check your plan details, renewal date, and billing receipts.',
            'url' => Route::has('firm.billing') ? route('firm.billing') : '#',
            'label' => 'View Billing',
        ],
    ] : [
        [
            'key' => 'profile',
            'title' => 'Complete Your Profile',
            'desc' => 'Update your practice details, contact information, and avatar.',
            'url' => route('profile.edit'),
            'label' => 'Edit Profile',
        ],
        [
            'key' => 'ai_receptionist',
            'title' => 'Set Up AI Receptionist',
            'desc' => 'Configure your virtual phone assistant, greeting, and booking rules.',
            'url' => route('ai.show'),
            'label' => 'Configure AI',
        ],
        [
            'key' => 'calendar',
            'title' => 'Set Availability & Calendar',
            'desc' => 'Define your working hours and appointment booking slots.',
            'url' => route('schedule.index'),
            'label' => 'Set Schedule',
        ],
        [
            'key' => 'client_case',
            'title' => 'Add Your First Client or Case',
            'desc' => 'Start tracking clients, case notes, and consultations.',
            'url' => route('clients.index'),
            'label' => 'Add Client',
        ],
    ];

    $totalSteps = count($steps);
    $completedCount = 0;
    foreach ($steps as $st) {
        if ($authUser->isStepCompleted($st['key'])) {
            $completedCount++;
        }
    }
    $progressPercent = $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0;
@endphp

<div id="onboardingBanner" class="mb-6 rounded-2xl border border-teal-500/20 bg-gradient-to-r from-navy-900 to-navy-800 p-5 shadow-lg text-slate-100">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-500/10 px-2.5 py-0.5 text-xs font-bold text-teal-400 border border-teal-500/20">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Getting Started
                </span>
                <h3 class="text-base font-bold text-white">
                    {{ $isFirmAdmin ? 'Law Firm Workspace Setup' : 'Individual Lawyer Setup' }}
                </h3>
            </div>
            <p class="mt-1 text-xs text-slate-400">
                Complete these recommended steps to get the most out of your AI Lawyer workspace.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right">
                <div class="text-xs font-semibold text-teal-400">{{ $completedCount }} of {{ $totalSteps }} completed</div>
                <div class="mt-1 h-2 w-32 rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-teal-400 to-teal-500 transition-all duration-300" style="width: {{ $progressPercent }}%;"></div>
                </div>
            </div>

            <button
                type="button"
                onclick="dismissOnboarding()"
                class="rounded-lg border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-medium text-slate-400 hover:bg-white/10 hover:text-white transition"
                title="Dismiss checklist"
            >
                Dismiss
            </button>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($steps as $st)
            @php $isDone = $authUser->isStepCompleted($st['key']); @endphp
            <div class="flex flex-col justify-between rounded-xl border {{ $isDone ? 'border-teal-500/30 bg-teal-950/20' : 'border-white/5 bg-white/[0.02]' }} p-3.5 transition hover:border-teal-500/40">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs font-bold {{ $isDone ? 'text-teal-300 line-through' : 'text-slate-200' }}">
                            {{ $st['title'] }}
                        </span>
                        <input
                            type="checkbox"
                            {{ $isDone ? 'checked' : '' }}
                            onchange="toggleOnboardingStep('{{ $st['key'] }}')"
                            class="h-4 w-4 rounded border-white/20 bg-white/5 text-teal-500 focus:ring-teal-400 cursor-pointer"
                            title="Mark step completed"
                        >
                    </div>
                    <p class="mt-1.5 text-[11.5px] leading-relaxed text-slate-400">
                        {{ $st['desc'] }}
                    </p>
                </div>
                <div class="mt-3 pt-2 border-t border-white/5">
                    <a href="{{ $st['url'] }}" class="inline-flex items-center gap-1 text-xs font-semibold text-teal-400 hover:text-teal-300">
                        <span>{{ $st['label'] }}</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
function toggleOnboardingStep(step) {
    fetch('{{ route("onboarding.step") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ step: step })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(err => console.error(err));
}

function dismissOnboarding() {
    fetch('{{ route("onboarding.dismiss") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const banner = document.getElementById('onboardingBanner');
            if (banner) banner.remove();
        }
    })
    .catch(err => console.error(err));
}
</script>
@endif
