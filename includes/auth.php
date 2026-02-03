<?php
/**
 * Функции авторизации и работы с сессиями
 */

// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Проверка авторизации пользователя
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Проверка роли администратора
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Получение ID текущего пользователя
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Получение имени текущего пользователя
 */
function getUserName() {
    return $_SESSION['user_name'] ?? 'Гость';
}

/**
 * Регистрация пользователя
 */
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

/**
 * Авторизация пользователя
 */
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

/**
 * Выход пользователя
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Требовать авторизацию
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Требовать права администратора
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /dashboard.php');
        exit;
    }
}

/**
 * Генерация CSRF токена
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Проверка CSRF токена
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
