<x-ui.modal name="case-details" title="Case details" subtitle="Matter info, client, and attachments" maxWidth="2xl">
    <div x-show="$store.caseUi.details" x-cloak class="space-y-4">
        <div class="rounded-xl border border-(--line) bg-(--canvas) px-4 py-3">
            <div class="text-xs font-semibold uppercase tracking-wider ui-muted">Case</div>
            <h3 class="font-display text-lg m-0" x-text="$store.caseUi.details?.title"></h3>
            <p class="mb-0 mt-1 text-sm text-(--muted)" x-text="$store.caseUi.details?.case_number || '—'"></p>
        </div>

        <dl class="lawyer-detail-list">
            <div>
                <dt>Client</dt>
                <dd x-text="$store.caseUi.details?.client || '—'"></dd>
            </div>
            <div>
                <dt>Lawyer</dt>
                <dd x-text="$store.caseUi.details?.lawyer || '—'"></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd class="capitalize" x-text="($store.caseUi.details?.status || '').replaceAll('_', ' ')"></dd>
            </div>
            <div>
                <dt>Opened</dt>
                <dd x-text="$store.caseUi.details?.opened_on || '—'"></dd>
            </div>
        </dl>

        <div>
            <div class="mb-2 text-sm font-semibold">Description / notes</div>
            <div class="rounded-xl border border-(--line) bg-(--canvas) p-3 text-sm" style="white-space: pre-wrap" x-text="$store.caseUi.details?.description || 'No notes yet.'"></div>
        </div>

        <div>
            <div class="mb-2 text-sm font-semibold">Attachments</div>
            <template x-if="!($store.caseUi.details?.files?.length)">
                <p class="mb-0 text-sm text-(--muted)">No files attached.</p>
            </template>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2" x-show="$store.caseUi.details?.files?.length">
                <template x-for="(file, i) in ($store.caseUi.details?.files || [])" :key="i">
                    <div class="overflow-hidden rounded-xl border border-(--line)">
                        <template x-if="file.is_image && file.preview_url">
                            <a :href="file.preview_url" target="_blank" rel="noopener">
                                <img :src="file.preview_url" :alt="file.name" class="h-36 w-full object-contain bg-slate-50">
                            </a>
                        </template>
                        <div class="flex items-center justify-between gap-2 p-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold" x-text="file.name"></div>
                                <div class="text-xs text-(--muted)" x-text="file.size"></div>
                            </div>
                            <a :href="file.download_url" class="ui-btn ui-btn-ghost btn-sm">Download</a>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
            <button
                type="button"
                class="ui-btn ui-btn-primary"
                @click="show = false; $store.caseUi.openNotes($store.caseUi.details)"
            >Notes</button>
        </div>
    </div>
</x-ui.modal>
