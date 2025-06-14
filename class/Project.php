<?php
class Project {
    private $id;
    private $name;
    private $company_id;
    private $db;

    public function __construct($db, $id = null) {
        $this->db = $db;
        if ($id) {
            $this->load($id);
        }
    }

    private function load($id) {
        $stmt = $this->db->prepare("SELECT id, name, company_id FROM Projects WHERE id = :id");
        $stmt->bindParam(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $project = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($project) {
            $this->id = $project['id'];
            $this->name = $project['name'];
            $this->company_id = $project['company_id'];
            return true;
        }
        return false;
    }

    public static function create($db, $name, $user_id) {
        $name = trim($name);
        
        // Проверка существования проекта
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM Projects 
                                  WHERE name = :name 
                                  AND company_id = (SELECT company_id FROM Users WHERE id = :user_id)");
        $check_stmt->bindParam(':name', $name, SQLITE3_TEXT);
        $check_stmt->bindParam(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $check_stmt->execute();
        $count = $result->fetchArray(SQLITE3_NUM)[0];
        
        if ($count > 0) {
            throw new Exception("Проект с таким названием уже существует");
        }

        // Создание проекта
        $stmt = $db->prepare("INSERT INTO Projects (name, company_id) 
                            VALUES (:name, (SELECT company_id FROM Users WHERE id = :user_id))");
        $stmt->bindParam(':name', $name, SQLITE3_TEXT);
        $stmt->bindParam(':user_id', $user_id, SQLITE3_INTEGER);
        
        if (!$stmt->execute()) {
            throw new Exception("Ошибка при создании проекта: " . $db->lastErrorMsg());
        }
        
        return new Project($db, $db->lastInsertRowID());
    }

    public function update($new_name, $user_id) {
        $new_name = trim($new_name);
        
        // Проверка существования проекта
        $check_stmt = $this->db->prepare("SELECT COUNT(*) FROM Projects 
                                        WHERE name = :name 
                                        AND company_id = (SELECT company_id FROM Users WHERE id = :user_id)
                                        AND id != :id");
        $check_stmt->bindParam(':name', $new_name, SQLITE3_TEXT);
        $check_stmt->bindParam(':user_id', $user_id, SQLITE3_INTEGER);
        $check_stmt->bindParam(':id', $this->id, SQLITE3_INTEGER);
        $result = $check_stmt->execute();
        $count = $result->fetchArray(SQLITE3_NUM)[0];
        
        if ($count > 0) {
            throw new Exception("Проект с таким названием уже существует");
        }

        $stmt = $this->db->prepare("UPDATE Projects SET name = :name WHERE id = :id");
        $stmt->bindParam(':name', $new_name, SQLITE3_TEXT);
        $stmt->bindParam(':id', $this->id, SQLITE3_INTEGER);
        
        if (!$stmt->execute()) {
            throw new Exception("Ошибка при обновлении проекта: " . $this->db->lastErrorMsg());
        }
        
        $this->name = $new_name;
        return $this;
    }

    public function delete() {
        $stmt = $this->db->prepare("DELETE FROM Projects WHERE id = :id");
        $stmt->bindParam(':id', $this->id, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    public static function getAllByUserCompany($db, $user_id) {
        $stmt = $db->prepare("SELECT id, name, company_id FROM Projects 
                             WHERE company_id = (SELECT company_id FROM Users WHERE id = :user_id)");
        $stmt->bindParam(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $projects = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $project = new Project($db);
            $project->id = $row['id'];
            $project->name = $row['name'];
            $project->company_id = $row['company_id'];
            $projects[] = $project;
        }
        
        return $projects;
    }

    // Добавьте этот метод в класс Project в Project.php
public static function handleDeleteRequest($db) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
        try {
            $project_id = $_POST['project_id'];
            $project = new Project($db, $project_id);
            
            // Удаляем все задачи проекта сначала
            $stmt = $db->prepare("DELETE FROM Tasks WHERE project_id = :project_id");
            $stmt->bindParam(':project_id', $project_id, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Затем удаляем сам проект
            if ($project->delete()) {
                header("Location: project.php");
                exit();
            } else {
                throw new Exception("Ошибка при удалении проекта");
            }
        } catch (Exception $e) {
            // Можно добавить обработку ошибки или логирование
            header("Location: projects.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

// В класс Project добавим эти методы
public static function handleCreateRequest($db) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
        try {
            Project::create($db, $_POST['project_name'], $_SESSION['user_id']);
            header("Location: project.php");
            exit();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    return null;
}

public static function handleUpdateRequest($db) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id']) && isset($_POST['project_name'])) {
        try {
            $project = new Project($db, $_POST['project_id']);
            $project->update($_POST['project_name'], $_SESSION['user_id']);
            
            header('Content-Type: application/json');
            echo json_encode(['new_name' => $project->getName()]);
            exit();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    return null;
}

    // Геттеры
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getCompanyId() { return $this->company_id; }
}
?>