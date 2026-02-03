<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Управление заказами';

// Параметры фильтрации
$searchName = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Построение запроса с фильтрами
$where = ['1=1'];
$params = [];

if (!empty($searchName)) {
    $where[] = 'users.name LIKE ?';
    $params[] = '%' . $searchName . '%';
}

if (!empty($filterStatus)) {
    $where[] = 'orders.status = ?';
    $params[] = $filterStatus;
}

$whereClause = implode(' AND ', $where);

$query = "SELECT orders.*, users.name as client_name, services.title as service_title 
    FROM orders 
    LEFT JOIN users ON orders.user_id = users.id 
    LEFT JOIN services ON orders.service_id = services.id 
    WHERE $whereClause
    ORDER BY orders.created_at DESC";

$result = paginate($pdo, $query, $params, $page, $perPage);

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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Управление заказами</h1>
            </div>

            <!-- Фильтры -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Поиск по имени клиента</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo e($searchName); ?>" placeholder="Введите имя">
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Статус</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Все</option>
                                <option value="new" <?php echo $filterStatus === 'new' ? 'selected' : ''; ?>>Новая</option>
                                <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                                <option value="review" <?php echo $filterStatus === 'review' ? 'selected' : ''; ?>>На согласовании</option>
                                <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Завершена</option>
                                <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Отклонена</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Применить</button>
                            <a href="orders.php" class="btn btn-secondary">Сбросить</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Таблица заказов -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Всего заказов: <?php echo $result['total']; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($result['items'])): ?>
                        <div class="alert alert-info">Заказы не найдены</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>№</th>
                                        <th>Клиент</th>
                                        <th>Услуга</th>
                                        <th>Статус</th>
                                        <th>Дата создания</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result['items'] as $order): ?>
                                    <tr>
                                        <td><?php echo e($order['id']); ?></td>
                                        <td><?php echo e($order['client_name']); ?></td>
                                        <td><?php echo e($order['service_title'] ?? 'Не указано'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getOrderStatusClass($order['status']); ?>">
                                                <?php echo e(getOrderStatusName($order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateTime($order['created_at']); ?></td>
                                        <td>
                                            <a href="order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                Редактировать
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Пагинация -->
                        <?php if ($result['pages'] > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchName); ?>&status=<?php echo urlencode($filterStatus); ?>">
                                        Предыдущая
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                                    <?php if ($i == $page || $i == 1 || $i == $result['pages'] || abs($i - $page) <= 2): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchName); ?>&status=<?php echo urlencode($filterStatus); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $result['pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchName); ?>&status=<?php echo urlencode($filterStatus); ?>">
                                        Следующая
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
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
