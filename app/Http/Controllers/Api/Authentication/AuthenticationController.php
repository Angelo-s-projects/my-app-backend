<?php

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginCodeRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'regex:/^\d{5}$/'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->loginCode?->verify($validated['code'])) {
            return ApiResponse::error(
                code: 'INVALID_OR_EXPIRED_LOGIN_CODE',
                status: 422,
            );
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return ApiResponse::success(
            code: 'LOGIN_SUCCESSFUL',
            data: [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ],
        );
    }
}
