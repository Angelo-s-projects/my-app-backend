<?php

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginCodeRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\LoginResource;
use App\Http\Responses\ApiResponse;
use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

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

    public function sendLoginCode(LoginCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return ApiResponse::success(
                code: 'LOGIN_CODE_SENT',
            );
        }

        $loginCode = $user->loginCode;

        if ($loginCode?->isBlocked()) {
            return ApiResponse::error(
                code: 'LOGIN_CODE_RESEND_LIMIT_EXCEEDED',
                status: 429,
            );
        }

        $code = $loginCode
            ? $loginCode->replaceCode()
            : $user->createLoginCode();

        if (! $code) {
            return ApiResponse::error(
                code: 'LOGIN_CODE_RESEND_LIMIT_EXCEEDED',
                status: 429,
            );
        }

        Mail::to($user->email)->send(
            new LoginCodeMail($code)
        );

        return ApiResponse::success(
            code: 'LOGIN_CODE_SENT',
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $loginCode = LoginCode::findValidCode(
            $validated['email'],
            $validated['code'],
        );

        if (! $loginCode || ! $loginCode->user) {
            return ApiResponse::error(
                code: 'INVALID_OR_EXPIRED_LOGIN_CODE',
                status: 422,
            );
        }

        $user = $loginCode->user;

        $loginCode->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return ApiResponse::success(
            code: 'LOGIN_SUCCESSFUL',
            data: (new LoginResource([
                'token' => $token,
            ]))->resolve(),
        );
    }
}
