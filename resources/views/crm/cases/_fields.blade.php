@php
    $idPrefix = $idPrefix ?? 'case';
    $case = $case ?? null;
    $restrictedLawyer = auth()->user()
        && auth()->user()->hasRole('Lawyer')
        && ! auth()->user()->hasAnyRole(['Owner', 'Admin']);
    $ownLawyerId = $restrictedLawyer
        ? \App\Models\Lawyer::query()->withoutGlobalScopes()->where('user_id', auth()->id())->value('id')
        : null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label class="ui-label" for="{{ $idPrefix }}-title">Title</label>
        <input
            id="{{ $idPrefix }}-title"
            name="title"
            class="ui-field"
            value="{{ old('title', $case?->title) }}"
            required
        >
    </div>
    <div class="col-md-4">
        <label class="ui-label" for="{{ $idPrefix }}-case_number">Case number</label>
        <input
            id="{{ $idPrefix }}-case_number"
            name="case_number"
            class="ui-field"
            value="{{ old('case_number', $case?->case_number) }}"
            placeholder="Auto if blank"
        >
    </div>
    <div class="col-md-6">
        <label class="ui-label" for="{{ $idPrefix }}-client_id">Client</label>
        <select id="{{ $idPrefix }}-client_id" name="client_id" class="ui-field">
            <option value="">— Optional —</option>
            @foreach($formClients ?? [] as $client)
                <option value="{{ $client->id }}" @selected(old('client_id', $case?->client_id) == $client->id)>
                    {{ $client->name }}{{ $client->client_number ? ' ('.$client->client_number.')' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="ui-label" for="{{ $idPrefix }}-lawyer_id">Lawyer</label>
        @if($restrictedLawyer && $ownLawyerId)
            <input type="hidden" id="{{ $idPrefix }}-lawyer_id" name="lawyer_id" value="{{ old('lawyer_id', $case?->lawyer_id ?: $ownLawyerId) }}">
            <input class="ui-field" value="{{ auth()->user()->name }} (you)" disabled>
        @else
            <select id="{{ $idPrefix }}-lawyer_id" name="lawyer_id" class="ui-field">
                <option value="">— Optional —</option>
                @foreach($formLawyers ?? [] as $lawyer)
                    <option value="{{ $lawyer->id }}" @selected(old('lawyer_id', $case?->lawyer_id) == $lawyer->id)>
                        {{ $lawyer->displayName() }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="col-md-6">
        <label class="ui-label" for="{{ $idPrefix }}-status">Status</label>
        <select id="{{ $idPrefix }}-status" name="status" class="ui-field" required>
            @foreach(['open' => 'Open', 'in_progress' => 'In progress', 'on_hold' => 'On hold', 'closed' => 'Closed'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $case?->status ?: 'open') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="ui-label" for="{{ $idPrefix }}-opened_on">Opened on</label>
        <input
            id="{{ $idPrefix }}-opened_on"
            type="date"
            name="opened_on"
            class="ui-field"
            value="{{ old('opened_on', $case?->opened_on?->format('Y-m-d')) }}"
        >
    </div>
    <div class="col-12">
        <label class="ui-label" for="{{ $idPrefix }}-description">Description</label>
        <textarea
            id="{{ $idPrefix }}-description"
            name="description"
            class="ui-field"
            rows="3"
        >{{ old('description', $case?->description) }}</textarea>
    </div>
    @if(empty($hideAttachments))
        <div class="col-12">
            @include('crm.cases._attachments', ['idPrefix' => $idPrefix])
        </div>
    @endif
</div>
