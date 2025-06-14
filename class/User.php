<?php
class User {
    private $db;
    private $id;
    private $username;
    private $role;
    private $company_id;
    private $view_restrict;
    private $first_name;
    private $last_name;
    private $patronymic;
    private $email;

    public function __construct($db, $user_id = null) {
        $this->db = $db;
        if ($user_id !== null) {
            $this->load($user_id);
        }
    }

    private function load($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $user_data = $result->fetchArray(SQLITE3_ASSOC);

        if ($user_data) {
            $this->id = $user_data['id'];
            $this->username = $user_data['username'];
            $this->role = $user_data['role'];
            $this->company_id = $user_data['company_id'];
            $this->view_restrict = $user_data['view_restrict'];
            $this->first_name = $user_data['first_name'] ?? '';
            $this->last_name = $user_data['last_name'] ?? '';
            $this->patronymic = $user_data['patronymic'] ?? '';
            $this->email = $user_data['email'] ?? '';
        }
    }

    // Геттеры
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getRole() { return $this->role; }
    public function getCompanyId() { return $this->company_id; }
    public function getViewRestrict() { return $this->view_restrict; }
    public function getFirstName() { return $this->first_name; }
    public function getLastName() { return $this->last_name; }
    public function getPatronymic() { return $this->patronymic; }
    public function getEmail() { return $this->email; }

