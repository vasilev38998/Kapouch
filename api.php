<?php
require __DIR__ . '/lib.php';
$config = require __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$csrf_exempt = ['tinkoff_callback'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $csrf_exempt, true)) {
    require_csrf();
}

function redirect_with_message(string $url, string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . $url);
    exit;
}

if ($action === 'register') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_message('/index.php?page=register', 'Некорректный email', 'warning');
    }
    if ($password !== $confirm || strlen($password) < 8) {
        redirect_with_message('/index.php?page=register', 'Пароль должен быть минимум 8 символов и совпадать с подтверждением', 'warning');
    }
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirect_with_message('/index.php?page=register', 'Пользователь уже существует', 'warning');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, "owner")');
    $stmt->execute([$email, $hash]);
    $_SESSION['user_id'] = db()->lastInsertId();
    redirect_with_message('/index.php?page=company_profile', 'Аккаунт создан. Заполните данные вашей компании.');
}

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        redirect_with_message('/index.php?page=login', 'Неверный email или пароль', 'warning');
    }
    $_SESSION['user_id'] = $user['id'];
    redirect_with_message('/index.php?page=dashboard', 'С возвращением!');
}

if ($action === 'logout') {
    session_destroy();
    header('Location: /');
    exit;
}

if ($action === 'request_reset') {
    $email = trim($_POST['email'] ?? '');
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        redirect_with_message('/index.php?page=reset', 'Если email зарегистрирован, ссылка будет отправлена.', 'success');
    }
    $token = bin2hex(random_bytes(16));
    $ttl = (int)$config['security']['password_reset_ttl_minutes'];
    $expires = (new DateTime())->modify("+{$ttl} minutes");
    $stmt = db()->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$user['id'], $token, $expires->format('Y-m-d H:i:s')]);
    $link = $config['app']['url'] . '/index.php?page=reset_confirm&token=' . $token;
    send_email($email, 'Восстановление пароля Kapouch', "Перейдите по ссылке для установки нового пароля: {$link}");
    redirect_with_message('/index.php?page=reset', 'Ссылка отправлена на почту.');
}

if ($action === 'confirm_reset') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 8) {
        redirect_with_message('/index.php?page=reset_confirm&token=' . $token, 'Пароль должен быть минимум 8 символов', 'warning');
    }
    $stmt = db()->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW() ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    if (!$reset) {
        redirect_with_message('/index.php?page=reset', 'Ссылка истекла или некорректна', 'warning');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $reset['user_id']]);
    $stmt = db()->prepare('DELETE FROM password_resets WHERE user_id = ?');
    $stmt->execute([$reset['user_id']]);
    redirect_with_message('/index.php?page=login', 'Пароль обновлён, войдите в аккаунт.');
}

$user = current_user();

if ($action === 'lookup_company') {
    $user = require_auth();
    $inn_raw = $_POST['inn'] ?? '';
    $inn = preg_replace('/\D/', '', $inn_raw);
    if (!in_array(strlen($inn), [10, 12], true)) {
        redirect_with_message('/index.php?page=company_profile', 'ИНН должен содержать 10 или 12 цифр.', 'warning');
    }
    $company_data = fetch_company_data($inn);
    if (!$company_data) {
        redirect_with_message('/index.php?page=company_profile', 'Не удалось получить данные по ИНН. Проверьте ключ API или попробуйте позже.', 'warning');
    }
    $stmt = db()->prepare('INSERT INTO company_profiles (user_id, inn, company_name, short_name, ogrn, kpp, address, ceo_name, status, entity_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE inn = VALUES(inn), company_name = VALUES(company_name), short_name = VALUES(short_name), ogrn = VALUES(ogrn), kpp = VALUES(kpp), address = VALUES(address), ceo_name = VALUES(ceo_name), status = VALUES(status), entity_type = VALUES(entity_type)');
    $stmt->execute([
        $user['id'],
        $inn,
        $company_data['company_name'],
        $company_data['short_name'],
        $company_data['ogrn'],
        $company_data['kpp'],
        $company_data['address'],
        $company_data['ceo_name'],
        $company_data['status'],
        $company_data['entity_type'],
    ]);
    redirect_with_message('/index.php?page=company_profile', 'Данные компании обновлены.');
}

if ($action === 'save_company_profile') {
    $user = require_auth();
    $inn_raw = $_POST['inn'] ?? '';
    $inn = preg_replace('/\D/', '', $inn_raw);
    if (!in_array(strlen($inn), [10, 12], true)) {
        redirect_with_message('/index.php?page=company_profile', 'ИНН должен содержать 10 или 12 цифр.', 'warning');
    }
    $entity_type = $_POST['entity_type'] === 'individual' ? 'individual' : 'company';
    $stmt = db()->prepare('INSERT INTO company_profiles (user_id, inn, company_name, short_name, ogrn, kpp, address, ceo_name, status, entity_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE inn = VALUES(inn), company_name = VALUES(company_name), short_name = VALUES(short_name), ogrn = VALUES(ogrn), kpp = VALUES(kpp), address = VALUES(address), ceo_name = VALUES(ceo_name), status = VALUES(status), entity_type = VALUES(entity_type)');
    $stmt->execute([
        $user['id'],
        $inn,
        trim($_POST['company_name'] ?? ''),
        trim($_POST['short_name'] ?? ''),
        trim($_POST['ogrn'] ?? ''),
        trim($_POST['kpp'] ?? ''),
        trim($_POST['address'] ?? ''),
        trim($_POST['ceo_name'] ?? ''),
        trim($_POST['status'] ?? ''),
        $entity_type,
    ]);
    redirect_with_message('/index.php?page=company_profile', 'Данные компании сохранены.');
}

if ($action === 'create_cafe') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $stmt = db()->prepare('SELECT COUNT(*) AS total FROM cafes WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $count = $stmt->fetch()['total'];
    if ($count >= user_cafe_limit($subscription)) {
        redirect_with_message('/index.php?page=cafes', 'Достигнут лимит кофеен для тарифа.', 'warning');
    }
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $stmt = db()->prepare('INSERT INTO cafes (user_id, name, city) VALUES (?, ?, ?)');
    $stmt->execute([$user['id'], $name, $city]);
    redirect_with_message('/index.php?page=cafes', 'Кофейня добавлена.');
}

