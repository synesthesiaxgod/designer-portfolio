<?php
require_once 'includes/auth.php';

// Выход из системы
logoutUser();

// Перенаправление на главную страницу
header('Location: /index.php');
exit;
