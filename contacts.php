<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

$pageTitle = 'Контакты';
$success = false;
$error = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Ошибка безопасности. Пожалуйста, попробуйте снова.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validate
        if (empty($name) || empty($email) || empty($message)) {
            $error = 'Пожалуйста, заполните все поля';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Пожалуйста, введите корректный email';
        } else {
            // Insert into database
            try {
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $message]);
                $success = true;
                
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $error = 'Произошла ошибка. Пожалуйста, попробуйте позже.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="text-center mb-5">Контакты</h1>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <h4>Спасибо за ваше сообщение!</h4>
                <p>Я свяжусь с вами в ближайшее время.</p>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo e($error); ?>
            </div>
            <?php endif; ?>
            
            <div class="row mb-5">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Связаться со мной</h5>
                            <p class="card-text">
                                <strong>Телефон:</strong><br>
                                <?php echo e(getSetting($pdo, 'designer_phone', '+7 (999) 123-45-67')); ?>
                            </p>
                            <p class="card-text">
                                <strong>Telegram:</strong><br>
                                <?php echo e(getSetting($pdo, 'designer_telegram', '@designer_anna')); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Социальные сети</h5>
                            <p class="card-text">
                                <a href="https://behance.net" target="_blank" class="text-decoration-none">
                                    <strong>Behance</strong><br>
                                    behance.net/annadesign
                                </a>
                            </p>
                            <p class="card-text">
                                <a href="https://linkedin.com" target="_blank" class="text-decoration-none">
                                    <strong>LinkedIn</strong><br>
                                    linkedin.com/in/annadesign
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="mb-4">Отправить сообщение</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Ваше имя *</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($_POST['name']) ? e($_POST['name']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Сообщение *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? e($_POST['message']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">Отправить сообщение</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
