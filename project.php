<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

// Get project ID
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$projectId) {
    header('Location: portfolio.php');
    exit;
}

// Get project details
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM portfolio p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    $pageTitle = 'Проект не найден';
    include 'includes/header.php';
    ?>
    <div class="container py-5">
        <div class="alert alert-warning text-center">
            <h4>Проект не найден</h4>
            <p>К сожалению, запрошенный проект не существует или был удален.</p>
            <a href="portfolio.php" class="btn btn-primary">Вернуться к портфолио</a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

$pageTitle = $project['title'];
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="mb-4">
        <a href="portfolio.php" class="btn btn-outline-secondary">← Назад к портфолио</a>
    </div>
    
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Project Image -->
            <div class="mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 600px; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border-radius: 8px;">
                800x600
            </div>
            
            <!-- Project Title and Category -->
            <h1 class="mb-2"><?php echo e($project['title']); ?></h1>
            <p class="text-muted mb-4">
                <strong>Категория:</strong> <?php echo e($project['category_name']); ?>
            </p>
            
            <!-- Description -->
            <?php if ($project['description']): ?>
            <div class="mb-4">
                <p><?php echo nl2br(e($project['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Task Description -->
            <?php if ($project['task_description']): ?>
            <div class="mb-4">
                <h3>Задача</h3>
                <p><?php echo nl2br(e($project['task_description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Process Description -->
            <?php if ($project['process_description']): ?>
            <div class="mb-4">
                <h3>Процесс работы</h3>
                <p><?php echo nl2br(e($project['process_description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Result Description -->
            <?php if ($project['result_description']): ?>
            <div class="mb-4">
                <h3>Результат</h3>
                <p><?php echo nl2br(e($project['result_description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="mt-5 text-center">
                <a href="portfolio.php" class="btn btn-primary btn-lg">Назад к портфолио</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
