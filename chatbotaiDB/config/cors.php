<?php

return [
    'paths' => [
        'embed/*',          // Allow iframe embedding routes
        'send',             // Allow chat message submissions
        'api/*',            // API endpoints
        'sanctum/csrf-cookie'
    ],

    'allowed_methods' => ['GET', 'POST'],

    // Restrict to specific domains instead of '*'
    'allowed_origins' => [
        'https://www.yourdomain.com',       // Replace with parent domain
        'https://*.yourdomain.com'      // Optional: Allow subdomains
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN'
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // Only if using cookies/sessions
];