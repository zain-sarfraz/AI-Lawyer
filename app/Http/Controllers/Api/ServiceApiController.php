<?php

namespace App\Http\Controllers\Api;

use App\Domain\Scheduling\AvailabilityService;
use App\Http\Controllers\Controller;
use App\Models\Lawyer;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceApiController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::query()->active()->with('category')->get();

        return response()->json(['data' => $services]);
    }

    public function availability(Request $request, Service $service, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
        ]);
        $lawyer = isset($data['lawyer_id']) ? Lawyer::query()->find($data['lawyer_id']) : null;

        return response()->json([
            'data' => $availability->findSlots($service, Carbon::parse($data['date']), $lawyer),
        ]);
    }
}
