<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

$pageTitle = 'Главная';
include 'includes/header.php';

// Get settings
$heroTitle = getSetting($pdo, 'hero_title', 'Профессиональный дизайн на заказ');
$heroSubtitle = getSetting($pdo, 'hero_subtitle', 'Создаю уникальный визуальный стиль для вашего бизнеса');

// Get featured services
$stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order LIMIT 4");
$featuredServices = $stmt->fetchAll();

// Get recent portfolio items
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM portfolio p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 3");
$recentPortfolio = $stmt->fetchAll();
?>

<style>
.hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 100px 0;
    text-align: center;
}

.hero h1 {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.hero p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
}

.portfolio-item img,
.service-card img {
    width: 100%;
    height: 250px;
    object-fit: cover;
}

.portfolio-item {
    transition: transform 0.3s;
}

.portfolio-item:hover {
    transform: translateY(-5px);
}
</style>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1><?php echo e($heroTitle); ?></h1>
        <p class="lead"><?php echo e($heroSubtitle); ?></p>
        <div class="mt-4">
            <a href="portfolio.php" class="btn btn-light btn-lg me-2">Смотреть портфолио</a>
            <?php if (isLoggedIn()): ?>
                <a href="admin/create-order.php" class="btn btn-outline-light btn-lg">Оформить заказ</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light btn-lg">Оформить заказ</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Services Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Популярные услуги</h2>
        <div class="row g-4">
            <?php foreach ($featuredServices as $service): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo e($service['title']); ?></h5>
                        <p class="card-text text-muted"><?php echo e(mb_substr($service['description'], 0, 100)) . '...'; ?></p>
                        <p class="text-primary fw-bold">
                            <?php 
                            if ($service['price_to'] && $service['price_to'] != $service['price_from']) {
                                echo 'от ' . formatPrice($service['price_from']) . ' до ' . formatPrice($service['price_to']);
                            } else {
                                echo 'от ' . formatPrice($service['price_from']);
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="services.php" class="btn btn-primary">Все услуги</a>
        </div>
    </div>
</section>

<!-- Recent Portfolio Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Последние работы</h2>
        <div class="row g-4">
            <?php foreach ($recentPortfolio as $item): ?>
            <div class="col-md-4">
                <a href="project.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                    <div class="card portfolio-item h-100 shadow-sm">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 250px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            400x300
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo e($item['title']); ?></h5>
                            <p class="text-muted mb-0">
                                <small><?php echo e($item['category_name']); ?></small>
                            </p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="portfolio.php" class="btn btn-primary">Все работы</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
