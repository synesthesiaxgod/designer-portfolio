# Отчёт по разработке веб-сервиса для дизайнера-фрилансера

## 1. Введение

### 1.1 Актуальность

В современном мире дизайнеры-фрилансеры сталкиваются с необходимостью профессионального онлайн-присутствия для привлечения клиентов и эффективного управления заказами. Традиционные методы работы, такие как общение через мессенджеры и использование разрозненных инструментов, часто приводят к потере информации, неэффективному управлению временем и сложностям в отслеживании проектов.

Создание единой веб-платформы, объединяющей портфолио, систему приёма заявок и коммуникацию с клиентами, позволяет дизайнеру автоматизировать рутинные процессы, создать профессиональный имидж и повысить качество обслуживания клиентов. Такая платформа обеспечивает прозрачность работы, удобство для обеих сторон и возможность масштабирования бизнеса.

### 1.2 Цель работы

Создать профессиональную, безопасную и удобную веб-платформу для привлечения клиентов, презентации работ и управления полным циклом заказа — от первичного обращения до завершения проекта с возможностью коммуникации на каждом этапе.

### 1.3 Задачи

1. Спроектировать архитектуру веб-приложения и структуру базы данных
2. Разработать публичную часть сайта (портфолио, услуги, контакты)
3. Реализовать систему регистрации и авторизации пользователей без использования email
4. Создать личный кабинет клиента с возможностью создания и отслеживания заявок
5. Разработать административную панель для управления контентом и заявками
6. Внедрить систему обмена сообщениями между клиентом и дизайнером
7. Обеспечить безопасность приложения (защита от SQL-инъекций, XSS, CSRF)
8. Провести тестирование функционала и безопасности

## 2. Решение задач

### 2.1 Проектирование

#### 2.1.1 Архитектура приложения

Проект построен по модульному принципу с разделением на слои:

- **Слой представления (View)**: HTML-шаблоны с PHP-вставками, использующие Bootstrap 5
- **Слой бизнес-логики (Controller)**: PHP-скрипты, обрабатывающие запросы и взаимодействующие с базой данных
- **Слой данных (Model)**: Функции для работы с базой данных через PDO

Структура проекта:
```
designer-portfolio/
├── includes/          # Общие модули
├── assets/           # Статические ресурсы
├── admin/            # Административная панель
├── uploads/          # Загруженные файлы
├── database.sql      # Схема БД
└── *.php            # Страницы приложения
```

#### 2.1.2 Схема базы данных

База данных состоит из 8 основных таблиц:

1. **users** - Пользователи системы (клиенты и администраторы)
2. **categories** - Категории услуг и работ портфолио
3. **services** - Услуги дизайнера с ценами
4. **portfolio** - Работы портфолио
5. **orders** - Заявки клиентов
6. **order_files** - Файлы, прикреплённые к заявкам
7. **messages** - Сообщения в чате по заявкам
8. **contact_messages** - Сообщения с контактной формы
9. **settings** - Настройки сайта

Связи между таблицами реализованы через внешние ключи с каскадным удалением для зависимых записей.

#### 2.1.3 Обоснование выбора технологий

- **PHP** - универсальный серверный язык с широкой поддержкой хостингов
- **MySQL** - надёжная реляционная СУБД с хорошей производительностью
- **PDO** - современный интерфейс для работы с БД, обеспечивающий безопасность
- **Bootstrap 5** - современный CSS-фреймворк для адаптивного дизайна
- **Чистый PHP без фреймворков** - простота развёртывания и поддержки

### 2.2 Реализация

#### 2.2.1 Система авторизации

Реализована авторизация по логину и паролю без использования email:

```php
function loginUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT id, username, password, name, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        return ['success' => true, 'role' => $user['role']];
    }
    
    return ['success' => false, 'message' => 'Неверный логин или пароль'];
}
```

Ключевые особенности:
- Хеширование паролей с помощью `password_hash()` и `PASSWORD_DEFAULT` (bcrypt)
- Проверка паролей через `password_verify()`
- Хранение данных пользователя в сессии
- Разделение ролей: клиент и администратор

#### 2.2.2 Публичная часть сайта

**Главная страница (index.php)**:
- Hero-секция с градиентным фоном
- Превью услуг из базы данных
- Последние работы портфолио
- Адаптивная сетка на Bootstrap 5

**Портфолио (portfolio.php)**:
- Фильтрация по категориям с помощью JavaScript
- Адаптивная сетка работ (3 колонки на десктопе)
- Переход к детальному просмотру проекта

**Услуги (services.php)**:
- Группировка по категориям
- Отображение диапазона цен
- CTA-кнопки для оформления заказа

#### 2.2.3 Личный кабинет клиента

