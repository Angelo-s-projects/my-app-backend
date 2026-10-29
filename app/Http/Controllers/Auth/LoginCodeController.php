<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class LoginCodeController extends Controller
{
    private const CODE_EXPIRY_MINUTES = 5;

    private const MAX_RESENDS = 3;

    private const RESEND_COOLDOWN_MINUTES = 5;

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Avoid revealing whether an email has an account.
        if (! $user) {
            return response()->json([
                'message' => 'If the account exists, a login code has been sent.',
            ]);
        }

        $result = DB::transaction(function () use ($user): array {
            $loginCode = LoginCode::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $now = now();

            if (
                $loginCode?->blocked_until !== null
                && $now->lt($loginCode->blocked_until)
            ) {
                return ['blocked' => true];
            }

            $resendAttempts = $loginCode?->resend_attempts ?? 0;

            if ($loginCode && $loginCode->blocked_until !== null) {
                // The cooldown has ended.
                $resendAttempts = 0;
            }

            if ($loginCode && $resendAttempts >= self::MAX_RESENDS) {
                $loginCode->update([
                    'blocked_until' => $now->copy()->addMinutes(
                        self::RESEND_COOLDOWN_MINUTES
                    ),
                ]);

                return ['blocked' => true];
            }

            $code = str_pad(
                (string) random_int(0, 99999),
                5,
                '0',
                STR_PAD_LEFT,
            );

            $data = [
                'code' => Hash::make($code),
                'expires_at' => $now->copy()->addMinutes(
                    self::CODE_EXPIRY_MINUTES
                ),
                'resend_attempts' => $loginCode
                    ? $resendAttempts + 1
                    : 0,
                'blocked_until' => null,
            ];

            if ($loginCode) {
                $loginCode->update($data);
            } else {
                $user->loginCode()->create($data);
            }

            return [
                'blocked' => false,
                'code' => $code,
            ];
        });

        if ($result['blocked']) {
            return response()->json([
                'message' => 'Too many resend attempts. Please try again in 5 minutes.',
            ], 429);
        }

        logger()->info('Sending login code email', [
    'email' => $user->email,
]);

        Mail::to($user->email)->send(
            new LoginCodeMail($result['code'])
        );

        return response()->json([
            'message' => 'If the account exists, a login code has been sent.',
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'regex:/^\d{5}$/'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'The login code is invalid or expired.',
            ], 422);
        }

        $loginCode = $user->loginCode;

        if (
            ! $loginCode
            || (
                $loginCode->blocked_until !== null
                && now()->lt($loginCode->blocked_until)
            )
            || now()->greaterThanOrEqualTo($loginCode->expires_at)
            || ! Hash::check($validated['code'], $loginCode->code)
        ) {
            return response()->json([
                'message' => 'The login code is invalid or expired.',
            ], 422);
        }

        // A code can only be used once.
        $loginCode->delete();

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
}