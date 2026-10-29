<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginCode extends Model
{
    protected $fillable = [
        'user_id',
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
}