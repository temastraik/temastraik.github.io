document.addEventListener('DOMContentLoaded', () => {
    const contactWindow = document.getElementById('contact-window');
    const closeButton = document.getElementById('close-btn');

    contactWindow.style.display = 'block';

    // Закрытие окна
    closeButton.addEventListener('click', () => {
        contactWindow.style.display = 'none';
    });
});