if ($action === 'add_ingredient') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $stmt = db()->prepare('SELECT id FROM cafes WHERE id = ? AND user_id = ?');
    $stmt->execute([$cafe_id, $user['id']]);
    if (!$stmt->fetch()) {
        redirect_with_message('/index.php?page=cafes', 'Кофейня не найдена', 'warning');
    }
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']);
    $cost = (float)$_POST['cost_per_unit'];
    $stock = (float)$_POST['stock_qty'];
    $stmt = db()->prepare('INSERT INTO ingredients (cafe_id, name, unit, cost_per_unit, stock_qty) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$cafe_id, $name, $unit, $cost, $stock]);
    $ingredient_id = db()->lastInsertId();
    $stmt = db()->prepare('INSERT INTO ingredient_cost_history (ingredient_id, cost_per_unit) VALUES (?, ?)');
    $stmt->execute([$ingredient_id, $cost]);
    redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id, 'Ингредиент добавлен.');
}

if ($action === 'add_purchase') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    if (!fetch_user_cafe($user, $cafe_id)) {
        redirect_with_message('/index.php?page=cafes', 'Кофейня не найдена', 'warning');
    }
    $ingredient_id = (int)$_POST['ingredient_id'];
    $qty = (float)$_POST['qty'];
    $price_total = (float)$_POST['price_total'];
    $purchased_at = $_POST['purchased_at'];
    if ($qty <= 0 || $price_total < 0) {
        redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id, 'Некорректные данные закупки', 'warning');
    }
    $stmt = db()->prepare('SELECT * FROM ingredients WHERE id = ? AND cafe_id = ?');
    $stmt->execute([$ingredient_id, $cafe_id]);
    $ingredient = $stmt->fetch();
    if (!$ingredient) {
        redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id, 'Ингредиент не найден', 'warning');
    }
    $purchase_unit_cost = $qty > 0 ? $price_total / $qty : 0;
    $old_qty = (float)$ingredient['stock_qty'];
    $old_cost = (float)$ingredient['cost_per_unit'];
    $new_qty = $old_qty + $qty;
    $new_cost = $new_qty > 0 ? (($old_qty * $old_cost) + ($qty * $purchase_unit_cost)) / $new_qty : $old_cost;
    $stmt = db()->prepare('UPDATE ingredients SET stock_qty = ?, cost_per_unit = ? WHERE id = ?');
    $stmt->execute([$new_qty, $new_cost, $ingredient_id]);
    $stmt = db()->prepare('INSERT INTO purchases (cafe_id, ingredient_id, qty, price_total, purchased_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$cafe_id, $ingredient_id, $qty, $price_total, $purchased_at]);
    $stmt = db()->prepare('INSERT INTO ingredient_cost_history (ingredient_id, cost_per_unit) VALUES (?, ?)');
    $stmt->execute([$ingredient_id, $new_cost]);
    redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id, 'Закупка добавлена.');
}

if ($action === 'update_ingredient') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    if (!fetch_user_cafe($user, $cafe_id)) {
        redirect_with_message('/index.php?page=cafes', 'Кофейня не найдена', 'warning');
    }
    $ingredient_id = (int)$_POST['ingredient_id'];
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']);
    $cost = (float)$_POST['cost_per_unit'];
    $stock = (float)$_POST['stock_qty'];
    $reorder = max(0, (float)($_POST['reorder_level'] ?? 0));
    $stmt = db()->prepare('SELECT id, cost_per_unit FROM ingredients WHERE id = ? AND cafe_id = ?');
    $stmt->execute([$ingredient_id, $cafe_id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id, 'Ингредиент не найден', 'warning');
    }
    $stmt = db()->prepare('UPDATE ingredients SET name = ?, unit = ?, cost_per_unit = ?, stock_qty = ?, reorder_level = ? WHERE id = ?');
    $stmt->execute([$name, $unit, $cost, $stock, $reorder, $ingredient_id]);
    if ((float)$existing['cost_per_unit'] !== $cost) {
        $stmt = db()->prepare('INSERT INTO ingredient_cost_history (ingredient_id, cost_per_unit) VALUES (?, ?)');
        $stmt->execute([$ingredient_id, $cost]);
    }
    redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id . '&ingredient_id=' . $ingredient_id, 'Ингредиент обновлён.');
}

