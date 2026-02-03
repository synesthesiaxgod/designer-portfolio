<?php
/**
 * Конфигурация базы данных
 */

// Настройки подключения к базе данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'designer_portfolio');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Настройки сайта
define('SITE_URL', 'http://localhost');
define('SITE_NAME', 'Анна Дизайн');

// Настройки безопасности
define('SESSION_LIFETIME', 3600 * 24); // 24 часа

// Настройки загрузки файлов
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip']);

// Включаем отображение ошибок для разработки (отключить в продакшене)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Часовой пояс
date_default_timezone_set('Europe/Moscow');
