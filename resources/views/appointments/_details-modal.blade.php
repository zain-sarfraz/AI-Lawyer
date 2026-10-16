<x-ui.modal name="appointment-details" title="Appointment details" subtitle="Booking info, call transcript, and voice recording" maxWidth="2xl">
    <div x-show="$store.appointmentUi.details" x-cloak class="appt-details">
        <div class="appt-details-hero">
            <div class="min-w-0">
                <h3 class="font-display text-xl m-0" x-text="$store.appointmentUi.details?.name"></h3>
                <p class="ui-muted mb-0" x-text="$store.appointmentUi.details?.when || '—'"></p>
            </div>
            <span class="appt-details-status" x-text="$store.appointmentUi.details?.status || '—'"></span>
        </div>

        <dl class="lawyer-detail-list mb-4">
            <div>
                <dt>Service</dt>
                <dd x-text="$store.appointmentUi.details?.service || '—'"></dd>
            </div>
            <div>
                <dt>Lawyer</dt>
                <dd x-text="$store.appointmentUi.details?.lawyer || '—'"></dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd x-text="$store.appointmentUi.details?.phone || '—'"></dd>
            </div>
            <div>
                <dt>Source</dt>
                <dd x-text="$store.appointmentUi.details?.source || '—'"></dd>
            </div>
        </dl>

        <div class="appt-details-section">
            <div class="appt-details-section-head">
                <h4 class="font-display text-base m-0">Notes</h4>
            </div>
            <template x-if="$store.appointmentUi.details?.can_edit_notes">
                <form method="POST" :action="$store.appointmentUi.details.notes_url" class="space-y-2">
                    @csrf
                    @method('PATCH')
                    <textarea
                        name="notes"
                        class="ui-field"
                        rows="4"
                        placeholder="Add notes for this appointment…"
                        x-model="$store.appointmentUi.details.notes"
                    ></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="ui-btn ui-btn-primary" style="padding:0.45rem 0.9rem">Save notes</button>
                    </div>
                </form>
            </template>
            <template x-if="!$store.appointmentUi.details?.can_edit_notes">
                <div class="legal-empty py-3" style="white-space: pre-wrap" x-text="$store.appointmentUi.details?.notes || 'No notes.'"></div>
            </template>
        </div>

        <div class="appt-details-section">
            <div class="appt-details-section-head">
                <h4 class="font-display text-base m-0">Voice recording</h4>
                <span class="small ui-muted" x-show="$store.appointmentUi.details?.call_duration" x-text="'Duration ' + ($store.appointmentUi.details?.call_duration || '')"></span>
            </div>
            <template x-if="$store.appointmentUi.details?.recording_url">
                <div class="appt-recording">
                    <audio
                        class="appt-recording-player"
                        controls
                        preload="none"
                        :src="$store.appointmentUi.details.recording_url"
                    ></audio>
                    <a
                        class="ui-btn ui-btn-ghost"
                        style="padding:0.4rem 0.75rem"
                        :href="$store.appointmentUi.details.recording_url"
                        target="_blank"
                        rel="noopener"
                    >Open recording</a>
                </div>
            </template>
            <template x-if="!$store.appointmentUi.details?.recording_url">
                <div class="legal-empty py-3">
                    <span x-text="$store.appointmentUi.details?.has_call ? 'No recording available for this call yet.' : 'No linked AI call for this appointment.'"></span>
                </div>
            </template>
        </div>

        <div class="appt-details-section">
            <div class="appt-details-section-head">
                <h4 class="font-display text-base m-0">Transcript</h4>
                <span class="small ui-muted" x-show="$store.appointmentUi.details?.call_status" x-text="$store.appointmentUi.details?.call_status"></span>
            </div>
            <template x-if="($store.appointmentUi.details?.summary || '').length">
                <p class="appt-details-summary" x-text="$store.appointmentUi.details.summary"></p>
            </template>
            <template x-if="($store.appointmentUi.details?.transcript_lines || []).length">
                <div class="appt-transcript">
                    <template x-for="(line, index) in ($store.appointmentUi.details?.transcript_lines || [])" :key="index">
                        <div class="appt-transcript-line" :class="line.role === 'ai' ? 'is-ai' : 'is-caller'">
                            <div class="appt-transcript-speaker" x-text="line.role === 'ai' ? 'AI Receptionist' : 'Caller'"></div>
                            <div class="appt-transcript-text" x-text="line.text"></div>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="!($store.appointmentUi.details?.transcript_lines || []).length">
                <div class="legal-empty py-3">
                    <span x-text="$store.appointmentUi.details?.has_call ? 'No transcript yet.' : 'Transcript appears when an AI call is linked to this booking.'"></span>
                </div>
            </template>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
        <template x-if="$store.appointmentUi.details?.can_edit">
            <button
                type="button"
                class="ui-btn ui-btn-primary"
                @click="show = false; $store.appointmentUi.openEdit($store.appointmentUi.details)"
            >Edit appointment</button>
        </template>
    </x-slot:footer>
</x-ui.modal>
