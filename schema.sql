-- Kapouch database schema
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('owner','admin') NOT NULL DEFAULT 'owner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cafes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(190) NOT NULL,
    city VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    price INT NOT NULL,
    duration_days INT NOT NULL,
    max_cafes INT NOT NULL,
    features_json JSON NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('active','expired','canceled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount INT NOT NULL,
    status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    provider VARCHAR(30) NOT NULL,
    payment_id VARCHAR(64) DEFAULT NULL,
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
);

CREATE TABLE receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    receipt_no VARCHAR(64) DEFAULT NULL,
    status VARCHAR(30) NOT NULL,
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);

CREATE TABLE ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    name VARCHAR(190) NOT NULL,
    unit VARCHAR(30) NOT NULL,
    cost_per_unit DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE ingredient_cost_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    cost_per_unit DECIMAL(12,2) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
);

CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    name VARCHAR(190) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE recipe_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    qty DECIMAL(12,3) NOT NULL,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
);

CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    qty DECIMAL(12,3) NOT NULL,
    price_total DECIMAL(12,2) NOT NULL,
    purchased_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    category VARCHAR(120) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    recipe_id INT NOT NULL,
    qty DECIMAL(12,2) NOT NULL,
    price_total DECIMAL(12,2) NOT NULL,
    cost_total DECIMAL(12,2) NOT NULL,
    sold_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
);

CREATE TABLE kpi_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    target_margin DECIMAL(6,2) NOT NULL DEFAULT 0,
    target_profit DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value JSON NOT NULL
);

INSERT INTO plans (name, price, duration_days, max_cafes, features_json, active) VALUES
('Trial', 299, 7, 1, JSON_OBJECT(
    'pnl_basic', true,
    'pnl_full', false,
    'analytics_periods', false,
    'comparisons', false,
    'breakeven', false,
    'unit_economics', false,
    'kpi', false,
    'alerts', false,
    'export', false,
    'import_limit', 50,
    'recommendations', false,
    'what_if', false,
    'advanced_analytics', false
), 1),
('Pro', 1299, 30, 3, JSON_OBJECT(
    'pnl_basic', true,
    'pnl_full', true,
    'analytics_periods', true,
    'comparisons', true,
    'breakeven', true,
    'unit_economics', true,
    'kpi', true,
    'alerts', true,
    'export', true,
    'import_limit', 1000000,
    'recommendations', false,
    'what_if', false,
    'advanced_analytics', false
), 1),
('Maxi', 2499, 30, 10, JSON_OBJECT(
    'pnl_basic', true,
    'pnl_full', true,
    'analytics_periods', true,
    'comparisons', true,
    'breakeven', true,
    'unit_economics', true,
    'kpi', true,
    'alerts', true,
    'export', true,
    'import_limit', 1000000,
    'recommendations', true,
    'what_if', true,
    'advanced_analytics', true
), 1);

INSERT INTO settings (setting_key, setting_value) VALUES
('landing', JSON_OBJECT(
    'hero_title', 'Финансовая система для владельцев кофеен',
    'hero_subtitle', 'Полный контроль маржи, себестоимости и прибыли за 10 минут в день.',
    'cta_primary', 'Попробовать 7 дней',
    'cta_secondary', 'Купить доступ',
    'advantages', JSON_ARRAY(
        JSON_OBJECT('title','Себестоимость по рецептам','text','Средневзвешенный метод и история изменений ингредиентов.'),
        JSON_OBJECT('title','P&L и unit-экономика','text','Понимайте, где зарабатываете, а где теряете.'),
        JSON_OBJECT('title','Контроль расходов','text','OPEX, точки безубыточности и предупреждения.'),
        JSON_OBJECT('title','Готово к приёму денег','text','Тинькофф СБП + фискализация по 54-ФЗ.')
    ),
    'testimonials', JSON_ARRAY(
        JSON_OBJECT('name','Алина, сеть кофеен в Казани','text','Перестали продавать убыточные напитки уже на второй неделе.'),
        JSON_OBJECT('name','Илья, владелец кофейни в Москве','text','Вижу P&L и маржу по каждому напитку. Решения принимаю быстрее.'),
        JSON_OBJECT('name','Мария, кофейня в Екатеринбурге','text','Сервис полностью заменил Excel и дал прозрачную аналитику.')
    )
));
