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


// Public
Route::get('/', fn () => view('welcome'));
Route::get('/check-gd', function () {
    dd(extension_loaded('gd'), gd_info());
});

Route::get('/register', Register::class)->name('register');
Route::get('/login', Login::class)->name('login');
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

    // STUDENT
    Route::middleware('role:student,officer')->group(function () {
        Route::get('/student/new-transaction/{template?}', NewTransaction::class)
            ->name('student.new-transaction');
        Route::get('/student/workspaces/{workspace}/download-pdf', [StudentDocumentWorkspaceController::class, 'downloadPdf'])
            ->name('student.workspaces.download-pdf');


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

        Route::get('/admin/transactions/{workspace}', TransactionRecordShow::class)
            ->name('admin.transactions.show');

        Route::get('/admin/transactions/{workspace}/download-pdf', [StudentDocumentWorkspaceController::class, 'downloadPdf'])
            ->name('admin.transactions.download-pdf');

        Route::get('/admin/transactions/verification/{verification}/download', [AdminTransactionDocumentController::class, 'downloadVerification'])
            ->name('admin.transactions.download-verification');

        Route::get('/admin/clearance-monitoring', ClearanceMonitoring::class)
            ->name('admin.clearance-monitoring');

        Route::get('/admin/templates', TemplateManager::class)
            ->name('admin.templates');

        Route::get('/admin/templates/{template}/editor', function ($templateId) {
            return "Editor coming soon for Template ID: " . $templateId;
        })->name('admin.templates.editor');
        
        Route::get('/admin/templates/{template}/editor', TemplateEditor::class)
            ->name('admin.templates.editor');
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
