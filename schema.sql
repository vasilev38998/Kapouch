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
    reorder_level DECIMAL(12,3) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE ingredient_cost_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    cost_per_unit DECIMAL(12,2) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    INDEX idx_ingredient_costs (ingredient_id, recorded_at)
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
    comment VARCHAR(255) DEFAULT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE,
    INDEX idx_expenses_cafe_date (cafe_id, expense_date)
);

CREATE TABLE expense_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    category VARCHAR(120) NOT NULL,
    monthly_limit DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_budget (cafe_id, category),
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
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    INDEX idx_sales_cafe_date (cafe_id, sold_at)
);

CREATE TABLE daily_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    item VARCHAR(190) NOT NULL,
    checklist_date DATE NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE,
    INDEX idx_checklist_cafe_date (cafe_id, checklist_date)
);

CREATE TABLE checklist_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    item VARCHAR(190) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE,
    INDEX idx_checklist_templates_cafe (cafe_id)
);

CREATE TABLE writeoffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    qty DECIMAL(12,3) NOT NULL,
    reason VARCHAR(190) DEFAULT NULL,
    writeoff_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    INDEX idx_writeoffs_cafe_date (cafe_id, writeoff_date)
);

CREATE TABLE kpi_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    target_margin DECIMAL(6,2) NOT NULL DEFAULT 0,
    target_profit DECIMAL(12,2) NOT NULL DEFAULT 0,
    target_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    event_type ENUM('rent','salary','tax','payment','custom') NOT NULL,
    title VARCHAR(190) NOT NULL,
    amount DECIMAL(12,2) DEFAULT NULL,
    due_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE cash_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    shift_date DATE NOT NULL,
    opening_cash DECIMAL(12,2) NOT NULL DEFAULT 0,
    closing_cash DECIMAL(12,2) NOT NULL DEFAULT 0,
    cash_sales DECIMAL(12,2) NOT NULL DEFAULT 0,
    difference DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE plan_fact_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    target_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
    target_profit DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT NOT NULL,
    name VARCHAR(190) NOT NULL,
    role VARCHAR(120) NOT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE
);

CREATE TABLE staff_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    shift_date DATE NOT NULL,
    hours DECIMAL(6,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);

CREATE TABLE payment_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_payment_event (payment_id, event_type),
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);

CREATE TABLE aqsi_sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT DEFAULT NULL,
    status VARCHAR(30) NOT NULL,
    message VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE email_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cafe_id INT DEFAULT NULL,
    filename VARCHAR(255) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'received',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    'smart_calendar', false,
    'smart_reminders', false,
    'daily_focus', false,
    'quick_report', true,
    'cashflow_forecast', false,
    'category_insights', false,
    'anomaly_radar', false,
    'benchmark_gap', false,
    'inventory_alerts', true,
    'writeoffs', false,
    'cashflow_90', false,
    'profit_forecast', false,
    'kpi_alerts', false,
    'abc_visuals', true,
    'advanced_recommendations', false,
    'export_pdf', false,
    'export_1c', false,
    'expense_budgets', false,
    'cost_alerts', false,
    'daily_checklist', false,
    'staff_efficiency', false,
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
    'smart_calendar', true,
    'smart_reminders', true,
    'daily_focus', true,
    'quick_report', true,
    'cashflow_forecast', true,
    'category_insights', true,
    'anomaly_radar', false,
    'benchmark_gap', false,
    'inventory_alerts', true,
    'writeoffs', true,
    'cashflow_90', true,
    'profit_forecast', true,
    'kpi_alerts', true,
    'abc_visuals', true,
    'advanced_recommendations', false,
    'export_pdf', true,
    'export_1c', false,
    'expense_budgets', true,
    'cost_alerts', true,
    'daily_checklist', true,
    'staff_efficiency', true,
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
    'smart_calendar', true,
    'smart_reminders', true,
    'daily_focus', true,
    'quick_report', true,
    'cashflow_forecast', true,
    'category_insights', true,
    'anomaly_radar', true,
    'benchmark_gap', true,
    'inventory_alerts', true,
    'writeoffs', true,
    'cashflow_90', true,
    'profit_forecast', true,
    'kpi_alerts', true,
    'abc_visuals', true,
    'advanced_recommendations', true,
    'export_pdf', true,
    'export_1c', true,
    'expense_budgets', true,
    'cost_alerts', true,
    'daily_checklist', true,
    'staff_efficiency', true,
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
)),
('expense_categories', JSON_ARRAY('Закупка', 'Аренда', 'Зарплата', 'Маркетинг', 'Коммунальные', 'Логистика', 'Оборудование', 'Прочее')),
('cost_alert_threshold', CAST(0.15 AS JSON));

