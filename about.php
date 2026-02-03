<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

$pageTitle = 'Обо мне';
include 'includes/header.php';

$aboutText = getSetting($pdo, 'about_text', 'Информация о дизайнере');
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="text-center mb-5">Обо мне</h1>
            
            <div class="text-center mb-5">
                <div class="d-inline-block" style="width: 300px; height: 300px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                    300x300
                </div>
            </div>
            
            <div class="mb-5">
                <p class="lead"><?php echo nl2br(e($aboutText)); ?></p>
            </div>
            
            <div class="row text-center mb-5">
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-primary mb-2">10+</h3>
                            <p class="mb-0">Лет опыта</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-primary mb-2">200+</h3>
                            <p class="mb-0">Завершенных проектов</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-primary mb-2">150+</h3>
                            <p class="mb-0">Довольных клиентов</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="mb-3">Мои навыки</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2">✓ Фирменный стиль и брендинг</li>
                        <li class="mb-2">✓ Полиграфический дизайн</li>
                        <li class="mb-2">✓ Дизайн упаковки</li>
                        <li class="mb-2">✓ Иллюстрация</li>
                        <li class="mb-2">✓ Adobe Photoshop, Illustrator, InDesign</li>
                    </ul>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="contacts.php" class="btn btn-primary btn-lg">Связаться со мной</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
