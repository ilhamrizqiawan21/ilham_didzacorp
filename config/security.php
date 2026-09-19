<?php

return [
    // Password default tidak lagi memblokir akses; dapat diaktifkan eksplisit
    // pada instalasi yang membutuhkan kebijakan tersebut.
    'require_student_phone' => env('REQUIRE_STUDENT_PHONE', false),
    'force_password_change' => env('FORCE_PASSWORD_CHANGE', false),
    'rate_limit_per_minute' => (int) env('SECURITY_RATE_LIMIT_PER_MINUTE', 180),
    'max_upload_mb' => (int) env('SECURITY_MAX_UPLOAD_MB', 5),
];
