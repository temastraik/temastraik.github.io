<?php
// Проверка аутентификации и подключение необходимых классов
require_once 'class/Task.php';
require_once 'class/User.php';

// Подключение шапки сайта
$current_page = basename($_SERVER['PHP_SELF']);
include('header.php');

// Подключение к базе данных и получение информации о задаче
$db = new SQLite3('database.db');
$task_id = $_GET['id'] ?? 0;
$task = new Task($db, $task_id);

// Проверка существования задачи
if (!$task->getId()) {
    die("Задача не найдена");
}

// Обработка запроса на удаление задачи
$delete_result = Task::handleDeleteTaskRequest($db, $task_id);
if ($delete_result) {
    echo "<script>alert('$delete_result');</script>";
}

// Получение информации об исполнителе через класс User
$user = User::fetchBasicUserInfo($db, $task->getUserId());

// Получение подробной информации о пользователе для модального окна
if ($user) {
    $user_data = User::fetchDetailedUserInfo($db, $user['id']);
}

/**
 * Функция для получения данных о важности задачи
 * @param string $importance Уровень важности задачи
 * @return array Массив с данными для отображения важности
 */
function getImportanceData($importance) {
    $importance_icons = [
        'high' => ['svg' => 'svg_high_description', 'text' => 'Важно'],
        'medium' => ['svg' => 'svg_medium_description', 'text' => 'Подождет'],
        'low' => ['svg' => 'svg_low_description', 'text' => 'Последнее']
    ];
    return $importance_icons[$importance] ?? $importance_icons['low'];
}
$importance = getImportanceData($task->getImportance());
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр задачи - <?= htmlspecialchars($task->getName()) ?></title>
    <meta name="description" content="Страница просмотра детальной информации о задаче">
    <meta name="keywords" content="просмотр задачи, детали задачи, управление проектами">
    
    <!-- Open Graph разметка -->
    <meta property="og:title" content="Просмотр задачи - <?= htmlspecialchars($task->getName()) ?>">
    <meta property="og:description" content="Страница просмотра детальной информации о задаче">
    <meta property="og:type" content="website">
    
    <!-- Подключение шрифта -->
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
</head>
<body>
    <div class="task-container">
        <p class="pattern_heading"><?= htmlspecialchars($task->getName()) ?></p>
        
        <!-- Основная информация о задаче -->
        <div class="task_info">
            <p id="task_info_description"><strong>Описание:</strong> <?= htmlspecialchars($task->getDescription()) ?></p>
            <p><strong>Важность:</strong></p><img src="SVG/importants.svg" id="<?= $importance['svg']?>">
            <p id="<?= 'description_important_' . $task->getImportance(); ?>"><?php echo $importance['text']; ?></p>
            <p><strong>Исполнитель:</strong><?= User::renderUserLinkWithModal($user['id'], $user['username']) ?></p>
            <p><strong>Прогресс:</strong> <?= htmlspecialchars($task->getProgress()) ?>%</p>
            <p><strong>Срок выполнения:</strong> <?= htmlspecialchars($task->getDeadline()) ?></p>
            <p><strong>Тег:</strong> <span>#<?= htmlspecialchars($task->getTag()) ?></span></p>
            
            <?php if ($task->getDownloadLink()): ?>
                <p><strong>Прикрепленный файл:</strong></p>
                <a href="<?= $task->getDownloadLink() ?>" class="file-download">
                    <?= htmlspecialchars($task->getFileDisplayName()) ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Чек-лист задачи (если есть) -->
        <?php if (!empty($task->getChecklistItems())): ?>
            <div class="checklist-container">
                <div class="checklist-title">Чек-лист:</div>
                <?php foreach ($task->getChecklistItems() as $item): ?>
                    <div class="checklist-item">
                        <input type="checkbox" <?= $item['is_checked'] ? 'checked' : '' ?> disabled>
                        <label><?= htmlspecialchars($item['item_text']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Кнопки управления задачей -->
        <a href="task_edit.php?id=<?php echo htmlspecialchars($task->getId(), ENT_QUOTES); ?>" class="change_task">Изменить</a>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить задачу \'<?= addslashes($task->getName()) ?>\'?')">
            <input type="hidden" name="delete_task" value="1">
            <button type="submit" id="delete_project">Удалить задачу</button>
        </form>

        <!-- Модальное окно с информацией о пользователе -->
        <?php if ($user && $user_data): ?>
            <?= User::renderUserProfileModal($user['id'], $user['username'], $user_data) ?>
        <?php endif; ?>

        <!-- Ссылка для возврата к списку проектов -->
        <a href="project.php" class="back-link">← К проектам</a>
    </div>
</body>
</html>