if ($action === 'add_writeoff') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'writeoffs')) {
        redirect_with_message('/index.php?page=writeoffs', 'Списание доступно на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_POST['cafe_id'];
    if (!fetch_user_cafe($user, $cafe_id)) {
        redirect_with_message('/index.php?page=cafes', 'Кофейня не найдена', 'warning');
    }
    $ingredient_id = (int)$_POST['ingredient_id'];
    $qty = (float)$_POST['qty'];
    if ($qty <= 0) {
        redirect_with_message('/index.php?page=writeoffs&cafe_id=' . $cafe_id, 'Количество должно быть больше нуля.', 'warning');
    }
    $reason = trim($_POST['reason'] ?? '');
    $writeoff_date = $_POST['writeoff_date'] ?? date('Y-m-d');
    $ingredient_stmt = db()->prepare('SELECT stock_qty FROM ingredients WHERE id = ? AND cafe_id = ?');
    $ingredient_stmt->execute([$ingredient_id, $cafe_id]);
    $ingredient = $ingredient_stmt->fetch();
    if (!$ingredient) {
        redirect_with_message('/index.php?page=writeoffs&cafe_id=' . $cafe_id, 'Ингредиент не найден.', 'warning');
    }
    $actual_qty = min($qty, (float)$ingredient['stock_qty']);
    if ($actual_qty <= 0) {
        redirect_with_message('/index.php?page=writeoffs&cafe_id=' . $cafe_id, 'Недостаточно остатка для списания.', 'warning');
    }
    db()->beginTransaction();
    try {
        $stmt = db()->prepare('INSERT INTO writeoffs (cafe_id, ingredient_id, qty, reason, writeoff_date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$cafe_id, $ingredient_id, $actual_qty, $reason, $writeoff_date]);
        db()->prepare('UPDATE ingredients SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE id = ?')->execute([$actual_qty, $ingredient_id]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    redirect_with_message('/index.php?page=writeoffs&cafe_id=' . $cafe_id, 'Списание добавлено.');
}

if ($action === 'add_checklist_item') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'daily_checklist')) {
        redirect_with_message('/index.php?page=checklist', 'Чек‑лист доступен на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_POST['cafe_id'];
    if (!fetch_user_cafe($user, $cafe_id)) {
        redirect_with_message('/index.php?page=cafes', 'Кофейня не найдена', 'warning');
    }
    $item = trim($_POST['item']);
    if ($item === '') {
        redirect_with_message('/index.php?page=checklist&cafe_id=' . $cafe_id, 'Задача не может быть пустой.', 'warning');
    }
    $date = $_POST['checklist_date'] ?? date('Y-m-d');
    $stmt = db()->prepare('INSERT INTO daily_checklist (cafe_id, item, checklist_date, is_done) VALUES (?, ?, ?, 0)');
    $stmt->execute([$cafe_id, $item, $date]);
    redirect_with_message('/index.php?page=checklist&cafe_id=' . $cafe_id, 'Пункт добавлен.');
}

if ($action === 'toggle_checklist_item') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $item_id = (int)$_POST['item_id'];
    $cafe_id = (int)$_POST['cafe_id'];
    $stmt = db()->prepare('SELECT dc.id FROM daily_checklist dc JOIN cafes c ON c.id = dc.cafe_id WHERE dc.id = ? AND c.user_id = ? AND dc.cafe_id = ?');
    $stmt->execute([$item_id, $user['id'], $cafe_id]);
    if (!$stmt->fetch()) {
        redirect_with_message('/index.php?page=checklist&cafe_id=' . $cafe_id, 'Задача не найдена.', 'warning');
    }
    $stmt = db()->prepare('UPDATE daily_checklist SET is_done = 1 - is_done WHERE id = ?');
    $stmt->execute([$item_id]);
    redirect_with_message('/index.php?page=checklist&cafe_id=' . $cafe_id, 'Статус обновлён.');
}

if ($action === 'add_checklist_template') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'daily_checklist')) {
        redirect_with_message('/index.php?page=checklist', 'Чек‑лист доступен на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_POST['cafe_id'];
    if (!fetch_user_cafe($user, $cafe_id)) {
        redirect_with_message('/index.php?page=cafes', 'Кофейня не найдена', 'warning');
    }
    $item = trim($_POST['item']);
    if ($item === '') {
        redirect_with_message('/index.php?page=checklist&cafe_id=' . $cafe_id, 'Задача не может быть пустой.', 'warning');
    }
    $stmt = db()->prepare('INSERT INTO checklist_templates (cafe_id, item) VALUES (?, ?)');
    $stmt->execute([$cafe_id, $item]);
    redirect_with_message('/index.php?page=checklist&cafe_id=' . $cafe_id, 'Шаблон добавлен.');
}

if ($action === 'generate_checklist') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'daily_checklist')) {
        redirect_with_message('/index.php?page=checklist', 'Чек‑лист доступен на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_POST['cafe_id'];
    if (!fetch_user_cafe($user, $cafe_id)) {
        redirect_with_message('/index.php?page=cafes', 'Кофейня не найдена', 'warning');
    }
    $date = $_POST['checklist_date'] ?? date('Y-m-d');
    $templates = db()->prepare('SELECT item FROM checklist_templates WHERE cafe_id = ?');
    $templates->execute([$cafe_id]);
    $templates = $templates->fetchAll();
    $existing_stmt = db()->prepare('SELECT item FROM daily_checklist WHERE cafe_id = ? AND checklist_date = ?');
    $existing_stmt->execute([$cafe_id, $date]);
    $existing_items = array_column($existing_stmt->fetchAll(), 'item');
    foreach ($templates as $template) {
        if (in_array($template['item'], $existing_items, true)) {
            continue;
        }
        $stmt = db()->prepare('INSERT INTO daily_checklist (cafe_id, item, checklist_date, is_done) VALUES (?, ?, ?, 0)');
        $stmt->execute([$cafe_id, $template['item'], $date]);
    }
    redirect_with_message('/index.php?page=checklist&cafe_id=' . $cafe_id . '&date=' . $date, 'Чек‑лист сформирован.');
}

if ($action === 'export_pdf') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'export_pdf')) {
        redirect_with_message('/index.php?page=analytics', 'PDF-отчёт доступен на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_GET['cafe_id'];
    $stmt = db()->prepare('SELECT name FROM cafes WHERE id = ? AND user_id = ?');
    $stmt->execute([$cafe_id, $user['id']]);
    $cafe = $stmt->fetch();
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="report.html"');
    echo '<h1>Отчёт кофейни ' . e($cafe['name'] ?? '') . '</h1>';
    echo '<p>Сформировано: ' . date('d.m.Y H:i') . '</p>';
    exit;
}

if ($action === 'export_1c') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'export_1c')) {
        redirect_with_message('/index.php?page=analytics', 'Экспорт для 1С доступен на тарифе Maxi', 'warning');
    }
    $cafe_id = (int)$_GET['cafe_id'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="1c_expenses.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Дата', 'Категория', 'Сумма', 'Комментарий'], ';');
    $stmt = db()->prepare('SELECT expense_date, category, amount, comment FROM expenses WHERE cafe_id = ?');
    $stmt->execute([$cafe_id]);
    foreach ($stmt->fetchAll() as $row) {
        fputcsv($output, [$row['expense_date'], $row['category'], $row['amount'], $row['comment']], ';');
    }
    exit;
}
if ($action === 'add_recipe') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $ingredient_id = (int)$_POST['ingredient_id'];
    $qty = (float)$_POST['qty'];
    $stmt = db()->prepare('INSERT INTO recipes (cafe_id, name, price) VALUES (?, ?, ?)');
    $stmt->execute([$cafe_id, $name, $price]);
    $recipe_id = db()->lastInsertId();
    $stmt = db()->prepare('INSERT INTO recipe_items (recipe_id, ingredient_id, qty) VALUES (?, ?, ?)');
    $stmt->execute([$recipe_id, $ingredient_id, $qty]);
    redirect_with_message('/index.php?page=recipes&cafe_id=' . $cafe_id, 'Рецепт создан.');
}

if ($action === 'add_recipe_item') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $recipe_id = (int)$_POST['recipe_id'];
    $ingredient_id = (int)$_POST['ingredient_id'];
    $qty = (float)$_POST['qty'];
    $stmt = db()->prepare('SELECT cafe_id FROM recipes WHERE id = ?');
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();
    $stmt = db()->prepare('INSERT INTO recipe_items (recipe_id, ingredient_id, qty) VALUES (?, ?, ?)');
    $stmt->execute([$recipe_id, $ingredient_id, $qty]);
    $cafe_id = $recipe ? $recipe['cafe_id'] : '';
    redirect_with_message('/index.php?page=recipes&cafe_id=' . $cafe_id, 'Ингредиент добавлен в рецепт.');
}

