<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\Login;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\StudentDocumentWorkspaceController;
use App\Livewire\Admin\ManageUsers;
use App\Livewire\Student\NewTransaction;
use App\Livewire\Admin\Templates\TemplateManager;
use App\Livewire\Admin\Templates\TemplateEditor;
use App\Livewire\VerifyStudent;
use App\Livewire\Admin\Dashboard;


// Public
Route::get('/', fn () => view('welcome'));
Route::get('/check-gd', function () {
    dd(extension_loaded('gd'), gd_info());
});

Route::get('/register', Register::class)->name('register');
Route::get('/login', Login::class)->name('login');
Route::middleware(['auth'])->group(function () {

    // Verification page (always accessible if needed)
    Route::get('/verify', VerifyStudent::class)->name('verify.page');

    // Protected pages
    Route::middleware(['verified.student'])->group(function () {

        Route::get('/dashboard', fn () => view('dashboard'));

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
    Route::post('/upload-e-slip', [VerifyStudent::class, 'uploadESlip'])
    ->middleware('auth');

    // ADMIN
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', Dashboard::class)
            ->name('admin.dashboard');

        Route::get('/admin/users', ManageUsers::class)
            ->name('admin.users');

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
        Route::view('/clearance-tagging', 'officer.clearance_tagging')
            ->name('officer.clearance');
    });

    // LOGOUT
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

require __DIR__.'/auth.php';
