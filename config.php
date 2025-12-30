<?php
// Основные настройки проекта
return [
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'kapouch',
        'user' => 'kapouch_user',
        'pass' => 'kapouch_pass',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Kapouch',
        'url' => 'https://your-domain.ru',
        'support_email' => 'support@your-domain.ru',
        'currency' => '₽',
    ],
    'security' => [
        'session_name' => 'kapouch_session',
        'password_reset_ttl_minutes' => 30,
    ],
    'tinkoff' => [
        'terminal_key' => 'TINKOFF_TERMINAL_KEY',
        'password' => 'TINKOFF_PASSWORD',
        'notification_url' => 'https://your-domain.ru/api.php?action=tinkoff_callback',
        'success_url' => 'https://your-domain.ru/index.php?page=payments&status=success',
        'fail_url' => 'https://your-domain.ru/index.php?page=payments&status=fail',
        'taxation' => 'usn_income',
        'vat' => 'none',
    ],
    'aqsi' => [
        'base_url' => 'https://api.aqsi.ru/pub',
        'sales_path' => '/v4/shops/{shopId}/sales',
        'timeout' => 20,
    ],
    'fiscalization' => [
        'enabled' => true,
        'provider' => 'tinkoff',
    ],
    'email' => [
        'from' => 'Kapouch <no-reply@your-domain.ru>',
    ],
    'company_data' => [
        'provider' => 'dadata',
        'base_url' => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party',
        'token' => 'DADATA_API_KEY',
        'secret' => 'DADATA_SECRET',
        'timeout' => 10,
    ],
];
