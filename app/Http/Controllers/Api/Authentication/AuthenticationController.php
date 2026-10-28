<?php

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthenticationController extends Controller
{
   public function register(RegisterRequest $request): JsonResponse
    {
        User::create($request->validated());

        return ApiResponse::success(
            code: 'REGISTRATION_SUCCESSFUL',
            status: 201,
        );
    }
}
