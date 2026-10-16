<x-ui.modal name="lawyer-details" title="Lawyer details" subtitle="Counsel profile for booking and voice routing" maxWidth="md">
    <div x-show="$store.lawyerUi.details" x-cloak>
        <div class="lawyer-card-identity mb-4">
            <div class="lawyer-card-avatar is-lg" x-text="$store.lawyerUi.details?.initials || ''"></div>
            <div class="min-w-0">
                <h3 class="font-display text-xl m-0" x-text="$store.lawyerUi.details?.name"></h3>
                <p class="ui-muted mb-2" x-text="$store.lawyerUi.details?.title || 'Counsel'"></p>
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="$store.lawyerUi.details?.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                    x-text="$store.lawyerUi.details?.status || 'inactive'"
                ></span>
            </div>
        </div>

        <dl class="lawyer-detail-list">
            <div>
                <dt>Phone</dt>
                <dd x-text="$store.lawyerUi.details?.phone || '—'"></dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd x-text="$store.lawyerUi.details?.email || '—'"></dd>
            </div>
            <div>
                <dt>Experience</dt>
                <dd x-text="$store.lawyerUi.details?.experience || '—'"></dd>
            </div>
            <div>
                <dt>Timezone</dt>
                <dd x-text="$store.lawyerUi.details?.timezone || '—'"></dd>
            </div>
            <div>
                <dt>Services</dt>
                <dd x-text="($store.lawyerUi.details?.services || []).join(', ') || '—'"></dd>
            </div>
            <div>
                <dt>Bio / notes</dt>
                <dd style="white-space: pre-wrap" x-text="$store.lawyerUi.details?.bio || 'No bio added yet.'"></dd>
            </div>
        </dl>
    </div>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
        <button
            type="button"
            class="ui-btn ui-btn-primary"
            @click="show = false; if ($store.lawyerUi.details?.id) $store.lawyerUi.openEdit($store.lawyerUi.details.id)"
        >
            Edit
        </button>
    </x-slot:footer>
</x-ui.modal>
