<?php

return [
    'login_code' => [
        'expiry_minutes' => (int) env('LOGIN_CODE_EXPIRY_MINUTES', 15),
        'max_resends' => (int) env('LOGIN_CODE_MAX_RESENDS', 3),
        'resend_cooldown_minutes' => (int) env('LOGIN_CODE_RESEND_COOLDOWN_MINUTES', 5),
    ],
];