**Панель управления (dashboard.php)**:
- Статистика заявок по статусам
- Последние 5 заявок с быстрым доступом
- Кнопка создания новой заявки

**Создание заявки (create-order.php)**:
```php
// Обработка загрузки файлов
$orderDir = UPLOAD_DIR . 'orders/' . $orderId . '/';
if (!is_dir($orderDir)) {
    mkdir($orderDir, 0755, true);
}

for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
    $file = [
        'name' => $_FILES['files']['name'][$i],
        'tmp_name' => $_FILES['files']['tmp_name'][$i],
        'size' => $_FILES['files']['size'][$i],
        'error' => $_FILES['files']['error'][$i]
    ];
    
    $result = uploadFile($file, $orderDir);
    if ($result['success']) {
        // Сохранение в БД
    }
}
```

**Детали заявки (order.php)**:
- Полная информация о заявке
- Условное отображение контактов дизайнера (только для статусов "В работе" и "На согласовании")
- Встроенный чат для коммуникации
- Скачивание прикреплённых файлов

#### 2.2.4 Административная панель

**Управление заявками (admin/orders.php)**:
- Фильтрация по статусу
- Поиск по имени клиента
- Просмотр и изменение статуса заявок
- Установка финальной цены

**Управление портфолио (admin/portfolio.php, admin/portfolio-edit.php)**:
- CRUD операции для работ портфолио
- Загрузка изображений
- Редактирование описаний (задача, процесс, результат)

**Управление услугами (admin/services.php, admin/service-edit.php)**:
- CRUD операции для услуг
- Настройка цен и категорий
- Активация/деактивация услуг

#### 2.2.5 Система обмена сообщениями

Реализован встроенный чат для каждой заявки:

```php
// Загрузка сообщений
$stmt = $pdo->prepare("
    SELECT messages.*, users.name, users.role 
    FROM messages 
    JOIN users ON messages.user_id = users.id 
    WHERE order_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$orderId]);
$messages = $stmt->fetchAll();

// Отображение с разделением по ролям
foreach ($messages as $msg) {
    $class = ($msg['role'] === 'admin') ? 'admin' : 'user';
    echo '<div class="chat-message ' . $class . '">...</div>';
}
```

### 2.3 Безопасность

#### 2.3.1 Защита от SQL-инъекций

