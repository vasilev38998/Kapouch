<?php
require __DIR__ . '/lib.php';
$config = require __DIR__ . '/config.php';

$page = $_GET['page'] ?? 'home';
$user = current_user();
$subscription = $user ? active_subscription((int)$user['id']) : null;
$landing = get_setting('landing', []);

function page_header(string $title, ?array $user): void {
    $app = (require __DIR__ . '/config.php')['app'];
    $body_class = $user ? 'app-body' : 'marketing-body';
    echo "<!doctype html><html lang=\"ru\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>" . e($title) . " | " . e($app['name']) . "</title><link rel=\"stylesheet\" href=\"/assets/style.css\"><link rel=\"manifest\" href=\"/manifest.json\"><meta name=\"theme-color\" content=\"#1b1a17\"><meta name=\"apple-mobile-web-app-capable\" content=\"yes\"><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black-translucent\"><meta name=\"csrf-token\" content=\"" . e(csrf_token()) . "\"></head><body class=\"" . e($body_class) . "\">";
    echo "<header class=\"site-header\"><div class=\"container header-inner\"><a class=\"logo\" href=\"/\"><span class=\"logo-mark\">K</span>Kapouch</a>";
    echo "<nav class=\"nav\">";
    if ($user) {
        echo "<a href=\"/index.php?page=dashboard\">Рабочий стол</a>";
        echo "<a href=\"/index.php?page=cafes\">Мои кофейни</a>";
        echo "<a href=\"/index.php?page=plans\">Тарифы</a>";
        if ($user['role'] === 'admin') {
            echo "<a href=\"/index.php?page=admin\">Админка</a>";
        }
        echo "<a href=\"/api.php?action=logout\" class=\"btn btn-ghost\">Выйти</a>";
    } else {
        echo "<a href=\"/index.php?page=plans\">Тарифы</a>";
        echo "<a href=\"/index.php?page=login\" class=\"btn btn-ghost\">Войти</a>";
        echo "<a href=\"/index.php?page=register\" class=\"btn btn-primary\">Запустить учёт</a>";
    }
    echo "</nav></div></header>";
    if ($user) {
        echo "<div class=\"app-shell\">";
    }
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo "<div class=\"container\"><div class=\"alert {$flash['type']}\">" . e($flash['message']) . "</div></div>";
    }
}

function page_footer(): void {
    $year = date('Y');
    $user = current_user();
    if (!$user) {
        echo "<footer class=\"site-footer\"><div class=\"container footer-inner\"><div><strong>Kapouch</strong> — профессиональный учёт для кофеен. © {$year}</div><div>Поддержка: support@your-domain.ru</div></div></footer>";
    } else {
        echo "</div></div>";
    }
    echo "<script src=\"/assets/app.js\"></script></body></html>";
}

function app_nav(string $current, int $cafe_id = 0): void {
    $user = current_user();
    $subscription = $user ? active_subscription((int)$user['id']) : null;
    $plan_name = $subscription['plan_name'] ?? 'Без тарифа';
    $groups = [
        'Операции' => [
            'dashboard' => ['Рабочий стол', '/index.php?page=dashboard'],
            'calendar' => ['Календарь', $cafe_id ? "/index.php?page=calendar&cafe_id={$cafe_id}" : '/index.php?page=calendar'],
            'sales' => ['Продажи', $cafe_id ? "/index.php?page=sales&cafe_id={$cafe_id}" : '/index.php?page=sales'],
            'expenses' => ['Расходы', $cafe_id ? "/index.php?page=expenses&cafe_id={$cafe_id}" : '/index.php?page=expenses'],
            'cash_shifts' => ['Смены', $cafe_id ? "/index.php?page=cash_shifts&cafe_id={$cafe_id}" : '/index.php?page=cash_shifts'],
            'staff' => ['Персонал', $cafe_id ? "/index.php?page=staff&cafe_id={$cafe_id}" : '/index.php?page=staff'],
            'writeoffs' => ['Списания', $cafe_id ? "/index.php?page=writeoffs&cafe_id={$cafe_id}" : '/index.php?page=writeoffs'],
            'checklist' => ['Чек‑лист', $cafe_id ? "/index.php?page=checklist&cafe_id={$cafe_id}" : '/index.php?page=checklist'],
        ],
        'Продукты' => [
            'ingredients' => ['Ингредиенты', $cafe_id ? "/index.php?page=ingredients&cafe_id={$cafe_id}" : '/index.php?page=ingredients'],
            'recipes' => ['Рецепты', $cafe_id ? "/index.php?page=recipes&cafe_id={$cafe_id}" : '/index.php?page=recipes'],
        ],
        'Аналитика' => [
            'ceo_dashboard' => ['Сводка владельца', '/index.php?page=ceo_dashboard'],
            'analytics' => ['Аналитика', $cafe_id ? "/index.php?page=analytics&cafe_id={$cafe_id}" : '/index.php?page=analytics'],
            'plan_fact' => ['План‑факт', $cafe_id ? "/index.php?page=plan_fact&cafe_id={$cafe_id}" : '/index.php?page=plan_fact'],
            'abc_xyz' => ['ABC/XYZ', $cafe_id ? "/index.php?page=abc_xyz&cafe_id={$cafe_id}" : '/index.php?page=abc_xyz'],
        'benchmarks' => ['Бенчмарки', '/index.php?page=benchmarks'],
    ],
    'Сервис' => [
        'imports' => ['Импорт', '/index.php?page=imports'],
        'company_profile' => ['Профиль компании', '/index.php?page=company_profile'],
        'health' => ['Здоровье бизнеса', '/index.php?page=health'],
        'payments' => ['Платежи', '/index.php?page=payments'],
        'cafes' => ['Кофейни', '/index.php?page=cafes'],
    ],
];
    $top_tabs = [
        'dashboard' => ['Рабочий стол', '/index.php?page=dashboard'],
        'sales' => ['Продажи', $cafe_id ? "/index.php?page=sales&cafe_id={$cafe_id}" : '/index.php?page=sales'],
        'expenses' => ['Расходы', $cafe_id ? "/index.php?page=expenses&cafe_id={$cafe_id}" : '/index.php?page=expenses'],
        'analytics' => ['Аналитика', $cafe_id ? "/index.php?page=analytics&cafe_id={$cafe_id}" : '/index.php?page=analytics'],
        'staff' => ['Персонал', $cafe_id ? "/index.php?page=staff&cafe_id={$cafe_id}" : '/index.php?page=staff'],
    ];
    echo "<aside class=\"sidebar\">";
    echo "<div class=\"sidebar-header\"><div class=\"logo\"><span class=\"logo-mark\">K</span><div><div>Kapouch</div><div class=\"muted\">{$plan_name}</div></div></div></div>";
    if ($user) {
        $initials = mb_substr($user['email'], 0, 1, 'UTF-8');
        echo "<div class=\"sidebar-user\"><div class=\"avatar\">" . e($initials) . "</div><div><div class=\"user-name\">" . e($user['email']) . "</div><div class=\"muted\">Доступ активен</div></div></div>";
    }
    echo "<div class=\"sidebar-nav\">";
    foreach ($groups as $title => $links) {
        echo "<div class=\"sidebar-group\"><div class=\"sidebar-title\">" . e($title) . "</div><div class=\"sidebar-links\">";
        foreach ($links as $key => $data) {
            $active = $current === $key ? 'active' : '';
            echo "<a class=\"{$active}\" href=\"" . e($data[1]) . "\">" . e($data[0]) . "</a>";
        }
        echo "</div></div>";
    }
    echo "</div><div class=\"sidebar-footer\"><a class=\"btn btn-ghost\" href=\"/api.php?action=logout\">Выйти</a></div></aside>";
    echo "<div class=\"app-content\"><div class=\"topbar\"><div class=\"topbar-tabs\">";
    foreach ($top_tabs as $key => $data) {
        $active = $current === $key ? 'active' : '';
        echo "<a class=\"{$active}\" href=\"" . e($data[1]) . "\">" . e($data[0]) . "</a>";
    }
    echo "</div><div class=\"topbar-actions\"><a class=\"btn btn-primary\" href=\"/index.php?page=sales&cafe_id=" . e((string)$cafe_id) . "\">Добавить продажу</a><a class=\"btn btn-ghost\" href=\"/index.php?page=expenses&cafe_id=" . e((string)$cafe_id) . "\">Добавить расход</a></div></div>";
}

function guard_subscription(?array $subscription): void {
    if (!$subscription) {
        echo '<div class="alert warning">Для доступа к разделу необходимо активировать тариф.</div>';
    }
}

function period_selector(): string {
    return '<div class="period-selector">' .
        '<label>Период</label>' .
        '<select name="period">' .
        '<option value="month">Месяц</option>' .
        '<option value="week">Неделя</option>' .
        '<option value="day">День</option>' .
        '</select>' .
        '</div>';
}

page_header('Kapouch', $user);

if ($page === 'home') {
    $advantages = $landing['advantages'] ?? [];
    $testimonials = $landing['testimonials'] ?? [];
    echo "<main>";
    echo "<section class=\"hero\"><div class=\"container hero-inner\"><div><div class=\"badge\">SaaS для владельцев кофеен</div><h1>" . e($landing['hero_title'] ?? 'Финансовая система для владельцев кофеен') . "</h1><p>" . e($landing['hero_subtitle'] ?? 'Полный контроль маржи, себестоимости и прибыли. Решения на цифрах, а не на интуиции.') . "</p><div class=\"hero-actions\"><a class=\"btn btn-primary\" href=\"/index.php?page=register\">" . e($landing['cta_primary'] ?? 'Попробовать 7 дней') . "</a><a class=\"btn btn-ghost\" href=\"/index.php?page=plans\">" . e($landing['cta_secondary'] ?? 'Купить доступ') . "</a></div><div class=\"hero-note\">Без автопродления · Оплата за период · Фискализация по 54‑ФЗ</div><div class=\"hero-stats\"><div><span>+12%</span> рост маржи за 30 дней</div><div><span>4.9/5</span> средняя оценка клиентов</div><div><span>7 минут</span> на ежедневный контроль</div></div></div><div class=\"hero-card\"><h3>Что внутри Kapouch</h3><ul><li>Средневзвешенная себестоимость по рецептам</li><li>P&L, unit-экономика, точка безубыточности</li><li>Контроль закупок, расходов и продаж</li><li>Тинькофф СБП + онлайн-чеки</li></ul><div class=\"hero-visual\"><div class=\"visual-header\">Дашборд кофейни</div><div class=\"visual-bars\"><span style=\"height: 30%\"></span><span style=\"height: 55%\"></span><span style=\"height: 40%\"></span><span style=\"height: 70%\"></span><span style=\"height: 50%\"></span></div><div class=\"visual-footer\"><span>Маржа +8.4%</span><span>Прибыль 214 000 ₽</span></div></div><div class=\"hero-card-footer\"><strong>Запуск за 1 день</strong><span>без разработки и серверов</span></div></div></div></section>";
    echo "<section class=\"section\"><div class=\"container\"><div class=\"section-head\"><div><h2>Рост прибыли в цифрах</h2><p class=\"muted\">Визуальные метрики, которые видит владелец каждый день.</p></div><a class=\"btn btn-ghost\" href=\"/index.php?page=register\">Открыть демо</a></div><div class=\"grid grid-3\">"
        . "<div class=\"card chart-card\"><div class=\"chart-title\">Выручка</div><svg viewBox=\"0 0 120 60\" class=\"chart\"><path d=\"M0 50 L20 40 L40 45 L60 30 L80 25 L100 15 L120 10\" fill=\"none\" stroke=\"#c1a46e\" stroke-width=\"4\" stroke-linecap=\"round\"/></svg><div class=\"chart-foot\"><span>+18% к прошлому месяцу</span></div></div>"
        . "<div class=\"card chart-card\"><div class=\"chart-title\">Маржинальность</div><svg viewBox=\"0 0 120 60\" class=\"chart\"><path d=\"M0 55 L20 48 L40 44 L60 36 L80 28 L100 22 L120 18\" fill=\"none\" stroke=\"#2f3b2f\" stroke-width=\"4\" stroke-linecap=\"round\"/></svg><div class=\"chart-foot\"><span>Цель 65%</span></div></div>"
        . "<div class=\"card chart-card\"><div class=\"chart-title\">Расходы</div><svg viewBox=\"0 0 120 60\" class=\"chart\"><path d=\"M0 20 L20 25 L40 30 L60 40 L80 45 L100 38 L120 32\" fill=\"none\" stroke=\"#a8864d\" stroke-width=\"4\" stroke-linecap=\"round\"/></svg><div class=\"chart-foot\"><span>Контроль OPEX</span></div></div></div></div></section>";
    echo "<section class=\"section\"><div class=\"container\"><div class=\"section-head\"><div><h2>Почему владельцы кофеен выбирают Kapouch</h2><p class=\"muted\">Сервис учитывает специфику кофейного бизнеса и помогает видеть прибыль на уровне напитков.</p></div><a class=\"btn btn-ghost\" href=\"/index.php?page=register\">Начать сейчас</a></div><div class=\"grid grid-4\">";
    foreach ($advantages as $adv) {
        echo "<div class=\"card\"><h3>" . e($adv['title']) . "</h3><p>" . e($adv['text']) . "</p></div>";
    }
    echo "</div></div></section>";
    echo "<section class=\"section alt\"><div class=\"container\"><div class=\"section-head\"><div><h2>Отзывы владельцев кофеен</h2><span class=\"muted\">Люди, которые уже считают прибыль по каждому напитку</span></div><a class=\"btn btn-ghost\" href=\"/index.php?page=register\">Хочу так же</a></div><div class=\"testimonial-grid\">";
    foreach ($testimonials as $item) {
        $initials = mb_substr($item['name'], 0, 1, 'UTF-8');
        echo "<div class=\"testimonial-card\"><div class=\"testimonial-head\"><div class=\"avatar\">" . e($initials) . "</div><div><strong>" . e($item['name']) . "</strong><div class=\"muted\">Сеть кофеен</div></div></div><p>“" . e($item['text']) . "”</p><div class=\"testimonial-rating\">★★★★★</div></div>";
    }
    echo "</div></div></section>";
    echo "<section class=\"section\"><div class=\"container\"><div class=\"section-head\"><div><h2>Тарифы без автопродления</h2><p class=\"muted\">Вы оплачиваете только нужный период. Доступ активируется после оплаты и чека.</p></div><a class=\"btn btn-primary\" href=\"/index.php?page=register\">Запустить сейчас</a></div><div class=\"grid grid-3 pricing\">";
    $plans = db()->query('SELECT * FROM plans WHERE active = 1 ORDER BY price')->fetchAll();
    $feature_labels = [
        'pnl_full' => 'Полный P&L с детализацией',
        'analytics_periods' => 'Аналитика по периодам',
        'comparisons' => 'Сравнение периодов',
        'breakeven' => 'Точка безубыточности',
        'unit_economics' => 'Unit-экономика напитков',
        'kpi' => 'KPI и цели',
        'alerts' => 'Автоматические предупреждения',
        'smart_calendar' => 'Умный календарь бизнеса',
        'smart_reminders' => 'Напоминания и контроль сроков',
        'daily_focus' => 'Ежедневный фокус владельца',
        'quick_report' => 'Экспресс-отчёт недели',
        'cashflow_forecast' => 'Прогноз платежей на 30 дней',
        'category_insights' => 'Инсайты по категориям расходов',
        'anomaly_radar' => 'Радар аномалий',
        'benchmark_gap' => 'Сравнение с рынком',
        'inventory_alerts' => 'Уведомления по остаткам',
        'writeoffs' => 'Списания и потери',
        'expense_budgets' => 'Бюджеты расходов',
        'cost_alerts' => 'Контроль роста себестоимости',
        'daily_checklist' => 'Ежедневный чек‑лист',
        'staff_efficiency' => 'Эффективность персонала',
        'cashflow_90' => 'Кэш-флоу на 90 дней',
        'profit_forecast' => 'Прогноз прибыли',
        'kpi_alerts' => 'Авто‑контроль KPI',
        'abc_visuals' => 'ABC/XYZ визуализация',
        'advanced_recommendations' => 'Расширенные рекомендации',
        'export_pdf' => 'PDF‑отчёты',
        'export_1c' => 'Экспорт для 1С',
        'export' => 'Экспорт отчётов',
        'recommendations' => 'Умные рекомендации',
        'what_if' => 'Сценарии «А если»',
        'advanced_analytics' => 'Расширенная аналитика',
    ];
    foreach ($plans as $plan) {
        $features = json_decode($plan['features_json'], true);
        echo "<div class=\"card pricing-card\"><div class=\"pricing-head\"><h3>" . e($plan['name']) . "</h3><div class=\"price\">" . format_money($plan['price']) . " ₽ / " . e((string)$plan['duration_days']) . " дней</div></div><ul class=\"pricing-list\">";
        echo "<li>Кофеен: до " . e((string)$plan['max_cafes']) . "</li>";
        echo "<li>Ингредиенты и рецепты</li>";
        echo "<li>Продажи и расходы</li>";
        echo "<li>Импорт CSV: " . ($features['import_limit'] >= 100000 ? 'без ограничений' : 'до ' . $features['import_limit'] . ' строк') . "</li>";
        foreach ($feature_labels as $key => $label) {
            if (!empty($features[$key])) {
                echo "<li>" . e($label) . "</li>";
            }
        }
        if ($user) {
            echo "</ul><form method=\"post\" action=\"/api.php?action=init_payment\">" . csrf_field() . "<input type=\"hidden\" name=\"plan_id\" value=\"" . e((string)$plan['id']) . "\"><button class=\"btn btn-primary\" type=\"submit\">Оплатить</button></form></div>";
        } else {
            echo "</ul><a class=\"btn btn-primary\" href=\"/index.php?page=register\">Начать</a></div>";
        }
    }
    echo "</div></div></section>";
    echo "<section class=\"section cta\"><div class=\"container cta-inner\"><div><h2>Готовы управлять кофейней как бизнесом?</h2><p>Подключайтесь за 5 минут и начните видеть реальную прибыль.</p></div><a class=\"btn btn-dark\" href=\"/index.php?page=register\">Получить доступ</a></div></section>";
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'login') {
    echo "<main class=\"container auth\"><div class=\"card auth-card\"><h2>Вход в Kapouch</h2><form method=\"post\" action=\"/api.php?action=login\">" . csrf_field();
    echo "<label>Email</label><input type=\"email\" name=\"email\" required><label>Пароль</label><input type=\"password\" name=\"password\" required><button class=\"btn btn-primary\" type=\"submit\">Войти</button></form>";
    echo "<div class=\"auth-links\"><a href=\"/index.php?page=reset\">Забыли пароль?</a><a href=\"/index.php?page=register\">Создать аккаунт</a></div></div></main>";
    page_footer();
    exit;
}

