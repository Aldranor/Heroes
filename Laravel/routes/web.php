<?php

use App\Http\Controllers\AvatarAssetController;
use App\Http\Controllers\CombatController;
use App\Http\Controllers\EchoHallController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MissionMapController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\SkillTreeController;
use App\Http\Controllers\StorySceneController;
use App\Http\Controllers\TrainingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : view('pages.auth.register-intro')
)->name('home');
Route::view('inscription', 'pages.auth.register-intro')
    ->middleware('guest')
    ->name('register.intro');
Route::get('avatar-assets/{path}', [AvatarAssetController::class, 'show'])
    ->where('path', '.*')
    ->name('avatar.assets.show');

Route::middleware('auth')->group(function () {
    Route::get('onboarding/avatar', [OnboardingController::class, 'create'])
        ->name('onboarding.avatar.create');
    Route::get('onboarding/avatar/hair-variants/{model}', [OnboardingController::class, 'hairVariants'])
        ->name('onboarding.avatar.hair-variants');
    Route::post('onboarding/avatar', [OnboardingController::class, 'store'])
        ->name('onboarding.avatar.store');

    Route::middleware('avatar.complete')->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::get('lessons', [LessonController::class, 'index'])->name('lessons.index');
        Route::get('lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
        Route::post('lessons/{lesson}/flashcards/{flashcard}', [LessonController::class, 'flashcard'])->name('lessons.flashcards.update');
        Route::post('lessons/{lesson}/quiz', [LessonController::class, 'quiz'])->name('lessons.quiz');
        Route::get('missions', [MissionMapController::class, 'index'])->name('missions.index');
        Route::get('skills', [SkillTreeController::class, 'index'])->name('skills.index');
        Route::post('skills/{skillNode}/unlock', [SkillTreeController::class, 'unlock'])->name('skills.unlock');
        Route::post('skills/respec', [SkillTreeController::class, 'respec'])->name('skills.respec');
        Route::get('combat/prepare/{adventureNode}', [CombatController::class, 'prepare'])->name('combat.prepare');
        Route::post('combat', [CombatController::class, 'store'])->name('combat.store');
        Route::get('combat/{battle}', [CombatController::class, 'show'])->name('combat.show');
        Route::post('combat/{battle}/actions', [CombatController::class, 'action'])->name('combat.action');
        Route::post('story-scenes/complete', [StorySceneController::class, 'store'])->name('story-scenes.complete');
        Route::get('echoes', [EchoHallController::class, 'index'])->name('echoes.index');
        Route::get('training', [TrainingController::class, 'create'])->name('training.create');
        Route::post('training', [TrainingController::class, 'store'])->name('training.store');
        Route::get('training/{trainingSession}', [TrainingController::class, 'show'])->name('training.show');
        Route::post('training/{trainingSession}/answers', [TrainingController::class, 'answer'])->name('training.answer');
        Route::get('training/{trainingSession}/result', [TrainingController::class, 'result'])->name('training.result');
    });
});

require __DIR__.'/settings.php';
