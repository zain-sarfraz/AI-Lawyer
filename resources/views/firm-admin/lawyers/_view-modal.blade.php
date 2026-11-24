<x-ui.modal name="firm-member-details" title="Lawyer profile" subtitle="Firm member details" maxWidth="lg">
    <div x-show="$store.firmMemberUi.details" x-cloak>
        <div class="firm-lawyer-identity mb-4">
            <span class="firm-lawyer-avatar is-lg" x-text="$store.firmMemberUi.details?.initials || ''"></span>
            <div class="min-w-0">
                <h3 class="firm-lawyer-name text-xl" x-text="$store.firmMemberUi.details?.name"></h3>
                <p class="firm-lawyer-meta mb-2" x-text="$store.firmMemberUi.details?.roleMeta || 'Lawyer'"></p>
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="$store.firmMemberUi.details?.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                    x-text="$store.firmMemberUi.details?.status || 'inactive'"
                ></span>
            </div>
        </div>

        <dl class="lawyer-detail-list">
            <div>
                <dt>Email</dt>
                <dd x-text="$store.firmMemberUi.details?.email || '—'"></dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd x-text="$store.firmMemberUi.details?.phone || '—'"></dd>
            </div>
            <div>
                <dt>Dial country</dt>
                <dd x-text="$store.firmMemberUi.details?.country || '—'"></dd>
            </div>
            <div>
                <dt>Jurisdictions</dt>
                <dd x-text="$store.firmMemberUi.details?.jurisdictions || '—'"></dd>
            </div>
            <div>
                <dt>Title</dt>
                <dd x-text="$store.firmMemberUi.details?.title || '—'"></dd>
            </div>
            <div>
                <dt>Role</dt>
                <dd x-text="$store.firmMemberUi.details?.role || 'Lawyer'"></dd>
            </div>
            <div>
                <dt>Date added</dt>
                <dd x-text="$store.firmMemberUi.details?.added_at || '—'"></dd>
            </div>
            <div>
                <dt>Assigned cases</dt>
                <dd x-text="($store.firmMemberUi.details?.cases_count ?? 0) + ' case(s)'"></dd>
            </div>
        </dl>
    </div>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
        <button
            type="button"
            class="ui-btn ui-btn-primary"
            @click="show = false; if ($store.firmMemberUi.details?.id) $store.firmMemberUi.openEdit($store.firmMemberUi.details.id)"
        >
            Edit
        </button>
    </x-slot:footer>
</x-ui.modal>
