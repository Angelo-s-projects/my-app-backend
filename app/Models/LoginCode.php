<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class LoginCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'expires_at',
        'resend_attempts',
        'blocked_until',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'blocked_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isBlocked(): bool
    {
        if ($this->blocked_until === null) {
            return false;
        }

        if (now()->lt($this->blocked_until)) {
            return true;
        }

        // Reset attempts after cooldown.
        $this->update([
            'resend_attempts' => 0,
            'blocked_until' => null,
        ]);

        return false;
    }

    public function replaceCode(): ?string
    {
        if ($this->isBlocked()) {
            return null;
        }

        if ($this->resend_attempts >= config('authorization.login_code.max_resends')) {
            $this->update([
                'blocked_until' => now()->addMinutes(
                    config('authorization.login_code.resend_cooldown_minutes')
                ),
            ]);

            return null;
        }

        return $this->generateAndSaveCode(
            $this->resend_attempts + 1
        );
    }

    private function generateAndSaveCode(int $resendAttempts): string
    {
        $code = $this->generateCode();

        $this->update([
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(
                config('authorization.login_code.expiry_minutes')
            ),
            'resend_attempts' => $resendAttempts,
            'blocked_until' => null,
        ]);

        return $code;
    }

    private function generateCode(): string
    {
        return str_pad(
            (string) random_int(0, 99999),
            5,
            '0',
            STR_PAD_LEFT,
        );
    }

    public static function findValidCode(string $email, string $code): ?self
    {
        return self::query()
            ->whereHas('user', function ($query) use ($email) {
                $query->where('email', $email);
            })
            ->where('expires_at', '>', now())
            ->get()
            ->first(
                fn (self $loginCode) => Hash::check(
                    $code,
                    $loginCode->code,
                )
            );
    }
}
