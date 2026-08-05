<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Checklist operational timezone
    |--------------------------------------------------------------------------
    |
    | Checklist dates are compared in this timezone. Timestamps remain stored
    | in UTC because the application timezone is intentionally left at UTC.
    |
    */
    'timezone' => env('CHECKLIST_TIMEZONE', 'Asia/Kuala_Lumpur'),

    /*
    |--------------------------------------------------------------------------
    | Master admin session
    |--------------------------------------------------------------------------
    |
    | The password is read only from configuration so it remains compatible
    | with Laravel's configuration cache. It must never be stored in session.
    |
    */
    'admin_password' => env('CHECKLIST_ADMIN_PASSWORD'),
    'admin_session_key' => env('CHECKLIST_ADMIN_SESSION_KEY', 'checklist_admin_authenticated'),
    'admin_session_minutes' => (int) env('CHECKLIST_ADMIN_SESSION_MINUTES', 120),

    /*
    |--------------------------------------------------------------------------
    | On-demand checklist materialization range
    |--------------------------------------------------------------------------
    */
    'past_materialization_days' => (int) env('CHECKLIST_PAST_MATERIALIZATION_DAYS', 365),
    'future_materialization_days' => (int) env('CHECKLIST_FUTURE_MATERIALIZATION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Evidence upload policy
    |--------------------------------------------------------------------------
    |
    | These application limits must be matched by PHP's upload_max_filesize,
    | post_max_size, and max_file_uploads settings. The dashboard exposes the
    | effective lower limit when a host configuration is more restrictive.
    |
    */
    'evidence' => [
        'max_files' => (int) env('CHECKLIST_EVIDENCE_MAX_FILES', 5),
        'max_file_kb' => (int) env('CHECKLIST_EVIDENCE_MAX_FILE_KB', 10240),
        'max_request_kb' => (int) env('CHECKLIST_EVIDENCE_MAX_REQUEST_KB', 56320),
    ],
];
