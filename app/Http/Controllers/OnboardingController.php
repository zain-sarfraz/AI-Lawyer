<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function toggleStep(Request $request): JsonResponse
    {
        $request->validate([
            'step' => ['required', 'string', 'max:64'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $step = (string) $request->input('step');

        $user->markStepCompleted($step);

        return response()->json([
            'success' => true,
            'completed' => $user->isStepCompleted($step),
            'onboarding' => $user->onboarding,
        ]);
    }

    public function dismiss(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->dismissOnboarding();

        return response()->json([
            'success' => true,
        ]);
    }
}
