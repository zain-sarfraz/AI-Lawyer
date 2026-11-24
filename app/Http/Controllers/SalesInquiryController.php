<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SalesInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalesInquiryController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'firm_name' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'plan' => ['nullable', 'string', 'max:32'],
        ]);

        $inquiry = SalesInquiry::create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'firm_name' => $validated['firm_name'] ?? null,
            'message' => $validated['message'],
            'plan' => $validated['plan'] ?? 'pilot',
            'status' => 'new',
        ]);

        $message = 'Thank you for contacting sales. Our team will reach out to you within 24 hours.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'id' => $inquiry->id,
            ]);
        }

        return back()->with('success', $message);
    }
}
