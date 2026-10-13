<?php

// Merge into your existing config/documate.php if you already have one.
return [
    'workflow' => [
        'require_signature' => true,

        // Secret used to seal approvals and audit rows (HMAC-SHA256).
        // Generate once:  php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
        // Put it in .env as DOCUMATE_SEAL_KEY and NEVER change it after go-live,
        // or every document sealed with the old key will fail verification. Back it up.
        'seal_key' => env('DOCUMATE_SEAL_KEY'),
        'external_expiry_days' => 7,
        'rater_email_domain' => env('DOCUMATE_RATER_EMAIL_DOMAIN'),
        'signatory_invite_days' => 7,
        'mail_logo_url' => env('DOCUMATE_MAIL_LOGO_URL'),
    ],
];