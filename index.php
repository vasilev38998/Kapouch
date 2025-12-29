<?php
require __DIR__ . '/lib.php';
$config = require __DIR__ . '/config.php';

$page = $_GET['page'] ?? 'home';
$user = current_user();
$subscription = $user ? active_subscription((int)$user['id']) : null;
$landing = get_setting('landing', []);

function page_header(string $title, ?array $user): void {
    $app = (require __DIR__ . '/config.php')['app'];
    echo "<!doctype html><html lang=\"ru\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>" . e($title) . " | " . e($app['name']) . "</title><link rel=\"stylesheet\" href=\"/assets/style.css\"></head><body>";
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
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo "<div class=\"container\"><div class=\"alert {$flash['type']}\">" . e($flash['message']) . "</div></div>";
    }
}

function page_footer(): void {
    $year = date('Y');
    echo "<footer class=\"site-footer\"><div class=\"container footer-inner\"><div><strong>Kapouch</strong> — профессиональный учёт для кофеен. © {$year}</div><div>Поддержка: support@your-domain.ru</div></div></footer>";
    echo "<script src=\"/assets/app.js\"></script></body></html>";
}

function app_nav(string $current, int $cafe_id = 0): void {
    $links = [
        'dashboard' => ['Рабочий стол', '/index.php?page=dashboard'],
        'analytics' => ['Аналитика', $cafe_id ? "/index.php?page=analytics&cafe_id={$cafe_id}" : '/index.php?page=analytics'],
        'sales' => ['Продажи', $cafe_id ? "/index.php?page=sales&cafe_id={$cafe_id}" : '/index.php?page=sales'],
        'expenses' => ['Расходы', $cafe_id ? "/index.php?page=expenses&cafe_id={$cafe_id}" : '/index.php?page=expenses'],
        'recipes' => ['Рецепты', $cafe_id ? "/index.php?page=recipes&cafe_id={$cafe_id}" : '/index.php?page=recipes'],
        'ingredients' => ['Ингредиенты', $cafe_id ? "/index.php?page=ingredients&cafe_id={$cafe_id}" : '/index.php?page=ingredients'],
        'payments' => ['Платежи', '/index.php?page=payments'],
        'cafes' => ['Кофейни', '/index.php?page=cafes'],
    ];
    echo "<div class=\"app-nav\"><div class=\"container app-nav-inner\">";
    foreach ($links as $key => $data) {
        $active = $current === $key ? 'active' : '';
        echo "<a class=\"{$active}\" href=\"" . e($data[1]) . "\">" . e($data[0]) . "</a>";
    }
    echo "</div></div>";
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
    foreach ($plans as $plan) {
        $features = json_decode($plan['features_json'], true);
        echo "<div class=\"card pricing-card\"><div class=\"pricing-head\"><h3>" . e($plan['name']) . "</h3><div class=\"price\">" . format_money($plan['price']) . " ₽ / " . e((string)$plan['duration_days']) . " дней</div></div><ul class=\"pricing-list\">";
        echo "<li>Кофеен: до " . e((string)$plan['max_cafes']) . "</li>";
        echo "<li>" . ($features['pnl_full'] ? 'Полный P&L' : 'Базовый P&L') . "</li>";
        echo "<li>Импорт CSV: " . ($features['import_limit'] >= 100000 ? 'без ограничений' : 'до ' . $features['import_limit'] . ' строк') . "</li>";
        echo "<li>Экспорт: " . ($features['export'] ? 'доступен' : 'нет') . "</li>";
        echo "</ul><a class=\"btn btn-primary\" href=\"/index.php?page=register\">Начать</a></div>";
    }
    echo "</div></div></section>";
    echo "<section class=\"section cta\"><div class=\"container cta-inner\"><div><h2>Готовы управлять кофейней как бизнесом?</h2><p>Подключайтесь за 5 минут и начните видеть реальную прибыль.</p></div><a class=\"btn btn-dark\" href=\"/index.php?page=register\">Получить доступ</a></div></section>";
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'login') {
    echo "<main class=\"container auth\"><div class=\"card auth-card\"><h2>Вход в Kapouch</h2><form method=\"post\" action=\"/api.php?action=login\">";
    echo "<label>Email</label><input type=\"email\" name=\"email\" required><label>Пароль</label><input type=\"password\" name=\"password\" required><button class=\"btn btn-primary\" type=\"submit\">Войти</button></form>";
    echo "<div class=\"auth-links\"><a href=\"/index.php?page=reset\">Забыли пароль?</a><a href=\"/index.php?page=register\">Создать аккаунт</a></div></div></main>";
    page_footer();
    exit;
}

