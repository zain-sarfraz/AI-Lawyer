@php
    $idPrefix = $idPrefix ?? 'case';
@endphp

<div
    class="mt-3 rounded-xl border border-(--line) bg-(--canvas) p-3"
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
    <div class="mb-2 text-sm font-semibold text-(--ink)">Attachments</div>
    <p class="mb-3 text-xs text-(--muted)">Max 2 images and 3 files. Images show a preview below.</p>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="ui-label" for="{{ $idPrefix }}-images">Images (max 2)</label>
            <input
                id="{{ $idPrefix }}-images"
                type="file"
                name="images[]"
                class="ui-field"
                accept="image/*"
                multiple
                @change="onImages($event)"
            >
            <p class="mt-1 mb-0 text-xs text-rose-600" x-show="imageError" x-text="imageError" x-cloak></p>
            <div class="mt-2 flex flex-wrap gap-2" x-show="images.length" x-cloak>
                <template x-for="(img, i) in images" :key="i">
                    <div class="w-20 overflow-hidden rounded-lg border border-(--line) bg-white">
                        <img :src="img.url" :alt="img.name" class="h-16 w-full object-cover">
                        <div class="truncate px-1 py-0.5 text-[10px] text-(--muted)" x-text="img.name"></div>
                    </div>
                </template>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <label class="ui-label" for="{{ $idPrefix }}-files">Files (max 3)</label>
            <input
                id="{{ $idPrefix }}-files"
                type="file"
                name="files[]"
                class="ui-field"
                accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.csv,.zip,.rtf"
                multiple
                @change="onFiles($event)"
            >
            <p class="mt-1 mb-0 text-xs text-rose-600" x-show="fileError" x-text="fileError" x-cloak></p>
            <ul class="mt-2 mb-0 list-disc space-y-0.5 pl-4 text-xs text-(--muted)" x-show="files.length" x-cloak>
                <template x-for="(f, i) in files" :key="i">
                    <li>
                        <span class="font-medium text-(--ink)" x-text="f.name"></span>
                        (<span x-text="' (' + f.size + ')'"></span>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
