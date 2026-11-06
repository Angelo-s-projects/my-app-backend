<?php

use App\Http\Controllers\Api\Authentication\AuthenticationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthenticationController::class, 'register']);
Route::post('/send-login-code', [AuthenticationController::class, 'sendLoginCode']);
Route::post('/login', [AuthenticationController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
