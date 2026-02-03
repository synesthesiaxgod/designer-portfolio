<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Если уже авторизован, перенаправить
if (isLoggedIn()) {
    $redirectUrl = isAdmin() ? '/admin/index.php' : '/dashboard.php';
    header('Location: ' . $redirectUrl);
    exit;
}

$pageTitle = 'Вход';
$error = '';
$redirect = $_GET['redirect'] ?? '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        $result = loginUser($pdo, $username, $password);
        
        if ($result['success']) {
            // Определяем куда перенаправлять
            if (!empty($redirect)) {
                $redirectUrl = $redirect;
            } else {
                $redirectUrl = $result['role'] === 'admin' ? '/admin/index.php' : '/dashboard.php';
            }
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Вход в систему</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo e($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Логин</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo e($_POST['username'] ?? ''); ?>"
                                   required 
                                   autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary">Войти</button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
