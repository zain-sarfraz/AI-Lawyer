<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Calls\CallService;
use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CallController extends Controller implements HasMiddleware
{
    public function __construct(private readonly CallService $calls) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:calls.view'),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Call::class);

        return view('calls.index', [
            'calls' => $this->calls->list($request->only('q')),
            'filters' => $request->only('q'),
        ]);
    }

    public function show(Call $call): View
    {
        $this->authorize('view', $call);

        $call->load(['lead', 'client', 'lawyer.user', 'aiSession.toolCalls']);

        return view('calls.show', compact('call'));
    }
}
