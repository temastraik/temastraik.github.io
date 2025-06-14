document.addEventListener('DOMContentLoaded', function() {
    // Инициализация обработчика событий для всех маркеров
    function initTooltips() {
        // Маркеры задач
        document.querySelectorAll('.deadline-marker').forEach(marker => {
            marker.addEventListener('mouseover', function(e) {
                const tooltip = document.getElementById('task-tooltip');
                tooltip.innerHTML = this.getAttribute('data-tooltip-content');
                tooltip.style.display = 'block';
                tooltip.style.left = (e.pageX + 10) + 'px';
                tooltip.style.top = (e.pageY - tooltip.offsetHeight - 10) + 'px';
            });
            
            marker.addEventListener('mouseout', function() {
                document.getElementById('task-tooltip').style.display = 'none';
            });
        });
        
        // Маркеры событий
        document.querySelectorAll('.event-marker').forEach(marker => {
            marker.addEventListener('mouseover', function(e) {
                const tooltip = document.getElementById('event-tooltip');
                tooltip.innerHTML = this.getAttribute('data-tooltip-content');
                tooltip.style.display = 'block';
                tooltip.style.left = (e.pageX + 10) + 'px';
                tooltip.style.top = (e.pageY - tooltip.offsetHeight - 10) + 'px';
            });
            
            marker.addEventListener('mouseout', function() {
                document.getElementById('event-tooltip').style.display = 'none';
            });
        });
        
        // Повторяющиеся маркеры событий
        document.querySelectorAll('.recurring-marker').forEach(marker => {
            marker.addEventListener('mouseover', function(e) {
                const tooltip = document.getElementById('recurring-tooltip');
                tooltip.innerHTML = this.getAttribute('data-tooltip-content');
                tooltip.style.display = 'block';
                tooltip.style.left = (e.pageX + 10) + 'px';
                tooltip.style.top = (e.pageY - tooltip.offsetHeight - 10) + 'px';
            });
            
            marker.addEventListener('mouseout', function() {
                document.getElementById('recurring-tooltip').style.display = 'none';
            });
        });
    }
    
    initTooltips();
    
});