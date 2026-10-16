<x-ui.modal name="delete-case" title="Delete case" subtitle="This removes the case record from the firm desk" maxWidth="sm">
    <p class="mb-0">
        Delete
        <strong x-text="$store.caseUi.deleteTarget?.name || 'this case'"></strong>?
        Linked documents stay, but the case link is cleared.
    </p>
    <form id="delete-case-form" method="POST" action="#">
        @csrf
        @method('DELETE')
    </form>
    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="button" class="ui-btn ui-btn-danger" @click="$store.caseUi.confirmDelete()">Delete</button>
    </x-slot:footer>
</x-ui.modal>
