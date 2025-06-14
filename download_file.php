<?php
require_once 'class/Task.php';

$db = new SQLite3('database.db');
$task_id = $_GET['task_id'] ?? 0;
$task = new Task($db, $task_id);

if (!$task->getId() || !$task->getFilePath() || !file_exists($task->getFilePath())) {
    die("Файл не найден");
}

$file_path = $task->getFilePath();
$original_name = $task->getFileDisplayName();

// Определяем MIME-тип файла
$mime_types = [
    'txt' => 'text/plain',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'pdf' => 'application/pdf'
];
$extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
$mime_type = $mime_types[$extension] ?? 'application/octet-stream';

// Отправляем файл для скачивания
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $original_name . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
?>