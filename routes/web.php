<?php

use App\Http\Controllers\Admin\AcademicProgramController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\Admin\SurveyOversightController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Faculty\FacultyDashboardController;
use App\Http\Controllers\GraduateTracerController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserDashboardController;
use Illuminate\Support\Facades\Route;

// ---- Guest routes ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    // ---- Graduate / Alumni ("user") ----
    Route::middleware('role:user')->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::post('/gcash-number', [UserDashboardController::class, 'saveNumber'])->name('gcash-number.save');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/verify-identity', [ProfileController::class, 'verifyIdentity'])->name('profile.verify');
        Route::post('/profile/consent/give', [ProfileController::class, 'giveConsent'])->name('profile.consent.give');
        Route::post('/profile/consent/withdraw', [ProfileController::class, 'withdrawConsent'])->name('profile.consent.withdraw');

        Route::get('/survey', [GraduateTracerController::class, 'dashboard'])->name('survey');
        Route::get('/survey/preview', [GraduateTracerController::class, 'preview'])->name('survey.preview');
        Route::post('/survey', [GraduateTracerController::class, 'store'])->name('survey.store');
    });

    // ---- Faculty (aggregate/read-only) ----
    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/dashboard', [FacultyDashboardController::class, 'index'])->name('dashboard');
    });

    // ---- Admin (system administration) ----
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', UserManagementController::class)->except(['show']);

        Route::get('/programs', [AcademicProgramController::class, 'index'])->name('programs.index');
        Route::post('/programs', [AcademicProgramController::class, 'store'])->name('programs.store');
        Route::put('/programs/{academicProgram}', [AcademicProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{academicProgram}', [AcademicProgramController::class, 'destroy'])->name('programs.destroy');

        Route::get('/school-years', [SchoolYearController::class, 'index'])->name('school-years.index');
        Route::post('/school-years', [SchoolYearController::class, 'store'])->name('school-years.store');
        Route::post('/school-years/{schoolYear}/set-current', [SchoolYearController::class, 'setCurrent'])->name('school-years.set-current');
        Route::delete('/school-years/{schoolYear}', [SchoolYearController::class, 'destroy'])->name('school-years.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
        Route::get('/templates', [SystemSettingsController::class, 'templates'])->name('templates');
        Route::get('/templates/surveys/{survey}/preview', [SystemSettingsController::class, 'previewSurvey'])->name('templates.survey-preview');
        Route::get('/templates/surveys/{survey}/export', [SystemSettingsController::class, 'exportSurvey'])->name('templates.survey-export');
        Route::get('/integrations', [SystemSettingsController::class, 'integrations'])->name('integrations');
        Route::post('/integrations/{userNumber}/done', [SystemSettingsController::class, 'markNumberDone'])->name('integrations.mark-done');
        Route::get('/backups', [SystemSettingsController::class, 'backups'])->name('backups');

        // Deliberately unlinked from the nav - the one explicitly-authorized,
        // audit-logged path to view raw survey content.
        Route::get('/surveys/{survey}/reason', [SurveyOversightController::class, 'reasonForm'])->name('surveys.reason');
        Route::post('/surveys/{survey}', [SurveyOversightController::class, 'show'])->name('surveys.show');
        Route::post('/surveys/{survey}/export-docx', [SurveyOversightController::class, 'exportDocx'])->name('surveys.export-docx');
    });
});

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});
