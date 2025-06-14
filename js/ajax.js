// Инициализация обработчиков форм редактирования проекта
document.addEventListener('DOMContentLoaded', function() {
    const projectForms = document.querySelectorAll('form[id^="edit_project_form_"]');
    
    projectForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const projectId = formData.get('project_id');
            
            fetch('projects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.new_name) {
                    // Обновляем название проекта на странице
                    document.getElementById(`project_name_${projectId}`).textContent = data.new_name;
                    // Закрываем модальное окно
                    document.getElementById(`edit_project_${projectId}`).style.display = 'none';
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