    // Методы для работы с профилем пользователя
    public function updateProfile($data) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return "Введите корректную почту";
        }

        $stmt = $this->db->prepare("UPDATE Users SET username = :username, view_restrict = :view_restrict, 
                                  first_name = :first_name, last_name = :last_name, 
                                  patronymic = :patronymic, email = :email WHERE id = :id");
        $stmt->bindValue(':username', $data['username'], SQLITE3_TEXT);
        $stmt->bindValue(':first_name', $data['first_name'], SQLITE3_TEXT);
        $stmt->bindValue(':last_name', $data['last_name'], SQLITE3_TEXT);
        $stmt->bindValue(':patronymic', $data['patronymic'], SQLITE3_TEXT);
        $stmt->bindValue(':email', $data['email'], SQLITE3_TEXT);
        $stmt->bindValue(':id', $this->id, SQLITE3_INTEGER);
        $stmt->bindValue(':view_restrict', $data['view_restrict'], SQLITE3_TEXT);

        if ($stmt->execute()) {
            $this->username = $data['username'];
            $this->first_name = $data['first_name'];
            $this->last_name = $data['last_name'];
            $this->patronymic = $data['patronymic'];
            $this->email = $data['email'];
            $this->view_restrict = $data['view_restrict'];
            return true;
        } else {
            return "Ошибка при обновлении данных: " . $this->db->lastErrorMsg();
        }
    }

    public static function getCompanyName($db, $company_id) {
        $stmt = $db->prepare("SELECT name FROM Company WHERE id = :company_id");
        $stmt->bindParam(':company_id', $company_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $company = $result->fetchArray(SQLITE3_ASSOC);
        return $company ? $company['name'] : '';
    }

    // Статические методы для работы с пользователями
    public static function fetchUserData($db, $user_id) {
        $stmt = $db->prepare("SELECT username, first_name, last_name, patronymic, email, company_id, id, role, view_restrict FROM Users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, SQLITE3_INTEGER);
        $user_info = $stmt->execute();
        $user = $user_info->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) {
            die("Ошибка: пользователь не найден");
        }
        
        return $user;
    }

    public static function fetchUsers($db, $user_id) {
        $stmt = $db->prepare("SELECT id, username FROM Users WHERE company_id = (SELECT company_id FROM Users WHERE id = :user_id)");
        $stmt->bindParam(':user_id', $user_id, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    public static function fetchCompanyMembers($db, $company_id) {
        $stmt = $db->prepare("SELECT id, username, role FROM Users WHERE company_id = :company_id");
        $stmt->bindParam(':company_id', $company_id, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    public static function fetchAllUsers($db) {
        return $db->query("SELECT id, username, view_restrict, role FROM Users");
    }

    public static function fetchManagerData($db, $company_id) {
        $stmt = $db->prepare("SELECT view_restrict FROM Users WHERE role = 'manager' AND company_id = :company_id");
        $stmt->bindParam(':company_id', $company_id, SQLITE3_INTEGER);
        $users_management = $stmt->execute();
        return $users_management->fetchArray(SQLITE3_ASSOC);
    }

    // Методы для обработки действий с пользователями
    public static function handleUserRegistration($db, $company_id) {
        $error_add_user = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
            $new_username = trim($_POST['new_username']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($password !== $confirm_password) {
                $error_add_user = "Пароли не совпадают";
            } elseif (strlen($new_username) > 10) {
                $error_add_user = "Username должен быть не больше 10 символов";
            } else {
                // Проверка существования пользователя
                $stmt = $db->prepare("SELECT * FROM Users WHERE username = :username");
                $stmt->bindParam(':username', $new_username, SQLITE3_TEXT);
                $result = $stmt->execute();
                
                if ($result->fetchArray(SQLITE3_ASSOC)) {
                    $error_add_user = "Пользователь с таким именем уже существует.";
                } else {
                    // Создание нового пользователя
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO Users (username, password, role, company_id) 
                                    VALUES (:username, :password, 'executer', :company_id)");
                    $stmt->bindParam(':username', $new_username, SQLITE3_TEXT);
                    $stmt->bindParam(':password', $password_hash, SQLITE3_TEXT);
                    $stmt->bindParam(':company_id', $company_id, SQLITE3_INTEGER);

                    if (!$stmt->execute()) {
                        $error_add_user = "Ошибка при создании пользователя: " . $db->lastErrorMsg();
                    } else {
                        // Перенаправление после успешного добавления
                        header("Location: project.php");
                        exit();
                    }
                }
            }
        }
        
        return $error_add_user;
    }

    public static function deleteUser($db, $user_id) {
        $stmt = $db->prepare("DELETE FROM Users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    public static function changeUserRole($db, $user_id, $new_role) {
        $stmt = $db->prepare("UPDATE Users SET role = :role WHERE id = :user_id");
        $stmt->bindValue(':role', $new_role, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    public static function handleUserActions($db) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['delete_user'])) {
                $user_id = intval($_POST['user_id']);
                self::deleteUser($db, $user_id);
            }
            elseif (isset($_POST['downgrade_user'])) {
                $user_id = intval($_POST['user_id']);
                self::changeUserRole($db, $user_id, 'executer');
            }
            elseif (isset($_POST['update_user'])) {
                $user_id = intval($_POST['user_id']);
                self::changeUserRole($db, $user_id, 'manager');
            }
        }
    }

    // Вспомогательные методы
    public static function translateRole($role) {
        switch ($role) {
            case 'manager':
                return 'менеджер';
            case 'executer':
                return 'исполнитель';
            default:
                return $role;
        }
    }

    // Новые методы для вывода информации о пользователе
    public static function renderUserProfileModal($user_id, $username, $user_data) {
        ob_start(); ?>
        <div id="user_info_<?php echo $user_id; ?>" class="modal" style="display:none;">
            <div id="window_user_info" class="pattern_modal">
                <a onclick="document.getElementById('user_info_<?php echo $user_id; ?>').style.display='none'"><img src="SVG/cross.svg" alt="Закрыть окно" class="close"></a>
                <p id="name_user_info" class="pattern_heading">Профиль <?php echo htmlspecialchars($username); ?></p>
                <p id="window_user_info_p"><strong>Фамилия:</strong> <?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?></p>
                <p id="window_user_info_p"><strong>Имя:</strong> <?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?></p>
                <p id="window_user_info_p"><strong>Отчество:</strong> <?php echo htmlspecialchars($user_data['patronymic'] ?? ''); ?></p>
                <p id="window_user_info_p"><strong>Почта:</strong> <?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function renderUserLinkWithModal($user_id, $username) {
        return '<a href="#" onclick="document.getElementById(\'user_info_' . $user_id . '\').style.display=\'block\'">' . htmlspecialchars($username) . '</a>';
    }

    public static function renderUserSelectOptions($db, $current_user_id, $selected_user_id = null) {
        $output = '';
        $users_stmt = $db->prepare("SELECT id, username FROM Users WHERE company_id = (SELECT company_id FROM Users WHERE id = :user_id)");
        $users_stmt->bindParam(':user_id', $current_user_id, SQLITE3_INTEGER);
        $users_result = $users_stmt->execute();
        
        while ($user_row = $users_result->fetchArray(SQLITE3_ASSOC)) {
            $selected = $user_row['id'] == $selected_user_id ? 'selected' : '';
            $output .= '<option value="' . $user_row['id'] . '" ' . $selected . '>' . 
                      htmlspecialchars($user_row['username']) . '</option>';
        }
        
        return $output;
    }

    public static function fetchBasicUserInfo($db, $user_id) {
        $stmt = $db->prepare("SELECT username, id FROM Users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public static function fetchDetailedUserInfo($db, $user_id) {
        $stmt = $db->prepare("SELECT username, first_name, last_name, patronymic, email FROM Users WHERE id = :id");
        $stmt->bindParam(':id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public static function handleProfileUpdate($db, $user_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $user = new self($db, $user_id);
        if (!$user->getId()) {
            return "Пользователь не найден";
        }

        $data = [
            'username' => trim($_POST['username']),
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'patronymic' => trim($_POST['patronymic']),
            'email' => trim($_POST['email']),
            'view_restrict' => isset($_POST['view_restrict']) ? trim($_POST['view_restrict']) : 'no'
        ];

        $result = $user->updateProfile($data);
        
        if ($result === true) {
            // Обновление сессии
            $_SESSION['username'] = $data['username'];
            $_SESSION['first_name'] = $data['first_name'];
            $_SESSION['last_name'] = $data['last_name'];
            $_SESSION['patronymic'] = $data['patronymic'];
            $_SESSION['email'] = $data['email'];
            $_SESSION['view_restrict'] = $data['view_restrict'];
            
            header('Location: profile.php');
            exit();
        }

        return $result;
    }

    /**
     * Получает данные пользователя для отображения в профиле
     */
    public static function getProfileData($db, $user_id) {
        $user = new self($db, $user_id);
        if (!$user->getId()) {
            return null;
        }

        return [
            'user' => $user,
            'company_name' => self::getCompanyName($db, $user->getCompanyId())
        ];
    }

    /**
     * Обработка авторизации пользователя
     */
    public static function handleLogin($db) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $stmt = $db->prepare('SELECT id, password FROM Users WHERE username = :username');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: project.php');
                exit();
            } else {
                return "Неверное имя пользователя или пароль.";
            }
        }
        return null;
    }

    /**
     * Обработка регистрации нового пользователя (менеджера)
     */
    public static function handleRegistration($db) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $company = $_POST['company'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s]+$/u", $company)) {
                return "Название компании может содержать только буквы";
            } elseif ($password !== $confirm_password) {
                return "Пароли не совпадают";
            } elseif (strlen($username) > 10) {
                return "Username должен быть не больше 10 символов";
            } elseif (strlen($company) > 10) {
                return "Название компании должно быть не больше 10 символов";
            } else {
                $stmt = $db->prepare('SELECT * FROM Users WHERE username = :username');
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $result = $stmt->execute();
                
                if ($result->fetchArray(SQLITE3_ASSOC)) {
                    return "Имя пользователя занято";
                } else {
                    $stmt = $db->prepare('SELECT id FROM Company WHERE name = :name');
                    $stmt->bindValue(':name', $company, SQLITE3_TEXT);
                    $result = $stmt->execute();
                    $company_data = $result->fetchArray(SQLITE3_ASSOC);
                    
                    if ($company_data) {
                        return "Компания с таким названием уже существует. Выберите другое название.";
                    } else {
                        $stmt = $db->prepare('INSERT INTO Company (name) VALUES (:name)');
                        $stmt->bindValue(':name', $company, SQLITE3_TEXT);
                        $stmt->execute();
                        $company_id = $db->lastInsertRowID();
                        
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare('INSERT INTO Users (username, role, password, company_id) VALUES (:username, :role, :password, :company_id)');
                        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                        $stmt->bindValue(':role', 'manager', SQLITE3_TEXT);
                        $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
                        $stmt->bindValue(':company_id', $company_id, SQLITE3_INTEGER);
                        $stmt->execute();
                        
                        header('Location: #login');
                        exit();
                    }
                }
            }
        }
        return null;
    }

    /**
     * Рендерит модальное окно авторизации
     */
    public static function renderLoginModal($error = null) {
        ob_start(); ?>
        <div id="window_login" class="pattern_modal">
            <a href="#"><img src="SVG/cross.svg" alt="Закрыть окно" class="close"></a>
            <p id='name_autorization' class="pattern_heading">Вход в систему</p>
            <form method="POST">
                <label>username:</label>
                <input type="text" name="username" id="login_username" class="pattern_input" required maxlength="10"><br>
                <label>пароль:</label>
                <input type="password" name="password" id="login_password" class="pattern_input" required><br>
                <input type="submit" value="Войти" id="login_submit" class="pattern_button_2">
            </form>
            <p id='choice_window'>или</p>
            <button onclick="window.location.href='#register'" id='login_register' class="pattern_button_1">Зарегистрироваться</button>
            <?php if ($error): ?>
                <p style='color:red; text-align:center; margin-top:-217px;'><?php echo $error; ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Рендерит модальное окно регистрации
     */
    public static function renderRegistrationModal($error = null) {
        ob_start(); ?>
        <div id="window_register" class="pattern_modal">
            <a href="#"><img src="SVG/cross.svg" alt="Закрыть окно" class="close"></a>
            <p id="name_registration" class="pattern_heading">Регистрация менеджера</p>
            <form method="POST">
                <label>username:</label>
                <input type="text" name="username" id="register_username" class="pattern_input" placeholder="до 10 символов" required maxlength="10"><br>
                <label>компания:</label>
                <input type="text" name="company" id="register_company" class="pattern_input"placeholder="до 10 символов" required maxlength="10"><br>
                <label>пароль:</label>
                <input type="password" name="password" id="register_password" class="pattern_input" required maxlength="40"><br>
                <label>повторите пароль:</label>
                <input type="password" name="confirm_password" id="register_confirm_password" class="pattern_input" required maxlength="40"><br>
                <input type="submit" value="Зарегистрироваться" id="register_submit" class="pattern_button_2">
            </form>
            <p id="choice_window">или</p>
            <button onclick="window.location.href='#login'" id="register_login" class="pattern_button_1">Войти</button>
            <?php if ($error): ?>
                <p style='color:red; text-align:center; margin-top:-217px;'><?php echo $error; ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>