if ($page === 'register') {
    echo "<main class=\"container auth\"><div class=\"card auth-card\"><h2>Создание аккаунта</h2><form method=\"post\" action=\"/api.php?action=register\">" . csrf_field();
    echo "<label>Email</label><input type=\"email\" name=\"email\" required><label>Пароль</label><input type=\"password\" name=\"password\" required><label>Подтверждение пароля</label><input type=\"password\" name=\"password_confirm\" required><button class=\"btn btn-primary\" type=\"submit\">Создать аккаунт</button></form>";
    echo "<div class=\"auth-links\"><a href=\"/index.php?page=login\">Уже есть аккаунт?</a></div></div></main>";
    page_footer();
    exit;
}

if ($page === 'company_profile') {
    $user = require_auth();
    $stmt = db()->prepare('SELECT * FROM company_profiles WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch();
    app_nav('company_profile');
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Профиль компании</h2><div class=\"muted\">Укажите ИНН и подтяните данные вашей компании или ИП</div></div></div>";
    echo "<div class=\"grid grid-2\">";
    echo "<div class=\"card\"><h3>Подтянуть данные по ИНН</h3><form method=\"post\" action=\"/api.php?action=lookup_company\" class=\"inline-form\">" . csrf_field() . "<label>ИНН</label><input name=\"inn\" value=\"" . e($profile['inn'] ?? '') . "\" required><button class=\"btn btn-primary\" type=\"submit\">Подтянуть данные</button></form><div class=\"muted\">Для автозаполнения нужен ключ API провайдера (например, DaData).</div></div>";
    echo "<div class=\"card\"><h3>Данные компании</h3><form method=\"post\" action=\"/api.php?action=save_company_profile\" class=\"grid grid-2\">" . csrf_field();
    echo "<div><label>ИНН</label><input name=\"inn\" value=\"" . e($profile['inn'] ?? '') . "\" required></div>";
    echo "<div><label>Тип</label><select name=\"entity_type\"><option value=\"company\"" . (($profile['entity_type'] ?? '') !== 'individual' ? ' selected' : '') . ">Юр. лицо</option><option value=\"individual\"" . (($profile['entity_type'] ?? '') === 'individual' ? ' selected' : '') . ">ИП</option></select></div>";
    echo "<div><label>Полное название</label><input name=\"company_name\" value=\"" . e($profile['company_name'] ?? '') . "\"></div>";
    echo "<div><label>Краткое название</label><input name=\"short_name\" value=\"" . e($profile['short_name'] ?? '') . "\"></div>";
    echo "<div><label>ОГРН / ОГРНИП</label><input name=\"ogrn\" value=\"" . e($profile['ogrn'] ?? '') . "\"></div>";
    echo "<div><label>КПП (для юр. лица)</label><input name=\"kpp\" value=\"" . e($profile['kpp'] ?? '') . "\"></div>";
    echo "<div class=\"grid-span-2\"><label>Юридический адрес</label><input name=\"address\" value=\"" . e($profile['address'] ?? '') . "\"></div>";
    echo "<div><label>Руководитель</label><input name=\"ceo_name\" value=\"" . e($profile['ceo_name'] ?? '') . "\"></div>";
    echo "<div><label>Статус</label><input name=\"status\" value=\"" . e($profile['status'] ?? '') . "\"></div>";
    echo "<div><button class=\"btn btn-primary\" type=\"submit\">Сохранить</button></div>";
    echo "</form></div>";
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'reset') {
    echo "<main class=\"container auth\"><div class=\"card auth-card\"><h2>Восстановление пароля</h2><form method=\"post\" action=\"/api.php?action=request_reset\">" . csrf_field();
    echo "<label>Email</label><input type=\"email\" name=\"email\" required><button class=\"btn btn-primary\" type=\"submit\">Отправить ссылку</button></form>";
    echo "<div class=\"muted\">Ссылка придёт на почту в течение нескольких минут.</div></div></main>";
    page_footer();
    exit;
}

if ($page === 'reset_confirm') {
    $token = $_GET['token'] ?? '';
    echo "<main class=\"container auth\"><div class=\"card auth-card\"><h2>Установить новый пароль</h2><form method=\"post\" action=\"/api.php?action=confirm_reset\">" . csrf_field();
    echo "<input type=\"hidden\" name=\"token\" value=\"" . e($token) . "\"><label>Новый пароль</label><input type=\"password\" name=\"password\" required><button class=\"btn btn-primary\" type=\"submit\">Сохранить пароль</button></form></div></main>";
    page_footer();
    exit;
}

if ($page === 'plans') {
    $plans = db()->query('SELECT * FROM plans WHERE active = 1 ORDER BY price')->fetchAll();
    $feature_labels = [
        'pnl_full' => 'Полный P&L с детализацией',
        'analytics_periods' => 'Аналитика по периодам',
        'comparisons' => 'Сравнение периодов',
        'breakeven' => 'Точка безубыточности',
        'unit_economics' => 'Unit-экономика напитков',
        'kpi' => 'KPI и цели',
        'alerts' => 'Автоматические предупреждения',
        'smart_calendar' => 'Умный календарь бизнеса',
        'smart_reminders' => 'Напоминания и контроль сроков',
        'daily_focus' => 'Ежедневный фокус владельца',
        'quick_report' => 'Экспресс-отчёт недели',
        'cashflow_forecast' => 'Прогноз платежей на 30 дней',
        'category_insights' => 'Инсайты по категориям расходов',
        'anomaly_radar' => 'Радар аномалий',
        'benchmark_gap' => 'Сравнение с рынком',
        'inventory_alerts' => 'Уведомления по остаткам',
        'writeoffs' => 'Списания и потери',
        'cashflow_90' => 'Кэш-флоу на 90 дней',
        'profit_forecast' => 'Прогноз прибыли',
        'kpi_alerts' => 'Авто‑контроль KPI',
        'abc_visuals' => 'ABC/XYZ визуализация',
        'advanced_recommendations' => 'Расширенные рекомендации',
        'export_pdf' => 'PDF‑отчёты',
        'export_1c' => 'Экспорт для 1С',
        'export' => 'Экспорт отчётов',
        'recommendations' => 'Умные рекомендации',
        'what_if' => 'Сценарии «А если»',
        'advanced_analytics' => 'Расширенная аналитика',
    ];
    echo "<main class=\"container section\"><h2>Тарифы</h2><div class=\"grid grid-3 pricing\">";
    foreach ($plans as $plan) {
        $features = json_decode($plan['features_json'], true);
        echo "<div class=\"card pricing-card\"><h3>" . e($plan['name']) . "</h3><div class=\"price\">" . format_money($plan['price']) . " ₽ / " . e((string)$plan['duration_days']) . " дней</div><ul class=\"pricing-list\">";
        echo "<li>Кофеен: до " . e((string)$plan['max_cafes']) . "</li>";
        echo "<li>Ингредиенты и рецепты</li>";
        echo "<li>Продажи и расходы</li>";
        echo "<li>Импорт CSV: " . ($features['import_limit'] >= 100000 ? 'без ограничений' : 'до ' . $features['import_limit'] . ' строк') . "</li>";
        foreach ($feature_labels as $key => $label) {
            if (!empty($features[$key])) {
                echo "<li>" . e($label) . "</li>";
            }
        }
        echo "</ul>";
        if ($user) {
            echo "<form method=\"post\" action=\"/api.php?action=init_payment\">" . csrf_field() . "<input type=\"hidden\" name=\"plan_id\" value=\"" . e((string)$plan['id']) . "\"><button class=\"btn btn-primary\" type=\"submit\">Оплатить</button></form>";
        } else {
            echo "<a class=\"btn btn-primary\" href=\"/index.php?page=register\">Создать аккаунт</a>";
        }
        echo "</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if (!$user) {
    header('Location: /index.php?page=login');
    exit;
}

if ($page === 'dashboard') {
    $subscription = require_subscription($user);
    $cafes = db()->prepare('SELECT * FROM cafes WHERE user_id = ?');
    $cafes->execute([$user['id']]);
    $cafes = $cafes->fetchAll();
    $selected_cafe = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $metrics = [
        'revenue' => 0,
        'cogs' => 0,
        'gross_profit' => 0,
        'gross_margin' => 0,
        'expenses' => 0,
        'net_profit' => 0,
    ];
    if ($selected_cafe) {
        $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id = ?');
        $stmt->execute([$selected_cafe]);
        $sales = $stmt->fetch();
        $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id = ?');
        $stmt->execute([$selected_cafe]);
        $exp = $stmt->fetch();
        $metrics['revenue'] = $sales['revenue'];
        $metrics['cogs'] = $sales['cogs'];
        $metrics['gross_profit'] = $metrics['revenue'] - $metrics['cogs'];
        $metrics['gross_margin'] = $metrics['revenue'] > 0 ? ($metrics['gross_profit'] / $metrics['revenue']) * 100 : 0;
        $metrics['expenses'] = $exp['expenses'];
        $metrics['net_profit'] = $metrics['gross_profit'] - $metrics['expenses'];
    }
    app_nav('dashboard', (int)$selected_cafe);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Рабочий стол</h2><div class=\"muted\">Тариф: " . e($subscription['plan_name']) . " · Действует до " . e(date('d.m.Y', strtotime($subscription['ends_at']))) . "</div></div><div class=\"page-actions\"><a class=\"btn btn-ghost\" href=\"/index.php?page=payments\">Платежи</a><a class=\"btn btn-primary\" href=\"/index.php?page=sales&cafe_id=" . e((string)$selected_cafe) . "\">Добавить продажи</a></div></div>";
    if (empty($cafes)) {
        echo "<div class=\"empty-state\"><h3>Добавьте первую кофейню</h3><p>Это нужно, чтобы считать рецепты, продажи и аналитику.</p><a class=\"btn btn-primary\" href=\"/index.php?page=cafes\">Создать кофейню</a></div>";
        echo "</main>";
        page_footer();
        exit;
    }
    echo "<div class=\"card\"><form method=\"get\" class=\"inline-form\"><input type=\"hidden\" name=\"page\" value=\"dashboard\"><label>Кофейня</label><select name=\"cafe_id\">";
    foreach ($cafes as $cafe) {
        $selected = ((int)$selected_cafe === (int)$cafe['id']) ? 'selected' : '';
        echo "<option value=\"" . e((string)$cafe['id']) . "\" {$selected}>" . e($cafe['name']) . "</option>";
    }
    echo "</select><button class=\"btn btn-ghost\" type=\"submit\">Показать</button></form></div>";

    $ingredient_count = db()->prepare('SELECT COUNT(*) AS total FROM ingredients WHERE cafe_id = ?');
    $ingredient_count->execute([$selected_cafe]);
    $ingredients_total = $ingredient_count->fetch()['total'];
    $recipe_count = db()->prepare('SELECT COUNT(*) AS total FROM recipes WHERE cafe_id = ?');
    $recipe_count->execute([$selected_cafe]);
    $recipes_total = $recipe_count->fetch()['total'];
    $sales_count = db()->prepare('SELECT COUNT(*) AS total FROM sales WHERE cafe_id = ?');
    $sales_count->execute([$selected_cafe]);
    $sales_total = $sales_count->fetch()['total'];
    if ($ingredients_total == 0 || $recipes_total == 0 || $sales_total == 0) {
        echo "<div class=\"card onboarding\"><div class=\"onboarding-head\"><h3>Запуск учёта: 3 шага до аналитики</h3><span class=\"muted\">Сервис покажет прибыль сразу после заполнения ключевых данных.</span></div><ol><li>Добавьте ингредиенты и закупки.</li><li>Создайте рецепты напитков.</li><li>Загрузите продажи (CSV) или внесите вручную.</li></ol><div class=\"onboarding-actions\"><a class=\"btn btn-ghost\" href=\"/index.php?page=ingredients&cafe_id=" . e((string)$selected_cafe) . "\">Ингредиенты</a><a class=\"btn btn-ghost\" href=\"/index.php?page=recipes&cafe_id=" . e((string)$selected_cafe) . "\">Рецепты</a><a class=\"btn btn-primary\" href=\"/index.php?page=sales&cafe_id=" . e((string)$selected_cafe) . "\">Продажи</a></div></div>";
    }

    echo "<div class=\"grid grid-3 metrics\">";
    echo "<div class=\"card metric\"><div class=\"metric-title\">Выручка</div><div class=\"metric-value\">" . format_money($metrics['revenue']) . " ₽</div></div>";
    echo "<div class=\"card metric\"><div class=\"metric-title\">Себестоимость</div><div class=\"metric-value\">" . format_money($metrics['cogs']) . " ₽</div></div>";
    echo "<div class=\"card metric\"><div class=\"metric-title\">Валовая прибыль</div><div class=\"metric-value\">" . format_money($metrics['gross_profit']) . " ₽</div><div class=\"muted\">Маржа: " . number_format($metrics['gross_margin'], 1, ',', ' ') . "%</div></div>";
    echo "</div>";

    if (feature_enabled($subscription, 'kpi')) {
        $kpi_stmt = db()->prepare('SELECT * FROM kpi_targets WHERE cafe_id = ?');
        $kpi_stmt->execute([$selected_cafe]);
        $kpi = $kpi_stmt->fetch();
        $target_margin = $kpi['target_margin'] ?? 0;
        $target_profit = $kpi['target_profit'] ?? 0;
        $target_revenue = $kpi['target_revenue'] ?? 0;
        echo "<div class=\"card\"><h3>KPI и цели</h3><form method=\"post\" action=\"/api.php?action=update_kpi\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$selected_cafe) . "\"><label>Целевая маржа (%)</label><input name=\"target_margin\" type=\"number\" step=\"0.1\" value=\"" . e((string)$target_margin) . "\"><label>Целевая прибыль (₽)</label><input name=\"target_profit\" type=\"number\" step=\"0.01\" value=\"" . e((string)$target_profit) . "\"><label>Целевая выручка (₽)</label><input name=\"target_revenue\" type=\"number\" step=\"0.01\" value=\"" . e((string)$target_revenue) . "\"><button class=\"btn btn-ghost\" type=\"submit\">Сохранить</button></form><div class=\"muted\">Текущая маржа: " . number_format($metrics['gross_margin'], 1, ',', ' ') . "% · Чистая прибыль: " . format_money($metrics['net_profit']) . " ₽</div></div>";
    }

    if (feature_enabled($subscription, 'alerts')) {
        $alerts = [];
        if ($metrics['gross_margin'] < 55) {
            $alerts[] = 'Низкая валовая маржа. Проверьте цены и рецепты.';
        }
        if ($metrics['net_profit'] < 0) {
            $alerts[] = 'Убыточный период. Нужны меры по расходам или ценам.';
        }
        if ($alerts) {
            echo "<div class=\"card\"><h3>Автоматические предупреждения</h3><ul class=\"data-list\">";
            foreach ($alerts as $alert) {
                echo "<li>" . e($alert) . "</li>";
            }
            echo "</ul></div>";
        } else {
            echo "<div class=\"card\"><h3>Автоматические предупреждения</h3><div class=\"muted\">Критичных отклонений не обнаружено.</div></div>";
        }
    }

    if (feature_enabled($subscription, 'inventory_alerts')) {
        $stmt = db()->prepare('SELECT name, stock_qty, reorder_level, unit FROM ingredients WHERE cafe_id = ? AND reorder_level > 0 AND stock_qty <= reorder_level ORDER BY name');
        $stmt->execute([$selected_cafe]);
        $low_stock = $stmt->fetchAll();
        echo "<div class=\"card\"><h3>Уведомления по остаткам</h3>";
        if ($low_stock) {
            echo "<ul class=\"data-list\">";
            foreach ($low_stock as $item) {
                echo "<li>" . e($item['name']) . " <span>" . e($item['stock_qty']) . " " . e($item['unit']) . "</span></li>";
            }
            echo "</ul>";
        } else {
            echo "<div class=\"muted\">Все остатки выше минимального уровня.</div>";
        }
        echo "</div>";
    }

    if (feature_enabled($subscription, 'cost_alerts')) {
        $threshold = (float)get_setting('cost_alert_threshold', 0.15);
        $stmt = db()->prepare('SELECT i.name, i.cost_per_unit, (SELECT cost_per_unit FROM ingredient_cost_history h WHERE h.ingredient_id = i.id ORDER BY recorded_at DESC LIMIT 1,1) AS prev_cost FROM ingredients i WHERE i.cafe_id = ?');
        $stmt->execute([$selected_cafe]);
        $alerts = [];
        foreach ($stmt->fetchAll() as $row) {
            if ($row['prev_cost'] && $row['prev_cost'] > 0) {
                $delta = ($row['cost_per_unit'] - $row['prev_cost']) / $row['prev_cost'];
                if ($delta > $threshold) {
                    $alerts[] = $row['name'] . ' вырос на ' . number_format($delta * 100, 0) . '%.';
                }
            }
        }
        echo "<div class=\"card\"><h3>Рост себестоимости</h3>";
        if ($alerts) {
            echo "<ul class=\"data-list\">";
            foreach ($alerts as $alert) {
                echo "<li>" . e($alert) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class=\"muted\">Рост себестоимости не обнаружен.</div>";
        }
        echo "<div class=\"muted\">Порог: " . number_format($threshold * 100, 0) . "%</div></div>";
    }

    if (feature_enabled($subscription, 'kpi_alerts')) {
        $kpi_stmt = db()->prepare('SELECT * FROM kpi_targets WHERE cafe_id = ?');
        $kpi_stmt->execute([$selected_cafe]);
        $kpi = $kpi_stmt->fetch();
        if ($kpi) {
            $kpi_notes = [];
            if ($kpi['target_margin'] > 0 && $metrics['gross_margin'] < $kpi['target_margin']) {
                $kpi_notes[] = 'Маржа ниже цели (' . number_format($kpi['target_margin'], 1, ',', ' ') . '%).';
            }
            if ($kpi['target_profit'] > 0 && $metrics['net_profit'] < $kpi['target_profit']) {
                $kpi_notes[] = 'Прибыль ниже цели.';
            }
            if ($kpi['target_revenue'] > 0 && $metrics['revenue'] < $kpi['target_revenue']) {
                $kpi_notes[] = 'Выручка ниже цели.';
            }
            if ($kpi_notes) {
                echo "<div class=\"card\"><h3>KPI‑контроль</h3><ul class=\"data-list\">";
                foreach ($kpi_notes as $note) {
                    echo "<li>" . e($note) . "</li>";
                }
                echo "</ul></div>";
            }
        }
    }

    if (feature_enabled($subscription, 'daily_focus')) {
        $focus = [];
        $stmt = db()->prepare('SELECT r.name, COALESCE(SUM(s.price_total - s.cost_total),0) AS profit FROM recipes r LEFT JOIN sales s ON s.recipe_id = r.id WHERE r.cafe_id = ? GROUP BY r.id ORDER BY profit DESC LIMIT 1');
        $stmt->execute([$selected_cafe]);
        $top = $stmt->fetch();
        if ($top) {
            $focus[] = 'Фокус недели: продвигайте ' . $top['name'] . ' (лучший вклад в прибыль).';
        }
        $stmt = db()->prepare('SELECT category, COALESCE(SUM(amount),0) AS total FROM expenses WHERE cafe_id = ? GROUP BY category ORDER BY total DESC LIMIT 1');
        $stmt->execute([$selected_cafe]);
        $exp = $stmt->fetch();
        if ($exp) {
            $focus[] = 'Самая крупная статья расходов: ' . $exp['category'] . '.';
        }
        echo "<div class=\"card\"><h3>Ежедневный фокус</h3>";
        if ($focus) {
            echo "<ul class=\"data-list\">";
            foreach ($focus as $item) {
                echo "<li>" . e($item) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class=\"muted\">Нет данных для фокуса. Добавьте продажи и расходы.</div>";
        }
        echo "</div>";
    }

    if (feature_enabled($subscription, 'quick_report')) {
        $week_start = (new DateTime())->modify('-7 days')->format('Y-m-d');
        $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id = ? AND sold_at >= ?');
        $stmt->execute([$selected_cafe, $week_start]);
        $week_sales = $stmt->fetch();
        $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id = ? AND expense_date >= ?');
        $stmt->execute([$selected_cafe, $week_start]);
        $week_exp = $stmt->fetch();
        $week_profit = $week_sales['revenue'] - $week_sales['cogs'] - $week_exp['expenses'];
        echo "<div class=\"card\"><h3>Экспресс-отчёт недели</h3><ul class=\"data-list\"><li>Выручка <span>" . format_money($week_sales['revenue']) . " ₽</span></li><li>Себестоимость <span>" . format_money($week_sales['cogs']) . " ₽</span></li><li>Расходы <span>" . format_money($week_exp['expenses']) . " ₽</span></li><li>Прибыль <span>" . format_money($week_profit) . " ₽</span></li></ul></div>";
    }

    if (feature_enabled($subscription, 'cashflow_forecast')) {
        $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM calendar_events WHERE cafe_id = ? AND due_date >= CURDATE() AND due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)');
        $stmt->execute([$selected_cafe]);
        $cashflow_total = $stmt->fetch()['total'];
        echo "<div class=\"card\"><h3>Прогноз платежей на 30 дней</h3><div class=\"metric-value\">" . format_money($cashflow_total) . " ₽</div><div class=\"muted\">Сумма всех запланированных платежей.</div></div>";
    }

    if (feature_enabled($subscription, 'cashflow_90')) {
        $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM calendar_events WHERE cafe_id = ? AND due_date >= CURDATE() AND due_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)');
        $stmt->execute([$selected_cafe]);
        $cashflow_90 = $stmt->fetch()['total'];
        echo "<div class=\"card\"><h3>Кэш‑флоу на 90 дней</h3><div class=\"metric-value\">" . format_money($cashflow_90) . " ₽</div><div class=\"muted\">Планируемые платежи на квартал.</div></div>";
    }

    if (feature_enabled($subscription, 'profit_forecast')) {
        $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id = ? AND sold_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
        $stmt->execute([$selected_cafe]);
        $month_sales = $stmt->fetch();
        $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
        $stmt->execute([$selected_cafe]);
        $month_exp = $stmt->fetch();
        $forecast_profit = $month_sales['revenue'] - $month_sales['cogs'] - $month_exp['expenses'];
        echo "<div class=\"card\"><h3>Прогноз прибыли на месяц</h3><div class=\"metric-value\">" . format_money($forecast_profit) . " ₽</div><div class=\"muted\">На основе последних 30 дней.</div></div>";
    }

    echo "<div class=\"grid grid-2\">";
    echo "<div class=\"card\"><h3>Упрощённый P&L</h3><ul class=\"data-list\"><li>Выручка <span>" . format_money($metrics['revenue']) . " ₽</span></li><li>Себестоимость <span>" . format_money($metrics['cogs']) . " ₽</span></li><li>Валовая прибыль <span>" . format_money($metrics['gross_profit']) . " ₽</span></li><li>Расходы <span>" . format_money($metrics['expenses']) . " ₽</span></li><li>Чистая прибыль <span>" . format_money($metrics['net_profit']) . " ₽</span></li></ul></div>";

    $recipes_stmt = db()->prepare('SELECT r.*, (SELECT COALESCE(SUM(s.price_total - s.cost_total),0) FROM sales s WHERE s.recipe_id = r.id) AS profit FROM recipes r WHERE r.cafe_id = ? ORDER BY profit DESC LIMIT 5');
    $recipes_stmt->execute([$selected_cafe]);
    $top = $recipes_stmt->fetchAll();
    echo "<div class=\"card\"><h3>Маржинальность по напиткам</h3>";
    if ($top) {
        echo "<ul class=\"data-list\">";
        foreach ($top as $item) {
            echo "<li>" . e($item['name']) . " <span>" . format_money($item['profit']) . " ₽</span></li>";
        }
        echo "</ul>";
    } else {
        echo "<div class=\"empty-state\">Добавьте напитки и продажи, чтобы видеть маржинальность.</div>";
    }
    echo "</div></div>";

    if (feature_enabled($subscription, 'pnl_full')) {
        $stmt = db()->prepare('SELECT category, COALESCE(SUM(amount),0) AS total FROM expenses WHERE cafe_id = ? GROUP BY category ORDER BY total DESC');
        $stmt->execute([$selected_cafe]);
        $by_category = $stmt->fetchAll();
        echo "<div class=\"card\"><h3>OPEX по категориям</h3>";
        if ($by_category) {
            echo "<ul class=\"data-list\">";
            foreach ($by_category as $row) {
                echo "<li>" . e($row['category']) . " <span>" . format_money($row['total']) . " ₽</span></li>";
            }
            echo "</ul>";
        } else {
            echo "<div class=\"muted\">Нет данных по расходам.</div>";
        }
        echo "</div>";
    }

    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'cafes') {
    $subscription = require_subscription($user);
    $stmt = db()->prepare('SELECT * FROM cafes WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $cafes = $stmt->fetchAll();
    $limit = user_cafe_limit($subscription);
    app_nav('cafes');
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Кофейни</h2><div class=\"muted\">Доступно: " . count($cafes) . " / " . $limit . "</div></div><a class=\"btn btn-primary\" href=\"#create-cafe\">Добавить кофейню</a></div>";
    if (count($cafes) < $limit) {
        echo "<div class=\"card\" id=\"create-cafe\"><form method=\"post\" action=\"/api.php?action=create_cafe\" class=\"inline-form\"><label>Название</label><input name=\"name\" required><label>Город</label><input name=\"city\" required><button class=\"btn btn-primary\" type=\"submit\">Добавить</button></form></div>";
    } else {
        echo "<div class=\"alert warning\">Достигнут лимит кофеен для вашего тарифа. Обновите тариф, чтобы добавить больше.</div>";
    }
    if ($cafes) {
        echo "<div class=\"grid grid-3\">";
        foreach ($cafes as $cafe) {
            echo "<div class=\"card\"><h3>" . e($cafe['name']) . "</h3><p class=\"muted\">" . e($cafe['city']) . "</p><a class=\"btn btn-ghost\" href=\"/index.php?page=ingredients&cafe_id=" . e((string)$cafe['id']) . "\">Открыть</a></div>";
        }
        echo "</div>";
    } else {
        echo "<div class=\"empty-state\">Пока нет кофеен. Создайте первую.</div>";
    }
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'ingredients') {
    $subscription = require_subscription($user);
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $ingredients = db()->prepare('SELECT * FROM ingredients WHERE cafe_id = ?');
    $ingredients->execute([$cafe_id]);
    $ingredients = $ingredients->fetchAll();
    app_nav('ingredients', $cafe_id);
    $selected_ingredient_id = (int)($_GET['ingredient_id'] ?? ($ingredients[0]['id'] ?? 0));
    $selected_ingredient = null;
    foreach ($ingredients as $item) {
        if ((int)$item['id'] === $selected_ingredient_id) {
            $selected_ingredient = $item;
            break;
        }
    }
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Ингредиенты — " . e($cafe['name']) . "</h2><div class=\"muted\">Средневзвешенная себестоимость и остатки</div></div><a class=\"btn btn-primary\" href=\"#add-ingredient\">Добавить ингредиент</a></div>";
    if ($ingredients) {
        echo "<div class=\"card\"><form method=\"get\" class=\"inline-form\"><input type=\"hidden\" name=\"page\" value=\"ingredients\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><label>Выберите ингредиент</label><select name=\"ingredient_id\">";
        foreach ($ingredients as $item) {
            $selected = ((int)$item['id'] === $selected_ingredient_id) ? 'selected' : '';
            echo "<option value=\"" . e((string)$item['id']) . "\" {$selected}>" . e($item['name']) . "</option>";
        }
        echo "</select><button class=\"btn btn-ghost\" type=\"submit\">Открыть</button></form></div>";
    }
    echo "<div class=\"card\" id=\"add-ingredient\"><h3>Добавить новый ингредиент</h3><form method=\"post\" action=\"/api.php?action=add_ingredient\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><label>Название</label><input name=\"name\" required><label>Ед.изм.</label><input name=\"unit\" placeholder=\"г, мл, шт\" required><label>Себестоимость за ед.</label><input name=\"cost_per_unit\" type=\"number\" step=\"0.01\" required><label>Начальный остаток</label><input name=\"stock_qty\" type=\"number\" step=\"0.001\" value=\"0\"><button class=\"btn btn-primary\" type=\"submit\">Добавить</button></form></div>";
    if ($selected_ingredient) {
        echo "<div class=\"grid grid-2\"><div class=\"card\"><h3>Редактировать ингредиент</h3><form method=\"post\" action=\"/api.php?action=update_ingredient\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"hidden\" name=\"ingredient_id\" value=\"" . e((string)$selected_ingredient['id']) . "\"><label>Название</label><input name=\"name\" value=\"" . e($selected_ingredient['name']) . "\" required><label>Ед.изм.</label><input name=\"unit\" value=\"" . e($selected_ingredient['unit']) . "\" required><label>Себестоимость за ед.</label><input name=\"cost_per_unit\" type=\"number\" step=\"0.01\" value=\"" . e($selected_ingredient['cost_per_unit']) . "\" required><label>Остаток</label><input name=\"stock_qty\" type=\"number\" step=\"0.001\" value=\"" . e($selected_ingredient['stock_qty']) . "\"><label>Минимальный остаток</label><input name=\"reorder_level\" type=\"number\" step=\"0.001\" value=\"" . e($selected_ingredient['reorder_level']) . "\"><button class=\"btn btn-primary\" type=\"submit\">Сохранить</button></form>";
        if (feature_enabled($subscription, 'unit_economics')) {
            $history_stmt = db()->prepare('SELECT cost_per_unit, recorded_at FROM ingredient_cost_history WHERE ingredient_id = ? ORDER BY recorded_at DESC LIMIT 3');
            $history_stmt->execute([$selected_ingredient['id']]);
            $history = $history_stmt->fetchAll();
            if ($history) {
                echo "<div class=\"muted\">История себестоимости:</div><ul class=\"data-list\">";
                foreach ($history as $row) {
                    echo "<li>" . format_money($row['cost_per_unit']) . " ₽ <span>" . e(date('d.m.Y', strtotime($row['recorded_at']))) . "</span></li>";
                }
                echo "</ul>";
            }
        }
        echo "</div>";
        echo "<div class=\"card\"><h3>Закупка</h3><form method=\"post\" action=\"/api.php?action=add_purchase\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"hidden\" name=\"ingredient_id\" value=\"" . e((string)$selected_ingredient['id']) . "\"><label>Кол-во</label><input name=\"qty\" type=\"number\" step=\"0.001\" required><label>Сумма закупки</label><input name=\"price_total\" type=\"number\" step=\"0.01\" required><label>Дата</label><input name=\"purchased_at\" type=\"date\" required><button class=\"btn btn-ghost\" type=\"submit\">Добавить закупку</button></form></div></div>";
    } else {
        echo "<div class=\"empty-state\">Добавьте ингредиенты, чтобы рассчитывать себестоимость напитков.</div>";
    }
    echo "<div class=\"card\"><h3>CSV импорт закупок</h3><form method=\"post\" action=\"/api.php?action=import_purchases\" enctype=\"multipart/form-data\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"file\" name=\"csv_file\" accept=\".csv\" required><button class=\"btn btn-ghost\" type=\"submit\">Загрузить CSV</button></form><div class=\"muted\">Формат: ингредиент;кол-во;сумма;дата (YYYY-MM-DD)</div></div>";
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'recipes') {
    $subscription = require_subscription($user);
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $ingredients = db()->prepare('SELECT * FROM ingredients WHERE cafe_id = ?');
    $ingredients->execute([$cafe_id]);
    $ingredients = $ingredients->fetchAll();
    $recipes = db()->prepare('SELECT * FROM recipes WHERE cafe_id = ?');
    $recipes->execute([$cafe_id]);
    $recipes = $recipes->fetchAll();
    app_nav('recipes', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Рецепты напитков — " . e($cafe['name']) . "</h2><div class=\"muted\">Себестоимость считается автоматически по ингредиентам.</div></div><div class=\"page-actions\"><a class=\"btn btn-ghost\" href=\"/index.php?page=ingredients&cafe_id=" . e((string)$cafe_id) . "\">Ингредиенты</a><a class=\"btn btn-primary\" href=\"#add-recipe\">Добавить напиток</a></div></div>";
    echo "<div class=\"card\" id=\"add-recipe\"><form method=\"post\" action=\"/api.php?action=add_recipe\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Название напитка</label><input name=\"name\" required></div><div><label>Цена продажи</label><input name=\"price\" type=\"number\" step=\"0.01\" required></div><div><label>Ингредиент</label><select name=\"ingredient_id\" required>";
    foreach ($ingredients as $ing) {
        echo "<option value=\"" . e((string)$ing['id']) . "\">" . e($ing['name']) . "</option>";
    }
    echo "</select></div><div><label>Кол-во</label><input name=\"qty\" type=\"number\" step=\"0.001\" required></div><div><button class=\"btn btn-primary\" type=\"submit\">Создать рецепт</button></div></form><div class=\"muted\">Вы можете добавить больше ингредиентов к рецепту после создания.</div></div>";

    if ($recipes) {
        echo "<div class=\"grid grid-2\">";
        foreach ($recipes as $recipe) {
            $cost = calculate_recipe_cost((int)$recipe['id']);
            echo "<div class=\"card\"><h3>" . e($recipe['name']) . "</h3><div class=\"muted\">Цена: " . format_money($recipe['price']) . " ₽</div><div class=\"price\">Себестоимость: " . format_money($cost) . " ₽</div><form method=\"post\" action=\"/api.php?action=add_recipe_item\" class=\"inline-form\"><input type=\"hidden\" name=\"recipe_id\" value=\"" . e((string)$recipe['id']) . "\"><label>Ингредиент</label><select name=\"ingredient_id\">";
            foreach ($ingredients as $ing) {
                echo "<option value=\"" . e((string)$ing['id']) . "\">" . e($ing['name']) . "</option>";
            }
            echo "</select><label>Кол-во</label><input name=\"qty\" type=\"number\" step=\"0.001\" required><button class=\"btn btn-ghost\" type=\"submit\">Добавить ингредиент</button></form></div>";
        }
        echo "</div>";
    } else {
        echo "<div class=\"empty-state\">Создайте первый напиток, чтобы считать себестоимость.</div>";
    }
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'sales') {
    $subscription = require_subscription($user);
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $recipes = db()->prepare('SELECT * FROM recipes WHERE cafe_id = ?');
    $recipes->execute([$cafe_id]);
    $recipes = $recipes->fetchAll();
    $sales = db()->prepare('SELECT s.*, r.name FROM sales s JOIN recipes r ON r.id = s.recipe_id WHERE s.cafe_id = ? ORDER BY s.sold_at DESC LIMIT 50');
    $sales->execute([$cafe_id]);
    $sales = $sales->fetchAll();
    app_nav('sales', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Продажи — " . e($cafe['name']) . "</h2><div class=\"muted\">Ручной ввод и CSV импорт</div></div><a class=\"btn btn-primary\" href=\"#add-sale\">Добавить продажу</a></div>";
    echo "<div class=\"card\" id=\"add-sale\"><form method=\"post\" action=\"/api.php?action=add_sale\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Напиток</label><select name=\"recipe_id\" required>";
    foreach ($recipes as $recipe) {
        echo "<option value=\"" . e((string)$recipe['id']) . "\">" . e($recipe['name']) . "</option>";
    }
    echo "</select></div><div><label>Кол-во</label><input name=\"qty\" type=\"number\" step=\"0.01\" required></div><div><label>Сумма продажи</label><input name=\"price_total\" type=\"number\" step=\"0.01\" required></div><div><label>Дата</label><input name=\"sold_at\" type=\"date\" required></div><div><button class=\"btn btn-primary\" type=\"submit\">Добавить продажу</button></div></form></div>";

    echo "<div class=\"card\"><h3>CSV импорт</h3><form method=\"post\" action=\"/api.php?action=import_sales\" enctype=\"multipart/form-data\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"file\" name=\"csv_file\" accept=\".csv\" required><button class=\"btn btn-ghost\" type=\"submit\">Загрузить CSV</button></form><div class=\"muted\">Формат: напиток;кол-во;сумма;дата (YYYY-MM-DD)</div></div>";
    if (feature_enabled($subscription, 'export')) {
        echo "<div class=\"card\"><h3>Экспорт</h3><a class=\"btn btn-ghost\" href=\"/api.php?action=export_sales&cafe_id=" . e((string)$cafe_id) . "\">Экспортировать продажи CSV</a></div>";
    } else {
        echo "<div class=\"card\"><h3>Экспорт</h3><div class=\"muted\">Доступно на тарифах Pro и Maxi.</div></div>";
    }

    echo "<div class=\"card\"><h3>Последние продажи</h3>";
    if ($sales) {
        echo "<table class=\"table\"><thead><tr><th>Дата</th><th>Напиток</th><th>Кол-во</th><th>Сумма</th><th>Себестоимость</th></tr></thead><tbody>";
        foreach ($sales as $sale) {
            echo "<tr><td>" . e(date('d.m.Y', strtotime($sale['sold_at']))) . "</td><td>" . e($sale['name']) . "</td><td>" . e($sale['qty']) . "</td><td>" . format_money($sale['price_total']) . " ₽</td><td>" . format_money($sale['cost_total']) . " ₽</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Нет продаж. Импортируйте CSV или добавьте вручную.</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'expenses') {
    $subscription = require_subscription($user);
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $expenses = db()->prepare('SELECT * FROM expenses WHERE cafe_id = ? ORDER BY expense_date DESC LIMIT 50');
    $expenses->execute([$cafe_id]);
    $expenses = $expenses->fetchAll();
    app_nav('expenses', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Расходы — " . e($cafe['name']) . "</h2><div class=\"muted\">OPEX, аренда, зарплаты, маркетинг</div></div><a class=\"btn btn-primary\" href=\"#add-expense\">Добавить расход</a></div>";
    $expense_categories = get_setting('expense_categories', ['Закупка', 'Аренда', 'Зарплата', 'Маркетинг', 'Коммунальные', 'Логистика', 'Оборудование', 'Прочее']);
    echo "<div class=\"card\" id=\"add-expense\"><form method=\"post\" action=\"/api.php?action=add_expense\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Категория</label><select name=\"category\" required>";
    foreach ($expense_categories as $category) {
        echo "<option value=\"" . e($category) . "\">" . e($category) . "</option>";
    }
    echo "</select></div><div><label>Сумма</label><input name=\"amount\" type=\"number\" step=\"0.01\" required></div><div><label>Дата</label><input name=\"expense_date\" type=\"date\" required></div><div><label>Комментарий</label><input name=\"comment\" placeholder=\"Например, аренда за месяц\"></div><div><button class=\"btn btn-primary\" type=\"submit\">Добавить расход</button></div></form></div>";
    if (feature_enabled($subscription, 'expense_budgets')) {
        echo "<div class=\"card\"><h3>Бюджет по категориям</h3><form method=\"post\" action=\"/api.php?action=save_expense_budget\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><label>Категория</label><select name=\"category\">";
        foreach ($expense_categories as $category) {
            echo "<option value=\"" . e($category) . "\">" . e($category) . "</option>";
        }
        echo "</select><label>Лимит в месяц</label><input name=\"monthly_limit\" type=\"number\" step=\"0.01\" required><button class=\"btn btn-ghost\" type=\"submit\">Сохранить лимит</button></form><div class=\"muted\">Используем для контроля превышений.</div></div>";
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $budget_stmt = db()->prepare('SELECT b.category, b.monthly_limit, COALESCE(SUM(e.amount),0) AS spent FROM expense_budgets b LEFT JOIN expenses e ON e.cafe_id = b.cafe_id AND e.category = b.category AND e.expense_date BETWEEN ? AND ? WHERE b.cafe_id = ? GROUP BY b.id, b.category, b.monthly_limit ORDER BY b.category');
        $budget_stmt->execute([$monthStart, $monthEnd, $cafe_id]);
        $budgets = $budget_stmt->fetchAll();
        echo "<div class=\"card\"><h3>Контроль бюджета</h3>";
        if ($budgets) {
            echo "<table class=\"table\"><thead><tr><th>Категория</th><th>Лимит</th><th>Факт</th><th>%</th><th>Статус</th></tr></thead><tbody>";
            foreach ($budgets as $row) {
                $limit = (float)$row['monthly_limit'];
                $spent = (float)$row['spent'];
                $percent = $limit > 0 ? ($spent / $limit) * 100 : 0;
                $status = $limit > 0 && $spent > $limit ? 'Превышен' : 'В норме';
                echo "<tr><td>" . e($row['category']) . "</td><td>" . format_money($limit) . " ₽</td><td>" . format_money($spent) . " ₽</td><td>" . number_format($percent, 0) . "%</td><td>" . e($status) . "</td></tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class=\"empty-state\">Сначала задайте лимиты по категориям.</div>";
        }
        echo "</div>";
    }
    echo "<div class=\"card\"><h3>CSV импорт расходов</h3><form method=\"post\" action=\"/api.php?action=import_expenses\" enctype=\"multipart/form-data\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"file\" name=\"csv_file\" accept=\".csv\" required><button class=\"btn btn-ghost\" type=\"submit\">Загрузить CSV</button></form><div class=\"muted\">Формат: категория;сумма;дата (YYYY-MM-DD)</div></div>";

    echo "<div class=\"card\"><h3>Последние расходы</h3>";
    if ($expenses) {
        echo "<table class=\"table\"><thead><tr><th>Дата</th><th>Категория</th><th>Комментарий</th><th>Сумма</th></tr></thead><tbody>";
        foreach ($expenses as $exp) {
            echo "<tr><td>" . e(date('d.m.Y', strtotime($exp['expense_date']))) . "</td><td>" . e($exp['category']) . "</td><td>" . e($exp['comment'] ?? '') . "</td><td>" . format_money($exp['amount']) . " ₽</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Пока нет расходов. Добавьте вручную или импортируйте CSV.</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'cash_shifts') {
    $subscription = require_subscription($user);
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $shifts_stmt = db()->prepare('SELECT * FROM cash_shifts WHERE cafe_id = ? ORDER BY shift_date DESC LIMIT 30');
    $shifts_stmt->execute([$cafe_id]);
    $shifts = $shifts_stmt->fetchAll();
    app_nav('cash_shifts', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Контроль кассы — " . e($cafe['name']) . "</h2><div class=\"muted\">Смены, расхождения и дисциплина наличных</div></div><a class=\"btn btn-primary\" href=\"#add-shift\">Добавить смену</a></div>";
    echo "<div class=\"card\" id=\"add-shift\"><form method=\"post\" action=\"/api.php?action=add_cash_shift\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Дата</label><input type=\"date\" name=\"shift_date\" required></div><div><label>Открытие кассы</label><input type=\"number\" step=\"0.01\" name=\"opening_cash\" required></div><div><label>Закрытие кассы</label><input type=\"number\" step=\"0.01\" name=\"closing_cash\" required></div><div><label>Наличные продажи</label><input type=\"number\" step=\"0.01\" name=\"cash_sales\" required></div><div><button class=\"btn btn-primary\" type=\"submit\">Сохранить смену</button></div></form><div class=\"muted\">Шаблон CSV: <a href=\"/api.php?action=download_template&type=cash_shifts\">скачать</a></div></div>";
    echo "<div class=\"card\"><h3>Последние смены</h3>";
    if ($shifts) {
        echo "<table class=\"table\"><thead><tr><th>Дата</th><th>Открытие</th><th>Закрытие</th><th>Наличные продажи</th><th>Расхождение</th></tr></thead><tbody>";
        foreach ($shifts as $shift) {
            echo "<tr><td>" . e(date('d.m.Y', strtotime($shift['shift_date']))) . "</td><td>" . format_money($shift['opening_cash']) . " ₽</td><td>" . format_money($shift['closing_cash']) . " ₽</td><td>" . format_money($shift['cash_sales']) . " ₽</td><td>" . format_money($shift['difference']) . " ₽</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Пока нет смен. Добавьте первую.</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'plan_fact') {
    $subscription = require_subscription($user);
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $targets_stmt = db()->prepare('SELECT * FROM plan_fact_targets WHERE cafe_id = ? ORDER BY period_start DESC');
    $targets_stmt->execute([$cafe_id]);
    $targets = $targets_stmt->fetchAll();
    app_nav('plan_fact', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>План-факт — " . e($cafe['name']) . "</h2><div class=\"muted\">Контроль исполнения целей по выручке и прибыли</div></div><a class=\"btn btn-primary\" href=\"#add-plan\">Добавить план</a></div>";
    echo "<div class=\"card\" id=\"add-plan\"><form method=\"post\" action=\"/api.php?action=add_plan_fact\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Период с</label><input type=\"date\" name=\"period_start\" required></div><div><label>Период до</label><input type=\"date\" name=\"period_end\" required></div><div><label>План выручки</label><input type=\"number\" step=\"0.01\" name=\"target_revenue\" required></div><div><label>План прибыли</label><input type=\"number\" step=\"0.01\" name=\"target_profit\" required></div><div><button class=\"btn btn-primary\" type=\"submit\">Сохранить план</button></div></form></div>";
    echo "<div class=\"card\"><h3>План-факт таблица</h3>";
    if ($targets) {
        echo "<table class=\"table\"><thead><tr><th>Период</th><th>План выручки</th><th>Факт выручки</th><th>План прибыли</th><th>Факт прибыли</th></tr></thead><tbody>";
        foreach ($targets as $target) {
            $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id = ? AND sold_at BETWEEN ? AND ?');
            $stmt->execute([$cafe_id, $target['period_start'], $target['period_end']]);
            $sales = $stmt->fetch();
            $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id = ? AND expense_date BETWEEN ? AND ?');
            $stmt->execute([$cafe_id, $target['period_start'], $target['period_end']]);
            $exp = $stmt->fetch();
            $gross_profit = $sales['revenue'] - $sales['cogs'];
            $net_profit = $gross_profit - $exp['expenses'];
            echo "<tr><td>" . e(date('d.m.Y', strtotime($target['period_start']))) . " – " . e(date('d.m.Y', strtotime($target['period_end']))) . "</td><td>" . format_money($target['target_revenue']) . " ₽</td><td>" . format_money($sales['revenue']) . " ₽</td><td>" . format_money($target['target_profit']) . " ₽</td><td>" . format_money($net_profit) . " ₽</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Добавьте план, чтобы сравнивать с фактом.</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'abc_xyz') {
    $subscription = require_subscription($user);
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $stmt = db()->prepare('SELECT r.id, r.name, COALESCE(SUM(s.price_total),0) AS revenue, COALESCE(SUM(s.qty),0) AS qty FROM recipes r LEFT JOIN sales s ON s.recipe_id = r.id WHERE r.cafe_id = ? GROUP BY r.id');
    $stmt->execute([$cafe_id]);
    $items = $stmt->fetchAll();
    usort($items, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
    $total_revenue = array_sum(array_column($items, 'revenue'));
    $days_stmt = db()->prepare('SELECT recipe_id, sold_at, SUM(qty) AS qty FROM sales WHERE cafe_id = ? AND sold_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY recipe_id, sold_at');
    $days_stmt->execute([$cafe_id]);
    $day_map = [];
    foreach ($days_stmt->fetchAll() as $row) {
        $day_map[$row['recipe_id']][] = (float)$row['qty'];
    }
    app_nav('abc_xyz', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>ABC/XYZ анализ — " . e($cafe['name']) . "</h2><div class=\"muted\">Выделите ключевые напитки и прогнозируемость спроса</div></div></div>";
    echo "<div class=\"card\"><table class=\"table\"><thead><tr><th>Напиток</th><th>Выручка</th><th>Доля</th><th>ABC</th><th>XYZ</th></tr></thead><tbody>";
    $cumulative = 0;
    foreach ($items as $item) {
        $share = $total_revenue > 0 ? ($item['revenue'] / $total_revenue) * 100 : 0;
        $cumulative += $share;
        $abc = $cumulative <= 80 ? 'A' : ($cumulative <= 95 ? 'B' : 'C');
        $series = $day_map[$item['id']] ?? [];
        $mean = $series ? array_sum($series) / count($series) : 0;
        $variance = 0;
        if ($mean > 0 && $series) {
            foreach ($series as $value) {
                $variance += pow($value - $mean, 2);
            }
            $variance /= count($series);
        }
        $cv = $mean > 0 ? sqrt($variance) / $mean : 0;
        $xyz = $cv <= 0.5 ? 'X' : ($cv <= 1.0 ? 'Y' : 'Z');
        echo "<tr><td>" . e($item['name']) . "</td><td>" . format_money($item['revenue']) . " ₽</td><td>" . number_format($share, 1, ',', ' ') . "%</td><td>" . $abc . "</td><td>" . $xyz . "</td></tr>";
    }
    echo "</tbody></table></div></main>";
    if (feature_enabled($subscription, 'abc_visuals')) {
        echo "<div class=\"card\"><h3>Визуализация ABC</h3><div class=\"chart-foot\">Доля выручки по напиткам</div><div class=\"abc-bars\">";
        foreach (array_slice($items, 0, 5) as $item) {
            $width = $total_revenue > 0 ? ($item['revenue'] / $total_revenue) * 100 : 0;
            echo "<div class=\"abc-bar\"><span>" . e($item['name']) . "</span><div class=\"bar\" style=\"width:" . number_format($width, 1, '.', '') . "%\"></div></div>";
        }
        echo "</div></div>";
    }
    page_footer();
    exit;
}

if ($page === 'staff') {
    $subscription = require_subscription($user);
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $staff_stmt = db()->prepare('SELECT * FROM staff WHERE cafe_id = ?');
    $staff_stmt->execute([$cafe_id]);
    $staff = $staff_stmt->fetchAll();
    $payroll_stmt = db()->prepare('SELECT COALESCE(SUM(ss.hours * s.hourly_rate),0) AS payroll FROM staff_shifts ss JOIN staff s ON s.id = ss.staff_id WHERE s.cafe_id = ?');
    $payroll_stmt->execute([$cafe_id]);
    $payroll = $payroll_stmt->fetch()['payroll'];
    app_nav('staff', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Персонал — " . e($cafe['name']) . "</h2><div class=\"muted\">Фонд оплаты труда и часы сотрудников</div></div><a class=\"btn btn-primary\" href=\"#add-staff\">Добавить сотрудника</a></div>";
    echo "<div class=\"card\" id=\"add-staff\"><form method=\"post\" action=\"/api.php?action=add_staff\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Имя</label><input name=\"name\" required></div><div><label>Должность</label><input name=\"role\" required></div><div><label>Ставка (₽/час)</label><input name=\"hourly_rate\" type=\"number\" step=\"0.01\" required></div><div><button class=\"btn btn-primary\" type=\"submit\">Сохранить</button></div></form></div>";
    echo "<div class=\"card\"><h3>Сотрудники</h3>";
    if ($staff) {
        echo "<table class=\"table\"><thead><tr><th>Имя</th><th>Должность</th><th>Ставка</th><th>Смена</th></tr></thead><tbody>";
        foreach ($staff as $person) {
            echo "<tr><td>" . e($person['name']) . "</td><td>" . e($person['role']) . "</td><td>" . format_money($person['hourly_rate']) . " ₽</td><td><form method=\"post\" action=\"/api.php?action=add_staff_shift\" class=\"inline-form\"><input type=\"hidden\" name=\"staff_id\" value=\"" . e((string)$person['id']) . "\"><input type=\"date\" name=\"shift_date\" required><input type=\"number\" step=\"0.1\" name=\"hours\" placeholder=\"Часы\" required><button class=\"btn btn-ghost\" type=\"submit\">Добавить</button></form></td></tr>";
        }
        echo "</tbody></table>";
        echo "<div class=\"muted\">ФОТ за период: " . format_money($payroll) . " ₽</div>";
        if (feature_enabled($subscription, 'staff_efficiency')) {
            $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue FROM sales WHERE cafe_id = ? AND sold_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
            $stmt->execute([$cafe_id]);
            $revenue = $stmt->fetch()['revenue'];
            $stmt = db()->prepare('SELECT COALESCE(SUM(ss.hours),0) AS hours FROM staff_shifts ss JOIN staff s ON s.id = ss.staff_id WHERE s.cafe_id = ? AND ss.shift_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
            $stmt->execute([$cafe_id]);
            $hours = $stmt->fetch()['hours'];
            $per_hour = $hours > 0 ? $revenue / $hours : 0;
            echo "<div class=\"card\"><h3>Эффективность персонала</h3><div class=\"metric-value\">" . format_money($per_hour) . " ₽/час</div><div class=\"muted\">За последние 30 дней.</div></div>";
        }
    } else {
        echo "<div class=\"empty-state\">Добавьте сотрудников, чтобы считать фонд оплаты труда.</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'writeoffs') {
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'writeoffs')) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Списания доступны на тарифах Pro и Maxi.</div></main>";
        page_footer();
        exit;
    }
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $ingredients = db()->prepare('SELECT * FROM ingredients WHERE cafe_id = ?');
    $ingredients->execute([$cafe_id]);
    $ingredients = $ingredients->fetchAll();
    $writeoffs = db()->prepare('SELECT w.*, i.name FROM writeoffs w JOIN ingredients i ON i.id = w.ingredient_id WHERE w.cafe_id = ? ORDER BY w.writeoff_date DESC LIMIT 50');
    $writeoffs->execute([$cafe_id]);
    $writeoffs = $writeoffs->fetchAll();
    app_nav('writeoffs', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Списания — " . e($cafe['name']) . "</h2><div class=\"muted\">Учёт потерь и списаний</div></div><a class=\"btn btn-primary\" href=\"#add-writeoff\">Добавить списание</a></div>";
    echo "<div class=\"card\" id=\"add-writeoff\"><form method=\"post\" action=\"/api.php?action=add_writeoff\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Ингредиент</label><select name=\"ingredient_id\">";
    foreach ($ingredients as $item) {
        echo "<option value=\"" . e((string)$item['id']) . "\">" . e($item['name']) . "</option>";
    }
    echo "</select></div><div><label>Кол-во</label><input name=\"qty\" type=\"number\" step=\"0.001\" required></div><div><label>Причина</label><input name=\"reason\" placeholder=\"Порча/просрочка\"></div><div><label>Дата</label><input name=\"writeoff_date\" type=\"date\" required></div><div><button class=\"btn btn-primary\" type=\"submit\">Сохранить</button></div></form></div>";
    echo "<div class=\"card\"><h3>Последние списания</h3>";
    if ($writeoffs) {
        echo "<table class=\"table\"><thead><tr><th>Дата</th><th>Ингредиент</th><th>Кол-во</th><th>Причина</th></tr></thead><tbody>";
        foreach ($writeoffs as $row) {
            echo "<tr><td>" . e(date('d.m.Y', strtotime($row['writeoff_date']))) . "</td><td>" . e($row['name']) . "</td><td>" . e($row['qty']) . "</td><td>" . e($row['reason']) . "</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Пока нет списаний.</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'checklist') {
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'daily_checklist')) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Чек‑лист доступен на тарифах Pro и Maxi.</div></main>";
        page_footer();
        exit;
    }
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $date = $_GET['date'] ?? date('Y-m-d');
    $items_stmt = db()->prepare('SELECT * FROM daily_checklist WHERE cafe_id = ? AND checklist_date = ? ORDER BY created_at DESC');
    $items_stmt->execute([$cafe_id, $date]);
    $items = $items_stmt->fetchAll();
    $templates_stmt = db()->prepare('SELECT * FROM checklist_templates WHERE cafe_id = ? ORDER BY created_at DESC');
    $templates_stmt->execute([$cafe_id]);
    $templates = $templates_stmt->fetchAll();
    app_nav('checklist', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Ежедневный чек‑лист — " . e($cafe['name']) . "</h2><div class=\"muted\">Контроль ключевых задач дня</div></div></div>";
    echo "<div class=\"card\"><form method=\"get\" class=\"inline-form\"><input type=\"hidden\" name=\"page\" value=\"checklist\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><label>Дата</label><input type=\"date\" name=\"date\" value=\"" . e($date) . "\"><button class=\"btn btn-ghost\" type=\"submit\">Показать</button></form></div>";
    echo "<div class=\"card\"><h3>Добавить задачу</h3><form method=\"post\" action=\"/api.php?action=add_checklist_item\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"hidden\" name=\"checklist_date\" value=\"" . e($date) . "\"><label>Задача</label><input name=\"item\" required><button class=\"btn btn-primary\" type=\"submit\">Добавить</button></form></div>";
    echo "<div class=\"card\"><h3>Шаблоны задач</h3><div class=\"muted\">Используйте шаблоны, чтобы быстро сформировать чек‑лист на день.</div>";
    echo "<form method=\"post\" action=\"/api.php?action=add_checklist_template\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><label>Новая задача</label><input name=\"item\" required><button class=\"btn btn-ghost\" type=\"submit\">Добавить в шаблон</button></form>";
    echo "<form method=\"post\" action=\"/api.php?action=generate_checklist\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"hidden\" name=\"checklist_date\" value=\"" . e($date) . "\"><button class=\"btn btn-primary\" type=\"submit\">Сформировать на дату</button></form>";
    if ($templates) {
        echo "<ul class=\"data-list\">";
        foreach ($templates as $template) {
            echo "<li>" . e($template['item']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<div class=\"empty-state\">Шаблонов пока нет.</div>";
    }
    echo "</div>";
    echo "<div class=\"card\"><h3>Список</h3>";
    if ($items) {
        echo "<table class=\"table\"><thead><tr><th>Статус</th><th>Задача</th><th>Действие</th></tr></thead><tbody>";
        foreach ($items as $item) {
            $status = $item['is_done'] ? 'Выполнено' : 'В работе';
            echo "<tr><td>" . e($status) . "</td><td>" . e($item['item']) . "</td><td><form method=\"post\" action=\"/api.php?action=toggle_checklist_item\"><input type=\"hidden\" name=\"item_id\" value=\"" . e((string)$item['id']) . "\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><button class=\"btn btn-ghost\" type=\"submit\">Переключить</button></form></td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Задач на выбранную дату нет.</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'imports') {
    app_nav('imports');
    $cafes_stmt = db()->prepare('SELECT * FROM cafes WHERE user_id = ?');
    $cafes_stmt->execute([$user['id']]);
    $cafes = $cafes_stmt->fetchAll();
    $preview = !empty($_GET['preview']) ? ($_SESSION['import_preview']['rows'] ?? []) : [];
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Импорт данных</h2><div class=\"muted\">Понятные варианты загрузки продаж, расходов и закупок</div></div></div>";
    echo "<div class=\"grid grid-3\"><div class=\"card\"><h3>Быстрый старт</h3><p class=\"muted\">Скачайте шаблоны и заполните в Excel или выгрузите из кассы.</p><div class=\"stack\"><a class=\"btn btn-ghost\" href=\"/api.php?action=download_template&type=sales\">Шаблон продаж</a><a class=\"btn btn-ghost\" href=\"/api.php?action=download_template&type=expenses\">Шаблон расходов</a><a class=\"btn btn-ghost\" href=\"/api.php?action=download_template&type=purchases\">Шаблон закупок</a></div></div><div class=\"card\"><h3>Импорт продаж (с проверкой)</h3><p class=\"muted\">Сначала просмотрим первые строки, затем подтвердим.</p><form method=\"post\" action=\"/api.php?action=import_sales_preview\" enctype=\"multipart/form-data\" class=\"inline-form\"><label>Кофейня</label><select name=\"cafe_id\">";
    foreach ($cafes as $cafe) {
        echo "<option value=\"" . e((string)$cafe['id']) . "\">" . e($cafe['name']) . "</option>";
    }
    echo "</select><input type=\"file\" name=\"csv_file\" accept=\".csv\" required><button class=\"btn btn-primary\" type=\"submit\">Проверить</button></form>";
    if ($preview) {
        echo "<div class=\"muted\">Предпросмотр (до 10 строк):</div><table class=\"table\"><thead><tr><th>Напиток</th><th>Кол-во</th><th>Сумма</th><th>Дата</th></tr></thead><tbody>";
        foreach (array_slice($preview, 0, 10) as $row) {
            echo "<tr><td>" . e($row[0] ?? '') . "</td><td>" . e($row[1] ?? '') . "</td><td>" . e($row[2] ?? '') . "</td><td>" . e($row[3] ?? '') . "</td></tr>";
        }
        echo "</tbody></table><form method=\"post\" action=\"/api.php?action=confirm_sales_import\"><button class=\"btn btn-primary\" type=\"submit\">Подтвердить импорт</button></form>";
    }
    echo "</div><div class=\"card\"><h3>Импорт расходов</h3><p class=\"muted\">Поддерживает категории, суммы, даты и комментарии.</p><form method=\"post\" action=\"/api.php?action=import_expenses\" enctype=\"multipart/form-data\" class=\"inline-form\"><label>Кофейня</label><select name=\"cafe_id\">";
    foreach ($cafes as $cafe) {
        echo "<option value=\"" . e((string)$cafe['id']) . "\">" . e($cafe['name']) . "</option>";
    }
    echo "</select><input type=\"file\" name=\"csv_file\" accept=\".csv\" required><button class=\"btn btn-primary\" type=\"submit\">Импортировать</button></form></div></div>";
    echo "<div class=\"grid grid-2\"><div class=\"card\"><h3>Импорт закупок</h3><p class=\"muted\">Обновляйте себестоимость и остатки ингредиентов.</p><form method=\"post\" action=\"/api.php?action=import_purchases\" enctype=\"multipart/form-data\" class=\"inline-form\"><label>Кофейня</label><select name=\"cafe_id\">";
    foreach ($cafes as $cafe) {
        echo "<option value=\"" . e((string)$cafe['id']) . "\">" . e($cafe['name']) . "</option>";
    }
    echo "</select><input type=\"file\" name=\"csv_file\" accept=\".csv\" required><button class=\"btn btn-primary\" type=\"submit\">Импортировать</button></form></div><div class=\"card\"><h3>Подсказки по формату</h3><ul class=\"data-list\"><li>Разделитель — точка с запятой</li><li>Дата в формате YYYY-MM-DD</li><li>Имена напитков должны совпадать с рецептами</li><li>Расходы могут содержать комментарии</li></ul></div></div>";
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'calendar') {
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'smart_calendar')) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Умный календарь доступен на тарифах Pro и Maxi.</div></main>";
        page_footer();
        exit;
    }
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    $events_stmt = db()->prepare('SELECT * FROM calendar_events WHERE cafe_id = ? AND due_date >= CURDATE() ORDER BY due_date ASC LIMIT 30');
    $events_stmt->execute([$cafe_id]);
    $events = $events_stmt->fetchAll();
    $reminders = [];
    $today = new DateTime();
    $in_five = (clone $today)->modify('+5 days')->format('Y-m-d');
    foreach ($events as $event) {
        if ($event['due_date'] <= $in_five) {
            $reminders[] = "Через несколько дней " . lower_text($event['title']) . " (" . date('d.m', strtotime($event['due_date'])) . ").";
        }
    }
    $events_by_date = [];
    foreach ($events as $event) {
        $events_by_date[$event['due_date']][] = $event;
    }
    $month_stmt = db()->prepare('SELECT DATE_FORMAT(expense_date, \"%Y-%m\") AS month, SUM(amount) AS total FROM expenses WHERE cafe_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 4 MONTH) GROUP BY month ORDER BY month DESC');
    $month_stmt->execute([$cafe_id]);
    $months = $month_stmt->fetchAll();
    $current_month = $months[0]['total'] ?? 0;
    $previous = array_slice($months, 1);
    $avg = 0;
    if ($previous) {
        $avg = array_sum(array_column($previous, 'total')) / count($previous);
    }
    if ($avg > 0 && $current_month > $avg * 1.15) {
        $reminders[] = 'В этом месяце расходы выше нормы на ' . number_format((($current_month / $avg) - 1) * 100, 0) . '%.';
    }
    $subscription = active_subscription((int)$user['id']);
    if ($subscription && strtotime($subscription['ends_at']) <= strtotime('+5 days')) {
        $reminders[] = 'Доступ заканчивается ' . date('d.m', strtotime($subscription['ends_at'])) . '. Продлите тариф заранее.';
    }
    app_nav('calendar', $cafe_id);
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Умный календарь — " . e($cafe['name']) . "</h2><div class=\"muted\">Платежи, аренда, зарплаты и налоги в одном месте</div></div><a class=\"btn btn-primary\" href=\"#add-event\">Добавить событие</a></div>";
    echo "<div class=\"grid grid-3\"><div class=\"card metric\"><div class=\"metric-title\">Событий на 30 дней</div><div class=\"metric-value\">" . e((string)count($events)) . "</div></div>";
    $sum_stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM calendar_events WHERE cafe_id = ? AND due_date >= CURDATE() AND due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)');
    $sum_stmt->execute([$cafe_id]);
    $sum_total = $sum_stmt->fetch()['total'];
    echo "<div class=\"card metric\"><div class=\"metric-title\">Платежи на 30 дней</div><div class=\"metric-value\">" . format_money($sum_total) . " ₽</div></div>";
    echo "<div class=\"card metric\"><div class=\"metric-title\">Ближайшее событие</div><div class=\"metric-value\">" . ($events ? e(date('d.m', strtotime($events[0]['due_date']))) : '—') . "</div></div></div>";
    if (feature_enabled($subscription, 'smart_reminders')) {
        echo "<div class=\"card\"><h3>Напоминания</h3>";
        if ($reminders) {
            echo "<ul class=\"data-list\">";
            foreach ($reminders as $reminder) {
                echo "<li>" . e($reminder) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class=\"muted\">Пока нет новых напоминаний.</div>";
        }
        echo "</div>";
    }
    $month_start = new DateTime('first day of this month');
    $days_in_month = (int)$month_start->format('t');
    $start_weekday = (int)$month_start->format('N');
    $today_key = date('Y-m-d');
    echo "<div class=\"card calendar-card\"><div class=\"calendar-head\"><div><h3>Календарь на месяц</h3><div class=\"muted\">" . e($month_start->format('F Y')) . "</div></div><a class=\"btn btn-ghost\" href=\"#add-event\">Добавить событие</a></div>";
    echo "<div class=\"calendar-grid\">";
    foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $weekday) {
        echo "<div class=\"calendar-cell calendar-label\">" . e($weekday) . "</div>";
    }
    for ($i = 1; $i < $start_weekday; $i++) {
        echo "<div class=\"calendar-cell calendar-empty\"></div>";
    }
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date_key = $month_start->format('Y-m-') . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
        $is_today = $date_key === $today_key ? ' calendar-today' : '';
        $count = isset($events_by_date[$date_key]) ? count($events_by_date[$date_key]) : 0;
        echo "<div class=\"calendar-cell{$is_today}\"><div class=\"calendar-day\">" . e((string)$day) . "</div>";
        if ($count > 0) {
            echo "<div class=\"calendar-count\">Событий: " . e((string)$count) . "</div>";
        } else {
            echo "<div class=\"calendar-muted\">Нет событий</div>";
        }
        echo "</div>";
    }
    echo "</div></div>";
    echo "<div class=\"card\" id=\"add-event\"><h3>Добавить регулярный платеж</h3><form method=\"post\" action=\"/api.php?action=add_calendar_event\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Тип</label><select name=\"event_type\"><option value=\"rent\">Аренда</option><option value=\"salary\">Зарплата</option><option value=\"tax\">Налоги</option><option value=\"payment\">Платеж</option><option value=\"custom\">Другое</option></select></div><div><label>Название</label><input name=\"title\" required></div><div><label>Сумма</label><input name=\"amount\" type=\"number\" step=\"0.01\"></div><div><label>Дата</label><input name=\"due_date\" type=\"date\" required></div><div><button class=\"btn btn-primary\" type=\"submit\">Сохранить</button></div></form><div class=\"muted\">События сохраняются и напомнят вам заранее.</div></div>";
    echo "<div class=\"card\"><h3>Ближайшие события</h3>";
    if ($events) {
        echo "<table class=\"table\"><thead><tr><th>Дата</th><th>Тип</th><th>Название</th><th>Сумма</th></tr></thead><tbody>";
        foreach ($events as $event) {
            echo "<tr><td>" . e(date('d.m.Y', strtotime($event['due_date']))) . "</td><td>" . e($event['event_type']) . "</td><td>" . e($event['title']) . "</td><td>" . ($event['amount'] ? format_money($event['amount']) . ' ₽' : '—') . "</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Добавьте события, чтобы календарь начал работать.</div>";
    }
    echo "</div></main>";
    page_footer();
    exit;
}

if ($page === 'benchmarks') {
    app_nav('benchmarks');
    $benchmarks = [
        ['Маржа по напиткам', '60–70%', 'Ориентир для кофеен в РФ'],
        ['Доля аренды', '10–18%', 'От выручки'],
        ['ФОТ', '18–25%', 'Включая налоги'],
        ['Себестоимость молока', 'до 25%', 'В структуре рецепта'],
    ];
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Рыночные бенчмарки</h2><div class=\"muted\">Сравните свои показатели с ориентиром рынка</div></div></div>";
    echo "<div class=\"card\"><table class=\"table\"><thead><tr><th>Показатель</th><th>Ориентир</th><th>Комментарий</th></tr></thead><tbody>";
    foreach ($benchmarks as $row) {
        echo "<tr><td>" . e($row[0]) . "</td><td>" . e($row[1]) . "</td><td>" . e($row[2]) . "</td></tr>";
    }
    echo "</tbody></table></div></main>";
    page_footer();
    exit;
}

if ($page === 'ceo_dashboard') {
    $subscription = require_subscription($user);
    $cafes_stmt = db()->prepare('SELECT * FROM cafes WHERE user_id = ?');
    $cafes_stmt->execute([$user['id']]);
    $cafes = $cafes_stmt->fetchAll();
    $cafe_ids = array_column($cafes, 'id');
    $total_revenue = 0;
    $total_cogs = 0;
    $total_expenses = 0;
    if ($cafe_ids) {
        $placeholders = implode(',', array_fill(0, count($cafe_ids), '?'));
        $stmt = db()->prepare("SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id IN ({$placeholders})");
        $stmt->execute($cafe_ids);
        $sales = $stmt->fetch();
        $total_revenue = $sales['revenue'];
        $total_cogs = $sales['cogs'];
        $stmt = db()->prepare("SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id IN ({$placeholders})");
        $stmt->execute($cafe_ids);
        $total_expenses = $stmt->fetch()['expenses'];
    }
    $gross_profit = $total_revenue - $total_cogs;
    $net_profit = $gross_profit - $total_expenses;
    $margin = $total_revenue > 0 ? ($gross_profit / $total_revenue) * 100 : 0;
    app_nav('ceo_dashboard');
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Сводка владельца</h2><div class=\"muted\">Все кофейни в одном экране</div></div><a class=\"btn btn-primary\" href=\"/index.php?page=cafes\">Управлять кофейнями</a></div>";
    echo "<div class=\"grid grid-4 metrics\"><div class=\"card metric\"><div class=\"metric-title\">Выручка</div><div class=\"metric-value\">" . format_money($total_revenue) . " ₽</div></div><div class=\"card metric\"><div class=\"metric-title\">Себестоимость</div><div class=\"metric-value\">" . format_money($total_cogs) . " ₽</div></div><div class=\"card metric\"><div class=\"metric-title\">Валовая прибыль</div><div class=\"metric-value\">" . format_money($gross_profit) . " ₽</div><div class=\"muted\">Маржа " . number_format($margin, 1, ',', ' ') . "%</div></div><div class=\"card metric\"><div class=\"metric-title\">Чистая прибыль</div><div class=\"metric-value\">" . format_money($net_profit) . " ₽</div></div></div>";
    if ($cafes) {
        echo "<div class=\"card\"><h3>Сводка по кофейням</h3><table class=\"table\"><thead><tr><th>Кофейня</th><th>Выручка</th><th>Прибыль</th><th>Маржа</th></tr></thead><tbody>";
        foreach ($cafes as $cafe) {
            $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id = ?');
            $stmt->execute([$cafe['id']]);
            $sales = $stmt->fetch();
            $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id = ?');
            $stmt->execute([$cafe['id']]);
            $exp = $stmt->fetch();
            $gp = $sales['revenue'] - $sales['cogs'];
            $np = $gp - $exp['expenses'];
            $m = $sales['revenue'] > 0 ? ($gp / $sales['revenue']) * 100 : 0;
            echo "<tr><td>" . e($cafe['name']) . "</td><td>" . format_money($sales['revenue']) . " ₽</td><td>" . format_money($np) . " ₽</td><td>" . number_format($m, 1, ',', ' ') . "%</td></tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class=\"empty-state\">Добавьте первую кофейню, чтобы увидеть сводку.</div>";
    }
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'health') {
    $subscription = require_subscription($user);
    $cafes_stmt = db()->prepare('SELECT * FROM cafes WHERE user_id = ?');
    $cafes_stmt->execute([$user['id']]);
    $cafes = $cafes_stmt->fetchAll();
    app_nav('health');
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Здоровье бизнеса</h2><div class=\"muted\">Критичные сигналы, требующие внимания</div></div></div>";
    if ($cafes) {
        echo "<div class=\"card\"><table class=\"table\"><thead><tr><th>Кофейня</th><th>Сигналы</th><th>Комментарий</th></tr></thead><tbody>";
        foreach ($cafes as $cafe) {
            $alerts = [];
            $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id = ?');
            $stmt->execute([$cafe['id']]);
            $sales = $stmt->fetch();
            $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id = ?');
            $stmt->execute([$cafe['id']]);
            $exp = $stmt->fetch();
            $gross_profit = $sales['revenue'] - $sales['cogs'];
            $margin = $sales['revenue'] > 0 ? ($gross_profit / $sales['revenue']) * 100 : 0;
            if ($margin > 0 && $margin < 55) {
                $alerts[] = 'Низкая маржа';
            }
            if ($gross_profit - $exp['expenses'] < 0) {
                $alerts[] = 'Убыток';
            }
            $stmt = db()->prepare('SELECT COALESCE(SUM(ABS(difference)),0) AS diff FROM cash_shifts WHERE cafe_id = ? AND shift_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
            $stmt->execute([$cafe['id']]);
            $diff = $stmt->fetch()['diff'];
            if ($diff > 0) {
                $alerts[] = 'Расхождения по кассе';
            }
            if (feature_enabled($subscription, 'anomaly_radar')) {
                $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue FROM sales WHERE cafe_id = ? AND sold_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)');
                $stmt->execute([$cafe['id']]);
                $week_now = $stmt->fetch()['revenue'];
                $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue FROM sales WHERE cafe_id = ? AND sold_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)');
                $stmt->execute([$cafe['id']]);
                $week_prev = $stmt->fetch()['revenue'];
                if ($week_prev > 0 && $week_now < $week_prev * 0.8) {
                    $alerts[] = 'Падение выручки';
                }
            }
            if (feature_enabled($subscription, 'benchmark_gap')) {
                if ($margin > 0 && $margin < 60) {
                    $alerts[] = 'Маржа ниже рынка';
                }
            }
            $status = $alerts ? render_badge(implode(', ', $alerts), 'badge') : render_badge('Стабильно', 'badge');
            $comment = $alerts ? 'Нужна проверка цен, расходов или кассы.' : 'Отклонений не выявлено.';
            echo "<tr><td>" . e($cafe['name']) . "</td><td>" . $status . "</td><td>" . e($comment) . "</td></tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class=\"empty-state\">Нет кофеен для анализа.</div>";
    }
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'analytics') {
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'analytics_periods')) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Аналитика по периодам доступна только на тарифах Pro и Maxi.</div></main>";
        page_footer();
        exit;
    }
    $cafe_id = resolve_cafe_id($user, isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : null);
    $cafe_stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $cafe_stmt->execute([$cafe_id, $user['id']]);
    $cafe = $cafe_stmt->fetch();
    if (!$cafe) {
        echo "<main class=\"container section\"><div class=\"alert warning\">Кофейня не найдена.</div></main>";
        page_footer();
        exit;
    }
    app_nav('analytics', $cafe_id);
    $period = $_GET['period'] ?? 'month';
    $days = $period === 'day' ? 1 : ($period === 'week' ? 7 : 30);
    $start = (new DateTime())->modify("-{$days} days")->format('Y-m-d');
    $prev_start = (new DateTime())->modify("-" . ($days * 2) . " days")->format('Y-m-d');
    $prev_end = (new DateTime())->modify("-{$days} days")->format('Y-m-d');
    $stmt = db()->prepare("SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id = ? AND sold_at >= ?");
    $stmt->execute([$cafe_id, $start]);
    $sales = $stmt->fetch();
    $stmt = db()->prepare("SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id = ? AND expense_date >= ?");
    $stmt->execute([$cafe_id, $start]);
    $exp = $stmt->fetch();
    $gross_profit = $sales['revenue'] - $sales['cogs'];
    $net_profit = $gross_profit - $exp['expenses'];
    $breakeven = 0;
    $avg_contribution = 0;
    $stmt = db()->prepare("SELECT COALESCE(SUM(qty),0) AS qty, COALESCE(SUM(price_total - cost_total),0) AS contribution FROM sales WHERE cafe_id = ? AND sold_at >= ?");
    $stmt->execute([$cafe_id, $start]);
    $unit = $stmt->fetch();
    if ($unit['qty'] > 0) {
        $avg_contribution = $unit['contribution'] / $unit['qty'];
    }
    if ($avg_contribution > 0) {
        $breakeven = $exp['expenses'] / $avg_contribution;
    }
    $stmt = db()->prepare('SELECT expense_date, COALESCE(SUM(amount),0) AS total FROM expenses WHERE cafe_id = ? AND expense_date >= ? GROUP BY expense_date ORDER BY expense_date DESC LIMIT 7');
    $stmt->execute([$cafe_id, $start]);
    $expense_series = $stmt->fetchAll();
    echo "<main class=\"container section\"><div class=\"page-head\"><h2>Аналитика — " . e($cafe['name']) . "</h2><form method=\"get\" class=\"inline-form\"><input type=\"hidden\" name=\"page\" value=\"analytics\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div class=\"period-selector\"><label>Период</label><select name=\"period\"><option value=\"month\" " . ($period === 'month' ? 'selected' : '') . ">Месяц</option><option value=\"week\" " . ($period === 'week' ? 'selected' : '') . ">Неделя</option><option value=\"day\" " . ($period === 'day' ? 'selected' : '') . ">День</option></select></div><button class=\"btn btn-ghost\" type=\"submit\">Применить</button></form></div>";
    if (feature_enabled($subscription, 'export')) {
        echo "<div class=\"card\"><h3>Экспорт отчётов</h3><a class=\"btn btn-ghost\" href=\"/api.php?action=export_pnl&cafe_id=" . e((string)$cafe_id) . "&period=" . e($period) . "\">Скачать P&L в CSV</a></div>";
    }
    echo "<div class=\"grid grid-3\"><div class=\"card metric\"><div class=\"metric-title\">Выручка</div><div class=\"metric-value\">" . format_money($sales['revenue']) . " ₽</div></div><div class=\"card metric\"><div class=\"metric-title\">Валовая прибыль</div><div class=\"metric-value\">" . format_money($gross_profit) . " ₽</div></div><div class=\"card metric\"><div class=\"metric-title\">Чистая прибыль</div><div class=\"metric-value\">" . format_money($net_profit) . " ₽</div></div></div>";
    echo "<div class=\"grid grid-2\"><div class=\"card\"><h3>Unit-экономика</h3><ul class=\"data-list\"><li>Средний вклад в покрытие <span>" . format_money($avg_contribution) . " ₽</span></li><li>Точка безубыточности <span>" . number_format($breakeven, 0, ',', ' ') . " напитков</span></li></ul></div>";
    echo "<div class=\"card\"><h3>Динамика расходов (7 дней)</h3>";
    if ($expense_series) {
        echo "<ul class=\"data-list\">";
        foreach ($expense_series as $row) {
            echo "<li>" . e(date('d.m', strtotime($row['expense_date']))) . " <span>" . format_money($row['total']) . " ₽</span></li>";
        }
        echo "</ul>";
    } else {
        echo "<div class=\"muted\">Пока нет данных по расходам.</div>";
    }
    echo "</div>";
    if (feature_enabled($subscription, 'unit_economics')) {
        $profit_stmt = db()->prepare('SELECT r.name, COALESCE(SUM(s.price_total - s.cost_total),0) AS profit FROM recipes r LEFT JOIN sales s ON s.recipe_id = r.id WHERE r.cafe_id = ? GROUP BY r.id ORDER BY profit DESC LIMIT 5');
        $profit_stmt->execute([$cafe_id]);
        $top_drinks = $profit_stmt->fetchAll();
        $loss_stmt = db()->prepare('SELECT r.name, COALESCE(SUM(s.price_total - s.cost_total),0) AS profit FROM recipes r LEFT JOIN sales s ON s.recipe_id = r.id WHERE r.cafe_id = ? GROUP BY r.id ORDER BY profit ASC LIMIT 5');
        $loss_stmt->execute([$cafe_id]);
        $loss_drinks = $loss_stmt->fetchAll();
        echo "<div class=\"card\"><h3>ТОП напитков по прибыли</h3>";
        if ($top_drinks) {
            echo "<ul class=\"data-list\">";
            foreach ($top_drinks as $drink) {
                echo "<li>" . e($drink['name']) . " <span>" . format_money($drink['profit']) . " ₽</span></li>";
            }
            echo "</ul>";
        } else {
            echo "<div class=\"muted\">Нет данных по продажам.</div>";
        }
        echo "</div>";
        echo "<div class=\"card\"><h3>Убыточные напитки</h3>";
        if ($loss_drinks) {
            echo "<ul class=\"data-list\">";
            foreach ($loss_drinks as $drink) {
                echo "<li>" . e($drink['name']) . " <span>" . format_money($drink['profit']) . " ₽</span></li>";
            }
            echo "</ul>";
        } else {
            echo "<div class=\"muted\">Нет данных по продажам.</div>";
        }
        echo "</div>";
    }
    if (feature_enabled($subscription, 'comparisons')) {
        $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue FROM sales WHERE cafe_id = ? AND sold_at BETWEEN ? AND ?');
        $stmt->execute([$cafe_id, $prev_start, $prev_end]);
        $prev_revenue = $stmt->fetch()['revenue'];
        $delta = $prev_revenue > 0 ? (($sales['revenue'] - $prev_revenue) / $prev_revenue) * 100 : 0;
        echo "<div class=\"card\"><h3>Сравнение с предыдущим периодом</h3><ul class=\"data-list\"><li>Выручка сейчас <span>" . format_money($sales['revenue']) . " ₽</span></li><li>Выручка ранее <span>" . format_money($prev_revenue) . " ₽</span></li><li>Динамика <span>" . number_format($delta, 1, ',', ' ') . "%</span></li></ul></div>";
    }
    if (feature_enabled($subscription, 'category_insights')) {
        $stmt = db()->prepare('SELECT category, COALESCE(SUM(amount),0) AS total FROM expenses WHERE cafe_id = ? AND expense_date >= ? GROUP BY category ORDER BY total DESC LIMIT 3');
        $stmt->execute([$cafe_id, $start]);
        $top_categories = $stmt->fetchAll();
        echo "<div class=\"card\"><h3>Инсайты по расходам</h3>";
        if ($top_categories) {
            echo "<ul class=\"data-list\">";
            foreach ($top_categories as $row) {
                echo "<li>" . e($row['category']) . " <span>" . format_money($row['total']) . " ₽</span></li>";
            }
            echo "</ul>";
        } else {
            echo "<div class=\"muted\">Нет расходов за период.</div>";
        }
        echo "</div>";
    }

    if (feature_enabled($subscription, 'recommendations')) {
        echo "<div class=\"card\"><h3>Умные рекомендации</h3><ul class=\"data-list\"><li>Подними цену на топовый напиток на 10 ₽ для роста маржи.</li><li>Напиток с низкой маржой: пересмотри рецепт или цену.</li><li>Расходы выросли: проверь аренду и списания.</li></ul></div>";
    } else {
        echo "<div class=\"card\"><h3>Рекомендации</h3><div class=\"muted\">Доступно на тарифе Maxi.</div></div>";
    }
    if (feature_enabled($subscription, 'what_if')) {
        echo "<div class=\"card\"><h3>Сценарии «А если»</h3><form method=\"get\" class=\"inline-form\"><input type=\"hidden\" name=\"page\" value=\"analytics\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><label>Рост аренды (₽)</label><input name=\"rent_delta\" type=\"number\" step=\"0.01\" value=\"" . e($_GET['rent_delta'] ?? '0') . "\"><label>Рост себестоимости (%)</label><input name=\"cost_delta\" type=\"number\" step=\"0.1\" value=\"" . e($_GET['cost_delta'] ?? '0') . "\"><button class=\"btn btn-ghost\" type=\"submit\">Рассчитать</button></form>";
        $rent_delta = (float)($_GET['rent_delta'] ?? 0);
        $cost_delta = (float)($_GET['cost_delta'] ?? 0);
        $adjusted_cogs = $sales['cogs'] * (1 + ($cost_delta / 100));
        $adjusted_profit = $sales['revenue'] - $adjusted_cogs - $exp['expenses'] - $rent_delta;
        echo "<div class=\"muted\">Прогноз чистой прибыли: " . format_money($adjusted_profit) . " ₽</div></div>";
    }
    if (feature_enabled($subscription, 'advanced_analytics')) {
        $ebitda = $gross_profit - $exp['expenses'];
        $contribution_margin = $sales['revenue'] > 0 ? ($gross_profit / $sales['revenue']) * 100 : 0;
        echo "<div class=\"card\"><h3>Расширенная аналитика</h3><ul class=\"data-list\"><li>EBITDA (упрощённо) <span>" . format_money($ebitda) . " ₽</span></li><li>Contribution Margin <span>" . number_format($contribution_margin, 1, ',', ' ') . "%</span></li><li>ROMI <span>Заполните маркетинг, чтобы рассчитать</span></li></ul></div>";
    }
    echo "</div>";
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'payments') {
    $subscription = active_subscription((int)$user['id']);
    $payments = db()->prepare('SELECT p.*, pl.name AS plan_name FROM payments p JOIN plans pl ON pl.id = p.plan_id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 20');
    $payments->execute([$user['id']]);
    $payments = $payments->fetchAll();
    app_nav('payments');
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Платежи и чеки</h2><div class=\"muted\">История оплат и статусы чеков.</div></div><a class=\"btn btn-ghost\" href=\"/index.php?page=plans\">Продлить доступ</a></div>";
    if ($subscription) {
        echo "<div class=\"alert success\">Активен тариф: " . e($subscription['plan_name']) . ", до " . e(date('d.m.Y', strtotime($subscription['ends_at']))) . "</div>";
    } else {
        echo "<div class=\"alert warning\">Нет активного тарифа. Оплатите доступ.</div>";
    }
    if ($payments) {
        echo "<table class=\"table\"><thead><tr><th>Дата</th><th>Тариф</th><th>Сумма</th><th>Статус</th></tr></thead><tbody>";
        foreach ($payments as $pay) {
            echo "<tr><td>" . e(date('d.m.Y H:i', strtotime($pay['created_at']))) . "</td><td>" . e($pay['plan_name']) . "</td><td>" . format_money($pay['amount']) . " ₽</td><td>" . e($pay['status']) . "</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Платежей пока нет.</div>";
    }
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'admin') {
    $user = require_auth();
    require_admin($user);
    $tab = $_GET['tab'] ?? 'stats';
    echo "<main class=\"container section\"><div class=\"page-head\"><h2>Админка</h2><div class=\"tabs\"><a href=\"/index.php?page=admin&tab=stats\" class=\"" . ($tab === 'stats' ? 'active' : '') . "\">Статистика</a><a href=\"/index.php?page=admin&tab=plans\" class=\"" . ($tab === 'plans' ? 'active' : '') . "\">Тарифы</a><a href=\"/index.php?page=admin&tab=subscriptions\" class=\"" . ($tab === 'subscriptions' ? 'active' : '') . "\">Подписки</a><a href=\"/index.php?page=admin&tab=users\" class=\"" . ($tab === 'users' ? 'active' : '') . "\">Пользователи</a><a href=\"/index.php?page=admin&tab=payments\" class=\"" . ($tab === 'payments' ? 'active' : '') . "\">Платежи</a><a href=\"/index.php?page=admin&tab=landing\" class=\"" . ($tab === 'landing' ? 'active' : '') . "\">Лендинг</a></div></div>";

    if ($tab === 'stats') {
        $total_revenue = db()->query('SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE status = "paid"')->fetch()['total'];
        $active_subs = db()->query('SELECT COUNT(*) AS total FROM subscriptions WHERE status = "active" AND ends_at >= NOW()')->fetch()['total'];
        $users_count = db()->query('SELECT COUNT(*) AS total FROM users')->fetch()['total'];
        $trial_count = db()->query("SELECT COUNT(*) AS total FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE p.name = 'Trial'")->fetch()['total'];
        $paid_count = db()->query("SELECT COUNT(*) AS total FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE p.name IN ('Pro','Maxi')")->fetch()['total'];
        $conversion = $trial_count > 0 ? ($paid_count / $trial_count) * 100 : 0;
        echo "<div class=\"grid grid-4\"><div class=\"card metric\"><div class=\"metric-title\">Выручка</div><div class=\"metric-value\">" . format_money($total_revenue) . " ₽</div></div><div class=\"card metric\"><div class=\"metric-title\">ARPU</div><div class=\"metric-value\">" . format_money($users_count > 0 ? $total_revenue / $users_count : 0) . " ₽</div></div><div class=\"card metric\"><div class=\"metric-title\">Активные подписки</div><div class=\"metric-value\">" . e((string)$active_subs) . "</div></div><div class=\"card metric\"><div class=\"metric-title\">Конверсия Trial→Покупка</div><div class=\"metric-value\">" . number_format($conversion, 1, ',', ' ') . "%</div></div></div>";
    }

    if ($tab === 'plans') {
        $plans = db()->query('SELECT * FROM plans ORDER BY price')->fetchAll();
        echo "<div class=\"card\"><table class=\"table\"><thead><tr><th>Тариф</th><th>Цена</th><th>Дней</th><th>Кофеен</th><th>Активен</th></tr></thead><tbody>";
        foreach ($plans as $plan) {
            echo "<tr><td>" . e($plan['name']) . "</td><td>" . format_money($plan['price']) . " ₽</td><td>" . e((string)$plan['duration_days']) . "</td><td>" . e((string)$plan['max_cafes']) . "</td><td>" . ($plan['active'] ? 'Да' : 'Нет') . "</td></tr>";
        }
        echo "</tbody></table></div>";
        echo "<div class=\"card\"><form method=\"post\" action=\"/api.php?action=update_plan\" class=\"grid grid-4\"><div><label>Тариф</label><select name=\"plan_id\">";
        foreach ($plans as $plan) {
            echo "<option value=\"" . e((string)$plan['id']) . "\">" . e($plan['name']) . "</option>";
        }
        echo "</select></div><div><label>Цена</label><input name=\"price\" type=\"number\" required></div><div><label>Дней</label><input name=\"duration_days\" type=\"number\" required></div><div><label>Кофеен</label><input name=\"max_cafes\" type=\"number\" required></div><div><label>Активен</label><select name=\"active\"><option value=\"1\">Да</option><option value=\"0\">Нет</option></select></div><div><button class=\"btn btn-primary\" type=\"submit\">Сохранить</button></div></form></div>";
    }

    if ($tab === 'subscriptions') {
        $subs = db()->query('SELECT s.*, u.email, p.name AS plan_name FROM subscriptions s JOIN (SELECT user_id, MAX(ends_at) AS max_end FROM subscriptions GROUP BY user_id) latest ON latest.user_id = s.user_id AND latest.max_end = s.ends_at JOIN users u ON u.id = s.user_id JOIN plans p ON p.id = s.plan_id ORDER BY s.ends_at DESC')->fetchAll();
        echo "<div class=\"card\"><table class=\"table\"><thead><tr><th>Пользователь</th><th>Тариф</th><th>Статус</th><th>Начало</th><th>Окончание</th><th>Действие</th></tr></thead><tbody>";
        foreach ($subs as $sub) {
            echo "<tr><td>" . e($sub['email']) . "</td><td>" . e($sub['plan_name']) . "</td><td>" . e($sub['status']) . "</td><td>" . e(date('d.m.Y', strtotime($sub['starts_at']))) . "</td><td>" . e(date('d.m.Y', strtotime($sub['ends_at']))) . "</td><td><form method=\"post\" action=\"/api.php?action=extend_subscription\" class=\"inline-form\"><input type=\"hidden\" name=\"subscription_id\" value=\"" . e((string)$sub['id']) . "\"><input name=\"days\" type=\"number\" placeholder=\"Дней\" required><button class=\"btn btn-ghost\" type=\"submit\">Продлить</button></form><form method=\"post\" action=\"/api.php?action=cancel_subscription\" class=\"inline-form\"><input type=\"hidden\" name=\"subscription_id\" value=\"" . e((string)$sub['id']) . "\"><button class=\"btn btn-ghost\" type=\"submit\">Отключить</button></form></td></tr>";
        }
        echo "</tbody></table></div>";
    }

    if ($tab === 'users') {
        $users = db()->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 50')->fetchAll();
        echo "<div class=\"card\"><table class=\"table\"><thead><tr><th>Email</th><th>Роль</th><th>Дата</th><th>Действие</th></tr></thead><tbody>";
        foreach ($users as $u) {
            echo "<tr><td>" . e($u['email']) . "</td><td>" . e($u['role']) . "</td><td>" . e(date('d.m.Y', strtotime($u['created_at']))) . "</td><td><form method=\"post\" action=\"/api.php?action=toggle_role\"><input type=\"hidden\" name=\"user_id\" value=\"" . e((string)$u['id']) . "\"><button class=\"btn btn-ghost\" type=\"submit\">Сменить роль</button></form></td></tr>";
        }
        echo "</tbody></table></div>";
    }

    if ($tab === 'payments') {
        $payments = db()->query('SELECT p.*, u.email, pl.name AS plan_name FROM payments p JOIN users u ON u.id = p.user_id JOIN plans pl ON pl.id = p.plan_id ORDER BY p.created_at DESC LIMIT 50')->fetchAll();
        echo "<div class=\"card\"><table class=\"table\"><thead><tr><th>Дата</th><th>Пользователь</th><th>Тариф</th><th>Сумма</th><th>Статус</th></tr></thead><tbody>";
        foreach ($payments as $pay) {
            echo "<tr><td>" . e(date('d.m.Y H:i', strtotime($pay['created_at']))) . "</td><td>" . e($pay['email']) . "</td><td>" . e($pay['plan_name']) . "</td><td>" . format_money($pay['amount']) . " ₽</td><td>" . e($pay['status']) . "</td></tr>";
        }
        echo "</tbody></table></div>";
    }

    if ($tab === 'landing') {
        $landing = get_setting('landing', []);
        echo "<div class=\"card\"><form method=\"post\" action=\"/api.php?action=update_landing\" class=\"grid grid-2\">";
        echo "<div><label>Заголовок</label><input name=\"hero_title\" value=\"" . e($landing['hero_title'] ?? '') . "\" required></div>";
        echo "<div><label>Подзаголовок</label><input name=\"hero_subtitle\" value=\"" . e($landing['hero_subtitle'] ?? '') . "\" required></div>";
        echo "<div><label>CTA 1</label><input name=\"cta_primary\" value=\"" . e($landing['cta_primary'] ?? '') . "\" required></div>";
        echo "<div><label>CTA 2</label><input name=\"cta_secondary\" value=\"" . e($landing['cta_secondary'] ?? '') . "\" required></div>";
        echo "<div class=\"grid-span-2\"><label>Преимущества (JSON массив)</label><textarea name=\"advantages\" rows=\"6\">" . e(json_encode($landing['advantages'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</textarea></div>";
        echo "<div class=\"grid-span-2\"><label>Отзывы (JSON массив)</label><textarea name=\"testimonials\" rows=\"6\">" . e(json_encode($landing['testimonials'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</textarea></div>";
        echo "<div class=\"grid-span-2\"><button class=\"btn btn-primary\" type=\"submit\">Сохранить</button></div>";
        echo "</form></div>";
    }

    echo "</main>";
    page_footer();
    exit;
}

page_footer();
