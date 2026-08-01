<?php

require_once __DIR__ . '/../app/lib/Env.php';

Env::load(__DIR__ . '/.env');

// All timestamps (PHP and MySQL alike) run on Tanzania time, so "time ago"
// labels in the admin panel match the customer's actual clock. DB.php applies
// the matching offset to the MySQL connection.
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Africa/Dar_es_Salaam'));

return [
    'app' => [
        'env' => Env::get('APP_ENV', 'local'),
        'url' => Env::get('APP_URL', ''),
        'timezone' => Env::get('APP_TIMEZONE', 'Africa/Dar_es_Salaam'),
    ],

    'db' => [
        'host' => Env::get('DB_HOST', 'localhost'),
        'name' => Env::get('DB_NAME', ''),
        'user' => Env::get('DB_USER', ''),
        'pass' => Env::get('DB_PASS', ''),
    ],

    'whatsapp' => [
        'token' => Env::get('WHATSAPP_TOKEN', ''),
        'phone_number_id' => Env::get('WHATSAPP_PHONE_NUMBER_ID', ''),
        'verify_token' => Env::get('WHATSAPP_VERIFY_TOKEN', ''),
        'business_account_id' => Env::get('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
        'display_number' => Env::get('WHATSAPP_DISPLAY_NUMBER', ''),
    ],

    'admin' => [
        'phone_number' => Env::get('ADMIN_PHONE_NUMBER', ''),
    ],

    'links' => [
        'group_url' => Env::get('KUZAPANEL_GROUP_URL', '#'),
        'website_url' => Env::get('KUZAPANEL_WEBSITE_URL', '#'),
    ],

    'referral' => [
        'percent' => (float) Env::get('REFERRAL_PERCENT', '10'),
    ],

    'deepseek' => [
        'api_key' => Env::get('DEEPSEEK_API_KEY', ''),
    ],

    'payments' => [
        'zenopay' => [
            'api_key' => Env::get('ZENOPAY_API_KEY', ''),
        ],
        'snippe' => [
            'api_key' => Env::get('SNIPPE_API_KEY', ''),
            'webhook_secret' => Env::get('SNIPPE_WEBHOOK_SECRET', ''),
        ],
        'harakapay' => [
            'api_key' => Env::get('HARAKAPAY_API_KEY', ''),
        ],
    ],
];
