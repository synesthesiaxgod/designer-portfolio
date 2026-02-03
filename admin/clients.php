<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Управление клиентами';

// Получаем всех клиентов с количеством заказов
$stmt = $pdo->query("SELECT users.*, 
    (SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) as orders_count
    FROM users 
    WHERE users.role = 'client' 
    ORDER BY users.created_at DESC");
$clients = $stmt->fetchAll();

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
                        <a class="nav-link active" href="clients.php">
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
                <h1 class="h2">Управление клиентами</h1>
            </div>

            <!-- Таблица клиентов -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Всего клиентов: <?php echo count($clients); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($clients)): ?>
                        <div class="alert alert-info">Клиенты не зарегистрированы</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Логин</th>
                                        <th>Имя</th>
                                        <th>Дата регистрации</th>
                                        <th>Кол-во заявок</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?php echo e($client['id']); ?></td>
                                        <td><?php echo e($client['username']); ?></td>
                                        <td><?php echo e($client['name']); ?></td>
                                        <td><?php echo formatDateTime($client['created_at']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $client['orders_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($client['orders_count'] > 0): ?>
                                                <a href="orders.php?search=<?php echo urlencode($client['name']); ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    Просмотреть заказы
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Нет заказов</span>
                                            <?php endif; ?>
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