if ($action === 'add_sale') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $recipe_id = (int)$_POST['recipe_id'];
    $qty = (float)$_POST['qty'];
    $price_total = (float)$_POST['price_total'];
    $sold_at = $_POST['sold_at'];
    $cost_per_unit = calculate_recipe_cost($recipe_id);
    $cost_total = $cost_per_unit * $qty;
    $stmt = db()->prepare('INSERT INTO sales (cafe_id, recipe_id, qty, price_total, cost_total, sold_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$cafe_id, $recipe_id, $qty, $price_total, $cost_total, $sold_at]);
    // списание ингредиентов со склада
    $stmt = db()->prepare('SELECT ingredient_id, qty FROM recipe_items WHERE recipe_id = ?');
    $stmt->execute([$recipe_id]);
    foreach ($stmt->fetchAll() as $item) {
        $total_qty = $item['qty'] * $qty;
        db()->prepare('UPDATE ingredients SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE id = ?')->execute([$total_qty, $item['ingredient_id']]);
    }
    redirect_with_message('/index.php?page=sales&cafe_id=' . $cafe_id, 'Продажа добавлена.');
}

if ($action === 'update_kpi') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'kpi')) {
        redirect_with_message('/index.php?page=dashboard', 'KPI доступны на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_POST['cafe_id'];
    $target_margin = (float)$_POST['target_margin'];
    $target_profit = (float)$_POST['target_profit'];
    $target_revenue = (float)$_POST['target_revenue'];
    $stmt = db()->prepare('SELECT id FROM kpi_targets WHERE cafe_id = ?');
    $stmt->execute([$cafe_id]);
    $row = $stmt->fetch();
    if ($row) {
        db()->prepare('UPDATE kpi_targets SET target_margin = ?, target_profit = ?, target_revenue = ? WHERE cafe_id = ?')->execute([$target_margin, $target_profit, $target_revenue, $cafe_id]);
    } else {
        db()->prepare('INSERT INTO kpi_targets (cafe_id, target_margin, target_profit, target_revenue) VALUES (?, ?, ?, ?)')->execute([$cafe_id, $target_margin, $target_profit, $target_revenue]);
    }
    redirect_with_message('/index.php?page=dashboard&cafe_id=' . $cafe_id, 'Цели обновлены.');
}

if ($action === 'add_cash_shift') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $shift_date = $_POST['shift_date'];
    $opening_cash = (float)$_POST['opening_cash'];
    $closing_cash = (float)$_POST['closing_cash'];
    $cash_sales = (float)$_POST['cash_sales'];
    $difference = $closing_cash - ($opening_cash + $cash_sales);
    $stmt = db()->prepare('INSERT INTO cash_shifts (cafe_id, shift_date, opening_cash, closing_cash, cash_sales, difference) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$cafe_id, $shift_date, $opening_cash, $closing_cash, $cash_sales, $difference]);
    redirect_with_message('/index.php?page=cash_shifts&cafe_id=' . $cafe_id, 'Смена сохранена.');
}

if ($action === 'add_plan_fact') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $period_start = $_POST['period_start'];
    $period_end = $_POST['period_end'];
    $target_revenue = (float)$_POST['target_revenue'];
    $target_profit = (float)$_POST['target_profit'];
    $stmt = db()->prepare('INSERT INTO plan_fact_targets (cafe_id, period_start, period_end, target_revenue, target_profit) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$cafe_id, $period_start, $period_end, $target_revenue, $target_profit]);
    redirect_with_message('/index.php?page=plan_fact&cafe_id=' . $cafe_id, 'Плановые показатели добавлены.');
}

if ($action === 'add_staff') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $name = trim($_POST['name']);
    $role = trim($_POST['role']);
    $hourly_rate = (float)$_POST['hourly_rate'];
    $stmt = db()->prepare('INSERT INTO staff (cafe_id, name, role, hourly_rate) VALUES (?, ?, ?, ?)');
    $stmt->execute([$cafe_id, $name, $role, $hourly_rate]);
    redirect_with_message('/index.php?page=staff&cafe_id=' . $cafe_id, 'Сотрудник добавлен.');
}

if ($action === 'add_staff_shift') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $staff_id = (int)$_POST['staff_id'];
    $shift_date = $_POST['shift_date'];
    $hours = (float)$_POST['hours'];
    $stmt = db()->prepare('INSERT INTO staff_shifts (staff_id, shift_date, hours) VALUES (?, ?, ?)');
    $stmt->execute([$staff_id, $shift_date, $hours]);
    $stmt = db()->prepare('SELECT cafe_id FROM staff WHERE id = ?');
    $stmt->execute([$staff_id]);
    $cafe_id = $stmt->fetch()['cafe_id'] ?? 0;
    redirect_with_message('/index.php?page=staff&cafe_id=' . $cafe_id, 'Смена сотрудника добавлена.');
}

function import_csv_rows(string $path): array {
    $rows = [];
    if (($handle = fopen($path, 'r')) !== false) {
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if (count($data) === 1) {
                $data = str_getcsv($data[0], ',');
            }
            $rows[] = $data;
        }
        fclose($handle);
    }
    return $rows;
}

if ($action === 'import_sales') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $limit = (int)($subscription['features']['import_limit'] ?? 0);
    if (empty($_FILES['csv_file']['tmp_name'])) {
        redirect_with_message('/index.php?page=sales&cafe_id=' . $cafe_id, 'CSV файл не загружен', 'warning');
    }
    $rows = import_csv_rows($_FILES['csv_file']['tmp_name']);
    if ($limit > 0 && count($rows) > $limit) {
        redirect_with_message('/index.php?page=sales&cafe_id=' . $cafe_id, 'Превышен лимит строк CSV для вашего тарифа', 'warning');
    }
    foreach ($rows as $row) {
        if (count($row) < 4) {
            continue;
        }
        [$recipe_name, $qty, $price_total, $sold_at] = $row;
        $stmt = db()->prepare('SELECT id FROM recipes WHERE cafe_id = ? AND name = ?');
        $stmt->execute([$cafe_id, trim($recipe_name)]);
        $recipe = $stmt->fetch();
        if (!$recipe) {
            continue;
        }
        $cost_total = calculate_recipe_cost((int)$recipe['id']) * (float)$qty;
        $stmt = db()->prepare('INSERT INTO sales (cafe_id, recipe_id, qty, price_total, cost_total, sold_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$cafe_id, $recipe['id'], (float)$qty, (float)$price_total, $cost_total, $sold_at]);
        $items_stmt = db()->prepare('SELECT ingredient_id, qty FROM recipe_items WHERE recipe_id = ?');
        $items_stmt->execute([$recipe['id']]);
        foreach ($items_stmt->fetchAll() as $item) {
            $total_qty = $item['qty'] * (float)$qty;
            db()->prepare('UPDATE ingredients SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE id = ?')->execute([$total_qty, $item['ingredient_id']]);
        }
    }
    redirect_with_message('/index.php?page=sales&cafe_id=' . $cafe_id, 'CSV импорт завершён.');
}

