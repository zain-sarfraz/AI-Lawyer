<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Calls\CallService;
use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallController extends Controller
{
    public function __construct(private readonly CallService $calls) {}

    public function index(Request $request): View
    {
        return view('calls.index', [
            'calls' => $this->calls->list($request->only('q')),
            'filters' => $request->only('q'),
        ]);
    }

    public function show(Call $call): View
    {
        $call->load(['lead', 'client', 'lawyer.user', 'aiSession.toolCalls']);

        return view('calls.show', compact('call'));
    }
}
