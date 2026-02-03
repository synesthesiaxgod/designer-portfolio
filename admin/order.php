<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$orderId = $_GET['id'] ?? 0;
$userId = getUserId();

// Получаем информацию о заказе с данными клиента
$stmt = $pdo->prepare("SELECT orders.*, services.title as service_title, services.price_from, services.price_to,
    users.name as client_name, users.username as client_username, users.created_at as client_registered
    FROM orders 
    LEFT JOIN services ON orders.service_id = services.id 
    LEFT JOIN users ON orders.user_id = users.id
    WHERE orders.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php', 'Заказ не найден', 'danger');
}

$pageTitle = 'Заказ #' . $order['id'];

// Обработка обновления статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('order.php?id=' . $orderId, 'Ошибка проверки безопасности', 'danger');
    }
    
    $newStatus = $_POST['status'] ?? '';
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$newStatus, $orderId])) {
        redirect('order.php?id=' . $orderId, 'Статус заказа обновлен', 'success');
    }
}

// Обработка обновления финальной цены
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('order.php?id=' . $orderId, 'Ошибка проверки безопасности', 'danger');
    }
    
    $finalPrice = floatval($_POST['final_price'] ?? 0);
    $stmt = $pdo->prepare("UPDATE orders SET final_price = ? WHERE id = ?");
    if ($stmt->execute([$finalPrice, $orderId])) {
        redirect('order.php?id=' . $orderId, 'Цена заказа обновлена', 'success');
    }
}

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('order.php?id=' . $orderId, 'Ошибка проверки безопасности', 'danger');
    }
    
    $messageText = trim($_POST['message'] ?? '');
    
    if (!empty($messageText)) {
        $stmt = $pdo->prepare("INSERT INTO messages (order_id, user_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$orderId, $userId, $messageText])) {
            redirect('order.php?id=' . $orderId, 'Сообщение отправлено', 'success');
        }
    }
}

// Получаем файлы заказа
$stmt = $pdo->prepare("SELECT * FROM order_files WHERE order_id = ? ORDER BY uploaded_at");
$stmt->execute([$orderId]);
$files = $stmt->fetchAll();

