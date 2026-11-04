<?php

return [
    'timezone' => env('MAGANG_TIMEZONE', 'Asia/Jakarta'),
    'internships' => [
        'one_active_per_student' => env('MAGANG_ONE_ACTIVE_INTERNSHIP', true),
    ],
    'report_periods' => [
        'due_time' => env('MAGANG_REPORT_DUE_TIME', '23:59:59'),
    ],
    'reports' => [
        'allowed_types' => ['pdf' => ['application/pdf'], 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']],
        'max_size_bytes' => (int) env('MAGANG_REPORT_MAX_SIZE_BYTES', 10 * 1024 * 1024),
    ],
    'supervisor_assignments' => [
        'lecturer_historical_read_status' => env('MAGANG_LECTURER_HISTORICAL_READ_STATUS', 'ended'),
    ],
    'guidance' => [
        'default_duration_minutes' => (int) env('MAGANG_GUIDANCE_DEFAULT_DURATION', 30),
        'minimum_duration_minutes' => (int) env('MAGANG_GUIDANCE_MIN_DURATION', 15),
        'maximum_duration_minutes' => (int) env('MAGANG_GUIDANCE_MAX_DURATION', 120),
        'default_capacity' => (int) env('MAGANG_GUIDANCE_DEFAULT_CAPACITY', 1),
        'booking_cutoff_hours' => (int) env('MAGANG_GUIDANCE_BOOKING_CUTOFF_HOURS', 24),
        'cancellation_cutoff_hours' => (int) env('MAGANG_GUIDANCE_CANCELLATION_CUTOFF_HOURS', 24),
        'maximum_active_bookings' => (int) env('MAGANG_GUIDANCE_MAX_ACTIVE_BOOKINGS', 1),
    ],
    'initial_admin' => [
        'name' => env('MAGANG_ADMIN_NAME'),
        'email' => env('MAGANG_ADMIN_EMAIL'),
        'password' => env('MAGANG_ADMIN_PASSWORD'),
    ],
];