if ($page === 'register') {
    echo "<main class=\"container auth\"><div class=\"card auth-card\"><h2>Создание аккаунта</h2><form method=\"post\" action=\"/api.php?action=register\">";
    echo "<label>Email</label><input type=\"email\" name=\"email\" required><label>Пароль</label><input type=\"password\" name=\"password\" required><label>Подтверждение пароля</label><input type=\"password\" name=\"password_confirm\" required><button class=\"btn btn-primary\" type=\"submit\">Создать аккаунт</button></form>";
    echo "<div class=\"auth-links\"><a href=\"/index.php?page=login\">Уже есть аккаунт?</a></div></div></main>";
    page_footer();
    exit;
}

if ($page === 'reset') {
    echo "<main class=\"container auth\"><div class=\"card auth-card\"><h2>Восстановление пароля</h2><form method=\"post\" action=\"/api.php?action=request_reset\">";
    echo "<label>Email</label><input type=\"email\" name=\"email\" required><button class=\"btn btn-primary\" type=\"submit\">Отправить ссылку</button></form>";
    echo "<div class=\"muted\">Ссылка придёт на почту в течение нескольких минут.</div></div></main>";
    page_footer();
    exit;
}

if ($page === 'reset_confirm') {
    $token = $_GET['token'] ?? '';
    echo "<main class=\"container auth\"><div class=\"card auth-card\"><h2>Установить новый пароль</h2><form method=\"post\" action=\"/api.php?action=confirm_reset\">";
    echo "<input type=\"hidden\" name=\"token\" value=\"" . e($token) . "\"><label>Новый пароль</label><input type=\"password\" name=\"password\" required><button class=\"btn btn-primary\" type=\"submit\">Сохранить пароль</button></form></div></main>";
    page_footer();
    exit;
}

