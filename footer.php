<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
</head>
<body>
    <footer>
        <div class="footer-content">
          <meta content="2025">
          <meta content="YouProject">
          <p class="copyright">© 2025 YouProject. Все права защищены.</p>
          <div class="social-links">
            <a target="_blank" href="https://vk.com/temastraik" target="_blank"><img src="SVG/vk.svg" alt="Вконтакте"></a>
            <a target="_blank" href="https://t.me/temastraik" target="_blank"><img src="SVG/telegram.svg" alt="Telegram"></a>
            <a target="_blank" href="mailto:artm.korablv.07@gmail.ru" target="_blank"><img src="SVG/mail.svg" alt="Telegram"></a>
          </div>
        </div>
            <img src="SVG/up-arrow.svg" class="scroll-to-top">
    </footer>
        <script>
        // Плавная прокрутка вверх при клике на стрелку
        document.querySelector('.scroll-to-top').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>