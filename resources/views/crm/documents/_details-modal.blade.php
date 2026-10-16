<x-ui.modal name="document-details" title="Document details" subtitle="File metadata, client link, status, and notes" maxWidth="xl">
    <div x-show="$store.documentUi.details" x-cloak class="space-y-4">
        <div class="flex items-start justify-between gap-3 p-3 rounded-xl border border-(--line) bg-(--canvas)">
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold uppercase tracking-wider ui-muted mb-1">Document</div>
                <h3 class="font-display text-lg text-(--ink) m-0 break-words" x-text="$store.documentUi.details?.title"></h3>
            </div>
            <span
                :class="$store.documentUi.details?.status === 'closed' ? 'bg-slate-200/80 text-slate-700 dark:bg-white/10 dark:text-slate-200' : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'"
                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold capitalize shrink-0"
                x-text="$store.documentUi.details?.status || 'open'"
            ></span>
        </div>

        <dl class="lawyer-detail-list">
            <div>
                <dt>Original File</dt>
                <dd class="font-mono text-xs break-all" x-text="$store.documentUi.details?.original_name || '—'"></dd>
            </div>
            <div>
                <dt>File Size</dt>
                <dd x-text="$store.documentUi.details?.size || '—'"></dd>
            </div>
            <div>
                <dt>Client / Lead</dt>
                <dd x-text="$store.documentUi.details?.linked || '—'"></dd>
            </div>
            <div>
                <dt>Case</dt>
                <dd x-text="$store.documentUi.details?.case || '—'"></dd>
            </div>
            <div>
                <dt>Uploaded By</dt>
                <dd x-text="$store.documentUi.details?.uploader || 'System'"></dd>
            </div>
            <div>
                <dt>Uploaded On</dt>
                <dd x-text="$store.documentUi.details?.created_at || '—'"></dd>
            </div>
        </dl>

        @can('documents.manage')
            <form method="POST" :action="$store.documentUi.details?.update_url" class="space-y-4 pt-3 border-t border-(--line)">
                @csrf
                @method('PATCH')

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="ui-label" for="detail-doc-title">Title</label>
                        <input id="detail-doc-title" name="title" class="ui-field" x-model="$store.documentUi.details.title" required>
                    </div>
                    <div class="col-md-4">
                        <label class="ui-label" for="detail-doc-status">Status</label>
                        <select id="detail-doc-status" name="status" class="ui-field" x-model="$store.documentUi.details.status">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="ui-label" for="detail-doc-notes">Notes</label>
                        <textarea id="detail-doc-notes" name="notes" class="ui-field" rows="3" placeholder="Add or edit notes for this document…" x-model="$store.documentUi.details.notes"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a :href="$store.documentUi.details?.download_url" class="ui-btn ui-btn-ghost inline-flex items-center gap-1.5">
                        <x-ui.icon name="download" class="h-4 w-4" />
                        Download file
                    </a>
                    <div class="flex items-center gap-2">
                        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
                        <button type="submit" class="ui-btn ui-btn-primary">Save changes</button>
                    </div>
                </div>
            </form>
        @else
            <div class="space-y-3 pt-3 border-t border-(--line)">
                <div>
                    <label class="ui-label">Notes</label>
                    <div class="p-3 rounded-xl border border-(--line) bg-(--canvas) text-sm" style="white-space: pre-wrap" x-text="$store.documentUi.details?.notes || 'No notes added for this document.'"></div>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <a :href="$store.documentUi.details?.download_url" class="ui-btn ui-btn-ghost inline-flex items-center gap-1.5">
                        <x-ui.icon name="download" class="h-4 w-4" />
                        Download file
                    </a>
                    <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
                </div>
            </div>
        @endcan
    </div>
</x-ui.modal>
