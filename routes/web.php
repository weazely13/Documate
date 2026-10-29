<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\Login;
use App\Livewire\Admin\ClearanceMonitoring;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AdminTransactionDocumentController;
use App\Http\Controllers\StudentDocumentWorkspaceController;
use App\Livewire\Admin\ManageUsers;
use App\Livewire\Admin\TransactionRecordShow;
use App\Livewire\Admin\TransactionRecords;
use App\Livewire\Officer\ClearanceTagging;
use App\Livewire\Student\ClearanceStatusPage;
use App\Livewire\Student\Dashboard as StudentDashboard;
use App\Livewire\Student\NewTransaction;
use App\Livewire\Admin\Templates\TemplateManager;
use App\Livewire\Admin\Templates\TemplateEditor;
use App\Livewire\VerifyStudent;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Profile\ProfilePage;
use App\Http\Controllers\StudentVerificationController;
use App\Livewire\Admin\VerificationManagement;
use App\Livewire\Admin\ClearanceProfile as AdminClearanceProfile;
use App\Livewire\Officer\ClearanceProfile as OfficerClearanceProfile;
use App\Livewire\Student\StudentHandbook;
use App\Livewire\Admin\SemesterManagement;
use App\Livewire\Admin\Reports;
use App\Http\Controllers\VerifyDocumentController;
use App\Models\User;




// Public
Route::get('/', fn () => view('welcome'));
Route::view('/faqs', 'faqs')->name('faqs');
if (app()->environment('local')) {
    Route::get('/check-gd', fn () => dd(extension_loaded('gd'), gd_info()));
}
Route::get('/verify-document', [VerifyDocumentController::class, 'lookup'])->name('document.lookup');
Route::get('/verify-document/{token}', [VerifyDocumentController::class, 'show'])
    ->middleware('throttle:30,1')->name('document.verify');
Route::post('/verify-document/{token}/file', [VerifyDocumentController::class, 'checkFile'])
    ->middleware('throttle:10,1')->name('document.verify.file');
Route::post('/verify-document', [VerifyDocumentController::class, 'check'])
    ->middleware('throttle:10,1')->name('document.lookup.find');
Route::get('/invite/{token}', \App\Livewire\Auth\AcceptInvite::class)
    ->middleware('throttle:20,1')->name('invite.accept');
Route::get('/sign/{token}', \App\Livewire\External\Sign::class)
    ->middleware('throttle:60,1')->name('external.sign');

Route::get('/register', Register::class)->name('register');
Route::get('/login', Login::class)->name('login');
Route::get('/reset-password/{token}', \App\Livewire\Auth\ResetPassword::class)
    ->middleware('guest')->name('password.reset');

Route::get('/account/recover/{user}', function (int $user) {
    User::onlyTrashed()->findOrFail($user)->restore();

    return redirect()->route('login')->with('status', 'Your account was restored. You can sign in now.');
})->middleware(['signed', 'throttle:6,1'])->name('account.recover');


Route::middleware(['auth'])->group(function () {
    Route::get('/profile', ProfilePage::class)->name('profile');

    // Verification page (always accessible if needed)
    Route::get('/verify', VerifyStudent::class)->name('verify.page');

    // Protected pages
    Route::middleware(['verified.student', 'role:student,officer'])->group(function () {
        Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
        Route::get('/student/dashboard', StudentDashboard::class)->name('student.dashboard');
        Route::get('/clearance-status', ClearanceStatusPage::class)->name('student.clearance-status');

        // other system pages
    });
});

