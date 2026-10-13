<?php

use App\Http\Controllers\Admin\ExerciseController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Candidate\AnswerController;
use App\Http\Controllers\Candidate\DashboardController;
use App\Http\Controllers\Candidate\ExamController;
use App\Http\Controllers\Candidate\NotificationController;
use App\Http\Controllers\Candidate\PreparationController;
use App\Http\Controllers\Candidate\ResultController;
use App\Http\Controllers\Candidate\TrainingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

// ------------------------------------------------------------------ Invités
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    // Mot de passe oublié (broker Laravel, table password_reset_tokens).
    Route::get('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'requestForm'])
        ->middleware('throttle:5,1')
        ->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\PasswordResetController::class, 'resetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Santé publique (uptime/monitoring) : sans données sensibles, throttle anti-abus.
Route::get('/health', [App\Http\Controllers\HealthController::class, 'check'])
    ->middleware('throttle:30,1')
    ->name('health');

// -------------------------------------------------------------- Authentifié
// Plateforme 100 % QCM écrit, par niveau (A1 → C2) : aucune route audio.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Entraînement par niveau (QCM) : tirage sans remise (banques A1–B2),
    // génération IA inédite (C1–C2).
    Route::get('/preparation', [PreparationController::class, 'index'])->name('preparation.index');
    Route::post('/preparation/{level}/start', [PreparationController::class, 'startLevel'])
        ->whereIn('level', ['A1', 'B1', 'B2', 'C1', 'C2', 'a1', 'b1', 'b2', 'c1', 'c2', 'A2', 'a2'])->name('preparation.start');
    Route::post('/preparation/{level}/generate', [PreparationController::class, 'generateLevel'])
        ->whereIn('level', ['C1', 'C2', 'c1', 'c2'])->name('preparation.generate');

    // Moteur d'examen (QCM)
    Route::get('/exam/{attempt}', [ExamController::class, 'show'])->name('exam.show');
    Route::post('/exam/{attempt}/exercises/{attemptExercise}/complete', [ExamController::class, 'complete'])
        ->name('exam.complete');
    Route::post('/exam/{attempt}/exercises/{attemptExercise}/next', [ExamController::class, 'next'])
        ->name('exam.next');
    Route::get('/exam/{attempt}/timer', [ExamController::class, 'timer'])->name('exam.timer');
    Route::post('/exam/{attempt}/event', [ExamController::class, 'event'])
        ->middleware('throttle:60,1')
        ->name('exam.event');

    // Résultats
    Route::get('/results/{attempt}', [ResultController::class, 'show'])->name('results.show');
    Route::get('/results/{attempt}/report', [ResultController::class, 'report'])->name('results.report');
    Route::get('/results/{attempt}/pdf', [ResultController::class, 'pdf'])->name('results.pdf');

    // Espace Élite — Défi IA (2 scores parfaits consécutifs requis).
    Route::get('/challenges', [App\Http\Controllers\Candidate\ChallengeController::class, 'index'])->name('challenges.index');
    Route::post('/challenges', [App\Http\Controllers\Candidate\ChallengeController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('challenges.store');
    Route::get('/challenges/{challenge}/play', [App\Http\Controllers\Candidate\ChallengeController::class, 'play'])->name('challenges.play');
    Route::get('/challenges/{challenge}/status', [App\Http\Controllers\Candidate\ChallengeController::class, 'status'])->name('challenges.status');

    // Compte RGPD : export + suppression.
    Route::get('/account', [App\Http\Controllers\Candidate\AccountController::class, 'show'])->name('account.show');
    Route::get('/account/export', [App\Http\Controllers\Candidate\AccountController::class, 'export'])->name('account.export');
    Route::delete('/account', [App\Http\Controllers\Candidate\AccountController::class, 'destroy'])->name('account.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // Entraînement autonome par exercice (correction immédiate, sans chronomètre)
    Route::get('/training/exercises/{exercise}', [TrainingController::class, 'show'])->name('training.show');

    // -------------------------------------------------- API interne (session+CSRF)
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/exam/{attempt}/answers', [AnswerController::class, 'store'])
            ->middleware('throttle:180,1')
            ->name('answers.store');
    });

    // ---------------------------------------------------------- Administration
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::resource('exercises', ExerciseController::class)->except(['show']);

        Route::get('users', [UserController::class, 'index'])->name('users.index');

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
