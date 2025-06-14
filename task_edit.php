<?php 
require_once 'auth_check.php';
require_once 'class/Task.php';
require_once 'class/User.php';

$db = new SQLite3('database.db');
$user_id = $_SESSION['user_id'];

// Получение роли пользователя
$user = new User($db, $user_id);
$user_role = $user->getRole();

// Проверка роли
if ($user_role !== 'manager' && $user_role !== 'executer') {
    die("Доступ запрещен");
}

// Получение ID задачи
$task_id = $_GET['id'] ?? 0;
$task = new Task($db, $task_id);

if (!$task->getId()) {
    die("Задача не найдена");
}

$error = Task::handleEditTaskRequest($db, $user_id, $task_id);
$current_page = basename($_SERVER['PHP_SELF']);
include('header.php');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование задачи - <?= htmlspecialchars($task->getName()) ?></title>
    <meta name="description" content="Страница редактирования информации о задаче">
    <meta name="keywords" content="редактирование задачи, детали задачи, управление проектами">
    
    <!-- Open Graph разметка -->
    <meta property="og:title" content="Просмотр задачи - <?= htmlspecialchars($task->getName()) ?>">
    <meta property="og:description" content="Страница просмотра детальной информации о задаче">
    <meta property="og:type" content="website">
    
    <!-- Подключение шрифта -->
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
</head>
<body>
    <div class="task-container">
        <p id="name_task_edit">Редактирование задачи</p>
        
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="name">Название задачи:</label>
                <input type="text" id="name" name="name" class="pattern_input" value="<?= htmlspecialchars($task->getName()) ?>" 
                       placeholder="до 15 символов" required maxlength="15" 
                       <?= $user_role === 'executer' ? 'disabled' : '' ?>>
            </div>
            
            <div class="form-group">
                <label for="description">Описание:</label>
                <textarea id="description" name="description" rows="4" placeholder="до 500 символов" 
                          required maxlength="500" 
                          <?= $user_role === 'executer' ? 'disabled' : '' ?>><?= htmlspecialchars($task->getDescription()) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="importance">Важность:</label>
                <select name="importance" class="pattern_input" required <?= $user_role === 'executer' ? 'disabled' : '' ?>>
                    <option value="low" <?= $task->getImportance() === 'low' ? 'selected' : '' ?>>Низкая</option>
                    <option value="medium" <?= $task->getImportance() === 'medium' ? 'selected' : '' ?>>Средняя</option>
                    <option value="high" <?= $task->getImportance() === 'high' ? 'selected' : '' ?>>Высокая</option>
                </select>
            </div>
            
            <?php if ($user_role === 'manager'): ?>
                <div class="form-group">
                    <label for="tag">Тег:</label>
                    <select class="pattern_input" name="tag">
                        <option value="">Без тега</option>
                        <option value="IT" <?= $task->getTag() === 'IT' ? 'selected' : '' ?>>IT</option>
                        <option value="Дизайн" <?= $task->getTag() === 'Дизайн' ? 'selected' : '' ?>>Дизайн</option>
                        <option value="Маркетинг" <?= $task->getTag() === 'Маркетинг' ? 'selected' : '' ?>>Маркетинг</option>
                        <option value="Аналитика" <?= $task->getTag() === 'Аналитика' ? 'selected' : '' ?>>Аналитика</option>
                        <option value="Продажи" <?= $task->getTag() === 'Продажи' ? 'selected' : '' ?>>Продажи</option>
                        <option value="Копирайтинг" <?= $task->getTag() === 'Копирайтинг' ? 'selected' : '' ?>>Копирайтинг</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="user_id">Исполнитель:</label>
                    <select name="user_id" class="pattern_input" required>
                        <?= User::renderUserSelectOptions($db, $user_id, $task->getUserId()) ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="progress">Прогресс (0-100%):</label>
                <input type="number" name="progress" min="0" max="100" class="pattern_input"
                       value="<?= htmlspecialchars($task->getProgress()) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="deadline">Срок выполнения:</label>
                <input type="date" name="deadline" class="pattern_input"
                       value="<?= htmlspecialchars($task->getDeadline()) ?>" required 
                       <?= $user_role === 'executer' ? 'disabled' : '' ?>>
            </div>
            
            <!-- Чек-поинты -->
            <div class="checklist-container">
                <div class="checklist-title_edit">Чек-лист:</div>
                <?php foreach ($task->getChecklistItems() as $item): ?>
                    <div class="checklist-item_edit">
                        <input type="hidden" name="checklist[<?= $item['id'] ?>]" value="0">
                        <input type="checkbox" name="checklist[<?= $item['id'] ?>]" id="checklist_<?= $item['id'] ?>"
                               value="1" <?= $item['is_checked'] ? 'checked' : '' ?>>
                        <label for="checklist_<?= $item['id'] ?>"><?= htmlspecialchars($item['item_text']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="button-group">
                <button type="submit" id="change_project" class="pattern_button_2">Сохранить</button>
                <a href="task_view.php?id=<?= $task_id ?>" id="edit_task_no">Отмена</a>
            </div>
        </form>
        
        <a href="project.php" class="back-link">← К проектам</a>
    </div>
</body>
</html>