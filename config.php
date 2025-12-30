<?php
// Основные настройки проекта
return [
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'vasileab_3838',
        'user' => 'vasileab_3838',
        'pass' => 'Ktyf8Dfyz',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Kapouch store',
        'url' => 'https://kapouch.store',
        'support_email' => 'support@kapouch.store',
        'currency' => '₽',
    ],
    'security' => [
        'session_name' => 'kapouch_session',
        'password_reset_ttl_minutes' => 30,
    ],
    'tinkoff' => [
        'terminal_key' => '1766993380280',
        'password' => 'mCwB6WpLbfwaqsw0',
        'notification_url' => 'https://kapouch.store/api.php?action=tinkoff_callback',
        'success_url' => 'https://kapouch.store/index.php?page=payments&status=success',
        'fail_url' => 'https://kapouch.store/index.php?page=payments&status=fail',
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
        'from' => 'Kapouch <no-reply@kapouch.store>',
    ],
    'company_data' => [
        'provider' => 'dadata',
        'base_url' => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party',
        'token' => 'e77e0d0f4f1801cd60ef779f620dec449fa8771d',
        'secret' => 'dd7d9ace98ed16e119f5aa16f6239b22fe36dcad',
        'timeout' => 10,
    ],
];