if ($action === 'import_sales_preview') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)($_POST['cafe_id'] ?? 0);
    if (empty($_FILES['csv_file']['tmp_name'])) {
        redirect_with_message('/index.php?page=imports', 'CSV файл не загружен', 'warning');
    }
    $rows = import_csv_rows($_FILES['csv_file']['tmp_name']);
    $limit = (int)($subscription['features']['import_limit'] ?? 0);
    if ($limit > 0 && count($rows) > $limit) {
        redirect_with_message('/index.php?page=imports', 'Превышен лимит строк CSV для вашего тарифа', 'warning');
    }
    $_SESSION['import_preview'] = [
        'cafe_id' => $cafe_id,
        'rows' => array_slice($rows, 0, 100),
        'created_at' => time(),
    ];
    redirect_with_message('/index.php?page=imports&preview=1', 'Проверьте данные перед импортом.');
}

if ($action === 'confirm_sales_import') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $preview = $_SESSION['import_preview'] ?? null;
    if (!$preview || empty($preview['rows'])) {
        redirect_with_message('/index.php?page=imports', 'Нет данных для импорта', 'warning');
    }
    $cafe_id = (int)$preview['cafe_id'];
    foreach ($preview['rows'] as $row) {
        if (count($row) < 4) {
            continue;
        }
        [$recipe_name, $qty, $price_total, $sold_at] = $row;
        $stmt = db()->prepare('SELECT id FROM recipes WHERE cafe_id = ? AND name = ?');
        $stmt->execute([$cafe_id, trim($recipe_name)]);
        $recipe = $stmt->fetch();
        if (!$recipe) {
            continue;
        }
        $cost_total = calculate_recipe_cost((int)$recipe['id']) * (float)$qty;
        $stmt = db()->prepare('INSERT INTO sales (cafe_id, recipe_id, qty, price_total, cost_total, sold_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$cafe_id, $recipe['id'], (float)$qty, (float)$price_total, $cost_total, $sold_at]);
    }
    unset($_SESSION['import_preview']);
    redirect_with_message('/index.php?page=sales&cafe_id=' . $cafe_id, 'Импорт подтверждён.');
}

if ($action === 'add_expense') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $category = trim($_POST['category']);
    $amount = (float)$_POST['amount'];
    $expense_date = $_POST['expense_date'];
    $comment = trim($_POST['comment'] ?? '');
    $stmt = db()->prepare('INSERT INTO expenses (cafe_id, category, comment, amount, expense_date) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$cafe_id, $category, $comment, $amount, $expense_date]);
    redirect_with_message('/index.php?page=expenses&cafe_id=' . $cafe_id, 'Расход добавлен.');
}

if ($action === 'save_expense_budget') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'expense_budgets')) {
        redirect_with_message('/index.php?page=expenses', 'Бюджеты доступны на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_POST['cafe_id'];
    if (!fetch_user_cafe($user, $cafe_id)) {
        redirect_with_message('/index.php?page=cafes', 'Кофейня не найдена', 'warning');
    }
    $category = trim($_POST['category']);
    $allowed_categories = get_setting('expense_categories', ['Закупка', 'Аренда', 'Зарплата', 'Маркетинг', 'Коммунальные', 'Логистика', 'Оборудование', 'Прочее']);
    if (!in_array($category, $allowed_categories, true)) {
        redirect_with_message('/index.php?page=expenses&cafe_id=' . $cafe_id, 'Некорректная категория.', 'warning');
    }
    $monthly_limit = (float)$_POST['monthly_limit'];
    if ($monthly_limit < 0) {
        redirect_with_message('/index.php?page=expenses&cafe_id=' . $cafe_id, 'Лимит не может быть отрицательным.', 'warning');
    }
    $stmt = db()->prepare('INSERT INTO expense_budgets (cafe_id, category, monthly_limit) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit)');
    $stmt->execute([$cafe_id, $category, $monthly_limit]);
    redirect_with_message('/index.php?page=expenses&cafe_id=' . $cafe_id, 'Бюджет сохранён.');
}

if ($action === 'import_expenses') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    if (empty($_FILES['csv_file']['tmp_name'])) {
        redirect_with_message('/index.php?page=expenses&cafe_id=' . $cafe_id, 'CSV файл не загружен', 'warning');
    }
    $rows = import_csv_rows($_FILES['csv_file']['tmp_name']);
    $limit = (int)($subscription['features']['import_limit'] ?? 0);
    if ($limit > 0 && count($rows) > $limit) {
        redirect_with_message('/index.php?page=expenses&cafe_id=' . $cafe_id, 'Превышен лимит строк CSV для вашего тарифа', 'warning');
    }
    foreach ($rows as $row) {
        if (count($row) < 3) {
            continue;
        }
        $category = $row[0];
        $amount = $row[1];
        $expense_date = $row[2];
        $comment = $row[3] ?? '';
        $stmt = db()->prepare('INSERT INTO expenses (cafe_id, category, comment, amount, expense_date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$cafe_id, trim($category), trim($comment), (float)$amount, $expense_date]);
    }
    redirect_with_message('/index.php?page=expenses&cafe_id=' . $cafe_id, 'CSV импорт расходов завершён.');
}

if ($action === 'import_purchases') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    if (empty($_FILES['csv_file']['tmp_name'])) {
        redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id, 'CSV файл не загружен', 'warning');
    }
    $rows = import_csv_rows($_FILES['csv_file']['tmp_name']);
    $limit = (int)($subscription['features']['import_limit'] ?? 0);
    if ($limit > 0 && count($rows) > $limit) {
        redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id, 'Превышен лимит строк CSV для вашего тарифа', 'warning');
    }
    foreach ($rows as $row) {
        if (count($row) < 4) {
            continue;
        }
        [$ingredient_name, $qty, $price_total, $purchased_at] = $row;
        $stmt = db()->prepare('SELECT * FROM ingredients WHERE cafe_id = ? AND name = ?');
        $stmt->execute([$cafe_id, trim($ingredient_name)]);
        $ingredient = $stmt->fetch();
        if (!$ingredient) {
            continue;
        }
        $purchase_unit_cost = $qty > 0 ? $price_total / $qty : 0;
        $old_qty = (float)$ingredient['stock_qty'];
        $old_cost = (float)$ingredient['cost_per_unit'];
        $new_qty = $old_qty + (float)$qty;
        $new_cost = $new_qty > 0 ? (($old_qty * $old_cost) + ($qty * $purchase_unit_cost)) / $new_qty : $old_cost;
        db()->prepare('UPDATE ingredients SET stock_qty = ?, cost_per_unit = ? WHERE id = ?')->execute([$new_qty, $new_cost, $ingredient['id']]);
        db()->prepare('INSERT INTO purchases (cafe_id, ingredient_id, qty, price_total, purchased_at) VALUES (?, ?, ?, ?, ?)')->execute([$cafe_id, $ingredient['id'], (float)$qty, (float)$price_total, $purchased_at]);
        db()->prepare('INSERT INTO ingredient_cost_history (ingredient_id, cost_per_unit) VALUES (?, ?)')->execute([$ingredient['id'], $new_cost]);
    }
    redirect_with_message('/index.php?page=ingredients&cafe_id=' . $cafe_id, 'CSV импорт закупок завершён.');
}

