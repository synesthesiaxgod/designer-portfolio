<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

$pageTitle = 'Портфолио';
include 'includes/header.php';

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order");
$categories = $stmt->fetchAll();

// Get all portfolio items
$stmt = $pdo->query("SELECT p.*, c.name as category_name, c.slug as category_slug FROM portfolio p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.created_at DESC");
$portfolioItems = $stmt->fetchAll();
?>

<style>
.portfolio-filter-btn {
    margin: 0.25rem;
}

.portfolio-grid {
    margin-top: 2rem;
}

.portfolio-item {
    margin-bottom: 2rem;
    transition: transform 0.3s;
}

.portfolio-item:hover {
    transform: translateY(-5px);
}

.portfolio-item img {
    width: 100%;
    height: 300px;
    object-fit: cover;
}

.portfolio-item.hidden {
    display: none;
}
</style>

<div class="container py-5">
    <h1 class="text-center mb-4">Портфолио</h1>
    
    <!-- Filter Buttons -->
    <div class="text-center mb-4">
        <button class="btn btn-primary portfolio-filter-btn" data-filter="all">Все</button>
        <?php foreach ($categories as $category): ?>
        <button class="btn btn-outline-primary portfolio-filter-btn" data-filter="<?php echo e($category['slug']); ?>">
            <?php echo e($category['name']); ?>
        </button>
        <?php endforeach; ?>
    </div>
    
    <!-- Portfolio Grid -->
    <div class="row portfolio-grid">
        <?php foreach ($portfolioItems as $item): ?>
        <div class="col-md-6 col-lg-4 portfolio-item" data-category="<?php echo e($item['category_slug']); ?>">
            <a href="project.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 300px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                        400x300
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-dark"><?php echo e($item['title']); ?></h5>
                        <p class="text-muted mb-0">
                            <small><?php echo e($item['category_name']); ?></small>
                        </p>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($portfolioItems)): ?>
    <div class="text-center py-5">
        <p class="text-muted">Пока нет работ в портфолио</p>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.portfolio-filter-btn');
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
            
            // Filter items
            portfolioItems.forEach(item => {
                if (filter === 'all' || item.getAttribute('data-category') === filter) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
