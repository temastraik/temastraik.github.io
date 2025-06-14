<?php
session_start();
require_once 'auth_check.php';
require_once 'class/Task.php';
require_once 'class/User.php';
require_once 'class/Project.php';

$db = new SQLite3('database.db');
$user_id = $_SESSION['user_id'];
$user = new User($db, $user_id);
$role = $user->getRole();
$company_id = $user->getCompanyId();
$username = $user->getUsername();
$view_restrict = $user->getViewRestrict();

// Обработка действий с пользователями
User::handleUserActions($db);

// Обработка удаления проекта
Project::handleDeleteRequest($db);

// Получаем данные через классы
$projects = Project::getAllByUserCompany($db, $user_id);
$tasks = Task::getTasksByCompany($db, $company_id);
$users_okei = User::fetchUsers($db, $user_id);

// Создание проекта
$error_create_project = Project::handleCreateRequest($db);

// Редактирование проекта
$error_edit_project = Project::handleUpdateRequest($db);

// Получаем данные менеджера если нужно
if ($role === 'manager') {
    $members_result = User::fetchCompanyMembers($db, $company_id);
    $users_result = User::fetchAllUsers($db);
}

$users_manager = User::fetchManagerData($db, $company_id);
$error_add_user = User::handleUserRegistration($db, $company_id);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление проектами</title>
    <!-- SEO мета-теги -->
    <meta name="description" content="Панель управления проектами и задачами для <?= htmlspecialchars($role === 'manager' ? 'менеджеров' : 'исполнителей') ?>">
    <meta name="keywords" content="управление проектами, задачи, менеджер задач, система управления проектами">
    <meta name="author" content="YouProject">
    
    <!-- Open Graph разметка -->
    <meta property="og:title" content="Управление проектами | <?= htmlspecialchars($username) ?>">
    <meta property="og:description" content="Панель управления проектами и задачами">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    
    <!-- Каноническая ссылка -->
    <link rel="canonical" href="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    
    <!-- Подключение шрифтов и скриптов -->
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
    <script src="js/main.js" defer></script>
    <script src="js/filters.js" defer></script>
    <script src="js/modals.js" defer></script>
    <script src="js/ajax.js" defer></script>
