<?php
class Note {
    private $db;
    private $userId;
    public $error_add_note = '';
    public $edit_note = null;
    public $notes = [];
    public $order = 'DESC';

    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }

    // Добавление новой заметки
    public function addNote($title, $description) {
        $title = trim($title ?? '');
        $description = trim($description ?? '');
        
        if (empty($title)) {
            $this->error_add_note = "Заголовок заметки не может быть пустым!";
            return false;
        }

        // Проверка на существование заметки с таким же названием
        $checkStmt = $this->db->prepare('SELECT COUNT(*) FROM Notes WHERE note_name = :note_name AND user_id = :user_id');
        $checkStmt->bindValue(':note_name', $title, SQLITE3_TEXT);
        $checkStmt->bindValue(':user_id', $this->userId, SQLITE3_INTEGER);
        $result = $checkStmt->execute();
        $count = $result->fetchArray(SQLITE3_NUM)[0] ?? 0;
        
        if ($count > 0) {
            $this->error_add_note = "Заметка с таким заголовком уже существует!";
            return false;
        }

        // Добавление заметки
        $stmt = $this->db->prepare('INSERT INTO Notes (note_name, description, user_id) VALUES (:note_name, :description, :user_id)');
        $stmt->bindValue(':note_name', $title, SQLITE3_TEXT);
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $this->userId, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            return true;
        } else {
            $this->error_add_note = "Ошибка при добавлении заметки!";
            return false;
        }
    }

    // Обновление существующей заметки
    public function updateNote($id, $title, $description) {
        $id = (int)($id ?? 0);
        $title = trim($title ?? '');
        $description = trim($description ?? '');
        
        if (empty($title) || $id <= 0) {
            $this->error_add_note = "Заголовок заметки не может быть пустым и ID должен быть корректным!";
            return false;
        }

        // Проверка на существование другой заметки с таким же названием
        $checkStmt = $this->db->prepare('SELECT COUNT(*) FROM Notes WHERE note_name = :note_name AND user_id = :user_id AND id != :id');
        $checkStmt->bindValue(':note_name', $title, SQLITE3_TEXT);
        $checkStmt->bindValue(':user_id', $this->userId, SQLITE3_INTEGER);
        $checkStmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $checkStmt->execute();
        $count = $result->fetchArray(SQLITE3_NUM)[0] ?? 0;
        
        if ($count > 0) {
            $this->error_add_note = "Другая заметка с таким заголовком уже существует!";
            return false;
        }

        // Обновление заметки
        $stmt = $this->db->prepare('UPDATE Notes SET note_name = :note_name, description = :description WHERE id = :id AND user_id = :user_id');
        $stmt->bindValue(':note_name', $title, SQLITE3_TEXT);
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $this->userId, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            return true;
        } else {
            $this->error_add_note = "Ошибка при обновлении заметки!";
            return false;
        }
    }

    // Удаление заметки
    public function deleteNote($id) {
        $id = (int)($id ?? 0);
        
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM Notes WHERE id = :id AND user_id = :user_id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $this->userId, SQLITE3_INTEGER);
        
        return $stmt->execute();
    }

    // Установка порядка сортировки
    public function setOrder($filter) {
        $this->order = ($filter === 'oldest') ? 'ASC' : 'DESC';
    }

    // Загрузка заметок из базы данных
    public function loadNotes() {
        $result = $this->db->query("SELECT * FROM Notes WHERE user_id = ".$this->userId." ORDER BY created_at ".$this->order);
        $this->notes = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->notes[] = $row;
        }
    }

    // Загрузка заметки для редактирования
    public function loadNoteForEdit($id) {
        $id = (int)$id;
        if ($id > 0) {
            $result = $this->db->query("SELECT * FROM Notes WHERE id = $id AND user_id = ".$this->userId);
            $this->edit_note = $result->fetchArray(SQLITE3_ASSOC);
        }
    }

    // Обработка POST и GET запросов
    public function handleRequests() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_note'])) {
                if ($this->addNote($_POST['note_name'], $_POST['description'])) {
                    header('Location: '.$_SERVER['PHP_SELF']);
                    exit();
                }
            } elseif (isset($_POST['update_note'])) {
                if ($this->updateNote($_POST['id'], $_POST['note_name'], $_POST['description'])) {
                    header('Location: '.$_SERVER['PHP_SELF']);
                    exit();
                }
            } elseif (isset($_POST['delete_note'])) {
                if ($this->deleteNote($_POST['id'])) {
                    header('Location: '.$_SERVER['PHP_SELF']);
                    exit();
                }
            }
        }

        if (isset($_GET['filter'])) {
            $this->setOrder($_GET['filter']);
        }

        if (isset($_GET['edit'])) {
            $this->loadNoteForEdit($_GET['edit']);
        }

        $this->loadNotes();
    }
}

// Инициализация и обработка запросов
$note = new Note($db, $_SESSION['user_id']);
$note->handleRequests();
?>