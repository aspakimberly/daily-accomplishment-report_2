<?php

return [
    // OTP security settings
    'otp_length' => (int) env('OTP_LENGTH', 6),
    'otp_ttl_minutes' => (int) env('OTP_TTL_MINUTES', 5),
    'otp_send_limit' => (int) env('OTP_SEND_LIMIT', 3),
    'otp_verify_limit' => (int) env('OTP_VERIFY_LIMIT', 5),
    'otp_rate_window_seconds' => (int) env('OTP_RATE_WINDOW_SECONDS', 600),
];
