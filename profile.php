<?php 
// Определение текущей страницы для активного пункта меню
$current_page = basename($_SERVER['PHP_SELF']);
include('header.php'); 
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя | YouProject</title>
    <meta name="description" content="Страница профиля пользователя системы YouProject">
    
    <!-- Open Graph разметка -->
    <meta property="og:title" content="Профиль пользователя | YouProject">
    <meta property="og:description" content="Управление профилем пользователя системы YouProject">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Каноническая ссылка -->
    <link rel="canonical" href="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Контент для авторизованных пользователей -->
        <?php 
        session_start();
        $current_page = basename($_SERVER['PHP_SELF']);
        include('profiles.php'); 
        ?>
    <?php else: ?>
        <!-- Контент для неавторизованных пользователей -->
        <div class="block_0">
            <p id="block_1_heading">Профиль</p>
            <p id="block_1_text">Обязательные поля - <b>username, компания, роль, пароль,</b> а остальное заполняется по вашему усмотрению</p>
        </div>
        
        <div class="block_1">
            <p id="block_1_heading">Пример профиля</p>
            <img src="Image/primer_10.png" alt="Пример проектов">
        </div>
        
        <div class="block_1">
            <p id="block_1_heading">Хотите также?</p>
            <p id="block_1_text">Тогда <a href="#login">войдите</a> или <a href="#register">зарегистрируйте</a> новый аккаунт</p>
        </div>

        <?php 
        $current_page = basename($_SERVER['PHP_SELF']);
        include('footer.php'); 
        ?>
    <?php endif; ?>
</body>
</html>