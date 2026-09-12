<?php

use App\Http\Controllers\Api\AiRequestController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseStepController;
use App\Http\Controllers\Api\PlacementTestController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StepChatController;
use App\Http\Controllers\Api\StepTestController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::post('/courses/{course}/retry', [CourseController::class, 'retry']);

    Route::post('/courses/{course}/placement-test/submit', [PlacementTestController::class, 'submit']);

    Route::get('/ai-requests/pending', [AiRequestController::class, 'pending']);
    Route::post('/ai-requests/{aiRequest}/complete', [AiRequestController::class, 'complete']);

    Route::scopeBindings()->group(function () {
        Route::get('/courses/{course}/steps/{step}/test', [StepTestController::class, 'show']);
        Route::post('/courses/{course}/steps/{step}/test/submit', [StepTestController::class, 'submit']);
        Route::post('/courses/{course}/steps/{step}/complete', [CourseStepController::class, 'complete']);
        Route::get('/courses/{course}/steps/{step}/chat', [StepChatController::class, 'index']);
        Route::post('/courses/{course}/steps/{step}/chat', [StepChatController::class, 'store']);
    });
});
