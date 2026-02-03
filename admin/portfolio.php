<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Управление портфолио';

// Обработка удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('portfolio.php', 'Ошибка проверки безопасности', 'danger');
    }
    
    $itemId = intval($_POST['item_id'] ?? 0);
    
    // Получаем файл изображения для удаления
    $stmt = $pdo->prepare("SELECT image FROM portfolio WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    
    if ($item) {
        // Удаляем запись
        $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
        if ($stmt->execute([$itemId])) {
            // Удаляем файл изображения
            if ($item['image'] && file_exists('../uploads/' . $item['image'])) {
                unlink('../uploads/' . $item['image']);
            }
            redirect('portfolio.php', 'Работа удалена', 'success');
        }
    }
}

// Получаем все работы портфолио
$stmt = $pdo->query("SELECT portfolio.*, categories.name as category_name 
    FROM portfolio 
    LEFT JOIN categories ON portfolio.category_id = categories.id 
    ORDER BY portfolio.created_at DESC");
$portfolioItems = $stmt->fetchAll();

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
                        <a class="nav-link active" href="portfolio.php">
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
                <h1 class="h2">Управление портфолио</h1>
                <a href="portfolio-edit.php" class="btn btn-primary">
                    + Добавить работу
                </a>
            </div>

            <!-- Таблица портфолио -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Всего работ: <?php echo count($portfolioItems); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($portfolioItems)): ?>
                        <div class="alert alert-info">Портфолио пусто</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Изображение</th>
                                        <th>Название</th>
                                        <th>Категория</th>
                                        <th>Активна</th>
                                        <th>Дата создания</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($portfolioItems as $item): ?>
                                    <tr>
                                        <td><?php echo e($item['id']); ?></td>
                                        <td>
                                            <?php if ($item['image']): ?>
                                                <img src="../uploads/<?php echo e($item['image']); ?>" 
                                                     alt="Thumbnail" class="img-thumbnail" style="max-width: 80px; max-height: 80px;">
                                            <?php else: ?>
                                                <span class="text-muted">Нет изображения</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($item['title']); ?></td>
                                        <td><?php echo e($item['category_name'] ?? 'Не указана'); ?></td>
                                        <td>
                                            <?php if ($item['is_active']): ?>
                                                <span class="badge bg-success">Активна</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Неактивна</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($item['created_at']); ?></td>
                                        <td>
                                            <a href="portfolio-edit.php?id=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-primary mb-1">
                                                Редактировать
                                            </a>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Вы уверены, что хотите удалить эту работу?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="delete_item" class="btn btn-sm btn-danger mb-1">
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
