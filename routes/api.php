<?php

use App\Http\Controllers\Api\Authentication\AuthenticationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginCodeController;

Route::get('/users', [UserController::class, 'index']);
Route::post('/register', [AuthenticationController::class, 'register']);
Route::post('/send-login-code', [LoginCodeController::class, 'send']);