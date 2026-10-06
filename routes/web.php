<?php

use App\Http\Controllers\Admin\AcademicProgramController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\Admin\SurveyOversightController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Faculty\FacultyDashboardController;
use App\Http\Controllers\GraduateTracerController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserDashboardController;
use Illuminate\Support\Facades\Route;

// ---- Guest routes ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');

    // Lets someone stuck on the registration form (an existing-but-unverified
    // email, see AuthController@register) request a fresh verification link
    // without needing to be logged in.
    Route::post('/email/resend', [EmailVerificationController::class, 'resendGuest'])
        ->middleware('throttle:6,1')
        ->name('verification.resend-guest');

    // ---- Forgot password (modal on the login page) ----
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    // ---- Email verification ----
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify-status', [EmailVerificationController::class, 'status'])->name('verification.status');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // ---- Graduate / Alumni ("user") ----
    // 'verified' gates the entire self-registered graduate area behind a
    // confirmed email - faculty/admin accounts are provisioned by an
    // administrator (see RoleSeeder/UserManagementController), not
    // self-registered, so they're deliberately left out of this gate.
    Route::middleware(['role:user', 'verified'])->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::post('/gcash-number', [UserDashboardController::class, 'saveNumber'])->name('gcash-number.save');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/verify-identity', [ProfileController::class, 'verifyIdentity'])->middleware('throttle:6,1')->name('profile.verify');

        Route::get('/survey', [GraduateTracerController::class, 'dashboard'])->name('survey');
        Route::get('/survey/preview', [GraduateTracerController::class, 'preview'])->name('survey.preview');
        Route::post('/survey', [GraduateTracerController::class, 'store'])->name('survey.store');
    });

    // ---- Faculty (aggregate/read-only) ----
    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/dashboard', [FacultyDashboardController::class, 'index'])->name('dashboard');
    });

    // Shared faculty tools, retaining existing URLs and export/preview links.
    Route::middleware('role:admin,faculty')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
        Route::get('/templates', [SystemSettingsController::class, 'templates'])->name('templates');
        Route::get('/templates/surveys/{survey}/preview', [SystemSettingsController::class, 'previewSurvey'])->name('templates.survey-preview');
        Route::get('/templates/surveys/{survey}/export', [SystemSettingsController::class, 'exportSurvey'])->name('templates.survey-export');
        Route::get('/integrations', [SystemSettingsController::class, 'integrations'])->name('integrations');
        Route::post('/integrations/{userNumber}/done', [SystemSettingsController::class, 'markNumberDone'])->name('integrations.mark-done');
        Route::get('/map', [MapController::class, 'index'])->name('map');
        Route::get('/map/locations', [MapController::class, 'locations'])->name('map.locations');
    });

    // ---- Admin (system administration) ----
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', UserManagementController::class)->except(['show']);
        Route::post('/users/survey-notification-recipients', [UserManagementController::class, 'surveyNotificationRecipients'])->name('users.survey-notification-recipients');
        Route::post('/users/{user}/survey-notification', [UserManagementController::class, 'sendSurveyNotificationToOne'])->name('users.survey-notification-send-one');

        Route::get('/programs', [AcademicProgramController::class, 'index'])->name('programs.index');
        Route::post('/programs', [AcademicProgramController::class, 'store'])->name('programs.store');
        Route::put('/programs/{academicProgram}', [AcademicProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{academicProgram}', [AcademicProgramController::class, 'destroy'])->name('programs.destroy');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');

        Route::get('/school-years', [SchoolYearController::class, 'index'])->name('school-years.index');
        Route::post('/school-years', [SchoolYearController::class, 'store'])->name('school-years.store');
        Route::post('/school-years/{schoolYear}/set-current', [SchoolYearController::class, 'setCurrent'])->name('school-years.set-current');
        Route::delete('/school-years/{schoolYear}', [SchoolYearController::class, 'destroy'])->name('school-years.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

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