Все запросы к базе данных выполняются через подготовленные выражения PDO:

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
```

#### 2.3.2 Защита от XSS

Все выводимые данные экранируются функцией `htmlspecialchars()`:

```php
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Использование
echo e($user['name']);
```

#### 2.3.3 Защита от CSRF

Все формы защищены CSRF-токенами:

```php
// Генерация токена
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка токена
if (!verifyCSRFToken($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

#### 2.3.4 Валидация загружаемых файлов

```php
function uploadFile($file, $destination) {
    // Проверка расширения
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Недопустимый тип файла'];
    }
    
    // Проверка размера
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Файл слишком большой'];
    }
    
    // Генерация безопасного имени файла
    $filename = uniqid() . '_' . time() . '.' . $extension;
    // ...
}
```

### 2.4 Тестирование

#### 2.4.1 Функциональное тестирование

Проверены следующие сценарии:

1. **Регистрация и авторизация**:
   - Регистрация нового пользователя
   - Вход с правильными учётными данными
   - Отклонение неверных учётных данных
   - Автоматический вход после регистрации

2. **Работа с заявками**:
   - Создание заявки с файлами
   - Просмотр списка заявок
   - Фильтрация по статусу
   - Отправка сообщений в чате
   - Условное отображение контактов

3. **Административная панель**:
   - Изменение статуса заявки
   - Добавление/редактирование услуг
   - CRUD операции с портфолио
   - Просмотр списка клиентов

#### 2.4.2 Тестирование безопасности

Проведено тестирование на уязвимости:

- ✅ SQL-инъекции: все запросы используют prepared statements
- ✅ XSS: все выводимые данные экранируются
- ✅ CSRF: все формы защищены токенами
- ✅ Загрузка файлов: валидация типа и размера
- ✅ Контроль доступа: проверка ролей на всех страницах

#### 2.4.3 Кроссбраузерность

Приложение протестировано в современных браузерах:
- Google Chrome
- Mozilla Firefox
- Safari
- Microsoft Edge

Адаптивный дизайн проверен на различных разрешениях экрана.

### 2.5 Результаты

Создана полнофункциональная веб-платформа со следующими возможностями:

**Для клиентов**:
- Просмотр портфолио и услуг
- Регистрация и авторизация
- Создание заявок с прикреплением файлов
- Отслеживание статуса заявок
- Общение с дизайнером через встроенный чат
- Получение контактов дизайнера на этапе работы

**Для дизайнера**:
- Управление портфолио и услугами
- Просмотр всех заявок с фильтрацией
- Изменение статусов заявок
- Установка финальной цены
- Общение с клиентами
- Просмотр списка клиентов

**Технические достижения**:
- Безопасная архитектура с защитой от основных веб-уязвимостей
- Адаптивный дизайн для всех устройств
- Интуитивный интерфейс на русском языке
- Модульная структура кода для лёгкой поддержки

## 3. Заключение

В результате выполнения работы создана полнофункциональная веб-платформа для дизайнера-фрилансера, объединяющая портфолио, систему управления заказами и коммуникацию с клиентами. Все поставленные задачи выполнены:

1. ✅ Спроектирована архитектура приложения и база данных
2. ✅ Реализована публичная часть сайта
3. ✅ Внедрена система авторизации без email
4. ✅ Создан личный кабинет клиента
5. ✅ Разработана административная панель
6. ✅ Реализован встроенный чат
7. ✅ Обеспечена безопасность приложения
8. ✅ Проведено тестирование

### Возможные улучшения в будущем:

1. **Уведомления**: email/SMS уведомления о смене статуса заявки
2. **Онлайн-оплата**: интеграция платёжных систем
3. **Календарь**: планирование встреч и дедлайнов
4. **Отзывы**: система отзывов от клиентов
5. **Статистика**: детальная аналитика для дизайнера
6. **API**: REST API для интеграции с другими сервисами
7. **Мультиязычность**: поддержка нескольких языков
8. **Темизация**: тёмная тема интерфейса

Платформа готова к развёртыванию и использованию в реальных условиях.

## 4. Список источников

1. Официальная документация PHP — https://www.php.net/docs.php
2. MySQL Documentation — https://dev.mysql.com/doc/
3. Bootstrap 5 Documentation — https://getbootstrap.com/docs/5.3/
4. OWASP Security Guidelines — https://owasp.org/
5. MDN Web Docs — https://developer.mozilla.org/
6. PHP: The Right Way — https://phptherightway.com/
7. PDO Tutorial — https://phpdelusions.net/pdo
8. Web Security Academy — https://portswigger.net/web-security

## 5. Приложение: Ключевые фрагменты кода

### 5.1 Система авторизации

```php
// includes/auth.php
function registerUser($pdo, $username, $password, $name) {
    // Проверка уникальности логина
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Пользователь с таким логином уже существует'];
    }
    
    // Хеширование пароля
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Вставка нового пользователя
    $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, 'client')");
    
    if ($stmt->execute([$username, $hashedPassword, $name])) {
        return ['success' => true, 'user_id' => $pdo->lastInsertId()];
    }
    
    return ['success' => false, 'message' => 'Ошибка при регистрации'];
}
```

### 5.2 CRUD операции с защитой от SQL-инъекций

```php
// admin/service-edit.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    
    $title = $_POST['title'];
    $categoryId = $_POST['category_id'];
    $description = $_POST['description'];
    $priceFrom = $_POST['price_from'];
    $priceTo = $_POST['price_to'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($serviceId) {
        // Обновление
        $stmt = $pdo->prepare("
            UPDATE services 
            SET title = ?, category_id = ?, description = ?, 
                price_from = ?, price_to = ?, is_active = ? 
            WHERE id = ?
        ");
        $stmt->execute([$title, $categoryId, $description, $priceFrom, $priceTo, $isActive, $serviceId]);
    } else {
        // Создание
        $stmt = $pdo->prepare("
            INSERT INTO services (title, category_id, description, price_from, price_to, is_active) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $categoryId, $description, $priceFrom, $priceTo, $isActive]);
    }
    
    redirect('/admin/services.php', 'Услуга успешно сохранена');
}
```

### 5.3 Защита от XSS

```php
// includes/functions.php
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Использование в шаблонах
<h1><?= e($portfolio['title']) ?></h1>
<p><?= e($user['name']) ?></p>
```

### 5.4 Загрузка и валидация файлов

```php
// includes/functions.php
function uploadFile($file, $destination) {
    $allowedExtensions = ALLOWED_EXTENSIONS;
    $maxSize = MAX_FILE_SIZE;
    
    // Проверка ошибок загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Ошибка при загрузке файла'];
    }
    
    // Проверка размера
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Файл слишком большой'];
    }
    
    // Проверка расширения
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Недопустимый тип файла'];
    }
    
    // Генерация уникального имени файла
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    // Создание директории если не существует
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Перемещение файла
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'original_name' => $file['name']];
    }
    
    return ['success' => false, 'message' => 'Не удалось сохранить файл'];
}
```

### 5.5 CSRF защита

```php
// Генерация токена
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка токена
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Использование в форме
<input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

// Проверка при обработке
if (!verifyCSRFToken($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

---

**Дата разработки**: 2024  
**Автор**: Разработчик веб-приложений  
**Версия**: 1.0
