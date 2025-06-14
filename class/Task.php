<?php
class Task {
    private $db;
    private $id;
    private $name;
    private $description;
    private $importance;
    private $user_id;
    private $project_id;
    private $progress;
    private $deadline;
    private $tag;
    private $file_path;
    private $checklist_items = [];

    public function __construct($db, $id = null) {
        $this->db = $db;
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        $stmt = $this->db->prepare("SELECT * FROM Tasks WHERE id = :id");
        $stmt->bindParam(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $task = $result->fetchArray(SQLITE3_ASSOC);

        if ($task) {
            $this->id = $task['id'];
            $this->name = $task['name'];
            $this->description = $task['description'];
            $this->importance = $task['importance'];
            $this->user_id = $task['user_id'];
            $this->project_id = $task['project_id'];
            $this->progress = $task['progress'];
            $this->deadline = $task['deadline'];
            $this->tag = $task['tag'];
            $this->file_path = $task['file_path'];
            $this->loadChecklist();
            return true;
        }
        return false;
    }

    private function loadChecklist() {
        $stmt = $this->db->prepare("SELECT * FROM Checklists WHERE task_id = :task_id");
        $stmt->bindParam(':task_id', $this->id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $this->checklist_items = [];
        while ($item = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->checklist_items[] = $item;
        }
    }

    public function create($data) {
        // Проверка уникальности названия задачи в проекте
        $check_stmt = $this->db->prepare("SELECT COUNT(*) FROM Tasks WHERE name = :name AND project_id = :project_id");
        $check_stmt->bindParam(':name', $data['name'], SQLITE3_TEXT);
        $check_stmt->bindParam(':project_id', $data['project_id'], SQLITE3_INTEGER);
        $result = $check_stmt->execute();
        $count = $result->fetchArray(SQLITE3_NUM)[0];
        
        if ($count > 0) {
            return "Задача с таким названием уже существует в этом проекте";
        }

        // Обработка файла
        $file_path = $this->handleFileUpload($data['file'] ?? null);

        // Создание задачи
        $stmt = $this->db->prepare("INSERT INTO Tasks (name, description, importance, user_id, project_id, progress, deadline, tag, file_path) 
                                  VALUES (:name, :description, :importance, :user_id, :project_id, :progress, :deadline, :tag, :file_path)");
        $stmt->bindParam(':name', $data['name'], SQLITE3_TEXT);
        $stmt->bindParam(':description', $data['description'], SQLITE3_TEXT);
        $stmt->bindParam(':importance', $data['importance'], SQLITE3_TEXT);
        $stmt->bindParam(':user_id', $data['user_id'], SQLITE3_INTEGER);
        $stmt->bindParam(':project_id', $data['project_id'], SQLITE3_INTEGER);
        $stmt->bindParam(':progress', $data['progress'], SQLITE3_INTEGER);
        $stmt->bindParam(':deadline', $data['deadline'], SQLITE3_TEXT);
        $stmt->bindParam(':tag', $data['tag'], SQLITE3_TEXT);
        $stmt->bindParam(':file_path', $file_path, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $this->id = $this->db->lastInsertRowID();
            $this->createChecklist($data['checklist_items'] ?? []);
            return true;
        } else {
            // Удаляем файл, если задача не создалась
            if ($file_path && file_exists($file_path)) {
                unlink($file_path);
            }
            return "Ошибка при создании задачи: " . $this->db->lastErrorMsg();
        }
    }

    public static function handleCreateTaskRequest($db, $user_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tasks_name'])) {
            $task = new Task($db);
            
            $data = [
                'name' => trim($_POST['tasks_name']),
                'description' => trim($_POST['task_description']),
                'importance' => $_POST['importance'],
                'user_id' => $_POST['user_id'],
                'project_id' => $_POST['project_id'],
                'progress' => $_POST['progress'],
                'deadline' => $_POST['deadline'],
                'tag' => !empty($_POST['tag']) ? $_POST['tag'] : null,
                'file' => $_FILES['task_file'] ?? null,
                'checklist_items' => []
            ];
            
            // Получаем элементы чек-листа
            for ($i = 1; $i <= 5; $i++) {
                if (!empty(trim($_POST['checklist_item_' . $i] ?? ''))) {
                    $data['checklist_items'][] = $_POST['checklist_item_' . $i];
                }
            }
            
            $result = $task->create($data);
            
            if ($result === true) {
                header("Location: project.php");
                exit();
            } else {
                return $result;
            }
        }
        return null;
    }

public static function handleEditTaskRequest($db, $user_id, $task_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $task = new Task($db, $task_id);
        
        if (!$task->getId()) {
            return "Задача не найдена";
        }
        
        // Получение роли пользователя
        $user = new User($db, $user_id);
        $user_role = $user->getRole();
        
        $data = [
            'progress' => (int)$_POST['progress'],
            'checklist' => $_POST['checklist'] ?? []
        ];
        
            $data['name'] = trim($_POST['name']);
            $data['description'] = trim($_POST['description']);
            $data['importance'] = $_POST['importance'];
            $data['user_id'] = (int)$_POST['user_id'];
            $data['deadline'] = $_POST['deadline'];
            $data['tag'] = !empty($_POST['tag']) ? $_POST['tag'] : null;
        
        if ($task->update($data, $user_role)) {
            header('Location: task_view.php?id=' . $task_id);
            exit;
        } else {
            return "Ошибка при обновлении задачи: " . $db->lastErrorMsg();
        }
    }
    return null;
}

    public static function handleDeleteTaskRequest($db, $task_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
            $task = new Task($db, $task_id);
            if ($task->getId() && $task->delete()) {
                header("Location: project.php");
                exit();
            } else {
                return "Ошибка при удалении задачи";
            }
        }
        return null;
    }

