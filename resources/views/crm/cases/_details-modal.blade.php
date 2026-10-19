<x-ui.modal name="case-details" title="Case details" subtitle="Matter info, client, and attachments" maxWidth="2xl">
    <div x-show="$store.caseUi.details" x-cloak class="space-y-4">
        @if ($errors->has('images') || $errors->has('files'))
            <div class="ui-alert ui-alert-danger mb-0">
                {{ $errors->first('images') ?: $errors->first('files') }}
            </div>
        @endif

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
                <p class="mb-3 text-sm text-(--muted)">No files attached yet.</p>
            </template>
            <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2" x-show="$store.caseUi.details?.files?.length">
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

            @can('cases.manage')
                <form
                    method="POST"
                    :action="$store.caseUi.details?.upload_url"
                    enctype="multipart/form-data"
                    class="rounded-xl border border-(--line) bg-(--canvas) p-3 space-y-3"
                    x-data="{
                        images: [],
                        files: [],
                        imageError: '',
                        fileError: '',
                        onImages(e) {
                            this.imageError = '';
                            const list = Array.from(e.target.files || []);
                            if (list.length > 2) {
                                this.imageError = 'Maximum 2 images allowed.';
                                e.target.value = '';
                                this.images = [];
                                return;
                            }
                            this.images = list.map((f) => ({
                                name: f.name,
                                size: (f.size / 1024).toFixed(1) + ' KB',
                                url: URL.createObjectURL(f),
                            }));
                        },
                        onFiles(e) {
                            this.fileError = '';
                            const list = Array.from(e.target.files || []);
                            if (list.length > 3) {
                                this.fileError = 'Maximum 3 files allowed.';
                                e.target.value = '';
                                this.files = [];
                                return;
                            }
                            this.files = list.map((f) => ({
                                name: f.name,
                                size: (f.size / 1024).toFixed(1) + ' KB',
                            }));
                        },
                    }"
                >
                    @csrf
                    <input type="hidden" name="_modal" value="case-details">
                    <div class="text-sm font-semibold">Add attachments</div>
                    <p class="mb-0 text-xs text-(--muted)">Max 2 images and 3 files total for this case.</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="ui-label" for="case-details-images">Images</label>
                            <input
                                id="case-details-images"
                                type="file"
                                name="images[]"
                                class="ui-field"
                                accept="image/*"
                                multiple
                                @change="onImages($event)"
                            >
                            <p class="mt-1 mb-0 text-xs text-rose-600" x-show="imageError" x-text="imageError" x-cloak></p>
                            <div class="mt-2 flex flex-wrap gap-2" x-show="images.length" x-cloak>
                                <template x-for="(img, i) in images" :key="'di'+i">
                                    <div class="w-20 overflow-hidden rounded-lg border border-(--line) bg-white">
                                        <img :src="img.url" :alt="img.name" class="h-16 w-full object-cover">
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="ui-label" for="case-details-files">Files</label>
                            <input
                                id="case-details-files"
                                type="file"
                                name="files[]"
                                class="ui-field"
                                accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.csv,.zip,.rtf"
                                multiple
                                @change="onFiles($event)"
                            >
                            <p class="mt-1 mb-0 text-xs text-rose-600" x-show="fileError" x-text="fileError" x-cloak></p>
                            <ul class="mt-2 mb-0 list-disc space-y-0.5 pl-4 text-xs text-(--muted)" x-show="files.length" x-cloak>
                                <template x-for="(f, i) in files" :key="'df'+i">
                                    <li>
                                        <span class="font-medium text-(--ink)" x-text="f.name"></span>
                                        <span x-text="' (' + f.size + ')'"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="ui-btn ui-btn-primary">Upload attachments</button>
                    </div>
                </form>
            @endcan
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
            @can('cases.manage')
                <button
                    type="button"
                    class="ui-btn ui-btn-primary"
                    @click="show = false; $store.caseUi.openNotes($store.caseUi.details)"
                >Notes</button>
            @endcan
        </div>
    </div>
</x-ui.modal>
