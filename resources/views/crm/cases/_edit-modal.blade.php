<x-ui.modal name="edit-case-{{ $case->id }}" title="Edit case" subtitle="Update matter details, status, and attachments" maxWidth="xl">
    <form method="POST" action="{{ route('cases.update', $case) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="_modal" value="edit-case-{{ $case->id }}">
        @include('crm.cases._fields', ['idPrefix' => 'edit-case-'.$case->id, 'case' => $case])

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-primary">Save changes</button>
        </div>
    </form>
</x-ui.modal>
