<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/debug-test', function () {
    return response()->json([
        'message' => 'Laravel is responding',
    ]);
});