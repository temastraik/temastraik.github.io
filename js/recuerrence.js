document.addEventListener('DOMContentLoaded', function() {
    const recurrenceSelect = document.querySelector('select[name="recurrence"]');
    const recurrenceFields = document.getElementById('recurrence-fields');
    const endDateInput = document.querySelector('input[name="recurrence_end_date"]');
    
    // Устанавливаем минимальную дату как сегодняшнюю
    const today = new Date().toISOString().split('T')[0];
    if (endDateInput) {
        endDateInput.min = today;
    }

    // Скрываем блок при загрузке страницы
    recurrenceFields.style.display = 'none';
    if (endDateInput) {
        endDateInput.required = false;
    }

    // Функция для проверки, нужно ли показывать поля повторения
    function shouldShowRecurrenceFields(value) {
        return value !== 'none';
    }

    // Обработчик изменения выбора
    recurrenceSelect.addEventListener('change', function() {
        const showFields = shouldShowRecurrenceFields(this.value);
        
        // Показываем/скрываем блок с полями
        recurrenceFields.style.display = showFields ? 'block' : 'none';
        
        // Устанавливаем обязательность поля даты окончания
        if (endDateInput) {
            endDateInput.required = showFields;
            
            // Сбрасываем значение, если поля скрыты
            if (!showFields) {
                endDateInput.value = '';
            }
        }
    });
});