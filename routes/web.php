<?php

use App\Http\Controllers\Admin\AiReceptionistController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\CallController;
use App\Http\Controllers\Admin\CaseResearchController;
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
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SalesInquiryController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\FirmAdmin\BillingController as FirmBillingController;
use App\Http\Controllers\FirmAdmin\DashboardController as FirmDashboardController;
use App\Http\Controllers\FirmAdmin\LawyerController as FirmLawyerController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Superadmin\AppointmentController as SuperadminAppointmentController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\FirmController as SuperadminFirmController;
use App\Http\Controllers\Superadmin\LawyerController as SuperadminLawyerController;
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

Route::redirect('/register', '/checkout?plan=starter');
Route::redirect('/contact', '/#pricing');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show')->middleware('throttle:60,1');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:20,1');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/status', [CheckoutController::class, 'status'])->name('checkout.status');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');
Route::post('/sales-inquiries', [SalesInquiryController::class, 'store'])->name('sales-inquiries.store')->middleware('throttle:10,1');

Route::middleware('auth')->group(function () {
    Route::post('/onboarding/step', [OnboardingController::class, 'toggleStep'])->name('onboarding.step');
    Route::post('/onboarding/dismiss', [OnboardingController::class, 'dismiss'])->name('onboarding.dismiss');
});

Route::middleware('guest')->group(function () {
    Route::get('/invitations/{token}', [InvitationAcceptController::class, 'show'])->name('invitations.accept.show');
    Route::post('/invitations/{token}', [InvitationAcceptController::class, 'store'])->name('invitations.accept.store');
});

Route::middleware(['auth', 'verified', 'active', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', SuperadminDashboardController::class)->name('dashboard');
    Route::get('firms', [SuperadminFirmController::class, 'index'])->name('firms.index');
    Route::get('firms/create', [SuperadminFirmController::class, 'create'])->name('firms.create');
    Route::post('firms', [SuperadminFirmController::class, 'store'])->name('firms.store');
    Route::get('firms/{firm}', [SuperadminFirmController::class, 'show'])->name('firms.show');
    Route::put('firms/{firm}', [SuperadminFirmController::class, 'update'])->name('firms.update');
    Route::patch('firms/{firm}/status', [SuperadminFirmController::class, 'updateStatus'])->name('firms.status');
    Route::delete('firms/{firm}', [SuperadminFirmController::class, 'destroy'])->name('firms.destroy');

    Route::get('lawyers', [SuperadminLawyerController::class, 'index'])->name('lawyers.index');
    Route::post('lawyers', [SuperadminLawyerController::class, 'store'])->name('lawyers.store');
    Route::put('lawyers/{lawyer}', [SuperadminLawyerController::class, 'update'])->name('lawyers.update');
    Route::delete('lawyers/{lawyer}', [SuperadminLawyerController::class, 'destroy'])->name('lawyers.destroy');

    Route::get('appointments', [SuperadminAppointmentController::class, 'index'])->name('appointments.index');

    Route::get('settings', [SuperadminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SuperadminSettingsController::class, 'update'])->name('settings.update');

    Route::get('profile', [SuperadminProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [SuperadminProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [SuperadminProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'verified', 'active', 'firm', 'firm.admin'])->prefix('firm')->name('firm.')->group(function () {
    Route::get('/', FirmDashboardController::class)->name('dashboard');
    Route::get('/lawyers', [FirmLawyerController::class, 'index'])->name('lawyers.index');
    Route::post('/lawyers', [FirmLawyerController::class, 'store'])->name('lawyers.store');
    Route::get('/lawyers/{user}', [FirmLawyerController::class, 'show'])->name('lawyers.show');
    Route::put('/lawyers/{user}', [FirmLawyerController::class, 'update'])->name('lawyers.update');
    Route::patch('/lawyers/{user}/status', [FirmLawyerController::class, 'updateStatus'])->name('lawyers.status');
    Route::get('/billing', FirmBillingController::class)->name('billing');
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
    Route::get('lawyers/{lawyer}/relevant-cases', [LawyerController::class, 'relevantCases'])->name('lawyers.relevant-cases');
    Route::get('lawyers/{lawyer}/case-law/{caseLaw}', [LawyerController::class, 'caseLawShow'])->name('lawyers.case-law.show');
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

    Route::get('case-research', [CaseResearchController::class, 'index'])->name('case-research.index');
    Route::get('case-research/search', [CaseResearchController::class, 'search'])->name('case-research.search');
    Route::get('case-research/records/{caseLaw}', [CaseResearchController::class, 'show'])->name('case-research.show');
    Route::get('case-research/api/search', [CaseResearchController::class, 'apiSearch'])->name('case-research.api.search');
    Route::get('case-research/api/case/{caseId}', [CaseResearchController::class, 'apiCase'])
        ->whereNumber('caseId')->name('case-research.api.case');
    Route::get('case-research/api/courts', [CaseResearchController::class, 'apiCourts'])->name('case-research.api.courts');
    Route::get('case-research/api/coverage', [CaseResearchController::class, 'apiCoverage'])->name('case-research.api.coverage');
    Route::get('case-research/api/case-types', [CaseResearchController::class, 'apiCaseTypes'])->name('case-research.api.case-types');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/integrations', [SettingsController::class, 'updateIntegration'])->name('settings.integrations.update');
    Route::get('audit-logs', [SettingsController::class, 'audit'])->name('audit.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