</head>
<body>

    <!-- ===================== БЛОК ДЛЯ МЕНЕДЖЕРОВ ===================== -->
    <?php if ($role === 'manager' && $username !== 'admin'): ?>
        <div class="block_manager_company">
            <div class="block_create_project">
                <p class="pattern_heading">Создать проект</p>
                <form method="POST">
                    <label>Название:</label>
                    <input type="text" name="project_name" class="pattern_input" placeholder="до 20 символов" required maxlength="20"><br>
                    <button type="submit" name="create_project" id="create_project" class="pattern_button_2">Создать</button>
                </form>
                <?php if (isset($error_create_project)) echo "<p style='color:red; text-align:center; margin-top:-100px;'>$error_create_project</p>"; ?>
            </div>
            
            <div id="register_executer">
                <div id="window_register_executer" class="pattern_modal">
                    <a href="#"><img src="SVG/cross.svg" alt="Закрыть окно" class="close"></a>
                    <p id="name_register_executer" class="pattern_heading">Добавить сотрудника</p>
                    <form method="POST">
                        <label>username:</label>
                        <input type="text" name="new_username" id="register_username_executer" class="pattern_input" placeholder="до 10 символов" required maxlength="10"><br>
                        <label>пароль:</label>
                        <input type="password" name="password" id="register_password_executer" class="pattern_input" required maxlength="20"><br>
                        <label>повторите пароль:</label>
                        <input type="password" name="confirm_password" id="register_confirm_password_executer" class="pattern_input" required maxlength="20"><br>
                        <input type="submit" name="add_user" value="Добавить" id="change_project" class="pattern_button_2">
                    </form>
                    <?php if (isset($error_add_user)) echo "<p style='color:red; text-align:center; margin-top:-88px;'>$error_add_user</p>"; ?>
                </div>
            </div>
            
            <div id="delete_executer">
                <div id="window_delete_executer" class="pattern_modal">
                    <a href="#"><img src="SVG/cross.svg" alt="Закрыть окно" class="close"></a>
                    <p id="name_delete_executer" class="pattern_heading">Удалить сотрудника</p>
                    <ul>
                        <?php 
                        $members_result->reset();
                        while ($member = $members_result->fetchArray(SQLITE3_ASSOC)): 
                            if ($member['role'] === 'executer'): ?>
                            <li>
                                <p><?php echo htmlspecialchars($member['username']); ?></p>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить пользователя \'<?= addslashes($member['username']) ?>\'?')">
                                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                    <input type="hidden" name="delete_user" value="1">
                                    <button type="submit" id="delete_user" class="pattern_button_3">Удалить</button>
                                </form>
                            </li>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            
            <div class="block_company_list">
                <p id="name_company_executers" class="pattern_heading">Сотрудники вашей компании</p>
                <ul>
                    <?php 
                    $members_result->reset();
                    while ($member = $members_result->fetchArray(SQLITE3_ASSOC)): ?>
                        <li>
                            <a href="#" onclick="document.getElementById('user_info_<?php echo $member['id']; ?>').style.display='block'"><?php echo htmlspecialchars($member['username']); ?></a>
                            <?php echo htmlspecialchars(' - ' . User::translateRole($member['role'])); ?>
                            
                            <?php
                            $user_data = User::fetchDetailedUserInfo($db, $member['id']);
                            ?>
                            
                            <div id="user_info_<?php echo $member['id']; ?>" class="modal" style="display:none;">
                                <div id="window_user_info" class="pattern_modal">
                                    <a onclick="document.getElementById('user_info_<?php echo $member['id']; ?>').style.display='none'"><img src="SVG/cross.svg" alt="Закрыть окно" class="close"></a>
                                    <p id="name_user_info" class="pattern_heading">Профиль <?php echo htmlspecialchars($member['username']); ?></p>
                                    <p id="window_user_info_p"><strong>Фамилия:</strong> <?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?></p>
                                    <p id="window_user_info_p"><strong>Имя:</strong> <?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?></p>
                                    <p id="window_user_info_p"><strong>Отчество:</strong> <?php echo htmlspecialchars($user_data['patronymic'] ?? ''); ?></p>
                                    <p id="window_user_info_p"><strong>Почта:</strong> <?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <div class="buttons_company">
                    <button onclick="window.location.href='#register_executer'" id="button_register_executer" class="pattern_button_1">Добавить сотрудника</button>
                    <button onclick="window.location.href='#delete_executer'" id="button_delete_executer" class="pattern_button_3">Удалить сотрудника</button>
                </div>
            </div>
        </div>

        <div class="block_projects_company">
            <p id="name_projects_list" class="pattern_heading">Проекты</p>
            <div class="container_project">
                <?php 
                $users_for_filters = [];
                $users_okei->reset();
                while ($user = $users_okei->fetchArray(SQLITE3_ASSOC)) {
                    $users_for_filters[$user['id']] = $user['username'];
                }
                
                foreach ($projects as $project): ?>
                    <div class="block_project">
                        <li>
                            <?php $projectID = $project->getId(); ?>
                            <p id="project_name_<?php echo $projectID; ?>" class="project_name"><?php echo htmlspecialchars($project->getName()); ?></p>
                            <hr>

                            <div class="filters-container" id="filters_<?php echo $projectID; ?>">
                                <div class="filter-header" onclick="toggleFilters(<?php echo $projectID; ?>)">
                                    <p class="filter-label">Фильтры: <span class="arrow-icon">▼</span></p>
                                </div>
                                <div class="filter-content" style="display: none;">
                                    <div class="filter-group">
                                        <select class="filter-select" id="importance_filter_<?php echo $projectID; ?>" onchange="filterTasks(<?php echo $projectID; ?>)">
                                            <option value="">Важность</option>
                                            <option value="high">Высокая</option>
                                            <option value="medium">Средняя</option>
                                            <option value="low">Низкая</option>
                                        </select>
                                        
                                        <select class="filter-select_username" id="user_filter_<?php echo $projectID; ?>" onchange="filterTasks(<?php echo $projectID; ?>)">
                                            <option value="">Исполнитель</option>
                                            <?php foreach ($users_for_filters as $id => $username): ?>
                                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($username); ?></option>
                                            <?php endforeach; ?>
                                        </select><br>
                                        
                                        <button class="filter-button reset-filters" onclick="resetFilters(<?php echo $projectID; ?>)">Сбросить</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="tasks_container_<?php echo $projectID; ?>">
                                <?php
                                foreach ($tasks as $task) {
                                    if ($task->getProjectId() === $projectID && $task->getProgress() !== 100) {
                                        $task->display($db);
                                    }
                                }
                                ?>
                            </div>
                            <a class="create_task" href="task_create.php?project_id=<?php echo $projectID; ?>">Добавить задачу</a>
                            <button onclick="showEditProjectModal(<?php echo $projectID; ?>)" id="change_project" class="pattern_button_2">Изменить</button>
                            
                            <div id="edit_project_<?php echo $projectID; ?>" class="modal" style="display:none;">
                                <div id="window_edit_project" class="pattern_modal">
                                    <a href="#" onclick="hideEditProjectModal(<?php echo $projectID; ?>)"><img src="SVG/cross.svg" alt="Закрыть окно" class="close"></a>
                                    <p id="name_edit_project" class="pattern_heading">Редактировать проект</p>
                                    <form method="POST" id="edit_project_form_<?php echo $projectID; ?>">
                                        <input type="hidden" name="project_id" value="<?php echo $projectID; ?>">
                                        <input type="text" name="project_name" id="projects_name_<?php echo $projectID; ?>" value="<?php echo htmlspecialchars($project->getName()); ?>" class="pattern_input" placeholder="до 20 символов" required maxlength="20"><br>
                                        <button type="submit" id="change_project" class="pattern_button_2">Сохранить</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить проект \'<?= addslashes($project->getName()) ?>\'?')">
                                        <input type="hidden" name="project_id" value="<?php echo $projectID; ?>">
                                        <input type="hidden" name="delete_project" value="1">
                                        <button type="submit" id="delete_project">Удалить проект</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    </div>
                <?php endforeach; ?>
                
                <div class="block_project">
                    <p class="project_name">Выполненные</p>
                    <hr>
                    <?php
                    foreach ($tasks as $task) {
                        if ($task->getProgress() === 100) {
                            $task->display($db);
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================== БЛОК ДЛЯ АДМИНИСТРАТОРА ===================== -->
    <?php if ($username === 'admin'): ?>
        <div class="block_company_list_admin">
            <p id="name_company_admin_list" class="pattern_heading">Пользователи в системе</p>
            <ul>
                <?php while ($users = $users_result->fetchArray(SQLITE3_ASSOC)): ?>
                    <li>
                        <a href="user_info.php?id=<?php echo $users['id']; ?>"><?php echo htmlspecialchars($users['username']); ?></a>
                        <?php echo htmlspecialchars(' - ' . User::translateRole($users['role'])); ?>
                        <?php if ($users['username'] !== 'admin'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить пользователя \'<?= addslashes($users['username']) ?>\'?')">
                                <input type="hidden" name="user_id" value="<?php echo $users['id']; ?>">
                                <input type="hidden" name="delete_user" value="1">
                                <button type="submit" name="delete_user" id="delete_user_admin">Удалить</button>
                            </form>
                        <?php endif; ?>    
                        <?php if ($users['role'] === 'manager' && $users['username'] !== 'admin'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите понизить \'<?= addslashes($users['username']) ?>\'?')">
                                <input type="hidden" name="user_id" value="<?php echo $users['id']; ?>">
                                <input type="hidden" name="downgrade_user" value="1">
                                <button type="submit" name="downgrade_user" id="downgrade_upgrade_user">&#8595;</button>
                            </form>
                        <?php endif; ?>       
                        <?php if ($users['role'] === 'executer'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите повысить \'<?= addslashes($users['username']) ?>\'?')">
                                <input type="hidden" name="user_id" value="<?php echo $users['id']; ?>">
                                <input type="hidden" name="update_user" value="1">
                                <button type="submit" name="update_user" id="downgrade_upgrade_user">&#8593;</button>
                            </form>
                        <?php endif; ?>     
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- ===================== БЛОК ДЛЯ ИСПОЛНИТЕЛЕЙ ===================== -->
    <?php if ($role === 'executer'): ?>
        <div class="block_projects_company">
            <p id="name_projects_list" class="pattern_heading">Проекты</p>
            <div class="container_project">
                <?php 
                $users_for_filters = [];
                $users_okei->reset();
                while ($user = $users_okei->fetchArray(SQLITE3_ASSOC)) {
                    $users_for_filters[$user['id']] = $user['username'];
                }
                
                foreach ($projects as $project): 
                    $has_tasks_in_project = false;
                    if ($view_restrict === 'yes') {
                        foreach ($tasks as $task) {
                            if ($task->getProjectId() === $project->getId() && $task->getUserId() == $user_id && $task->getProgress() < 100) {
                                $has_tasks_in_project = true;
                                break;
                            }
                        }
                    }
                    
                    if ($view_restrict === 'no' || $has_tasks_in_project): 
                ?>
                    <div class="block_project">
                        <li>
                            <?php $projectID = $project->getId(); ?>
                            <p class="project_name"><?php echo htmlspecialchars($project->getName()); ?></p>
                            <hr>

                            <div class="filters-container" id="filters_<?php echo $projectID; ?>">
                                <div class="filter-header" onclick="toggleFilters(<?php echo $projectID; ?>)">
                                    <p class="filter-label">Фильтры: <span class="arrow-icon">▼</span></p>
                                </div>
                                <div class="filter-content" style="display: none;">
                                    <div class="filter-group">
                                        <select class="filter-select" id="importance_filter_<?php echo $projectID; ?>" onchange="filterTasks(<?php echo $projectID; ?>)">
                                            <option value="">Важность</option>
                                            <option value="high">Высокая</option>
                                            <option value="medium">Средняя</option>
                                            <option value="low">Низкая</option>
                                        </select>
                                        
                                        <select class="filter-select_username" id="user_filter_<?php echo $projectID; ?>" onchange="filterTasks(<?php echo $projectID; ?>)">
                                            <option value="">Исполнитель</option>
                                            <?php foreach ($users_for_filters as $id => $username): ?>
                                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($username); ?></option>
                                            <?php endforeach; ?>
                                        </select><br>
                                        
                                        <button class="filter-button reset-filters" onclick="resetFilters(<?php echo $projectID; ?>)">Сбросить</button>
                                    </div>
                                </div>
                            </div>

                            <div id="tasks_container_<?php echo $projectID; ?>">
                                <?php
                                foreach ($tasks as $task) {
                                    if ($task->getProjectId() === $projectID && $task->getProgress() !== 100) {
                                        if ($users_manager['view_restrict'] === 'no' || 
                                            ($users_manager['view_restrict'] === 'yes' && $task->getUserId() == $user_id)) {
                                            $task->display($db);
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </li>
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="block_project">
                    <p class="project_name">Выполненные</p>
                    <hr>
                    <?php
                    foreach ($tasks as $task) {
                        if ($task->getProgress() === 100) {
                            if ($users_manager['view_restrict'] === 'no' || 
                                ($users_manager['view_restrict'] === 'yes' && $task->getUserId() == $user_id)) {
                                $task->display($db);
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>