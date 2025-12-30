<?php
$config = require __DIR__ . '/config.php';

session_name($config['security']['session_name']);
session_start();

function db(): PDO {
    static $pdo;
    if ($pdo) {
        return $pdo;
    }
    $config = require __DIR__ . '/config.php';
    $db = $config['db'];
    $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $db['driver'], $db['host'], $db['port'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function require_csrf(): void {
    $token = $_POST['_csrf'] ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo 'Некорректный CSRF токен';
        exit;
    }
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_auth(): array {
    $user = current_user();
    if (!$user) {
        header('Location: /index.php?page=login');
        exit;
    }
    return $user;
}

function require_admin(array $user): void {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo 'Доступ запрещён';
        exit;
    }
}

function get_setting(string $key, $default = null) {
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        return $default;
    }
    return json_decode($row['setting_value'], true);
}

function set_setting(string $key, array $value): void {
    $payload = json_encode($value, JSON_UNESCAPED_UNICODE);
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([$key, $payload]);
}

function active_subscription(int $user_id): ?array {
    $stmt = db()->prepare('SELECT s.*, p.name AS plan_name, p.max_cafes, p.features_json, p.price, p.duration_days FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.user_id = ? AND s.status = "active" AND s.ends_at >= NOW() ORDER BY s.ends_at DESC LIMIT 1');
    $stmt->execute([$user_id]);
    $sub = $stmt->fetch();
    if (!$sub) {
        return null;
    }
    $sub['features'] = json_decode($sub['features_json'], true);
    return $sub;
}

function plan_features(int $plan_id): array {
    $stmt = db()->prepare('SELECT features_json FROM plans WHERE id = ?');
    $stmt->execute([$plan_id]);
    $row = $stmt->fetch();
    return $row ? json_decode($row['features_json'], true) : [];
}

function feature_enabled(?array $subscription, string $feature): bool {
    if (!$subscription) {
        return false;
    }
    return !empty($subscription['features'][$feature]);
}

function user_cafe_limit(?array $subscription): int {
    if (!$subscription) {
        return 0;
    }
    return (int)$subscription['max_cafes'];
}

function format_money($value): string {
    return number_format((float)$value, 2, ',', ' ');
}

function lower_text(string $value): string {
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
}

function calculate_recipe_cost(int $recipe_id): float {
    $stmt = db()->prepare('SELECT ri.qty, i.cost_per_unit FROM recipe_items ri JOIN ingredients i ON i.id = ri.ingredient_id WHERE ri.recipe_id = ?');
    $stmt->execute([$recipe_id]);
    $total = 0.0;
    foreach ($stmt->fetchAll() as $row) {
        $total += $row['qty'] * $row['cost_per_unit'];
    }
    return $total;
}

function send_email(string $to, string $subject, string $message): void {
    $config = require __DIR__ . '/config.php';
    $headers = "From: {$config['email']['from']}\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($to, $subject, $message, $headers);
}

function require_subscription(array $user): array {
    $subscription = active_subscription((int)$user['id']);
    if (!$subscription) {
        header('Location: /index.php?page=plans');
        exit;
    }
    return $subscription;
}

function resolve_cafe_id(array $user, ?int $requested_id = null): ?int {
    if ($requested_id) {
        $stmt = db()->prepare('SELECT id FROM cafes WHERE id = ? AND user_id = ?');
        $stmt->execute([$requested_id, $user['id']]);
        if ($stmt->fetch()) {
            return $requested_id;
        }
    }
    $stmt = db()->prepare('SELECT id FROM cafes WHERE user_id = ? ORDER BY created_at ASC LIMIT 1');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

function fetch_user_cafe(array $user, int $cafe_id): ?array {
    $stmt = db()->prepare('SELECT * FROM cafes WHERE id = ? AND user_id = ?');
    $stmt->execute([$cafe_id, $user['id']]);
    $cafe = $stmt->fetch();
    return $cafe ?: null;
}

function render_badge(string $text, string $class = 'badge'): string {
    return "<span class=\"{$class}\">" . e($text) . "</span>";
}
