<x-ui.modal name="delete-admin" title="Delete admin" subtitle="Remove this firm admin login" maxWidth="sm">
    <div
        class="text-center py-2"
        x-data="{ name: '' }"
        x-on:fill-delete-admin.window="
            name = $event.detail?.name || '';
            const form = document.getElementById('delete-admin-form');
            if (form && $event.detail?.action) form.action = $event.detail.action;
        "
    >
        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-rose-100 text-rose-600">
            <x-ui.icon name="trash" class="h-5 w-5" />
        </div>
        <p class="text-sm text-(--ink) mb-1">
            Delete admin <strong x-text="name || 'this admin'"></strong>?
        </p>
        <p class="text-sm text-(--muted) mb-0">
            They will lose access to their firm dashboard. Firm data stays intact.
        </p>
    </div>

    <form id="delete-admin-form" method="POST" action="#">
        @csrf
        @method('DELETE')
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="$dispatch('close-modal', 'delete-admin')">Cancel</button>
        <button
            type="button"
            class="ui-btn ui-btn-primary !bg-rose-600 hover:!bg-rose-700"
            @click="document.getElementById('delete-admin-form')?.submit()"
        >
            Yes, delete
        </button>
    </x-slot:footer>
</x-ui.modal>
