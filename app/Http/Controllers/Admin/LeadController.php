<?php

namespace App\Http\Controllers\Admin;

use App\Domain\CRM\LeadService;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Lawyer;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LeadController extends Controller implements HasMiddleware
{
    public function __construct(private readonly LeadService $leads) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:leads.view', only: ['index', 'show']),
            new Middleware('permission:leads.manage', only: ['create', 'store', 'edit', 'update']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        return view('crm.leads.index', [
            'leads' => $this->leads->list($request->only('q', 'status')),
            'filters' => $request->only('q', 'status'),
            'formLead' => new Lead(),
            'formServices' => Service::query()->active()->orderBy('name')->get(),
            'formLawyers' => Lawyer::query()->active()->with('user')->get(),
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        return redirect()->route('leads.index', ['open' => 'create-lead']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'service_id' => ['nullable', 'exists:services,id'],
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'status' => ['required', 'string'],
            'source' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
        ]);
        $lead = $this->leads->create($data);

        return redirect()->route('leads.show', $lead)->with('success', 'Lead created.');
    }

    public function show(Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load([
            'service',
            'lawyer.user',
            'appointments' => fn ($q) => $q->latest('id'),
            'calls' => fn ($q) => $q->latest('started_at')->limit(10),
            'notesRelation' => fn ($q) => $q->latest()->limit(10),
        ]);

        return view('crm.leads.show', compact('lead'));
    }

    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        return view('crm.leads.form', [
            'lead' => $lead,
            'services' => Service::query()->active()->orderBy('name')->get(),
            'lawyers' => Lawyer::query()->active()->with('user')->get(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'service_id' => ['nullable', 'exists:services,id'],
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'status' => ['required', 'string'],
            'source' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
        ]);
        $this->leads->update($lead, $data);

        return redirect()->route('leads.show', $lead)->with('success', 'Lead updated.');
    }
}
