<?php

use App\Http\Controllers\Admin\CorrectionController;
use App\Http\Controllers\Admin\ExerciseController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Candidate\AiController;
use App\Http\Controllers\Candidate\AnswerController;
use App\Http\Controllers\Candidate\DashboardController;
use App\Http\Controllers\Candidate\ExamController;
use App\Http\Controllers\Candidate\MediaController;
use App\Http\Controllers\Candidate\ModellTestController;
use App\Http\Controllers\Candidate\NotificationController;
use App\Http\Controllers\Candidate\PreparationController;
use App\Http\Controllers\Candidate\ResultController;
use App\Http\Controllers\Candidate\SpeakingController;
use App\Http\Controllers\Candidate\TrainingController;
use App\Http\Controllers\Candidate\WritingController;
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
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.store');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// -------------------------------------------------------------- Authentifié
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Modelltests
    Route::get('/modelltests', [ModellTestController::class, 'index'])->name('modelltests.index');
    Route::post('/modelltests/{modellTest}/start', [ModellTestController::class, 'start'])
        ->name('modelltests.start');

    // Espaces de préparation
    Route::get('/preparation', [PreparationController::class, 'index'])->name('preparation.index');
    Route::post('/preparation/{level}/start', [PreparationController::class, 'startLevel'])
        ->whereIn('level', ['B1', 'B2', 'C1', 'b1', 'b2', 'c1'])->name('preparation.start');
    Route::get('/preparation/{skill}', [PreparationController::class, 'show'])->name('preparation.show');

    // Moteur d'examen
    Route::get('/exam/{attempt}', [ExamController::class, 'show'])->name('exam.show');
    Route::post('/exam/{attempt}/exercises/{attemptExercise}/complete', [ExamController::class, 'complete'])
        ->name('exam.complete');
    Route::post('/exam/{attempt}/exercises/{attemptExercise}/next', [ExamController::class, 'next'])
        ->name('exam.next');
    Route::get('/exam/{attempt}/timer', [ExamController::class, 'timer'])->name('exam.timer');

    // Résultats
    Route::get('/results/{attempt}', [ResultController::class, 'show'])->name('results.show');
    Route::get('/results/{attempt}/report', [ResultController::class, 'report'])->name('results.report');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // Entraînement autonome (correction immédiate, sans chronomètre)
    Route::get('/training/hoeren', [TrainingController::class, 'hoeren'])->name('training.hoeren');
    Route::get('/training/exercises/{exercise}', [TrainingController::class, 'show'])->name('training.show');

    // Médias privés (accès contrôlé)
    Route::get('/media/{media}', [MediaController::class, 'stream'])->name('media.stream');
    Route::get('/media/{media}/download', [MediaController::class, 'download'])->name('media.download');

    // -------------------------------------------------- API interne (session+CSRF)
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/exam/{attempt}/answers', [AnswerController::class, 'store'])
            ->middleware('throttle:180,1')
            ->name('answers.store');

        Route::get('/exam/{attempt}/writing', [WritingController::class, 'show'])->name('writing.show');
        Route::post('/exam/{attempt}/writing', [WritingController::class, 'store'])
            ->middleware('throttle:180,1')
            ->name('writing.store');

        Route::post('/exam/{attempt}/speaking', [SpeakingController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('speaking.store');

        Route::post('/exam/{attempt}/ai/request', [AiController::class, 'request'])
            ->middleware('throttle:20,1')
            ->name('ai.request');
        Route::get('/exam/{attempt}/ai/status', [AiController::class, 'status'])
            ->name('ai.status');
    });

    // ---------------------------------------------------------- Administration
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::resource('modelltests', App\Http\Controllers\Admin\ModellTestController::class);
        Route::resource('exercises', ExerciseController::class)->except(['show']);

        Route::get('corrections', [CorrectionController::class, 'index'])->name('corrections.index');
        Route::get('corrections/{type}/{id}', [CorrectionController::class, 'show'])
            ->whereIn('type', ['writing', 'speaking'])->name('corrections.show');
        Route::post('corrections/{type}/{id}', [CorrectionController::class, 'store'])
            ->whereIn('type', ['writing', 'speaking'])->name('corrections.store');

        Route::get('users', [UserController::class, 'index'])->name('users.index');

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
