<?php 
// Запуск сессии для работы с пользовательскими данными
session_start();

// Определение текущей страницы для активного пункта меню
$current_page = basename($_SERVER['PHP_SELF']);
include('header.php'); 
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проекты | Система управления задачами</title>
    
    <!-- SEO мета-теги -->
    <meta name="description" content="Система управления проектами и задачами с контролем выполнения и распределением между командой">
    <meta name="keywords" content="проекты, задачи, управление проектами, система контроля задач, командная работа">
    <meta name="author" content="YouProject">
    
    <!-- Open Graph разметка для соцсетей -->
    <meta property="og:title" content="Проекты | Система управления задачами">
    <meta property="og:description" content="Система управления проектами и задачами с контролем выполнения">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    
    <!-- Каноническая ссылка для SEO -->
    <link rel="canonical" href="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Контент для авторизованных пользователей -->
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']);
        include('projects.php'); 
        ?>
    <?php else: ?>
        <!-- Контент для неавторизованных пользователей (лендинг) -->
        <div class="block_0">
            <h1 id="block_1_heading">Проекты</h1>
            <p id="block_1_text">Система <b>для</b> чёткого <b>контроля</b>: группируйте задачи, распределяйте их между командой, выделяйте важное и отслеживайте прогресс</p>
        </div>
        
        <div class="block_10">
            <h2 id="block_1_heading">Пример проекта</h2>
            <img src="Image/primer_1.png" alt="Пример интерфейса управления проектами" title="Интерфейс системы управления проектами">
        </div>
        
        <div class="block_1">
            <h2 id="block_1_heading">Задачи</h2>
            <p id="block_1_text">Каждая задача содержит <b>все необходимое</b>: название, ответственного, прогресс выполнения, уровень важности и детальное описание</p>
        </div>
        
        <div class="block_1">
            <h2 id="block_1_heading">Пример задач</h2>
            <div class="container_tasks">
                <img src="Image/primer_2.png" alt="Пример интерфейса создания задачи" title="Создание задачи">
                <img src="Image/primer_3.png" alt="Пример интерфейса просмотра задачи" title="Просмотр задачи">
            </div>
        </div>
        
        <div class="block_1">
            <h2 id="block_1_heading">Компания</h2>
            <p id="block_1_text">Гибкое распределение: <b>добавляйте</b> или <b>удаляйте</b> исполнителей моментально. Чёткий контроль — эффективная работа команды</p>
        </div>
        
        <div class="block_1">
            <h2 id="block_1_heading">Пример компании</h2>
            <img src="Image/primer_8.png" alt="Пример интерфейса управления командой" title="Управление командой">
        </div>
        
        <div class="block_1">
            <h2 id="block_1_heading">Хотите также?</h2>
            <p id="block_1_text">Тогда <a href="#login" title="Вход в систему">войдите</a> или <a href="#register" title="Регистрация нового аккаунта">зарегистрируйте</a> новый аккаунт</p>
        </div>
    <?php endif; ?>
    
    <?php 
    // Подключение футера
    $current_page = basename($_SERVER['PHP_SELF']);
    include('footer.php'); 
    ?>
</body>
</html>