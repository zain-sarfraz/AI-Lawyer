<x-ui.modal name="create-case" title="New case" subtitle="Open a matter for case tracking" maxWidth="xl">
    <form method="POST" action="{{ route('cases.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="_modal" value="create-case">
        @include('crm.cases._fields', ['idPrefix' => 'create-case', 'case' => null])

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
            <button type="submit" class="ui-btn ui-btn-primary">Create case</button>
        </div>
    </form>
</x-ui.modal>