if ($action === 'export_sales') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'export')) {
        redirect_with_message('/index.php?page=sales&cafe_id=' . ($_GET['cafe_id'] ?? ''), 'Экспорт доступен на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_GET['cafe_id'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Дата', 'Напиток', 'Кол-во', 'Сумма', 'Себестоимость'], ';');
    $stmt = db()->prepare('SELECT s.*, r.name FROM sales s JOIN recipes r ON r.id = s.recipe_id WHERE s.cafe_id = ?');
    $stmt->execute([$cafe_id]);
    foreach ($stmt->fetchAll() as $sale) {
        fputcsv($output, [$sale['sold_at'], $sale['name'], $sale['qty'], $sale['price_total'], $sale['cost_total']], ';');
    }
    exit;
}

if ($action === 'export_pnl') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (!feature_enabled($subscription, 'export')) {
        redirect_with_message('/index.php?page=analytics&cafe_id=' . ($_GET['cafe_id'] ?? ''), 'Экспорт доступен на тарифах Pro и Maxi', 'warning');
    }
    $cafe_id = (int)$_GET['cafe_id'];
    $period = $_GET['period'] ?? 'month';
    $days = $period === 'day' ? 1 : ($period === 'week' ? 7 : 30);
    $start = (new DateTime())->modify("-{$days} days")->format('Y-m-d');
    $stmt = db()->prepare('SELECT COALESCE(SUM(price_total),0) AS revenue, COALESCE(SUM(cost_total),0) AS cogs FROM sales WHERE cafe_id = ? AND sold_at >= ?');
    $stmt->execute([$cafe_id, $start]);
    $sales = $stmt->fetch();
    $stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) AS expenses FROM expenses WHERE cafe_id = ? AND expense_date >= ?');
    $stmt->execute([$cafe_id, $start]);
    $exp = $stmt->fetch();
    $gross_profit = $sales['revenue'] - $sales['cogs'];
    $net_profit = $gross_profit - $exp['expenses'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=\"pnl.csv\"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Показатель', 'Сумма'], ';');
    fputcsv($output, ['Выручка', $sales['revenue']], ';');
    fputcsv($output, ['Себестоимость', $sales['cogs']], ';');
    fputcsv($output, ['Валовая прибыль', $gross_profit], ';');
    fputcsv($output, ['Расходы', $exp['expenses']], ';');
    fputcsv($output, ['Чистая прибыль', $net_profit], ';');
    exit;
}

if ($action === 'download_template') {
    $type = $_GET['type'] ?? '';
    $templates = [
        'sales' => ['напиток;кол-во;сумма;дата', 'Капучино;12;4200;2025-01-10'],
        'expenses' => ['категория;сумма;дата;комментарий', 'Аренда;85000;2025-01-05;Аренда за месяц'],
        'purchases' => ['ингредиент;кол-во;сумма;дата', 'Молоко;30;2100;2025-01-03'],
        'cash_shifts' => ['дата;открытие;закрытие;наличные_продажи', '2025-01-10;5000;12400;8900'],
    ];
    if (!isset($templates[$type])) {
        http_response_code(404);
        echo 'Template not found';
        exit;
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '_template.csv"');
    $output = fopen('php://output', 'w');
    foreach ($templates[$type] as $line) {
        fputcsv($output, explode(';', $line), ';');
    }
    exit;
}

if ($action === 'upload_email_import') {
    $user = require_auth();
    $subscription = require_subscription($user);
    if (empty($_FILES['csv_file']['tmp_name'])) {
        redirect_with_message('/index.php?page=imports', 'Файл не загружен', 'warning');
    }
    $cafe_id = (int)($_POST['cafe_id'] ?? 0);
    $filename = 'email_import_' . date('Ymd_His') . '_' . basename($_FILES['csv_file']['name']);
    $target = __DIR__ . '/uploads/' . $filename;
    if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $target)) {
        redirect_with_message('/index.php?page=imports', 'Не удалось сохранить файл', 'warning');
    }
    db()->prepare('INSERT INTO email_imports (cafe_id, filename, status) VALUES (?, ?, ?)')->execute([$cafe_id ?: null, $filename, 'received']);
    redirect_with_message('/index.php?page=imports', 'Файл принят и будет обработан.');
}

