<x-ui.modal name="upload-document" title="Upload documents" subtitle="Attach single or multiple files against a client, lead, or case" maxWidth="lg">
    <form method="POST" action="{{ route('crm.documents.store') }}" enctype="multipart/form-data" class="space-y-4" x-data="{ selectedFiles: [] }">
        @csrf
        <input type="hidden" name="_modal" value="upload-document">

        <div class="row g-3">
            <div class="col-md-8">
                <label class="ui-label" for="doc-title">Title / Label</label>
                <input id="doc-title" name="title" class="ui-field" value="{{ old('title') }}" placeholder="e.g. Identity Documents, Service Contract" required>
                <p class="mt-1 mb-0 small ui-muted">For multiple files, each file's name will be appended to this title.</p>
            </div>
            <div class="col-md-4">
                <label class="ui-label" for="doc-status">Status</label>
                <select id="doc-status" name="status" class="ui-field">
                    <option value="open" @selected(old('status', 'open') === 'open')>Open</option>
                    <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                </select>
            </div>
            <div class="col-12">
                <label class="ui-label" for="doc-files">Files (Select one or multiple)</label>
                <input
                    id="doc-files"
                    type="file"
                    name="files[]"
                    class="ui-field"
                    multiple
                    required
                    @change="selectedFiles = Array.from($event.target.files).map(f => ({ name: f.name, size: (f.size / 1024).toFixed(1) + ' KB' }))"
                >
                @error('files')
                    <p class="mt-1 mb-0 small text-rose-600">{{ $message }}</p>
                @enderror
                @error('file')
                    <p class="mt-1 mb-0 small text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 mb-0 small ui-muted">Max 20 MB each. Select multiple files to upload together. PDF, Word, images, office files.</p>
                <template x-if="selectedFiles.length > 0">
                    <div class="mt-2 p-2.5 rounded-xl border border-(--line) bg-(--canvas) text-xs">
                        <div class="font-semibold mb-1 text-(--ink)" x-text="selectedFiles.length + ' file(s) selected for upload:'"></div>
                        <ul class="list-disc pl-4 space-y-0.5 ui-muted max-h-24 overflow-y-auto m-0">
                            <template x-for="(f, i) in selectedFiles" :key="i">
                                <li><span class="font-medium text-(--ink)" x-text="f.name"></span> (<span x-text="f.size"></span>)</li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
            <div class="col-md-4">
                <label class="ui-label" for="doc-client">Client</label>
                <select id="doc-client" name="client_id" class="ui-field">
                    <option value="">— Optional —</option>
                    @foreach($formClients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="ui-label" for="doc-lead">Lead</label>
                <select id="doc-lead" name="lead_id" class="ui-field">
                    <option value="">— Optional —</option>
                    @foreach($formLeads as $lead)
                        <option value="{{ $lead->id }}" @selected(old('lead_id') == $lead->id)>
                            {{ $lead->name ?: $lead->phone }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="ui-label" for="doc-case">Case</label>
                <select id="doc-case" name="legal_case_id" class="ui-field">
                    <option value="">— Optional —</option>
                    @foreach($formCases as $case)
                        <option value="{{ $case->id }}" @selected(old('legal_case_id') == $case->id)>
                            {{ $case->case_number ?: $case->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="ui-label" for="doc-notes">Notes</label>
                <textarea id="doc-notes" name="notes" class="ui-field" rows="3" placeholder="Add optional notes or descriptions for these documents…">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-primary">Upload</button>
        </div>
    </form>
</x-ui.modal>
