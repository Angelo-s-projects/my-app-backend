<?php

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
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

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return $this->loginCodeResponse();
        }

        $loginCode = $user->loginCode;

        if ($loginCode?->isBlocked()) {
            return response()->json([
                'message' => 'Too many resend attempts. Please try again in 5 minutes.',
            ], 429);
        }

        $code = $loginCode
            ? $loginCode->replaceCode()
            : $user->createLoginCode();

        if (! $code) {
            return response()->json([
                'message' => 'Too many resend attempts. Please try again in 5 minutes.',
            ], 429);
        }

        Mail::to($user->email)->send(
            new LoginCodeMail($code)
        );

        return $this->loginCodeResponse();
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'regex:/^\d{5}$/'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->loginCode?->verify($validated['code'])) {
            return response()->json([
                'message' => 'The login code is invalid or expired.',
            ], 422);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }

    private function loginCodeResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'If the account exists, a login code has been sent.',
        ]);
    }
}
