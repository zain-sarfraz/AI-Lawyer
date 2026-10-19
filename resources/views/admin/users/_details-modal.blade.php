<x-ui.modal name="user-details" title="User details" subtitle="Firm user account and access" maxWidth="md">
    <div x-show="$store.userUi.details" x-cloak>
        <div class="lawyer-card-identity mb-4">
            <div class="lawyer-card-avatar is-lg" x-text="$store.userUi.details?.initials || ''"></div>
            <div class="min-w-0">
                <h3 class="font-display text-xl m-0" x-text="$store.userUi.details?.name"></h3>
                <p class="ui-muted mb-2" x-text="$store.userUi.details?.title || 'Team member'"></p>
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="$store.userUi.details?.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                    x-text="$store.userUi.details?.status || 'inactive'"
                ></span>
            </div>
        </div>

        <dl class="lawyer-detail-list">
            <div>
                <dt>Role</dt>
                <dd x-text="$store.userUi.details?.role || '—'"></dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd x-text="$store.userUi.details?.phone || '—'"></dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd x-text="$store.userUi.details?.email || '—'"></dd>
            </div>
        </dl>
    </div>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
        <template x-if="$store.userUi.details?.statusUrl">
            <form method="POST" :action="$store.userUi.details.statusUrl" class="m-0">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" :value="$store.userUi.details.toggleStatus">
                <button type="submit" class="ui-btn ui-btn-ghost">
                    <span x-text="$store.userUi.details?.status === 'active' ? 'Deactivate' : 'Activate'"></span>
                </button>
            </form>
        </template>
        <button
            type="button"
            class="ui-btn ui-btn-primary"
            @click="show = false; if ($store.userUi.details?.id) $store.userUi.openEdit($store.userUi.details.id)"
        >
            Edit
        </button>
    </x-slot:footer>
</x-ui.modal>
