<?php

return [
    'school_email_domain' => env('SCHOOL_EMAIL_DOMAIN', 'starlight.edu.kh'),
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost')),
    'activation_link_ttl_hours' => (int) env('STUDENT_ACTIVATION_LINK_TTL_HOURS', 24),
];
