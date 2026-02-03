<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Создать заявку';
$userId = getUserId();
$errors = [];
$success = false;

// Получаем предвыбранную услугу из параметра
$preSelectedServiceId = $_GET['service_id'] ?? null;

// Получаем список активных услуг
$stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, title");
$services = $stmt->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ошибка проверки безопасности';
    }
    
    $serviceId = $_POST['service_id'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    
    // Валидация
    if (empty($description)) {
        $errors[] = 'Описание обязательно для заполнения';
    }
    
    if (strlen($description) < 10) {
        $errors[] = 'Описание должно содержать минимум 10 символов';
    }
    
    // Если нет ошибок, создаем заказ
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Вставка заказа
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, service_id, description, budget, deadline, status) 
                VALUES (?, ?, ?, ?, ?, 'new')");
            $stmt->execute([$userId, $serviceId, $description, $budget, $deadline]);
            $orderId = $pdo->lastInsertId();
            
            // Обработка загруженных файлов
            if (!empty($_FILES['files']['name'][0])) {
                $uploadDir = UPLOAD_DIR . 'orders/' . $orderId;
                $filesUploaded = 0;
                $maxFiles = 5;
                
                foreach ($_FILES['files']['name'] as $key => $filename) {
                    if ($filesUploaded >= $maxFiles) {
                        break;
                    }
                    
                    if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['files']['name'][$key],
                            'type' => $_FILES['files']['type'][$key],
                            'tmp_name' => $_FILES['files']['tmp_name'][$key],
                            'error' => $_FILES['files']['error'][$key],
                            'size' => $_FILES['files']['size'][$key]
                        ];
                        
                        $uploadResult = uploadFile($file, $uploadDir);
                        
                        if ($uploadResult['success']) {
                            // Сохраняем информацию о файле в БД
                            $stmt = $pdo->prepare("INSERT INTO order_files (order_id, filename, original_name) VALUES (?, ?, ?)");
                            $stmt->execute([$orderId, $uploadResult['filename'], $uploadResult['original_name']]);
                            $filesUploaded++;
                        }
                    }
                }
            }
            
            $pdo->commit();
            redirect('my-orders.php', 'Заявка успешно создана!', 'success');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Ошибка при создании заявки: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Создать новую заявку</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Выбор услуги -->
                        <div class="mb-3">
                            <label for="service_id" class="form-label">Услуга</label>
                            <select class="form-select" id="service_id" name="service_id">
                                <option value="">Выберите услугу (необязательно)</option>
                                <?php foreach ($services as $service): ?>
                                    <?php
                                    $isSelected = false;
                                    if (isset($_POST['service_id'])) {
                                        $isSelected = $_POST['service_id'] == $service['id'];
                                    } elseif ($preSelectedServiceId) {
                                        $isSelected = $preSelectedServiceId == $service['id'];
                                    }
                                    ?>
                                    <option value="<?php echo $service['id']; ?>" 
                                        <?php echo $isSelected ? 'selected' : ''; ?>>
                                        <?php echo e($service['title']); ?>
                                        <?php if ($service['price_from']): ?>
                                            - от <?php echo formatPrice($service['price_from']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Выберите подходящую услугу из списка или оставьте пустым</div>
                        </div>
                        
                        <!-- Описание -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                Описание задачи <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="6" required><?php echo isset($_POST['description']) ? e($_POST['description']) : ''; ?></textarea>
                            <div class="form-text">Опишите подробно, что вам нужно (минимум 10 символов)</div>
                        </div>
                        
                        <!-- Бюджет -->
                        <div class="mb-3">
                            <label for="budget" class="form-label">Бюджет (₽)</label>
                            <input type="number" class="form-control" id="budget" name="budget" 
                                min="0" step="100" 
                                value="<?php echo isset($_POST['budget']) ? e($_POST['budget']) : ''; ?>">
                            <div class="form-text">Укажите примерный бюджет (необязательно)</div>
                        </div>
                        
                        <!-- Дедлайн -->
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Желаемый срок выполнения</label>
                            <input type="date" class="form-control" id="deadline" name="deadline"
                                min="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo isset($_POST['deadline']) ? e($_POST['deadline']) : ''; ?>">
                            <div class="form-text">Укажите, к какой дате нужен результат (необязательно)</div>
                        </div>
                        
                        <!-- Файлы -->
                        <div class="mb-4">
                            <label for="files" class="form-label">Прикрепить файлы</label>
                            <input type="file" class="form-control" id="files" name="files[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip">
                            <div class="form-text">
                                Можно загрузить до 5 файлов. Разрешенные форматы: JPG, PNG, PDF, DOC, DOCX, ZIP. Максимальный размер файла: 10 МБ
                            </div>
                            <div id="file-preview" class="mt-2"></div>
                        </div>
                        
                        <!-- Кнопки -->
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">Создать заявку</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Превью выбранных файлов
document.getElementById('files').addEventListener('change', function(e) {
    const preview = document.getElementById('file-preview');
    preview.innerHTML = '';
    
    const files = Array.from(e.target.files).slice(0, 5);
    
    if (files.length > 0) {
        const list = document.createElement('div');
        list.className = 'alert alert-info mb-0';
        list.innerHTML = '<strong>Выбрано файлов:</strong> ' + files.length;
        const ul = document.createElement('ul');
        ul.className = 'mb-0 mt-2';
        
        files.forEach(file => {
            const li = document.createElement('li');
            li.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' КБ)';
            ul.appendChild(li);
        });
        
        list.appendChild(ul);
        preview.appendChild(list);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
