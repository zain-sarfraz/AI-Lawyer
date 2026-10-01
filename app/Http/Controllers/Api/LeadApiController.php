<?php

namespace App\Http\Controllers\Api;

use App\Domain\CRM\LeadService;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadApiController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->leads->list($request->only('q', 'status')),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'service_id' => ['nullable', 'exists:services,id'],
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'status' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json(['data' => $this->leads->create($data)], 201);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'service_id' => ['nullable', 'exists:services,id'],
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'status' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json(['data' => $this->leads->update($lead, $data)]);
    }
}
