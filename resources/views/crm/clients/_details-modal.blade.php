<x-ui.modal name="client-details" title="Client details" subtitle="Matter record, services, and appointments" maxWidth="lg">
    <div x-show="$store.clientUi.details" x-cloak>
        <div class="mb-4">
            <h3 class="font-display text-xl m-0" x-text="$store.clientUi.details?.name"></h3>
            <p class="ui-muted mb-2">
                <span x-text="$store.clientUi.details?.client_number || 'No client number'"></span>
                ·
                <span x-text="$store.clientUi.details?.status || 'inactive'"></span>
            </p>
        </div>

        <dl class="lawyer-detail-list mb-4">
            <div>
                <dt>Email</dt>
                <dd x-text="$store.clientUi.details?.email || '—'"></dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd x-text="$store.clientUi.details?.phone || '—'"></dd>
            </div>
            <div>
                <dt>Services</dt>
                <dd x-text="($store.clientUi.details?.services || []).join(', ') || '—'"></dd>
            </div>
            <div>
                <dt>Notes</dt>
                <dd style="white-space: pre-wrap" x-text="$store.clientUi.details?.notes || 'No notes yet.'"></dd>
            </div>
        </dl>

        <h4 class="font-display text-base mb-2">Appointments</h4>
        <template x-if="!($store.clientUi.details?.appointments || []).length">
            <div class="legal-empty py-3">No appointments on record.</div>
        </template>
        <div class="space-y-2" x-show="($store.clientUi.details?.appointments || []).length">
            <template x-for="appt in ($store.clientUi.details?.appointments || [])" :key="appt.id">
                <div class="sched-holiday-item !items-start">
                    <div class="min-w-0 flex-1">
                        <div class="fw-semibold" x-text="appt.when"></div>
                        <div class="small ui-muted" x-text="(appt.service || '—') + ' · ' + (appt.status || '')"></div>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <template x-if="appt.can_act">
                            <form method="POST" :action="appt.complete_url">
                                @csrf
                                <button type="submit" class="lawyer-action is-details" style="width:auto;padding:0.35rem 0.55rem">Complete</button>
                            </form>
                        </template>
                        <template x-if="appt.can_act">
                            <form method="POST" :action="appt.cancel_url">
                                @csrf
                                <button type="submit" class="lawyer-action is-edit" style="width:auto;padding:0.35rem 0.55rem">Cancel</button>
                            </form>
                        </template>
                        <button
                            type="button"
                            class="lawyer-action is-delete"
                            style="width:auto;padding:0.35rem 0.55rem"
                            @click="$store.appointmentUi.askDelete({ name: $store.clientUi.details?.name, url: appt.delete_url })"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
        @can('appointments.manage')
            <a href="{{ route('appointments.index', ['open' => 'create-appointment']) }}" class="ui-btn ui-btn-ghost">Book appointment</a>
        @endcan
        @can('clients.manage')
            <button
                type="button"
                class="ui-btn ui-btn-primary"
                @click="show = false; if ($store.clientUi.details?.id) $store.clientUi.openEdit($store.clientUi.details.id)"
            >
                Edit client
            </button>
        @endcan
    </x-slot:footer>
</x-ui.modal>
