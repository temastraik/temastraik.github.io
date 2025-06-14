// Функции подтверждения действий
function confirmDeleteMember(username) {
    return confirm(`Вы уверены, что хотите удалить пользователя '${username}'?`);
}

function confirmDeleteTask(taskName) {
    return confirm(`Вы уверены, что хотите удалить задачу '${taskName}'?`);
}