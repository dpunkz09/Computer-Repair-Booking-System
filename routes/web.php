<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TwoFactorController;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Home page (customer-facing only; staff go to dashboard)
Route::get('/', function () {
    if (Auth::check() && Auth::user()->role !== 'customer') {
        return redirect()->route('dashboard');
    }

    $categories = ServiceCategory::active()->orderBy('name')->get();

    return view('welcome', compact('categories'));
})->name('home');

Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Email verification (allowed before full app access when required)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:verification-resend')
        ->name('verification.send');

    Route::get('/two-factor/challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])
        ->middleware('throttle:two-factor')
        ->name('two-factor.verify');
});

// Protected Routes (Authenticated Users Only)
Route::middleware(['auth', 'verified.when.required', 'admin.two.factor'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/picture', [ProfileController::class, 'uploadPicture'])->name('profile.picture.store');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/picture', [ProfileController::class, 'destroyPicture'])->name('profile.picture.destroy');

    Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])
        ->middleware('throttle:two-factor')
        ->name('two-factor.confirm');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    Route::resource('tickets', TicketController::class);
    Route::post('/tickets/{ticket}/photos', [TicketController::class, 'storePhotos'])->name('tickets.photos.store');
    Route::get('/tickets/{ticket}/comments-feed', [TicketController::class, 'commentsFeed'])->name('tickets.comments.feed');
    Route::post('/tickets/{ticket}/comments', [TicketController::class, 'addComment'])
        ->middleware('throttle:comments')
        ->name('tickets.comments.store');
    Route::post('/tickets/{ticket}/cancel', [TicketController::class, 'cancel'])->name('tickets.cancel');
    Route::patch('/tickets/{ticket}/details', [TicketController::class, 'updateDetails'])->name('tickets.details.update');
    Route::patch('/tickets/{ticket}/eta', [TicketController::class, 'updateEta'])->name('tickets.eta.update');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{userNotification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

// Admin Routes
Route::middleware(['auth', 'verified.when.required', 'admin.two.factor', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/upgrade', [AdminController::class, 'upgradeTechnician'])->name('upgrade-technician');
    Route::post('/users/{user}/downgrade', [AdminController::class, 'downgradeTechnician'])->name('downgrade-technician');
    Route::post('/users/{user}/promote-admin', [AdminController::class, 'promoteAdmin'])->name('promote-admin');
    Route::post('/users/{user}/demote-admin', [AdminController::class, 'demoteAdmin'])->name('demote-admin');
    Route::get('/unassigned-tickets', [AdminController::class, 'unassignedTickets'])->name('unassigned-tickets');
    Route::post('/tickets/{ticket}/assign', [AdminController::class, 'assignTicket'])->name('assign-ticket');
    Route::post('/tickets/{ticket}/unassign', [AdminController::class, 'unassignTicket'])->name('unassign-ticket');

    Route::get('/categories', [ServiceCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [ServiceCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [ServiceCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [ServiceCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-mail', [AdminSettingsController::class, 'sendTestMail'])->name('settings.test-mail');
});
