// Показать модальное окно создания задачи с выбранным проектом
function showCreateTaskModal(projectId) {
    document.getElementById('project_id').value = projectId;
    document.getElementById('create_task').style.display = 'flex';
}

// Показать модальное окно редактирования проекта
function showEditProjectModal(projectId) {
    const modal = document.getElementById("edit_project_"+projectId);
    modal.style.display = "flex";
    const main = document.querySelector("main");
    if (main) {
        main.style.filter = "brightness(0.8)";
    }
}

// Скрыть модальное окно редактирования проекта
function hideEditProjectModal(projectId) {
    const modal = document.getElementById("edit_project_"+projectId);
    modal.style.display = "none";
    const main = document.querySelector("main");
    if (main) {
        main.style.filter = "";
    }
}

// Показать чек-листы
function toggleChecklist(header) {
    const container = header.nextElementSibling;
    const arrow = header.querySelector('.checklist-arrow');
    
    if (container.style.display === 'none') {
        container.style.display = 'block';
        arrow.textContent = '▲';
    } else {
        container.style.display = 'none';
        arrow.textContent = '▼';
    }
}

// Закрытие модальных окон при клике вне их области
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });
});