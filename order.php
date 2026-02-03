<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$userId = getUserId();
$orderId = $_GET['id'] ?? 0;

// Проверяем, что заказ принадлежит текущему пользователю
$stmt = $pdo->prepare("SELECT orders.*, services.title as service_title, services.price_from, services.price_to
    FROM orders 
    LEFT JOIN services ON orders.service_id = services.id 
    WHERE orders.id = ? AND orders.user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(403);
    die('Доступ запрещен. Заказ не найден или принадлежит другому пользователю.');
}

$pageTitle = 'Заказ #' . $order['id'];

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

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('order.php?id=' . $orderId, 'Ошибка проверки безопасности', 'danger');
    }
    
    $messageText = trim($_POST['message']);
    
    if (!empty($messageText)) {
        $stmt = $pdo->prepare("INSERT INTO messages (order_id, user_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$orderId, $userId, $messageText])) {
            redirect('order.php?id=' . $orderId, 'Сообщение отправлено', 'success');
        }
    }
}

// Получаем контакты дизайнера (если статус позволяет)
$showDesignerContacts = in_array($order['status'], ['in_progress', 'review']);
$designerPhone = '';
$designerTelegram = '';

if ($showDesignerContacts) {
    $designerPhone = getSetting($pdo, 'designer_phone');
    $designerTelegram = getSetting($pdo, 'designer_telegram');
}

include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Навигация -->
    <div class="mb-4">
        <a href="my-orders.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Назад к списку
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
                            <?php if ($order['price_from']): ?>
                                <br>
                                <small class="text-muted">
                                    от <?php echo formatPrice($order['price_from']); ?>
                                    <?php if ($order['price_to']): ?>
                                        до <?php echo formatPrice($order['price_to']); ?>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Описание:</div>
                        <div class="col-sm-8"><?php echo nl2br(e($order['description'])); ?></div>
                    </div>

                    <?php if ($order['budget']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Бюджет:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo formatPrice($order['budget']); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['deadline']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Желаемый срок:</div>
                            <div class="col-sm-8"><?php echo formatDate($order['deadline']); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['final_price']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Итоговая стоимость:</div>
                            <div class="col-sm-8 fw-bold text-success fs-5"><?php echo formatPrice($order['final_price']); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Дата создания:</div>
                        <div class="col-sm-8"><?php echo formatDateTime($order['created_at']); ?></div>
                    </div>

                    <div class="row">
                        <div class="col-sm-4 text-muted">Последнее обновление:</div>
                        <div class="col-sm-8"><?php echo formatDateTime($order['updated_at']); ?></div>
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
                        <div class="list-group list-group-flush">
                            <?php foreach ($files as $file): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-file-earmark"></i>
                                        <?php echo e($file['original_name']); ?>
                                        <br>
                                        <small class="text-muted">Загружен: <?php echo formatDateTime($file['uploaded_at']); ?></small>
                                    </div>
                                    <a href="uploads/orders/<?php echo $orderId; ?>/<?php echo e($file['filename']); ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       download="<?php echo e($file['original_name']); ?>">
                                        Скачать
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Чат -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Переписка с дизайнером</h5>
                </div>
                <div class="card-body">
                    <div class="chat-messages">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-4">
                                <p>Сообщений пока нет. Начните общение!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="chat-message <?php echo $message['role'] === 'admin' ? 'admin' : 'user'; ?>">
                                    <div class="fw-semibold mb-1"><?php echo e($message['user_name']); ?></div>
                                    <div><?php echo nl2br(e($message['message'])); ?></div>
                                    <div class="chat-message-time">
                                        <?php echo formatDateTime($message['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="mb-3">
                            <textarea class="form-control" name="message" rows="3" 
                                placeholder="Введите ваше сообщение..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Отправить сообщение
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Боковая панель -->
        <div class="col-lg-4">
            <!-- Контакты дизайнера (только для определенных статусов) -->
            <?php if ($showDesignerContacts): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Контакты дизайнера</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Ваш заказ в работе! Вы можете связаться с дизайнером напрямую:</p>
                        
                        <?php if ($designerPhone): ?>
                            <div class="mb-3">
                                <div class="text-muted small">Телефон:</div>
                                <a href="tel:<?php echo e($designerPhone); ?>" class="fw-semibold text-decoration-none">
                                    <i class="bi bi-phone"></i> <?php echo e($designerPhone); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($designerTelegram): ?>
                            <div>
                                <div class="text-muted small">Telegram:</div>
                                <a href="https://t.me/<?php echo e(ltrim($designerTelegram, '@')); ?>" 
                                   class="fw-semibold text-decoration-none" 
                                   target="_blank">
                                    <i class="bi bi-telegram"></i> <?php echo e($designerTelegram); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Информация о статусах -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Статусы заказа</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge bg-secondary">Новая</span>
                        <small class="d-block text-muted mt-1">Заявка получена, ожидает рассмотрения</small>
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-primary">В работе</span>
                        <small class="d-block text-muted mt-1">Заказ принят в работу</small>
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-warning">На согласовании</span>
                        <small class="d-block text-muted mt-1">Ожидает вашего одобрения</small>
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-success">Завершена</span>
                        <small class="d-block text-muted mt-1">Работа выполнена</small>
                    </div>
                    <div>
                        <span class="badge bg-danger">Отклонена</span>
                        <small class="d-block text-muted mt-1">Заказ отклонен</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Автоматическая прокрутка чата вниз
window.addEventListener('load', function() {
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
