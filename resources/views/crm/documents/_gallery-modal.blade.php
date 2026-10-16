<x-ui.modal name="document-gallery" title="Client documents" subtitle="All files for this client in one place" maxWidth="2xl">
    <div x-show="$store.documentUi.gallery" x-cloak class="space-y-4">
        <div class="rounded-xl border border-(--line) bg-(--canvas) px-4 py-3">
            <div class="text-xs font-semibold uppercase tracking-wider ui-muted">Client</div>
            <h3 class="font-display text-lg m-0" x-text="$store.documentUi.gallery?.client || '—'"></h3>
            <p class="mb-0 mt-1 text-sm text-(--muted)" x-text="($store.documentUi.gallery?.files?.length || 0) + ' file(s)'"></p>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <template x-for="(file, i) in ($store.documentUi.gallery?.files || [])" :key="i">
                <div class="overflow-hidden rounded-xl border border-(--line) bg-white">
                    <template x-if="file.is_image && file.preview_url">
                        <a :href="file.preview_url" target="_blank" rel="noopener" class="block bg-slate-50">
                            <img :src="file.preview_url" :alt="file.name" class="h-40 w-full object-contain">
                        </a>
                    </template>
                    <template x-if="!file.is_image">
                        <div class="flex h-28 items-center justify-center bg-slate-50 px-3 text-center text-sm text-(--muted)">
                            <span x-text="file.name"></span>
                        </div>
                    </template>
                    <div class="space-y-2 p-3">
                        <div class="truncate text-sm font-semibold" x-text="file.title || file.name"></div>
                        <div class="text-xs text-(--muted)" x-text="file.size"></div>
                        <a :href="file.download_url" class="ui-btn ui-btn-ghost btn-sm inline-flex items-center gap-1">
                            <x-ui.icon name="download" class="h-4 w-4" />
                            Download
                        </a>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex justify-end">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Close</button>
        </div>
    </div>
</x-ui.modal>
