<?php

use App\Http\Controllers\Admin\AiReceptionistController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\CallController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CrmDocumentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KnowledgeBaseController;
use App\Http\Controllers\Admin\LawyerController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LegalCaseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Superadmin\AdminController as SuperadminAdminController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\FirmController as SuperadminFirmController;
use App\Http\Controllers\Superadmin\ProfileController as SuperadminProfileController;
use App\Http\Controllers\Superadmin\SettingsController as SuperadminSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return view('welcome');
    }

    $user = auth()->user();

    return redirect()->route($user->homeRoute());
})->name('home');

Route::middleware(['auth', 'verified', 'active', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', SuperadminDashboardController::class)->name('dashboard');
    Route::get('firms', [SuperadminFirmController::class, 'index'])->name('firms.index');
    Route::get('firms/create', [SuperadminFirmController::class, 'create'])->name('firms.create');
    Route::post('firms', [SuperadminFirmController::class, 'store'])->name('firms.store');
    Route::get('firms/{firm}', [SuperadminFirmController::class, 'show'])->name('firms.show');
    Route::put('firms/{firm}', [SuperadminFirmController::class, 'update'])->name('firms.update');
    Route::patch('firms/{firm}/status', [SuperadminFirmController::class, 'updateStatus'])->name('firms.status');
    Route::delete('firms/{firm}', [SuperadminFirmController::class, 'destroy'])->name('firms.destroy');

    Route::get('admins', [SuperadminAdminController::class, 'index'])->name('admins.index');
    Route::get('admins/create', [SuperadminAdminController::class, 'create'])->name('admins.create');
    Route::post('admins', [SuperadminAdminController::class, 'store'])->name('admins.store');
    Route::put('admins/{admin}', [SuperadminAdminController::class, 'update'])->name('admins.update');
    Route::delete('admins/{admin}', [SuperadminAdminController::class, 'destroy'])->name('admins.destroy');

    Route::get('settings', [SuperadminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SuperadminSettingsController::class, 'update'])->name('settings.update');

    Route::get('profile', [SuperadminProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [SuperadminProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [SuperadminProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'verified', 'active', 'firm'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('leads', LeadController::class)->except(['destroy']);
    Route::resource('clients', ClientController::class);
    Route::patch('clients/{client}/status', [ClientController::class, 'updateStatus'])->name('clients.status');
    Route::resource('cases', LegalCaseController::class)->parameters(['cases' => 'legal_case']);
    Route::get('crm/documents', [CrmDocumentController::class, 'index'])->name('crm.documents.index');
    Route::post('crm/documents', [CrmDocumentController::class, 'store'])->name('crm.documents.store');
    Route::get('crm/documents/{crm_document}/download', [CrmDocumentController::class, 'download'])->name('crm.documents.download');
    Route::get('crm/documents/{crm_document}/preview', [CrmDocumentController::class, 'preview'])->name('crm.documents.preview');
    Route::patch('crm/documents/{crm_document}', [CrmDocumentController::class, 'update'])->name('crm.documents.update');
    Route::delete('crm/documents/{crm_document}', [CrmDocumentController::class, 'destroy'])->name('crm.documents.destroy');
    Route::patch('cases/{legal_case}/notes', [LegalCaseController::class, 'updateNotes'])->name('cases.notes');
    Route::post('cases/{legal_case}/attachments', [LegalCaseController::class, 'storeAttachments'])->name('cases.attachments');
    Route::resource('appointments', AppointmentController::class)->only(['index', 'create', 'store', 'update', 'destroy']);
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::patch('appointments/{appointment}/notes', [AppointmentController::class, 'updateNotes'])->name('appointments.notes');
    Route::get('appointments-availability', [AppointmentController::class, 'availability'])->name('appointments.availability');
    Route::get('notifications/appointments/feed', [NotificationController::class, 'appointmentsFeed'])->name('notifications.appointments.feed');
    Route::post('notifications/appointments/mark-read', [NotificationController::class, 'markAppointmentsRead'])->name('notifications.appointments.mark-read');

    Route::resource('services', ServiceController::class)->except(['show']);
    Route::resource('lawyers', LawyerController::class);
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('calls', [CallController::class, 'index'])->name('calls.index');
    Route::get('calls/{call}', [CallController::class, 'show'])->name('calls.show');

    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('schedule/availability', [ScheduleController::class, 'availability'])->name('schedule.availability');
    Route::post('schedule/availability', [ScheduleController::class, 'syncAvailability'])->name('schedule.availability.sync');
    Route::post('schedule/working-hours', [ScheduleController::class, 'storeWorkingHour'])->name('schedule.working-hours.store');
    Route::post('schedule/holidays', [ScheduleController::class, 'storeHoliday'])->name('schedule.holidays.store');

    Route::get('knowledge', [KnowledgeBaseController::class, 'index'])->name('knowledge.index');
    Route::post('knowledge', [KnowledgeBaseController::class, 'store'])->name('knowledge.store');

    Route::get('ai-receptionist', [AiReceptionistController::class, 'show'])->name('ai.show');
    Route::put('ai-receptionist', [AiReceptionistController::class, 'update'])->name('ai.update');
    Route::post('ai-receptionist/finetuner', [AiReceptionistController::class, 'connectFineTuner'])->name('ai.finetuner.connect');
    Route::post('ai-receptionist/finetuner/create-agent', [AiReceptionistController::class, 'createFineTunerAgent'])->name('ai.finetuner.create-agent');
    Route::post('ai-receptionist/finetuner/assign', [AiReceptionistController::class, 'assignFineTuner'])->name('ai.finetuner.assign');
    Route::post('ai-receptionist/finetuner/sync', [AiReceptionistController::class, 'syncFineTuner'])->name('ai.finetuner.sync');
    Route::post('ai-receptionist/synthflow', [AiReceptionistController::class, 'connectFineTuner'])->name('ai.synthflow.connect');
    Route::post('ai-receptionist/synthflow/create-agent', [AiReceptionistController::class, 'createFineTunerAgent'])->name('ai.synthflow.create-agent');
    Route::post('ai-receptionist/synthflow/assign', [AiReceptionistController::class, 'assignFineTuner'])->name('ai.synthflow.assign');
    Route::post('ai-receptionist/synthflow/sync', [AiReceptionistController::class, 'syncFineTuner'])->name('ai.synthflow.sync');
    Route::post('ai-receptionist/telnyx/search', [AiReceptionistController::class, 'searchTelnyx'])->name('ai.telnyx.search');
    Route::post('ai-receptionist/telnyx/assign', [AiReceptionistController::class, 'assignTelnyx'])->name('ai.telnyx.assign');
    Route::post('ai-receptionist/synthflow/test-call', [AiReceptionistController::class, 'testCall'])->name('ai.synthflow.test-call');

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