if ($action === 'add_calendar_event') {
    $user = require_auth();
    $subscription = require_subscription($user);
    $cafe_id = (int)$_POST['cafe_id'];
    $event_type = $_POST['event_type'] ?? 'custom';
    $title = trim($_POST['title'] ?? '');
    $amount = $_POST['amount'] !== '' ? (float)$_POST['amount'] : null;
    $due_date = $_POST['due_date'] ?? '';
    $stmt = db()->prepare('INSERT INTO calendar_events (cafe_id, event_type, title, amount, due_date) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$cafe_id, $event_type, $title, $amount, $due_date]);
    redirect_with_message('/index.php?page=calendar&cafe_id=' . $cafe_id, 'Событие добавлено.');
}

if ($action === 'init_payment') {
    $user = require_auth();
    $plan_id = (int)$_POST['plan_id'];
    $stmt = db()->prepare('SELECT * FROM plans WHERE id = ? AND active = 1');
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();
    if (!$plan) {
        redirect_with_message('/index.php?page=plans', 'Тариф недоступен', 'warning');
    }
    if ($plan['name'] === 'Trial') {
        $trial_stmt = db()->prepare('SELECT COUNT(*) AS total FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.user_id = ? AND p.name = ?');
        $trial_stmt->execute([$user['id'], 'Trial']);
        $trial_used = (int)$trial_stmt->fetch()['total'] > 0;
        $trial_payment = db()->prepare('SELECT COUNT(*) AS total FROM payments p JOIN plans pl ON pl.id = p.plan_id WHERE p.user_id = ? AND pl.name = ? AND p.status IN ("paid","pending")');
        $trial_payment->execute([$user['id'], 'Trial']);
        $trial_pending = (int)$trial_payment->fetch()['total'] > 0;
        if ($trial_used || $trial_pending) {
            redirect_with_message('/index.php?page=plans', 'Тариф Trial можно купить только один раз.', 'warning');
        }
    }
    $amount = (int)$plan['price'];
    $stmt = db()->prepare('INSERT INTO payments (user_id, plan_id, amount, status, provider) VALUES (?, ?, ?, "pending", "tinkoff")');
    $stmt->execute([$user['id'], $plan_id, $amount]);
    $payment_id = db()->lastInsertId();

    $payload = [
        'TerminalKey' => $config['tinkoff']['terminal_key'],
        'Amount' => $amount * 100,
        'OrderId' => 'KAPOUCH-' . $payment_id,
        'Description' => 'Доступ Kapouch: ' . $plan['name'],
        'Receipt' => [
            'Email' => $user['email'],
            'Taxation' => $config['tinkoff']['taxation'],
            'Items' => [[
                'Name' => 'Тариф ' . $plan['name'],
                'Price' => $amount * 100,
                'Quantity' => 1,
                'Amount' => $amount * 100,
                'Tax' => $config['tinkoff']['vat'],
            ]],
        ],
        'NotificationURL' => $config['tinkoff']['notification_url'],
        'SuccessURL' => $config['tinkoff']['success_url'],
        'FailURL' => $config['tinkoff']['fail_url'],
    ];
    $token = tinkoff_token($payload, $config['tinkoff']['password']);
    $payload['Token'] = $token;
    $response = tinkoff_request('https://securepay.tinkoff.ru/v2/Init', $payload);
    if (empty($response['data']['PaymentURL'])) {
        $retry = tinkoff_request('https://securepay.tinkoff.ru/v2/Init', $payload);
        $response = $retry['data']['PaymentURL'] ? $retry : $response;
    }
    if (empty($response['data']['PaymentURL'])) {
        db()->prepare('UPDATE payments SET status = "failed", payload = ? WHERE id = ?')->execute([json_encode($response, JSON_UNESCAPED_UNICODE), $payment_id]);
        redirect_with_message('/index.php?page=plans', 'Ошибка инициализации платежа. Проверьте настройки Тинькофф или повторите позже.', 'warning');
    }
    db()->prepare('UPDATE payments SET payment_id = ?, payload = ? WHERE id = ?')->execute([$response['data']['PaymentId'] ?? null, json_encode($response, JSON_UNESCAPED_UNICODE), $payment_id]);
    header('Location: ' . $response['data']['PaymentURL']);
    exit;
}

if ($action === 'tinkoff_callback') {
    $data = json_decode(file_get_contents('php://input'), true);
    $payment_id = $data['OrderId'] ?? '';
    $status = $data['Status'] ?? '';
    $payment_db_id = (int)str_replace('KAPOUCH-', '', $payment_id);
    $stmt = db()->prepare('SELECT * FROM payments WHERE id = ?');
    $stmt->execute([$payment_db_id]);
    $payment = $stmt->fetch();
    if (!$payment) {
        http_response_code(404);
        echo 'not found';
        exit;
    }
    $event_type = $status ?: 'unknown';
    db()->prepare('INSERT IGNORE INTO payment_events (payment_id, event_type, payload) VALUES (?, ?, ?)')
        ->execute([$payment_db_id, $event_type, json_encode($data, JSON_UNESCAPED_UNICODE)]);
    if ($payment['status'] === 'paid') {
        echo 'OK';
        exit;
    }
    if ($status === 'CONFIRMED' || $status === 'AUTHORIZED') {
        db()->prepare('UPDATE payments SET status = "paid", paid_at = NOW() WHERE id = ?')->execute([$payment_db_id]);
        $plan_stmt = db()->prepare('SELECT * FROM plans WHERE id = ?');
        $plan_stmt->execute([$payment['plan_id']]);
        $plan = $plan_stmt->fetch();
        $active_stmt = db()->prepare('SELECT * FROM subscriptions WHERE user_id = ? AND status = "active" ORDER BY ends_at DESC LIMIT 1');
        $active_stmt->execute([$payment['user_id']]);
        $active_sub = $active_stmt->fetch();
        $now = new DateTime();
        if ($active_sub && (int)$active_sub['plan_id'] === (int)$payment['plan_id']) {
            $current_end = new DateTime($active_sub['ends_at']);
            $base = $current_end > $now ? $current_end : $now;
            $new_end = (clone $base)->modify('+' . (int)$plan['duration_days'] . ' days');
            db()->prepare('UPDATE subscriptions SET ends_at = ?, status = "active" WHERE id = ?')->execute([$new_end->format('Y-m-d H:i:s'), $active_sub['id']]);
        } else {
            db()->prepare('UPDATE subscriptions SET status = "canceled" WHERE user_id = ? AND status = "active"')->execute([$payment['user_id']]);
            $ends = (clone $now)->modify('+' . (int)$plan['duration_days'] . ' days');
            db()->prepare('INSERT INTO subscriptions (user_id, plan_id, starts_at, ends_at, status) VALUES (?, ?, ?, ?, "active")')->execute([$payment['user_id'], $payment['plan_id'], $now->format('Y-m-d H:i:s'), $ends->format('Y-m-d H:i:s')]);
        }
        db()->prepare('INSERT INTO receipts (payment_id, receipt_no, status, payload) VALUES (?, ?, ?, ?)')->execute([$payment_db_id, $data['ReceiptId'] ?? null, 'success', json_encode($data, JSON_UNESCAPED_UNICODE)]);
        echo 'OK';
        exit;
    }
    db()->prepare('UPDATE payments SET status = "failed" WHERE id = ?')->execute([$payment_db_id]);
    db()->prepare('INSERT INTO receipts (payment_id, receipt_no, status, payload) VALUES (?, ?, ?, ?)')->execute([$payment_db_id, $data['ReceiptId'] ?? null, 'failed', json_encode($data, JSON_UNESCAPED_UNICODE)]);
    echo 'OK';
    exit;
}

if ($action === 'update_plan') {
    $user = require_auth();
    require_admin($user);
    $plan_id = (int)$_POST['plan_id'];
    $price = (int)$_POST['price'];
    $duration_days = (int)$_POST['duration_days'];
    $max_cafes = (int)$_POST['max_cafes'];
    $active = (int)$_POST['active'];
    $stmt = db()->prepare('UPDATE plans SET price = ?, duration_days = ?, max_cafes = ?, active = ? WHERE id = ?');
    $stmt->execute([$price, $duration_days, $max_cafes, $active, $plan_id]);
    redirect_with_message('/index.php?page=admin&tab=plans', 'Тариф обновлён.');
}

if ($action === 'extend_subscription') {
    $user = require_auth();
    require_admin($user);
    $subscription_id = (int)$_POST['subscription_id'];
    $days = (int)$_POST['days'];
    $stmt = db()->prepare('SELECT * FROM subscriptions WHERE id = ?');
    $stmt->execute([$subscription_id]);
    $sub = $stmt->fetch();
    if ($sub && $days > 0) {
        $current_end = new DateTime($sub['ends_at']);
        $base = $current_end > new DateTime() ? $current_end : new DateTime();
        $new_end = $base->modify("+{$days} days")->format('Y-m-d H:i:s');
        db()->prepare('UPDATE subscriptions SET ends_at = ?, status = "active" WHERE id = ?')->execute([$new_end, $subscription_id]);
        redirect_with_message('/index.php?page=admin&tab=subscriptions', 'Подписка продлена.');
    }
    redirect_with_message('/index.php?page=admin&tab=subscriptions', 'Не удалось продлить подписку.', 'warning');
}

if ($action === 'cancel_subscription') {
    $user = require_auth();
    require_admin($user);
    $subscription_id = (int)$_POST['subscription_id'];
    db()->prepare('UPDATE subscriptions SET status = "canceled", ends_at = NOW() WHERE id = ?')->execute([$subscription_id]);
    redirect_with_message('/index.php?page=admin&tab=subscriptions', 'Доступ отключён.');
}

if ($action === 'toggle_role') {
    $user = require_auth();
    require_admin($user);
    $user_id = (int)$_POST['user_id'];
    $stmt = db()->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $target = $stmt->fetch();
    if ($target) {
        $new_role = $target['role'] === 'admin' ? 'owner' : 'admin';
        db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$new_role, $user_id]);
    }
    redirect_with_message('/index.php?page=admin&tab=users', 'Роль обновлена.');
}

