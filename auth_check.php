<?php
session_start();
$db = new SQLite3('database.db');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>