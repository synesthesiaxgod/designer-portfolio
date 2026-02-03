<?php
/**
 * Вспомогательные функции
 */

/**
 * Экранирование HTML для защиты от XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Форматирование цены
 */
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' ₽';
}

/**
 * Форматирование даты
 */
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

/**
 * Форматирование даты и времени
 */
function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}

/**
 * Получение названия статуса заявки
 */
function getOrderStatusName($status) {
    $statuses = [
        'new' => 'Новая',
        'in_progress' => 'В работе',
        'review' => 'На согласовании',
        'completed' => 'Завершена',
        'rejected' => 'Отклонена'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * Получение класса badge для статуса
 */
function getOrderStatusClass($status) {
    $classes = [
        'new' => 'secondary',
        'in_progress' => 'primary',
        'review' => 'warning',
        'completed' => 'success',
        'rejected' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}

/**
 * Загрузка файла
 */
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

/**
 * Получение настройки из БД
 */
function getSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

/**
 * Редирект с сообщением
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Получение и очистка flash-сообщения
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'success'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Пагинация
 */
function paginate($pdo, $query, $params, $page, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    
    // Получаем общее количество записей
    $countQuery = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $query);
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Получаем записи для текущей страницы
    $query .= " LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    return [
        'items' => $items,
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'current_page' => $page
    ];
}
