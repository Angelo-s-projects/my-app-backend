<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
    ];

    public function loginCode(): HasOne
    {
        return $this->hasOne(LoginCode::class);
    }

    public function createLoginCode(): string
    {
        $code = str_pad(
            (string) random_int(0, 99999),
            5,
            '0',
            STR_PAD_LEFT,
        );

        $this->loginCode()->create([
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
            'resend_attempts' => 0,
        ]);

        return $code;
    }
}