<?php

namespace App\Http\Controllers\Api;

use App\Domain\Calls\CallService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallApiController extends Controller
{
    public function __construct(private readonly CallService $calls) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->calls->list($request->only('q'))]);
    }
}
