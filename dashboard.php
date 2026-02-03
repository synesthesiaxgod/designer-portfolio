<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Личный кабинет';
$userId = getUserId();

// Получаем статистику заказов
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// Получаем последние 5 заказов
$stmt = $pdo->prepare("SELECT orders.*, services.title as service_title 
    FROM orders 
    LEFT JOIN services ON orders.service_id = services.id 
    WHERE orders.user_id = ? 
    ORDER BY orders.created_at DESC 
    LIMIT 5");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12 mb-4">
            <h1 class="display-5 fw-bold">Добро пожаловать, <?php echo e(getUserName()); ?>!</h1>
            <p class="text-muted">Здесь вы можете управлять своими заказами и следить за их статусом</p>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-5">
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-number"><?php echo $stats['total']; ?></div>
                    <div class="stats-label">Всего заказов</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-number text-secondary"><?php echo $stats['new_count']; ?></div>
                    <div class="stats-label">Новые</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-number text-primary"><?php echo $stats['in_progress_count']; ?></div>
                    <div class="stats-label">В работе</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-number text-success"><?php echo $stats['completed_count']; ?></div>
                    <div class="stats-label">Завершено</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Кнопка создания заявки -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="create-order.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle"></i> Создать заявку
            </a>
            <a href="my-orders.php" class="btn btn-outline-primary btn-lg ms-2">
                Все мои заказы
            </a>
        </div>
    </div>

    <!-- Последние заказы -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Последние заказы</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentOrders)): ?>
                        <div class="p-4 text-center text-muted">
                            <p>У вас пока нет заказов</p>
                            <a href="create-order.php" class="btn btn-primary">Создать первый заказ</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Услуга</th>
                                        <th>Статус</th>
                                        <th>Дата создания</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo e($order['service_title'] ?? 'Не указано'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getOrderStatusClass($order['status']); ?>">
                                                    <?php echo e(getOrderStatusName($order['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($order['created_at']); ?></td>
                                            <td>
                                                <a href="order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    Подробнее
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
