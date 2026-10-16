<x-ui.modal name="delete-client" title="Delete client" subtitle="Confirm before removing this client record" maxWidth="sm">
    <div class="text-center py-2">
        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-rose-100 text-rose-600">
            <x-ui.icon name="trash" class="h-5 w-5" />
        </div>
        <p class="text-sm text-(--ink) mb-1">
            Delete
            <strong x-text="$store.clientUi.deleteTarget?.name || 'this client'"></strong>?
        </p>
        <p class="text-sm text-(--muted) mb-0">
            If the client has appointments, the profile will be set inactive instead of deleted.
        </p>
    </div>

    <form id="delete-client-form" method="POST" action="#">
        @csrf
        @method('DELETE')
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button
            type="button"
            class="ui-btn ui-btn-primary !bg-rose-600 hover:!bg-rose-700"
            @click="$store.clientUi.confirmDelete()"
        >
            Yes, delete
        </button>
    </x-slot:footer>
</x-ui.modal>
