<?php 
// Инициализация сессии и подключение к базе данных
session_start();
$db = new SQLite3('database.db');

// Определение текущей страницы для активного пункта меню
$current_page = basename($_SERVER['PHP_SELF']);
include('header.php'); 
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Планировщик задач и событий | YouProject</title>
    <meta name="description" content="Календарь-планировщик с событиями и задачами для организации вашего времени">
    <meta name="keywords" content="планировщик, календарь, задачи, события, организация времени">
    <meta name="author" content="YouProject">
    
    <!-- Open Graph разметка для соцсетей -->
    <meta property="og:title" content="Планировщик задач и событий | YouProject">
    <meta property="og:description" content="Календарь-планировщик с событиями и задачами для организации вашего времени">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Каноническая ссылка для избежания дублирования контента -->
    <link rel="canonical" href="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Контент для авторизованных пользователей -->
        <?php 
        session_start();
        $current_page = basename($_SERVER['PHP_SELF']);
        include('plans.php'); 
        ?>
    <?php else: ?>
        <!-- Контент для неавторизованных пользователей -->
        <div class="block_0">
            <p id="block_1_heading">Календарь-планировщик</p>
            <p id="block_1_text">Ваш идеальный помощник: календарь с <b>событиями</b> и <b>задачами</b> для чёткого расписания</p>
        </div>
        
        <div class="block_1">
            <p id="block_1_heading">Пример календаря-планировщика</p>
            <img src="Image/primer_4.png" alt="Пример календаря-планировщика">
        </div>

        <div class="block_1">
            <p id="block_1_heading">Заметки</p>
            <p id="block_1_text">Храните идеи, списки дел или важные мысли в удобном формате. <b>Ничего лишнего</b> — только заголовок и текст</p>
        </div>
        
        <div class="block_10">
            <p id="block_1_heading">Пример заметок</p>
            <img src="Image/primer_9.png" alt="Пример заметок">
        </div>

        <div class="block_1">
            <p id="block_1_heading">Хотите также?</p>
            <p id="block_1_text">Тогда <a href="#login">войдите</a> или <a href="#register">зарегистрируйте</a> новый аккаунт</p>
        </div>
    <?php endif; ?>
    <?php 
    $current_page = basename($_SERVER['PHP_SELF']);
    include('footer.php'); 
    ?>
</body>
</html>