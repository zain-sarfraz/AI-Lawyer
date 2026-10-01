<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Lawyer;
use App\Models\ScheduleBreak;
use App\Models\WorkingHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        return view('admin.schedule.index', [
            'lawyers' => Lawyer::query()->with('user')->active()->get(),
            'workingHours' => WorkingHour::query()->with('lawyer.user')->orderBy('day_of_week')->get(),
            'breaks' => ScheduleBreak::query()->with('lawyer.user')->orderBy('day_of_week')->get(),
            'holidays' => Holiday::query()->orderBy('date')->get(),
        ]);
    }

    public function storeWorkingHour(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required'],
            'end_time' => ['required'],
        ]);
        WorkingHour::query()->create($data + ['status' => 'active']);

        return back()->with('success', 'Working hours saved.');
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        Holiday::query()->updateOrCreate(['date' => $data['date']], $data + ['status' => 'active']);

        return back()->with('success', 'Holiday saved.');
    }
}
