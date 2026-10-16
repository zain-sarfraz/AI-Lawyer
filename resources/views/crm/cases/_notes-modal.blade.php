<x-ui.modal name="case-notes" title="Case notes" subtitle="Add or update notes for this matter" maxWidth="lg">
    <form method="POST" :action="$store.caseUi.notes?.update_url" class="space-y-4" x-show="$store.caseUi.notes">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_modal" value="case-notes">

        <div>
            <div class="mb-1 text-sm font-semibold" x-text="$store.caseUi.notes?.title"></div>
            <div class="text-xs text-(--muted)" x-text="$store.caseUi.notes?.case_number"></div>
        </div>

        <div>
            <label class="ui-label" for="case-notes-body">Notes</label>
            <textarea
                id="case-notes-body"
                name="description"
                class="ui-field"
                rows="6"
                placeholder="Write case notes…"
                x-model="$store.caseUi.notes.description"
            ></textarea>
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-primary">Save notes</button>
        </div>
    </form>
</x-ui.modal>
