<?php

namespace App\Http\Controllers\Admin;

use App\Domain\CRM\ClientService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ClientController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ClientService $clients) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:clients.view', only: ['index', 'show']),
            new Middleware('permission:clients.manage', only: ['create', 'store', 'edit', 'update', 'updateStatus', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        return view('crm.clients.index', [
            'clients' => $this->clients->list($request->only('q')),
            'filters' => $request->only('q'),
            'formClient' => new Client(['status' => 'active']),
            'formServices' => Service::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', Client::class);

        return redirect()->route('clients.index', ['open' => 'create-client']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_number' => ['nullable', 'string', 'max:64'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:services,id'],
        ]);

        $this->clients->create(
            collect($data)->except('service_ids')->all(),
            $data['service_ids'] ?? [],
        );

        return redirect()->route('clients.index')->with('success', 'Client created.');
    }

    public function show(Client $client): RedirectResponse
    {
        $this->authorize('view', $client);

        return redirect()->route('clients.index', ['details' => $client->id]);
    }

    public function edit(Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        return redirect()->route('clients.index', ['open' => 'edit-client-'.$client->id]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_number' => ['nullable', 'string', 'max:64'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:services,id'],
        ]);

        $this->clients->update(
            $client,
            collect($data)->except('service_ids')->all(),
            $data['service_ids'] ?? [],
        );

        return redirect()->route('clients.index')->with('success', 'Client updated.');
    }

    public function updateStatus(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $this->clients->updateStatus($client, $data['status']);

        return back()->with('success', 'Client marked '.$data['status'].'.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $message = $this->clients->delete($client);

        return redirect()->route('clients.index')->with('success', $message);
    }
}