if ($action === 'update_landing') {
    $user = require_auth();
    require_admin($user);
    $advantages = json_decode($_POST['advantages'] ?? '[]', true);
    $testimonials = json_decode($_POST['testimonials'] ?? '[]', true);
    $landing = [
        'hero_title' => trim($_POST['hero_title'] ?? ''),
        'hero_subtitle' => trim($_POST['hero_subtitle'] ?? ''),
        'cta_primary' => trim($_POST['cta_primary'] ?? ''),
        'cta_secondary' => trim($_POST['cta_secondary'] ?? ''),
        'advantages' => $advantages ?: [],
        'testimonials' => $testimonials ?: [],
    ];
    set_setting('landing', $landing);
    redirect_with_message('/index.php?page=admin&tab=landing', 'Лендинг обновлён.');
}

function tinkoff_token(array $payload, string $password): string {
    $payload['Password'] = $password;
    ksort($payload);
    $values = '';
    foreach ($payload as $value) {
        if (is_array($value)) {
            continue;
        }
        $values .= $value;
    }
    return hash('sha256', $values);
}

function tinkoff_request(string $url, array $payload): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        return ['status' => 0, 'data' => [], 'error' => curl_error($ch)];
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'data' => json_decode($response, true) ?: [], 'raw' => $response];
}

function fetch_company_data(string $inn): ?array {
    $config = require __DIR__ . '/config.php';
    $provider = $config['company_data']['provider'] ?? '';
    if ($provider !== 'dadata') {
        return null;
    }
    $token = $config['company_data']['token'] ?? '';
    if (!$token || $token === 'DADATA_API_KEY') {
        return null;
    }
    $payload = json_encode(['query' => $inn, 'count' => 1], JSON_UNESCAPED_UNICODE);
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Token ' . $token,
    ];
    if (!empty($config['company_data']['secret']) && $config['company_data']['secret'] !== 'DADATA_SECRET') {
        $headers[] = 'X-Secret: ' . $config['company_data']['secret'];
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $payload,
            'timeout' => (int)($config['company_data']['timeout'] ?? 10),
        ],
    ]);
    $result = @file_get_contents($config['company_data']['base_url'], false, $context);
    if (!$result) {
        return null;
    }
    $data = json_decode($result, true);
    $suggestion = $data['suggestions'][0]['data'] ?? null;
    if (!$suggestion) {
        return null;
    }
    $entity_type = ($suggestion['type'] ?? '') === 'INDIVIDUAL' ? 'individual' : 'company';
    $status = $suggestion['state']['status'] ?? '';
    $status_map = [
        'ACTIVE' => 'Действующая',
        'LIQUIDATING' => 'В процессе ликвидации',
        'LIQUIDATED' => 'Ликвидирована',
        'REORGANIZING' => 'В процессе реорганизации',
        'BANKRUPT' => 'Банкротство',
    ];
    return [
        'company_name' => $suggestion['name']['full_with_opf'] ?? '',
        'short_name' => $suggestion['name']['short_with_opf'] ?? '',
        'ogrn' => $suggestion['ogrn'] ?? ($suggestion['ogrnip'] ?? ''),
        'kpp' => $suggestion['kpp'] ?? '',
        'address' => $suggestion['address']['value'] ?? '',
        'ceo_name' => $suggestion['management']['name'] ?? '',
        'status' => $status_map[$status] ?? $status,
        'entity_type' => $entity_type,
    ];
}

http_response_code(404);
echo 'Not found';
