<x-ui.modal name="delete-lawyer" title="Delete lawyer" subtitle="Confirm before removing this counsel profile" maxWidth="sm">
    <div class="text-center py-2">
        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-rose-100 text-rose-600">
            <x-ui.icon name="trash" class="h-5 w-5" />
        </div>
        <p class="text-sm text-(--ink) mb-1">
            Delete
            <strong x-text="$store.lawyerUi.deleteTarget?.name || 'this lawyer'"></strong>?
        </p>
        <p class="text-sm text-(--muted) mb-0">
            This removes the lawyer from booking and voice routing. If they already have appointments, the profile will be set inactive instead.
        </p>
    </div>

    <form id="delete-lawyer-form" method="POST" action="#">
        @csrf
        @method('DELETE')
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button
            type="button"
            class="ui-btn ui-btn-primary !bg-rose-600 hover:!bg-rose-700"
            @click="$store.lawyerUi.confirmDelete()"
        >
            Yes, delete
        </button>
    </x-slot:footer>
</x-ui.modal>
