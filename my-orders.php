<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Мои заказы';
$userId = getUserId();

// Фильтр по статусу
$statusFilter = $_GET['status'] ?? '';

// Формируем запрос
$query = "SELECT orders.*, services.title as service_title 
    FROM orders 
    LEFT JOIN services ON orders.service_id = services.id 
    WHERE orders.user_id = ?";
$params = [$userId];

if ($statusFilter !== '') {
    $query .= " AND orders.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY orders.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-6 fw-bold">Мои заказы</h1>
            <p class="text-muted">Все ваши заказы и их текущий статус</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="create-order.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Создать заявку
            </a>
        </div>
    </div>

    <!-- Фильтр по статусу -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="status" class="form-label">Фильтр по статусу</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        <option value="">Все статусы</option>
                        <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>Новая</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                        <option value="review" <?php echo $statusFilter === 'review' ? 'selected' : ''; ?>>На согласовании</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Завершена</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Отклонена</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <?php if ($statusFilter): ?>
                        <a href="my-orders.php" class="btn btn-outline-secondary w-100">Сбросить</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица заказов -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($orders)): ?>
                <div class="p-5 text-center text-muted">
                    <h5>Заказы не найдены</h5>
                    <p>
                        <?php if ($statusFilter): ?>
                            Нет заказов со статусом "<?php echo e(getOrderStatusName($statusFilter)); ?>"
                        <?php else: ?>
                            У вас пока нет заказов. Создайте первый заказ!
                        <?php endif; ?>
                    </p>
                    <?php if (!$statusFilter): ?>
                        <a href="create-order.php" class="btn btn-primary mt-3">Создать заказ</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80">№</th>
                                <th>Услуга</th>
                                <th width="180">Статус</th>
                                <th width="150">Дата создания</th>
                                <th width="150" class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($order['service_title'] ?? 'Другое'); ?></div>
                                        <small class="text-muted">
                                            <?php echo e(mb_substr($order['description'], 0, 50)); ?>
                                            <?php echo mb_strlen($order['description']) > 50 ? '...' : ''; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getOrderStatusClass($order['status']); ?>">
                                            <?php echo e(getOrderStatusName($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?php echo formatDate($order['created_at']); ?></div>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                    </td>
                                    <td class="text-end">
                                        <a href="order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Подробнее
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer bg-white">
                    <div class="text-muted">
                        Всего заказов: <strong><?php echo count($orders); ?></strong>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
