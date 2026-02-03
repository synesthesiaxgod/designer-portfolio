<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

$pageTitle = 'Услуги';
include 'includes/header.php';

// Get all services grouped by category
$stmt = $pdo->query("
    SELECT s.*, c.name as category_name, c.id as category_id 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    WHERE s.is_active = 1 
    ORDER BY c.sort_order, s.sort_order
");
$allServices = $stmt->fetchAll();

// Group services by category
$servicesByCategory = [];
foreach ($allServices as $service) {
    $categoryName = $service['category_name'] ?? 'Без категории';
    if (!isset($servicesByCategory[$categoryName])) {
        $servicesByCategory[$categoryName] = [];
    }
    $servicesByCategory[$categoryName][] = $service;
}
?>

<div class="container py-5">
    <h1 class="text-center mb-5">Услуги</h1>
    
    <?php foreach ($servicesByCategory as $categoryName => $services): ?>
    <div class="mb-5">
        <h2 class="mb-4"><?php echo e($categoryName); ?></h2>
        <div class="row g-4">
            <?php foreach ($services as $service): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo e($service['title']); ?></h5>
                        <p class="card-text text-muted"><?php echo e($service['description']); ?></p>
                        <div class="mt-3">
                            <p class="text-primary fw-bold mb-3">
                                <?php 
                                if ($service['price_to'] && $service['price_to'] != $service['price_from']) {
                                    echo 'от ' . formatPrice($service['price_from']) . ' до ' . formatPrice($service['price_to']);
                                } else {
                                    echo 'от ' . formatPrice($service['price_from']);
                                }
                                ?>
                            </p>
                            <?php if (isLoggedIn()): ?>
                                <a href="admin/create-order.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">Заказать</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Заказать</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($allServices)): ?>
    <div class="text-center py-5">
        <p class="text-muted">Пока нет доступных услуг</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
