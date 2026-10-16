<x-ui.modal name="delete-document" title="Delete document" subtitle="This permanently removes the file from storage" maxWidth="sm">
    <p class="mb-0">
        Delete
        <strong x-text="$store.documentUi.deleteTarget?.name || 'this document'"></strong>?
    </p>
    <form id="delete-document-form" method="POST" action="#">
        @csrf
        @method('DELETE')
    </form>
    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="button" class="ui-btn ui-btn-danger" @click="$store.documentUi.confirmDelete()">Delete</button>
    </x-slot:footer>
</x-ui.modal>