// Protected
Route::middleware(['auth'])->group(function () {

    Route::get('/profile/signature', function () {
        $path = auth()->user()->signature_path;
        abort_unless($path && \Storage::disk('local')->exists($path), 404);

        return \Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store']);
    })->name('profile.signature');
    // Feedback & issue reports: users send them here, admins handle them in the inbox
    Route::get('/feedback', \App\Livewire\Support\FeedbackCenter::class)->name('support.feedback');
    Route::get('/feedback/{report}/attachment', function (\App\Models\FeedbackReport $report) {
        abort_unless(auth()->user()->isAdmin() || $report->user_id === auth()->id(), 403);
        abort_unless($report->attachment_path && \Storage::disk('local')->exists($report->attachment_path), 404);

        return \Storage::disk('local')->response($report->attachment_path, null, ['Cache-Control' => 'private, no-store']);
    })->name('feedback.attachment');

    Route::get('/workflow/{workspace}/item-photo', [\App\Http\Controllers\ItemPhotoController::class, 'show'])
        ->name('workflow.item-photo');
    Route::get('/signing', \App\Livewire\Signatory\Inbox::class)->name('signatory.inbox');
    Route::get('/signing/issue', \App\Livewire\Admin\Workflow\Issue::class)->name('signatory.issue');
    Route::get('/signing/availability', \App\Livewire\Signatory\Availability::class)->name('signatory.availability');
    Route::get('/signing/oversight', \App\Livewire\Signatory\Oversight::class)->name('signatory.oversight');
    Route::get('/signing/oversight/{workspace}', \App\Livewire\Signatory\OversightShow::class)->name('signatory.oversight.show');
    Route::get('/signing/oversight/{workspace}/preview', [\App\Http\Controllers\WorkflowPreviewController::class, 'oversight'])
        ->name('signatory.oversight.preview');
    Route::get('/signing/oversight/{workspace}/report', [\App\Http\Controllers\WorkflowReportController::class, 'download'])
        ->name('signatory.oversight.report');
    Route::get('/signing/issued', \App\Livewire\Signatory\Issued::class)->name('signatory.issued');
    Route::get('/signing/issued/{workspace}', \App\Livewire\Signatory\IssuedShow::class)->name('signatory.issued.show');
    Route::get('/signing/issued/{workspace}/preview', [\App\Http\Controllers\IssuedDocumentController::class, 'preview'])
        ->name('signatory.issued.preview');
    Route::get('/signing/issued/{workspace}/download', [\App\Http\Controllers\IssuedDocumentController::class, 'download'])
        ->name('signatory.issued.download');
    Route::get('/signing/{approval}', \App\Livewire\Signatory\Show::class)->name('signatory.show');
    Route::get('/signing/{approval}/preview', [\App\Http\Controllers\WorkflowPreviewController::class, 'signatory'])
        ->name('signatory.preview');


    // STUDENT
    Route::middleware('role:student,officer')->group(function () {
        Route::get('/student/new-transaction/{template?}', NewTransaction::class)
            ->name('student.new-transaction');
        Route::get('/student/workspaces/{workspace}/download-pdf', [StudentDocumentWorkspaceController::class, 'downloadPdf'])
            ->name('student.workspaces.download-pdf');
        Route::get('/student/documents', \App\Livewire\Student\Documents\DocumentsIndex::class)
            ->name('student.documents.index');
        Route::get('/student/documents/{workspace}', \App\Livewire\Student\Documents\DocumentShow::class)
            ->name('student.documents.show');
        Route::get('/student/new-transaction/{template}/edit/{workspace}', NewTransaction::class)
            ->name('student.new-transaction.edit');
        Route::get('/student/appointments/new/{workspace?}', \App\Livewire\Student\Appointments\NewAppointment::class)
            ->name('student.appointments.new');
        Route::get('/student/appointments', \App\Livewire\Student\Appointments\AppointmentsIndex::class)
            ->name('student.appointments.index');
        Route::get('/student/appointments/{appointment}', \App\Livewire\Student\Appointments\AppointmentShow::class)
            ->name('student.appointments.show');
        Route::get('/student/appointments/{appointment}/reapply', \App\Livewire\Student\Appointments\ReapplyAppointment::class)
            ->name('student.appointments.reapply');
        Route::get('/clearance-tagging/{studentId}', OfficerClearanceProfile::class)
            ->name('officer.clearance.show');
        Route::get('/handbook', StudentHandbook::class)->name('handbook');
        Route::redirect('/student/transactions', '/student/new-transaction')->name('student.transactions.index');
        Route::get('/student/transactions/new/{type}', \App\Livewire\Student\Transactions\Form::class)
            ->name('student.transactions.new');
        Route::get('/student/transactions/{workspace}', \App\Livewire\Student\Transactions\Form::class)
            ->name('student.transactions.show');
      
            


    });
    Route::post('/upload-e-slip', [StudentVerificationController::class, 'uploadESlip'])
    ->middleware('auth');

    // ADMIN
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', Dashboard::class)
            ->name('admin.dashboard');

        Route::get('/admin/users', ManageUsers::class)
            ->name('admin.users');

        Route::get('/admin/transactions', TransactionRecords::class)
            ->name('admin.transactions.index');

        Route::get('/admin/transactions/online/{workspace}', \App\Livewire\Admin\OnlineTransactionRecordShow::class)
            ->name('admin.transactions.online')
            ->withTrashed();

        Route::get('/admin/transactions/{workspace}', TransactionRecordShow::class)
            ->name('admin.transactions.show')
            ->withTrashed();

        Route::get('/admin/transactions/{workspace}/download-pdf', [StudentDocumentWorkspaceController::class, 'downloadPdf'])
            ->name('admin.transactions.download-pdf')
            ->withTrashed();

        Route::get('/admin/transactions/verification/{verification}/download', [AdminTransactionDocumentController::class, 'downloadVerification'])
            ->name('admin.transactions.download-verification');

        Route::get('/admin/clearance-monitoring', ClearanceMonitoring::class)
            ->name('admin.clearance-monitoring');

        Route::get('/admin/templates', TemplateManager::class)
            ->name('admin.templates');

        Route::get('/admin/templates/{template}/editor', function ($templateId) {
            return "     for Template ID: " . $templateId;
        })->name('admin.templates.editor');
        
        Route::get('/admin/templates/{template}/editor', TemplateEditor::class)
            ->name('admin.templates.editor');

        Route::get('/admin/appointments', \App\Livewire\Admin\Appointments\AppointmentsBoard::class)
            ->name('admin.appointments.index');

        Route::get('/admin/appointments/availability', \App\Livewire\Admin\Appointments\AvailabilityCalendar::class)
            ->name('admin.appointments.availability');
            
        Route::get('/admin/appointments/{appointment}', \App\Livewire\Admin\Appointments\AppointmentShow::class)
            ->name('admin.appointments.show');

        Route::get('/admin/document-uploads', \App\Livewire\Admin\DocumentUploads\DocumentUploadsIndex::class)
            ->name('admin.document-uploads.index');

        Route::get('/admin/document-uploads/{workspace}', \App\Livewire\Admin\DocumentUploads\DocumentUploadShow::class)
            ->name('admin.document-uploads.show');

        Route::get('/admin/verification', VerificationManagement::class)
            ->name('admin.verification');
        
        Route::get('/admin/reports', Reports::class)
            ->name('admin.reports');

        Route::get('/admin/reports/sheet', [\App\Http\Controllers\ReportSheetController::class, 'show'])
            ->name('admin.reports.sheet');

        Route::get('/admin/clearance-monitoring/{studentId}', AdminClearanceProfile::class)
            ->name('admin.clearance-monitoring.show');

        Route::get('/admin/semesters', SemesterManagement::class)
            ->name('admin.semesters');

        Route::get('/admin/feedback', \App\Livewire\Admin\FeedbackInbox::class)
            ->name('admin.feedback');

        Route::get('/admin/audit-trail', \App\Livewire\Admin\AuditTrail::class)
            ->name('admin.audit-trail');

        Route::get('/admin/workflow', \App\Livewire\Admin\Workflow\Index::class)->name('admin.workflow.index');
        Route::get('/admin/workflow/issue', \App\Livewire\Admin\Workflow\Issue::class)->name('admin.workflow.issue');
        Route::get('/admin/workflow', \App\Livewire\Admin\Workflow\Index::class)->name('admin.workflow.index');
        Route::get('/admin/workflow/{workspace}', \App\Livewire\Admin\Workflow\Show::class)->name('admin.workflow.show');
        Route::get('/admin/workflow/{workspace}/report', [\App\Http\Controllers\WorkflowReportController::class, 'download'])
            ->name('admin.workflow.report');
        Route::get('/admin/workflow/{workspace}/preview', [\App\Http\Controllers\WorkflowPreviewController::class, 'admin'])
            ->name('admin.workflow.preview');
    });

    // OFFICER
    Route::middleware('role:officer')->group(function () {
        Route::get('/clearance-tagging', ClearanceTagging::class)
            ->name('officer.clearance');
    });

    // LOGOUT
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

require __DIR__.'/auth.php';