// Получаем сообщения чата
$stmt = $pdo->prepare("SELECT messages.*, users.name as user_name, users.role 
    FROM messages 
    JOIN users ON messages.user_id = users.id 
    WHERE messages.order_id = ? 
    ORDER BY messages.created_at ASC");
$stmt->execute([$orderId]);
$messages = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 bg-light sidebar">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Управление</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            📊 Панель управления
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">
                            📋 Заказы
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php">
                            👥 Клиенты
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="portfolio.php">
                            🎨 Портфолио
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">
                            💼 Услуги
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Навигация -->
            <div class="mb-4 pt-3">
                <a href="orders.php" class="btn btn-outline-secondary">
                    ← Назад к списку
                </a>
            </div>

            <div class="row">
                <!-- Основная информация о заказе -->
                <div class="col-lg-8 mb-4">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">Заказ #<?php echo $order['id']; ?></h4>
                                <span class="badge bg-<?php echo getOrderStatusClass($order['status']); ?> fs-6">
                                    <?php echo e(getOrderStatusName($order['status'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Услуга:</div>
                                <div class="col-sm-8 fw-semibold">
                                    <?php echo e($order['service_title'] ?? 'Не указано'); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Описание задачи:</div>
                                <div class="col-sm-8">
                                    <?php echo nl2br(e($order['description'])); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Бюджет клиента:</div>
                                <div class="col-sm-8">
                                    <?php echo $order['budget'] ? formatPrice($order['budget']) : 'Не указан'; ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Желаемый срок:</div>
                                <div class="col-sm-8">
                                    <?php echo $order['deadline'] ? formatDate($order['deadline']) : 'Не указан'; ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Финальная цена:</div>
                                <div class="col-sm-8 fw-bold text-success">
                                    <?php echo $order['final_price'] ? formatPrice($order['final_price']) : 'Не установлена'; ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Дата создания:</div>
                                <div class="col-sm-8">
                                    <?php echo formatDateTime($order['created_at']); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Обновлен:</div>
                                <div class="col-sm-8">
                                    <?php echo formatDateTime($order['updated_at']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Прикрепленные файлы -->
                    <?php if (!empty($files)): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Прикрепленные файлы</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($files as $file): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo e($file['original_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Загружен: <?php echo formatDateTime($file['uploaded_at']); ?>
                                        </small>
                                    </div>
                                    <a href="../uploads/<?php echo e($file['filename']); ?>" 
                                       class="btn btn-sm btn-outline-primary" download>
                                        Скачать
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Чат -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Переписка с клиентом</h5>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <?php if (empty($messages)): ?>
                                <div class="alert alert-info">Сообщений пока нет</div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                <div class="mb-3 <?php echo $message['role'] === 'admin' ? 'text-end' : ''; ?>">
                                    <div class="d-inline-block text-start" style="max-width: 70%;">
                                        <div class="card <?php echo $message['role'] === 'admin' ? 'bg-primary text-white' : 'bg-light'; ?>">
                                            <div class="card-body p-2">
                                                <div class="fw-bold small mb-1">
                                                    <?php echo e($message['user_name']); ?>
                                                    <?php if ($message['role'] === 'admin'): ?>
                                                        <span class="badge bg-warning text-dark">Админ</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div><?php echo nl2br(e($message['message'])); ?></div>
                                                <div class="small mt-1 opacity-75">
                                                    <?php echo formatDateTime($message['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <div class="input-group">
                                    <textarea name="message" class="form-control" rows="2" 
                                              placeholder="Введите сообщение..." required></textarea>
                                    <button type="submit" name="send_message" class="btn btn-primary">
                                        Отправить
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar с управлением -->
                <div class="col-lg-4">
                    <!-- Информация о клиенте -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Информация о клиенте</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="text-muted small">Имя</div>
                                <div class="fw-semibold"><?php echo e($order['client_name']); ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small">Логин</div>
                                <div><?php echo e($order['client_username']); ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small">Дата регистрации</div>
                                <div><?php echo formatDateTime($order['client_registered']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Изменение статуса -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Изменить статус</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <div class="mb-3">
                                    <select name="status" class="form-select" required>
                                        <option value="new" <?php echo $order['status'] === 'new' ? 'selected' : ''; ?>>
                                            Новая
                                        </option>
                                        <option value="in_progress" <?php echo $order['status'] === 'in_progress' ? 'selected' : ''; ?>>
                                            В работе
                                        </option>
                                        <option value="review" <?php echo $order['status'] === 'review' ? 'selected' : ''; ?>>
                                            На согласовании
                                        </option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>
                                            Завершена
                                        </option>
                                        <option value="rejected" <?php echo $order['status'] === 'rejected' ? 'selected' : ''; ?>>
                                            Отклонена
                                        </option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-primary w-100">
                                    Сохранить статус
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Установить финальную цену -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Установить цену</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <div class="mb-3">
                                    <label for="final_price" class="form-label">Финальная цена (₽)</label>
                                    <input type="number" step="0.01" min="0" name="final_price" id="final_price" 
                                           class="form-control" value="<?php echo e($order['final_price'] ?? ''); ?>" required>
                                    <?php if ($order['price_from'] && $order['price_to']): ?>
                                    <small class="text-muted">
                                        Рекомендуемая цена: <?php echo formatPrice($order['price_from']); ?> - 
                                        <?php echo formatPrice($order['price_to']); ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" name="update_price" class="btn btn-success w-100">
                                    Сохранить цену
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    min-height: calc(100vh - 100px);
}
.sidebar .nav-link {
    color: #333;
    padding: 0.75rem 1rem;
}
.sidebar .nav-link.active {
    background-color: #e9ecef;
    font-weight: 500;
}
.sidebar .nav-link:hover {
    background-color: #f8f9fa;
}
</style>

<?php include '../includes/footer.php'; ?>
