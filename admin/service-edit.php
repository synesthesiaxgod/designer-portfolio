<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$serviceId = intval($_GET['id'] ?? 0);
$isEdit = $serviceId > 0;

// Если редактирование, получаем существующую услугу
$service = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        redirect('services.php', 'Услуга не найдена', 'danger');
    }
}

$pageTitle = $isEdit ? 'Редактирование услуги' : 'Добавление услуги';

// Получаем категории
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name");
$categories = $stmt->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('service-edit.php' . ($isEdit ? '?id=' . $serviceId : ''), 'Ошибка проверки безопасности', 'danger');
    }
    
    $title = trim($_POST['title'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $priceFrom = floatval($_POST['price_from'] ?? 0);
    $priceTo = floatval($_POST['price_to'] ?? 0);
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Валидация
    $errors = [];
    if (empty($title)) {
        $errors[] = 'Название обязательно для заполнения';
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            // Обновление
            $stmt = $pdo->prepare("UPDATE services SET 
                category_id = ?, title = ?, description = ?, price_from = ?, price_to = ?, 
                sort_order = ?, is_active = ?
                WHERE id = ?");
            $result = $stmt->execute([
                $categoryId ?: null, $title, $description, $priceFrom ?: null, $priceTo ?: null,
                $sortOrder, $isActive, $serviceId
            ]);
        } else {
            // Создание
            $stmt = $pdo->prepare("INSERT INTO services 
                (category_id, title, description, price_from, price_to, sort_order, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $categoryId ?: null, $title, $description, $priceFrom ?: null, $priceTo ?: null,
                $sortOrder, $isActive
            ]);
        }
        
        if ($result) {
            redirect('services.php', $isEdit ? 'Услуга обновлена' : 'Услуга добавлена', 'success');
        } else {
            $errors[] = 'Ошибка при сохранении';
        }
    }
}

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
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <a href="services.php" class="btn btn-outline-secondary">
                    ← Назад к списку
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Название услуги *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo e($service['title'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Категория</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Не выбрана</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                <?php echo ($service['category_id'] ?? 0) == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo e($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo e($service['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price_from" class="form-label">Цена от (₽)</label>
                                            <input type="number" step="0.01" min="0" class="form-control" id="price_from" name="price_from" 
                                                   value="<?php echo e($service['price_from'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price_to" class="form-label">Цена до (₽)</label>
                                            <input type="number" step="0.01" min="0" class="form-control" id="price_to" name="price_to" 
                                                   value="<?php echo e($service['price_to'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Порядок сортировки</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                           value="<?php echo e($service['sort_order'] ?? 0); ?>">
                                    <small class="text-muted">Чем меньше число, тем выше в списке</small>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                           <?php echo ($service['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Услуга активна (отображается на сайте)
                                    </label>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $isEdit ? 'Сохранить изменения' : 'Добавить услугу'; ?>
                                    </button>
                                    <a href="services.php" class="btn btn-secondary">Отмена</a>
                                </div>
                            </div>
                        </div>
                    </form>
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
