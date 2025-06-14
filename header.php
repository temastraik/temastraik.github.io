<?php
session_start();
require_once 'class/User.php';
$db = new SQLite3('database.db');

// Проверяем, авторизован ли пользователь
$isLoggedIn = isset($_SESSION['user_id']);

// Обработка выхода из системы
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: project.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="SVG/logo.svg">
    <link rel="stylesheet" href="css/styles.css">
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
</head>
<body>
    <header>
        <div class="block_header">
            <img src="SVG/logo.svg" id='logo' alt='Логотип'>
            <a href="index.php" id="header_href_index">YouProject</a>
            <nav class="header-nav">
                <ul>
                    <li>
                        <a href="project.php" class="<?php echo ($current_page == 'project.php' || $current_page == 'task_view.php' || $current_page == 'task_edit.php' || $current_page == 'task_create.php') ? 'active' : ''; ?>">Проекты</a>
                    </li>
                    <li>
                        <a href="plan.php" class="<?php echo ($current_page == 'plan.php') ? 'active' : ''; ?>">Планировщик</a>
                    </li>
                    <li>
                        <a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">Личный кабинет</a>
                    </li>
                </ul>
            </nav>
            <?php if ($isLoggedIn): ?>
                <button onclick="window.location.href='?logout=1'" class="pattern_button_1" id="header_log">Выйти</button>
            <?php else: ?>
                <button onclick="window.location.href='#login'" class="pattern_button_1" id="header_log">Войти</button>
            <?php endif; ?>
        </div>
    </header>

    <!-- Авторизация пользователя -->
    <?php if (!$isLoggedIn): ?>
    <div id="login">
        <?php
        $error = User::handleLogin($db);
        echo User::renderLoginModal($error);
        ?>
    </div>
    <?php endif; ?>

    <!-- Регистрация пользователя -->
    <?php if (!$isLoggedIn): ?>
    <div id="register">
        <?php
        $error = User::handleRegistration($db);
        echo User::renderRegistrationModal($error);
        ?>
    </div>
    <?php endif; ?>
</body>
</html>