if ($page === 'plans') {
    $plans = db()->query('SELECT * FROM plans WHERE active = 1 ORDER BY price')->fetchAll();
    echo "<main class=\"container section\"><h2>Тарифы</h2><div class=\"grid grid-3 pricing\">";
    foreach ($plans as $plan) {
        $features = json_decode($plan['features_json'], true);
        echo "<div class=\"card pricing-card\"><h3>" . e($plan['name']) . "</h3><div class=\"price\">" . format_money($plan['price']) . " ₽ / " . e((string)$plan['duration_days']) . " дней</div><ul class=\"pricing-list\">";
        echo "<li>Кофеен: до " . e((string)$plan['max_cafes']) . "</li>";
        echo "<li>P&L: " . ($features['pnl_full'] ? 'полный' : 'упрощённый') . "</li>";
        echo "<li>CSV импорт: " . ($features['import_limit'] >= 100000 ? 'без ограничений' : 'до ' . $features['import_limit'] . ' строк') . "</li>";
        echo "</ul>";
        if ($user) {
            echo "<form method=\"post\" action=\"/api.php?action=init_payment\"><input type=\"hidden\" name=\"plan_id\" value=\"" . e((string)$plan['id']) . "\"><button class=\"btn btn-primary\" type=\"submit\">Оплатить</button></form>";
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
    $selected_cafe = $_GET['cafe_id'] ?? ($cafes[0]['id'] ?? null);
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
        echo "<div class=\"card\"><h3>KPI и цели</h3><form method=\"post\" action=\"/api.php?action=update_kpi\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$selected_cafe) . "\"><label>Целевая маржа (%)</label><input name=\"target_margin\" type=\"number\" step=\"0.1\" value=\"" . e((string)$target_margin) . "\"><label>Целевая прибыль (₽)</label><input name=\"target_profit\" type=\"number\" step=\"0.01\" value=\"" . e((string)$target_profit) . "\"><button class=\"btn btn-ghost\" type=\"submit\">Сохранить</button></form><div class=\"muted\">Текущая маржа: " . number_format($metrics['gross_margin'], 1, ',', ' ') . "% · Чистая прибыль: " . format_money($metrics['net_profit']) . " ₽</div></div>";
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
    $cafe_id = (int)($_GET['cafe_id'] ?? 0);
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
    echo "<main class=\"container section\"><div class=\"page-head\"><div><h2>Ингредиенты — " . e($cafe['name']) . "</h2><div class=\"muted\">Средневзвешенная себестоимость</div></div><a class=\"btn btn-primary\" href=\"#add-ingredient\">Добавить ингредиент</a></div>";
    echo "<div class=\"card\" id=\"add-ingredient\"><form method=\"post\" action=\"/api.php?action=add_ingredient\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><label>Название</label><input name=\"name\" required><label>Ед.изм.</label><input name=\"unit\" placeholder=\"г, мл, шт\" required><label>Себестоимость за ед.</label><input name=\"cost_per_unit\" type=\"number\" step=\"0.01\" required><label>Начальный остаток</label><input name=\"stock_qty\" type=\"number\" step=\"0.001\" value=\"0\"><button class=\"btn btn-primary\" type=\"submit\">Добавить</button></form></div>";

    echo "<div class=\"grid grid-2\">";
    foreach ($ingredients as $item) {
        echo "<div class=\"card\"><h3>" . e($item['name']) . "</h3><div class=\"muted\">" . e($item['unit']) . " · Остаток: " . e($item['stock_qty']) . "</div><div class=\"price\">" . format_money($item['cost_per_unit']) . " ₽ / " . e($item['unit']) . "</div>";
        if (feature_enabled($subscription, 'unit_economics')) {
            $history_stmt = db()->prepare('SELECT cost_per_unit, recorded_at FROM ingredient_cost_history WHERE ingredient_id = ? ORDER BY recorded_at DESC LIMIT 3');
            $history_stmt->execute([$item['id']]);
            $history = $history_stmt->fetchAll();
            if ($history) {
                echo "<div class=\"muted\">История себестоимости:</div><ul class=\"data-list\">";
                foreach ($history as $row) {
                    echo "<li>" . format_money($row['cost_per_unit']) . " ₽ <span>" . e(date('d.m.Y', strtotime($row['recorded_at']))) . "</span></li>";
                }
                echo "</ul>";
            }
        }
        echo "<form method=\"post\" action=\"/api.php?action=add_purchase\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"hidden\" name=\"ingredient_id\" value=\"" . e((string)$item['id']) . "\"><label>Закупка (кол-во)</label><input name=\"qty\" type=\"number\" step=\"0.001\" required><label>Сумма закупки</label><input name=\"price_total\" type=\"number\" step=\"0.01\" required><label>Дата</label><input name=\"purchased_at\" type=\"date\" required><button class=\"btn btn-ghost\" type=\"submit\">Добавить закупку</button></form></div>";
    }
    if (!$ingredients) {
        echo "<div class=\"empty-state\">Добавьте ингредиенты, чтобы рассчитывать себестоимость напитков.</div>";
    }
    echo "</div>";
    echo "<div class=\"card\"><h3>CSV импорт закупок</h3><form method=\"post\" action=\"/api.php?action=import_purchases\" enctype=\"multipart/form-data\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"file\" name=\"csv_file\" accept=\".csv\" required><button class=\"btn btn-ghost\" type=\"submit\">Загрузить CSV</button></form><div class=\"muted\">Формат: ингредиент;кол-во;сумма;дата (YYYY-MM-DD)</div></div>";
    echo "</main>";
    page_footer();
    exit;
}

if ($page === 'recipes') {
    $subscription = require_subscription($user);
    $cafe_id = (int)($_GET['cafe_id'] ?? 0);
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
    $cafe_id = (int)($_GET['cafe_id'] ?? 0);
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
    $cafe_id = (int)($_GET['cafe_id'] ?? 0);
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
    echo "<div class=\"card\" id=\"add-expense\"><form method=\"post\" action=\"/api.php?action=add_expense\" class=\"grid grid-4\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div><label>Категория</label><input name=\"category\" required></div><div><label>Сумма</label><input name=\"amount\" type=\"number\" step=\"0.01\" required></div><div><label>Дата</label><input name=\"expense_date\" type=\"date\" required></div><div><button class=\"btn btn-primary\" type=\"submit\">Добавить расход</button></div></form></div>";
    echo "<div class=\"card\"><h3>CSV импорт расходов</h3><form method=\"post\" action=\"/api.php?action=import_expenses\" enctype=\"multipart/form-data\" class=\"inline-form\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><input type=\"file\" name=\"csv_file\" accept=\".csv\" required><button class=\"btn btn-ghost\" type=\"submit\">Загрузить CSV</button></form><div class=\"muted\">Формат: категория;сумма;дата (YYYY-MM-DD)</div></div>";

    echo "<div class=\"card\"><h3>Последние расходы</h3>";
    if ($expenses) {
        echo "<table class=\"table\"><thead><tr><th>Дата</th><th>Категория</th><th>Сумма</th></tr></thead><tbody>";
        foreach ($expenses as $exp) {
            echo "<tr><td>" . e(date('d.m.Y', strtotime($exp['expense_date']))) . "</td><td>" . e($exp['category']) . "</td><td>" . format_money($exp['amount']) . " ₽</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class=\"empty-state\">Пока нет расходов. Добавьте вручную или импортируйте CSV.</div>";
    }
    echo "</div></main>";
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
    $cafe_id = (int)($_GET['cafe_id'] ?? 0);
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
    echo "<main class=\"container section\"><div class=\"page-head\"><h2>Аналитика — " . e($cafe['name']) . "</h2><form method=\"get\" class=\"inline-form\"><input type=\"hidden\" name=\"page\" value=\"analytics\"><input type=\"hidden\" name=\"cafe_id\" value=\"" . e((string)$cafe_id) . "\"><div class=\"period-selector\"><label>Период</label><select name=\"period\"><option value=\"month\" " . ($period === 'month' ? 'selected' : '') . ">Месяц</option><option value=\"week\" " . ($period === 'week' ? 'selected' : '') . ">Неделя</option><option value=\"day\" " . ($period === 'day' ? 'selected' : '') . ">День</option></select></div><button class=\"btn btn-ghost\" type=\"submit\">Применить</button></form></div>";
    if (feature_enabled($subscription, 'export')) {
        echo "<div class=\"card\"><h3>Экспорт отчётов</h3><a class=\"btn btn-ghost\" href=\"/api.php?action=export_pnl&cafe_id=" . e((string)$cafe_id) . "&period=" . e($period) . "\">Скачать P&L в CSV</a></div>";
    }
    echo "<div class=\"grid grid-3\"><div class=\"card metric\"><div class=\"metric-title\">Выручка</div><div class=\"metric-value\">" . format_money($sales['revenue']) . " ₽</div></div><div class=\"card metric\"><div class=\"metric-title\">Валовая прибыль</div><div class=\"metric-value\">" . format_money($gross_profit) . " ₽</div></div><div class=\"card metric\"><div class=\"metric-title\">Чистая прибыль</div><div class=\"metric-value\">" . format_money($net_profit) . " ₽</div></div></div>";
    echo "<div class=\"grid grid-2\"><div class=\"card\"><h3>Unit-экономика</h3><ul class=\"data-list\"><li>Средний вклад в покрытие <span>" . format_money($avg_contribution) . " ₽</span></li><li>Точка безубыточности <span>" . number_format($breakeven, 0, ',', ' ') . " напитков</span></li></ul></div>";
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
