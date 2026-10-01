<?php

namespace App\Http\Controllers\Admin;

use App\Domain\CRM\LeadService;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Lawyer;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    public function index(Request $request): View
    {
        return view('crm.leads.index', [
            'leads' => $this->leads->list($request->only('q', 'status')),
            'filters' => $request->only('q', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('crm.leads.form', [
            'lead' => new Lead(),
            'services' => Service::query()->active()->orderBy('name')->get(),
            'lawyers' => Lawyer::query()->active()->with('user')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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
        $lead->load([
            'service',
            'lawyer.user',
            'appointments' => fn ($q) => $q->latest('start_at'),
            'calls' => fn ($q) => $q->latest('started_at')->limit(10),
            'notesRelation' => fn ($q) => $q->latest()->limit(10),
        ]);

        return view('crm.leads.show', compact('lead'));
    }

    public function edit(Lead $lead): View
    {
        return view('crm.leads.form', [
            'lead' => $lead,
            'services' => Service::query()->active()->orderBy('name')->get(),
            'lawyers' => Lawyer::query()->active()->with('user')->get(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
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