INSERT INTO users (id, email, password_hash, role, created_at) VALUES
(1, 'demo@kapouch.ru', '$2y$12$wYyuUvHIE0H2HwLgYwIXt.vhXr.IWLrkDIJ.qvoTh8L6Mvd37EE1.', 'owner', NOW());

INSERT INTO cafes (id, user_id, name, city, created_at) VALUES
(1, 1, 'Kapouch Demo', 'Москва', NOW());

INSERT INTO subscriptions (user_id, plan_id, starts_at, ends_at, status) VALUES
(1, 3, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active');

INSERT INTO ingredients (id, cafe_id, name, unit, cost_per_unit, stock_qty, created_at) VALUES
(1, 1, 'Кофейное зерно', 'г', 1.80, 8000, NOW()),
(2, 1, 'Молоко', 'мл', 0.12, 25000, NOW()),
(3, 1, 'Сахар', 'г', 0.20, 5000, NOW()),
(4, 1, 'Стакан бумажный 200 мл', 'шт', 3.50, 800, NOW()),
(5, 1, 'Стакан бумажный 300 мл', 'шт', 4.20, 700, NOW()),
(6, 1, 'Стакан бумажный 400 мл', 'шт', 4.80, 600, NOW()),
(7, 1, 'Стакан бумажный 500 мл', 'шт', 5.30, 500, NOW()),
(8, 1, 'Стакан пластиковый 300 мл', 'шт', 3.10, 650, NOW()),
(9, 1, 'Стакан пластиковый 400 мл', 'шт', 3.60, 550, NOW()),
(10, 1, 'Стакан пластиковый 500 мл', 'шт', 4.00, 450, NOW()),
(11, 1, 'Крышки для стаканов', 'шт', 1.20, 1200, NOW()),
(12, 1, 'Трубочки', 'шт', 0.60, 1500, NOW()),
(13, 1, 'Сливки', 'мл', 0.20, 8000, NOW()),
(14, 1, 'Манжетки для стаканов', 'шт', 1.10, 900, NOW()),
(15, 1, 'Какао', 'г', 0.90, 2000, NOW()),
(16, 1, 'Чай листовой', 'г', 0.70, 2500, NOW()),
(17, 1, 'Сироп ваниль', 'мл', 0.45, 3000, NOW()),
(18, 1, 'Сироп карамель', 'мл', 0.45, 3000, NOW()),
(19, 1, 'Лёд', 'г', 0.05, 10000, NOW()),
(20, 1, 'Вода питьевая', 'мл', 0.02, 50000, NOW()),
(21, 1, 'Лимон', 'г', 0.15, 1500, NOW()),
(22, 1, 'Мята', 'г', 0.25, 800, NOW()),
(23, 1, 'Шоколад', 'г', 1.10, 1200, NOW());

INSERT INTO recipes (id, cafe_id, name, price, created_at) VALUES
(1, 1, 'Эспрессо', 190.00, NOW()),
(2, 1, 'Капучино', 260.00, NOW()),
(3, 1, 'Латте', 290.00, NOW()),
(4, 1, 'Какао', 240.00, NOW()),
(5, 1, 'Чай лимонный', 180.00, NOW());

INSERT INTO recipe_items (recipe_id, ingredient_id, qty) VALUES
(1, 1, 18),
(2, 1, 18),
(2, 2, 150),
(2, 4, 1),
(2, 11, 1),
(2, 14, 1),
(3, 1, 18),
(3, 2, 200),
(3, 3, 5),
(3, 5, 1),
(3, 11, 1),
(3, 14, 1),
(4, 2, 200),
(4, 15, 12),
(4, 5, 1),
(4, 11, 1),
(5, 16, 5),
(5, 21, 8),
(5, 20, 250),
(5, 5, 1),
(5, 11, 1);

INSERT INTO calendar_events (cafe_id, event_type, title, amount, due_date) VALUES
(1, 'rent', 'Аренда помещения', 85000, DATE_ADD(CURDATE(), INTERVAL 5 DAY)),
(1, 'salary', 'Зарплата бариста', 120000, DATE_ADD(CURDATE(), INTERVAL 10 DAY)),
(1, 'tax', 'Налоги и взносы', 45000, DATE_ADD(CURDATE(), INTERVAL 15 DAY));