    private function handleFileUpload($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Проверка расширения файла
        $allowed_extensions = ['txt', 'docx', 'pdf'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            return "Недопустимый формат файла. Разрешены только txt, docx, pdf.";
        }
        
        // Проверка размера файла (до 1MB)
        if ($file['size'] > 1048576) {
            return "Файл слишком большой. Максимальный размер - 1MB.";
        }
        
        // Создаем папку для файлов, если ее нет
        $upload_dir = 'user_files';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Генерируем уникальное имя файла
        $file_name = uniqid() . '_' . basename($file['name']);
        $file_path = $upload_dir . '/' . $file_name;
        
        // Перемещаем файл в целевую директорию
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return "Ошибка при загрузке файла.";
        }

        return $file_path;
    }

    private function createChecklist($items) {
        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            if (!empty(trim($item))) {
                $stmt = $this->db->prepare("INSERT INTO Checklists (task_id, item_text) VALUES (:task_id, :item_text)");
                $stmt->bindParam(':task_id', $this->id, SQLITE3_INTEGER);
                $stmt->bindParam(':item_text', trim($item), SQLITE3_TEXT);
                $stmt->execute();
            }
        }
        $this->loadChecklist();
    }

    public function update($data, $user_role) {
    try {
        $this->db->exec('BEGIN TRANSACTION');
        
        if ($user_role === 'manager') {
            $stmt = $this->db->prepare("UPDATE Tasks SET name = :name, description = :description, importance = :importance, 
                                     user_id = :user_id, progress = :progress, deadline = :deadline, tag = :tag
                                     WHERE id = :id");
            $stmt->bindValue(':name', $data['name'], SQLITE3_TEXT);
            $stmt->bindValue(':description', $data['description'], SQLITE3_TEXT);
            $stmt->bindValue(':importance', $data['importance'], SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $data['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':progress', $data['progress'], SQLITE3_INTEGER);
            $stmt->bindValue(':deadline', $data['deadline'], SQLITE3_TEXT);
            $stmt->bindValue(':tag', $data['tag'], SQLITE3_TEXT);
            $stmt->bindValue(':id', $this->id, SQLITE3_INTEGER);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update task");
            }
        } else {
            // Для исполнителей обновляем только прогресс
            $stmt = $this->db->prepare("UPDATE Tasks SET progress = :progress WHERE id = :id");
            $stmt->bindValue(':progress', $data['progress'], SQLITE3_INTEGER);
            $stmt->bindValue(':id', $this->id, SQLITE3_INTEGER);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update progress");
            }
        }
        
        // Обновляем чек-лист (доступно всем ролям)
        if (isset($data['checklist'])) {
            foreach ($data['checklist'] as $item_id => $is_checked) {
                $update_stmt = $this->db->prepare("UPDATE Checklists SET is_checked = :is_checked WHERE id = :id");
                $update_stmt->bindValue(':is_checked', (int)$is_checked, SQLITE3_INTEGER);
                $update_stmt->bindValue(':id', (int)$item_id, SQLITE3_INTEGER);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update checklist item $item_id");
                }
            }
            $this->loadChecklist();
        }
        
        $this->db->exec('COMMIT');
        return true;
    } catch (Exception $e) {
        $this->db->exec('ROLLBACK');
        error_log("Task update error: " . $e->getMessage());
        return false;
    }
}

    public function delete() {
        // Удаляем чек-лист
        $stmt = $this->db->prepare("DELETE FROM Checklists WHERE task_id = :task_id");
        $stmt->bindParam(':task_id', $this->id, SQLITE3_INTEGER);
        $stmt->execute();

        // Удаляем файл, если он есть
        if ($this->file_path && file_exists($this->file_path)) {
            unlink($this->file_path);
        }

        // Удаляем саму задачу
        $stmt = $this->db->prepare("DELETE FROM Tasks WHERE id = :id");
        $stmt->bindParam(':id', $this->id, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    public function getDownloadLink() {
        if (!$this->file_path || !file_exists($this->file_path)) {
            return null;
        }

        $file_name = basename($this->file_path);
        $original_name = preg_replace('/^[a-z0-9]+_/', '', $file_name);
        return "download_file.php?task_id=" . $this->id;
    }

    public function getFileDisplayName() {
        if (!$this->file_path) {
            return null;
        }
        $file_name = basename($this->file_path);
        return preg_replace('/^[a-z0-9]+_/', '', $file_name);
    }

    // Геттеры для свойств
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getImportance() { return $this->importance; }
    public function getUserId() { return $this->user_id; }
    public function getProjectId() { return $this->project_id; }
    public function getProgress() { return $this->progress; }
    public function getDeadline() { return $this->deadline; }
    public function getTag() { return $this->tag; }
    public function getFilePath() { return $this->file_path; }
    public function getChecklistItems() { return $this->checklist_items; }

    public static function getTasksByProject($db, $project_id) {
        $stmt = $db->prepare("SELECT * FROM Tasks WHERE project_id = :project_id");
        $stmt->bindParam(':project_id', $project_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $tasks = [];
        while ($task_data = $result->fetchArray(SQLITE3_ASSOC)) {
            $task = new Task($db);
            $task->id = $task_data['id'];
            $task->name = $task_data['name'];
            $task->description = $task_data['description'];
            $task->importance = $task_data['importance'];
            $task->user_id = $task_data['user_id'];
            $task->project_id = $task_data['project_id'];
            $task->progress = $task_data['progress'];
            $task->deadline = $task_data['deadline'];
            $task->tag = $task_data['tag'];
            $task->file_path = $task_data['file_path'];
            $task->loadChecklist();
            $tasks[] = $task;
        }
        return $tasks;
    }

    public static function getTasksByCompany($db, $company_id) {
        $query = "SELECT t.* FROM Tasks t JOIN Projects p ON t.project_id = p.id WHERE p.company_id = :company_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':company_id', $company_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $tasks = [];
        while ($task_data = $result->fetchArray(SQLITE3_ASSOC)) {
            $task = new Task($db);
            $task->id = $task_data['id'];
            $task->name = $task_data['name'];
            $task->description = $task_data['description'];
            $task->importance = $task_data['importance'];
            $task->user_id = $task_data['user_id'];
            $task->project_id = $task_data['project_id'];
            $task->progress = $task_data['progress'];
            $task->deadline = $task_data['deadline'];
            $task->tag = $task_data['tag'];
            $task->file_path = $task_data['file_path'];
            $task->loadChecklist();
            $tasks[] = $task;
        }
        return $tasks;
    }

    public static function getTasksByMonth($db, $user_id, $month, $year) {
        $month_formatted = $month < 10 ? '0' . $month : $month;
        $tasks_query = "SELECT id, name, deadline FROM Tasks WHERE user_id = :user_id AND strftime('%Y-%m', deadline) = :month_year ORDER BY deadline";
        
        $stmt = $db->prepare($tasks_query);
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':month_year', "$year-$month_formatted", SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $tasks = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $deadline_day = date('j', strtotime($row['deadline']));
            if (!isset($tasks[$deadline_day])) {
                $tasks[$deadline_day] = [];
            }
            $tasks[$deadline_day][] = $row;
        }
        
        return $tasks;
    }


    public function display($db) {
    // Получение информации об исполнителе
    $user = User::fetchBasicUserInfo($db, $this->getUserId());
    
    // Иконки важности задач
    $importance_icons = [
        'high' => ['svg' => 'svg_high', 'text' => 'Важно'],
        'medium' => ['svg' => 'svg_medium', 'text' => 'Подождет'],
        'low' => ['svg' => 'svg_low', 'text' => 'Последнее']
    ];
    $importance = $importance_icons[$this->getImportance()] ?? $importance_icons['low'];
    
    // Блок задачи
    if ($this->getProgress() === 100) {
        echo '<div class="block_task_100">';
        echo "<p id='block_task_name_100'>" . htmlspecialchars($this->getName()) . "</p>";
        echo '<pre>';
        echo "<p id='block_task_name_worker_100'>@" . htmlspecialchars($user['username']) . "</p>";
        echo '<pre>';
        if (!empty($this->getTag())) {
            echo "<p id='block_task_tag_100'>#" . htmlspecialchars($this->getTag()) . "</p>";
            echo '<pre>';
        }
        echo '<a id="block_task_href_100" href="task_view.php?id=' . $this->getId() . '">Описание...</a>';
        echo '</div>';
    } else {
        echo '<div class="block_task">';
        echo "<p id='block_task_name'>" . htmlspecialchars($this->getName()) . "</p>";
        echo '<pre>';
        echo "<img src='SVG/importants.svg' id='{$importance['svg']}'>";
        echo "<p id='important_{$this->getImportance()}'>{$importance['text']}</p>";
        echo '<pre>';
        echo "<p id='block_task_name_worker'>@" . htmlspecialchars($user['username']) . "</p>";
        echo '<pre>';
        echo "<p id='block_task_progress'>Прогресс: " . htmlspecialchars($this->getProgress()) . "%</p>";
        echo '<pre>';
        if (!empty($this->getTag())) {
            echo "<p id='block_task_tag'>#" . htmlspecialchars($this->getTag()) . "</p>";
            echo '<pre>';
        }
        echo '<a id="block_task_href" href="task_view.php?id=' . $this->getId() . '">Описание...</a>';
        echo '</div>';
    }
}
}
?>