<?php

namespace App\Http\Controllers\Admin;

use App\Domain\CRM\ClientService;
use App\Http\Controllers\Controller;
use App\Models\Client;
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
            new Middleware('permission:clients.manage', only: ['create', 'store', 'edit', 'update']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        return view('crm.clients.index', [
            'clients' => $this->clients->list($request->only('q')),
            'filters' => $request->only('q'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('crm.clients.form', ['client' => new Client()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
        $client = $this->clients->create($data);

        return redirect()->route('clients.show', $client)->with('success', 'Client created.');
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        $client->load(['appointments.service', 'leads']);

        return view('crm.clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('crm.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
        $this->clients->update($client, $data);

        return redirect()->route('clients.show', $client)->with('success', 'Client updated.');
    }
}
