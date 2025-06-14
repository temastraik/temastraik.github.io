// Переключение видимости блока фильтров
function toggleFilters(projectID) {
    const filterContent = document.querySelector(`#filters_${projectID} .filter-content`);
    const arrowIcon = document.querySelector(`#filters_${projectID} .arrow-icon`);
    
    if (filterContent.style.display === 'none') {
        filterContent.style.display = 'block';
        arrowIcon.textContent = '▲';
    } else {
        filterContent.style.display = 'none';
        arrowIcon.textContent = '▼';
    }
}

// Фильтрация задач
function filterTasks(projectId) {
    const importanceFilter = document.getElementById(`importance_filter_${projectId}`).value;
    const userFilter = document.getElementById(`user_filter_${projectId}`).value;
    const tasksContainer = document.getElementById(`tasks_container_${projectId}`);
    const tasks = tasksContainer.querySelectorAll('.block_task');
    
    tasks.forEach(task => {
        const taskImportance = task.querySelector('[id^="important_"]').id.replace('important_', '');
        const taskUserId = task.querySelector('[id^="name_worker"]').textContent.replace('@', '');
        const userMatch = userFilter === '' || task.querySelector('[id^="name_worker"]').textContent.includes(
            document.getElementById(`user_filter_${projectId}`).options[document.getElementById(`user_filter_${projectId}`).selectedIndex].text
        );
        const importanceMatch = importanceFilter === '' || taskImportance === importanceFilter;
        
        if (userMatch && importanceMatch) {
            task.style.display = 'block';
        } else {
            task.style.display = 'none';
        }
    });
}

// Сброс фильтров
function resetFilters(projectId) {
    document.getElementById(`importance_filter_${projectId}`).value = '';
    document.getElementById(`user_filter_${projectId}`).value = '';
    filterTasks(projectId);
}