<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$itemId = intval($_GET['id'] ?? 0);
$isEdit = $itemId > 0;

// Если редактирование, получаем существующий элемент
$item = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM portfolio WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    
    if (!$item) {
        redirect('portfolio.php', 'Работа не найдена', 'danger');
    }
}

$pageTitle = $isEdit ? 'Редактирование работы' : 'Добавление работы';

// Получаем категории
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name");
$categories = $stmt->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirect('portfolio-edit.php' . ($isEdit ? '?id=' . $itemId : ''), 'Ошибка проверки безопасности', 'danger');
    }
    
    $title = trim($_POST['title'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $taskDescription = trim($_POST['task_description'] ?? '');
    $processDescription = trim($_POST['process_description'] ?? '');
    $resultDescription = trim($_POST['result_description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Валидация
    $errors = [];
    if (empty($title)) {
        $errors[] = 'Название обязательно для заполнения';
    }
    
    // Обработка загрузки изображения
    $imageName = $item['image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['image'], '../uploads');
        
        if ($uploadResult['success']) {
            // Удаляем старое изображение
            if ($imageName && file_exists('../uploads/' . $imageName)) {
                unlink('../uploads/' . $imageName);
            }
            $imageName = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['message'];
        }
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            // Обновление
            $stmt = $pdo->prepare("UPDATE portfolio SET 
                category_id = ?, title = ?, image = ?, description = ?, 
                task_description = ?, process_description = ?, result_description = ?, is_active = ?
                WHERE id = ?");
            $result = $stmt->execute([
                $categoryId ?: null, $title, $imageName, $description,
                $taskDescription, $processDescription, $resultDescription, $isActive, $itemId
            ]);
        } else {
            // Создание
            $stmt = $pdo->prepare("INSERT INTO portfolio 
                (category_id, title, image, description, task_description, process_description, result_description, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $categoryId ?: null, $title, $imageName, $description,
                $taskDescription, $processDescription, $resultDescription, $isActive
            ]);
        }
        
        if ($result) {
            redirect('portfolio.php', $isEdit ? 'Работа обновлена' : 'Работа добавлена', 'success');
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
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <a href="portfolio.php" class="btn btn-outline-secondary">
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
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Название работы *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo e($item['title'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Категория</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Не выбрана</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                <?php echo ($item['category_id'] ?? 0) == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo e($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="image" class="form-label">Изображение</label>
                                    <?php if ($item && $item['image']): ?>
                                        <div class="mb-2">
                                            <img src="../uploads/<?php echo e($item['image']); ?>" 
                                                 alt="Текущее изображение" class="img-thumbnail" style="max-width: 300px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="image" name="image" 
                                           accept="image/jpeg,image/png,image/gif">
                                    <small class="text-muted">Допустимые форматы: JPG, PNG, GIF. Максимальный размер: 10 МБ</small>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Краткое описание</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo e($item['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="task_description" class="form-label">Описание задачи</label>
                                    <textarea class="form-control" id="task_description" name="task_description" rows="4"><?php echo e($item['task_description'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="process_description" class="form-label">Описание процесса</label>
                                    <textarea class="form-control" id="process_description" name="process_description" rows="4"><?php echo e($item['process_description'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="result_description" class="form-label">Описание результата</label>
                                    <textarea class="form-control" id="result_description" name="result_description" rows="4"><?php echo e($item['result_description'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                           <?php echo ($item['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Работа активна (отображается на сайте)
                                    </label>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $isEdit ? 'Сохранить изменения' : 'Добавить работу'; ?>
                                    </button>
                                    <a href="portfolio.php" class="btn btn-secondary">Отмена</a>
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
