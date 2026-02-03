<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Если уже авторизован, перенаправить
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Регистрация';
$errors = [];
$success = false;

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $name = trim($_POST['name'] ?? '');
    
    // Валидация
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = 'Ошибка безопасности. Попробуйте еще раз.';
    }
    
    if (empty($username)) {
        $errors[] = 'Введите логин';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Логин должен содержать минимум 3 символа';
    }
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен содержать минимум 6 символов';
    }
    
    if ($password !== $passwordConfirm) {
        $errors[] = 'Пароли не совпадают';
    }
    
    if (empty($name)) {
        $errors[] = 'Введите ваше имя';
    }
    
    // Если нет ошибок валидации, пытаемся зарегистрировать
    if (empty($errors)) {
        $result = registerUser($pdo, $username, $password, $name);
        
        if ($result['success']) {
            // Автоматический вход после регистрации
            $loginResult = loginUser($pdo, $username, $password);
            
            if ($loginResult['success']) {
                header('Location: /dashboard.php');
                exit;
            } else {
                $success = true;
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Регистрация</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            Регистрация успешна! <a href="login.php">Войти</a>
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
                                   autofocus
                                   minlength="3">
                            <div class="form-text">Минимум 3 символа</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Имя</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo e($_POST['name'] ?? ''); ?>"
                                   required>
                            <div class="form-text">Ваше имя для отображения</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required
                                   minlength="6">
                            <div class="form-text">Минимум 6 символов</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   required
                                   minlength="6">
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Уже есть аккаунт? <a href="login.php">Войти</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
