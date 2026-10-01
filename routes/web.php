<?php

use App\Http\Controllers\Admin\AiReceptionistController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\CallController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KnowledgeBaseController;
use App\Http\Controllers\Admin\LawyerController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('leads', LeadController::class)->except(['destroy']);
    Route::resource('clients', ClientController::class)->except(['destroy']);
    Route::resource('appointments', AppointmentController::class)->only(['index', 'create', 'store']);
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::get('appointments-availability', [AppointmentController::class, 'availability'])->name('appointments.availability');

    Route::resource('services', ServiceController::class)->except(['show', 'destroy']);
    Route::resource('lawyers', LawyerController::class)->except(['show', 'destroy']);
    Route::get('calls', [CallController::class, 'index'])->name('calls.index');
    Route::get('calls/{call}', [CallController::class, 'show'])->name('calls.show');

    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('schedule/working-hours', [ScheduleController::class, 'storeWorkingHour'])->name('schedule.working-hours.store');
    Route::post('schedule/holidays', [ScheduleController::class, 'storeHoliday'])->name('schedule.holidays.store');

    Route::get('knowledge', [KnowledgeBaseController::class, 'index'])->name('knowledge.index');
    Route::post('knowledge', [KnowledgeBaseController::class, 'store'])->name('knowledge.store');

    Route::get('ai-receptionist', [AiReceptionistController::class, 'show'])->name('ai.show');
    Route::put('ai-receptionist', [AiReceptionistController::class, 'update'])->name('ai.update');
    Route::post('ai-receptionist/finetuner', [AiReceptionistController::class, 'connectFineTuner'])->name('ai.finetuner.connect');
    Route::post('ai-receptionist/finetuner/assign', [AiReceptionistController::class, 'assignFineTuner'])->name('ai.finetuner.assign');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/integrations', [SettingsController::class, 'updateIntegration'])->name('settings.integrations.update');
    Route::get('audit-logs', [SettingsController::class, 'audit'])->name('audit.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
