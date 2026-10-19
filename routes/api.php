<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/debug-test', function () {
    return response()->json([
        'message' => 'Laravel is responding',
    ]);
});
Route::get('/users', [UserController::class, 'index']);