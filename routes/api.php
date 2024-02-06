<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AnswerController,
    AuthController,
    QuizSessionController,
    QuoteController,
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('/quotes', QuoteController::class)->except(['index', 'show']);
    Route::apiResource('/answers', AnswerController::class)->except(['index', 'show']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/questionnaires/{id}', [QuoteController::class, 'getQuestionnaireById']);
    Route::get('/questionnaires', [QuoteController::class, 'getBinaryQuestionnaire']);
    Route::get('/questionnaires', [QuoteController::class, 'getMultipleChoiceQuestionnaire']);
    Route::get('/quiz/history', [QuizSessionController::class, 'guestUserHistory']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/getUser', [AuthController::class, 'getUser']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::apiResource('/quotes', QuoteController::class)->only(['index', 'show']);
        Route::apiResource('/answers', AnswerController::class)->only(['index', 'show']);
        Route::get('/top-scorers', [QuizSessionController::class, 'topScorers']);
    });

    Route::prefix('quiz')->group(function () {
        Route::get('/session/{sessionId}', [QuizSessionController::class, 'showSession']);
        Route::post('/start', [QuizSessionController::class, 'startSession']);
        Route::post('/{session}/answer', [QuizSessionController::class, 'submitAnswer']);
        Route::post('/end/{sessionId}', [QuizSessionController::class, 'endSession']);
        Route::get('/end/{sessionId}', [QuizSessionController::class, 'endSessionResults']);
    });
});
