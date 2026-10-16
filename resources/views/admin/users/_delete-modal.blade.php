<x-ui.modal name="delete-user" title="Delete user" subtitle="Confirm before removing this user account" maxWidth="sm">
    <div class="text-center py-2">
        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-rose-100 text-rose-600">
            <x-ui.icon name="trash" class="h-5 w-5" />
        </div>
        <p class="text-sm text-(--ink) mb-1">
            Delete user
            <strong x-text="$store.userUi.deleteTarget?.name || 'this user'"></strong>?
        </p>
        <p class="text-sm text-(--muted) mb-0" x-show="$store.userUi.deleteTarget?.isLawyer">
            This will permanently remove their user account, lawyer profile, and calendar schedule.
        </p>
        <p class="text-sm text-(--muted) mb-0" x-show="!$store.userUi.deleteTarget?.isLawyer">
            This will permanently remove this staff account and revoke their access.
        </p>
    </div>

    <form id="delete-user-form" method="POST" action="#">
        @csrf
        @method('DELETE')
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button
            type="button"
            class="ui-btn ui-btn-primary !bg-rose-600 hover:!bg-rose-700"
            @click="$store.userUi.confirmDelete()"
        >
            Yes, delete
        </button>
    </x-slot:footer>
</x-ui.modal>
