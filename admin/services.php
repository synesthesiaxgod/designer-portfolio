<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Управление услугами';

// Обработка удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('services.php', 'Ошибка проверки безопасности', 'danger');
    }
    
    $serviceId = intval($_POST['service_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    if ($stmt->execute([$serviceId])) {
        redirect('services.php', 'Услуга удалена', 'success');
    }
}

// Получаем все услуги
$stmt = $pdo->query("SELECT services.*, categories.name as category_name 
    FROM services 
    LEFT JOIN categories ON services.category_id = categories.id 
    ORDER BY services.sort_order, services.title");
$services = $stmt->fetchAll();

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
                        <a class="nav-link" href="orders.php">
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
                        <a class="nav-link active" href="services.php">
                            💼 Услуги
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Управление услугами</h1>
                <a href="service-edit.php" class="btn btn-primary">
                    + Добавить услугу
                </a>
            </div>

            <!-- Таблица услуг -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Всего услуг: <?php echo count($services); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($services)): ?>
                        <div class="alert alert-info">Услуги не добавлены</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Название</th>
                                        <th>Категория</th>
                                        <th>Цена</th>
                                        <th>Порядок</th>
                                        <th>Активна</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo e($service['id']); ?></td>
                                        <td><?php echo e($service['title']); ?></td>
                                        <td><?php echo e($service['category_name'] ?? 'Не указана'); ?></td>
                                        <td>
                                            <?php if ($service['price_from'] && $service['price_to']): ?>
                                                <?php echo formatPrice($service['price_from']); ?> - 
                                                <?php echo formatPrice($service['price_to']); ?>
                                            <?php elseif ($service['price_from']): ?>
                                                от <?php echo formatPrice($service['price_from']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Не указана</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($service['sort_order']); ?></td>
                                        <td>
                                            <?php if ($service['is_active']): ?>
                                                <span class="badge bg-success">Активна</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Неактивна</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="service-edit.php?id=<?php echo $service['id']; ?>" 
                                               class="btn btn-sm btn-primary mb-1">
                                                Редактировать
                                            </a>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Вы уверены, что хотите удалить эту услугу?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                <button type="submit" name="delete_service" class="btn btn-sm btn-danger mb-1">
                                                    Удалить
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
