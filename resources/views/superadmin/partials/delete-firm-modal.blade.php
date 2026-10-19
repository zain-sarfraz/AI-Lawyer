<x-ui.modal name="delete-firm" title="Delete firm" subtitle="Permanently remove this workspace" maxWidth="sm">
    <div
        class="text-center py-2"
        x-data="{ name: '' }"
        x-on:fill-delete-firm.window="
            name = $event.detail?.name || '';
            const form = document.getElementById('delete-firm-form');
            if (form && $event.detail?.action) form.action = $event.detail.action;
        "
    >
        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-rose-100 text-rose-600">
            <x-ui.icon name="trash" class="h-5 w-5" />
        </div>
        <p class="text-sm text-(--ink) mb-1">
            Delete firm <strong x-text="name || 'this firm'"></strong>?
        </p>
        <p class="text-sm text-(--muted) mb-0">
            This removes the firm workspace, its users, and related CRM data. This cannot be undone.
        </p>
    </div>

    <form id="delete-firm-form" method="POST" action="#">
        @csrf
        @method('DELETE')
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="$dispatch('close-modal', 'delete-firm')">Cancel</button>
        <button
            type="button"
            class="ui-btn ui-btn-primary !bg-rose-600 hover:!bg-rose-700"
            @click="document.getElementById('delete-firm-form')?.submit()"
        >
            Yes, delete firm
        </button>
    </x-slot:footer>
</x-ui.modal>
