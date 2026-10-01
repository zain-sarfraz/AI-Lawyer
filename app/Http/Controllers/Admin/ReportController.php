<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Lead;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports.view'),
        ];
    }

    public function index(): View
    {
        $today = now()->toDateString();

        return view('admin.reports.index', [
            'stats' => [
                'leads' => Lead::query()->whereDate('created_at', $today)->count(),
                'calls' => Call::query()->whereDate('started_at', $today)->count(),
                'appointments' => Appointment::query()->whereDate('start_at', $today)->count(),
                'conversions' => Lead::query()->where('status', 'converted')->whereDate('updated_at', $today)->count(),
            ],
        ]);
    }
}
