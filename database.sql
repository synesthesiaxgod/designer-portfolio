-- Создание базы данных
CREATE DATABASE IF NOT EXISTS designer_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE designer_portfolio;

-- Таблица пользователей
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('client', 'admin') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица категорий
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0
);

-- Таблица услуг
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price_from DECIMAL(10,2),
    price_to DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Таблица портфолио
CREATE TABLE portfolio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    title VARCHAR(200) NOT NULL,
    image VARCHAR(255),
    description TEXT,
    task_description TEXT,
    process_description TEXT,
    result_description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Таблица заявок
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    service_id INT,
    description TEXT NOT NULL,
    budget DECIMAL(10,2),
    deadline DATE,
    final_price DECIMAL(10,2),
    status ENUM('new', 'in_progress', 'review', 'completed', 'rejected') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- Таблица файлов заявок
CREATE TABLE order_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Таблица сообщений (чат)
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица контактных сообщений (с публичной формы)
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Настройки сайта
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT
);

-- Вставка администратора (пароль: admin123)
INSERT INTO users (username, password, name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Анна Дизайнер', 'admin');

-- Вставка категорий
INSERT INTO categories (name, slug, sort_order) VALUES
('Фирменный стиль', 'brand-identity', 1),
('Полиграфия', 'print', 2),
('Дизайн упаковки', 'packaging', 3),
('Иллюстрация', 'illustration', 4),
('Другое', 'other', 5);

-- Вставка настроек
INSERT INTO settings (setting_key, setting_value) VALUES
('designer_phone', '+7 (999) 123-45-67'),
('designer_telegram', '@designer_anna'),
('site_title', 'Анна Дизайн | Профессиональный дизайн на заказ'),
('hero_title', 'Профессиональный дизайн на заказ'),
('hero_subtitle', 'Создаю уникальный визуальный стиль для вашего бизнеса'),
('about_text', 'Привет! Меня зовут Анна, я профессиональный дизайнер с более чем 10-летним опытом работы. Специализируюсь на создании фирменного стиля, полиграфии и иллюстрациях.');

-- Вставка демо-услуг
INSERT INTO services (category_id, title, description, price_from, price_to, sort_order) VALUES
(1, 'Разработка логотипа', 'Создание уникального логотипа с несколькими вариантами концепций', 15000, 50000, 1),
(1, 'Фирменный стиль', 'Полный пакет: логотип, цвета, шрифты, визитки, бланки', 50000, 150000, 2),
(1, 'Брендбук', 'Руководство по использованию фирменного стиля', 30000, 80000, 3),
(2, 'Дизайн визитки', 'Двусторонняя визитная карточка', 3000, 8000, 4),
(2, 'Дизайн буклета', 'Буклет любого формата', 10000, 30000, 5),
(2, 'Дизайн каталога', 'Многостраничный каталог продукции', 25000, 100000, 6),
(3, 'Дизайн упаковки', 'Разработка дизайна упаковки продукта', 20000, 80000, 7),
(4, 'Иллюстрация', 'Уникальная иллюстрация для любых целей', 5000, 50000, 8);

-- Вставка демо-работ в портфолио
INSERT INTO portfolio (category_id, title, image, description, task_description, process_description, result_description) VALUES
(1, 'Логотип для кофейни "Бодрое утро"', 'portfolio_1.jpg', 'Разработка фирменного стиля для сети кофеен', 'Клиент обратился с задачей создать запоминающийся и тёплый образ для новой сети кофеен.', 'Было разработано несколько концепций, проведены исследования целевой аудитории, выбрана финальная версия.', 'Логотип успешно внедрён, клиент доволен результатом.'),
(2, 'Каталог мебельной компании', 'portfolio_2.jpg', 'Дизайн и вёрстка каталога на 48 страниц', 'Необходимо было создать премиальный каталог для мебельного производства.', 'Разработана сетка, стилистика, выполнена фотосъёмка и вёрстка.', 'Каталог напечатан тиражом 5000 экземпляров.'),
(3, 'Упаковка для чая "Горные травы"', 'portfolio_3.jpg', 'Серия упаковок для линейки травяных чаёв', 'Создать экологичный и привлекательный дизайн упаковки.', 'Исследование конкурентов, разработка иллюстраций, подготовка к печати.', 'Продукт успешно запущен в продажу.'),
(4, 'Иллюстрации для детской книги', 'portfolio_4.jpg', 'Серия из 20 иллюстраций для сказки', 'Иллюстрации в акварельном стиле для детской книги.', 'Создание эскизов, согласование с автором, финальная отрисовка.', 'Книга издана и получила положительные отзывы.');
