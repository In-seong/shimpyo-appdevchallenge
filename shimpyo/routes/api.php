<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\SessionController;
use Illuminate\Support\Facades\Route;

// ── 차량 앱 (쉼표)
Route::post('/session/start', [SessionController::class, 'start']);
Route::post('/session/{session}/select', [SessionController::class, 'select']);
Route::get('/session/{session}/playlist', [SessionController::class, 'playlist']);
Route::get('/session/{session}/quiz', [SessionController::class, 'quiz']);
Route::post('/session/{session}/feedback', [SessionController::class, 'feedback']);
Route::post('/control', [SessionController::class, 'control']);

// ── 관리자
Route::prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats']);

    Route::get('/rules', [AdminController::class, 'rules']);
    Route::post('/rules', [AdminController::class, 'storeRule']);
    Route::put('/rules/{rule}', [AdminController::class, 'updateRule']);
    Route::delete('/rules/{rule}', [AdminController::class, 'destroyRule']);

    Route::get('/sessions', [AdminController::class, 'sessions']);
    Route::get('/sessions/{session}', [AdminController::class, 'session']);

    Route::get('/contents', [AdminController::class, 'contents']);
    Route::post('/contents', [AdminController::class, 'storeContent']);
    Route::put('/contents/{content}', [AdminController::class, 'updateContent']);
    Route::delete('/contents/{content}', [AdminController::class, 'destroyContent']);

    Route::get('/quiz-templates', [AdminController::class, 'quizTemplates']);
    Route::post('/quiz-templates', [AdminController::class, 'storeQuiz']);
    Route::put('/quiz-templates/{quizTemplate}', [AdminController::class, 'updateQuiz']);
    Route::delete('/quiz-templates/{quizTemplate}', [AdminController::class, 'destroyQuiz']);

    Route::get('/personal-weights', [AdminController::class, 'personalWeights']);
    Route::post('/personal-weights/reset', [AdminController::class, 'resetPersonal']);
    Route::post('/reset-demo', [AdminController::class, 'resetDemo']);
});
