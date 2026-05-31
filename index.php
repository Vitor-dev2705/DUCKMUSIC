<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>DuckMusic - Descubra milhões de músicas</title>
    <meta name="theme-color" content="#0b0b0b">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/landing.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo"><span>🦆</span> DuckMusic</div>
        <div class="auth-group">
            <a href="/auth/login.php" class="btn btn-login">Entrar</a>
            <a href="/auth/registro.php" class="btn btn-register">Registrar</a>
            <button onclick="doar()" class="btn btn-donate"><i class="fas fa-heart"></i> Apoiar</button>
        </div>
    </nav>

    <header class="hero">
        <div class="container hero-content">
            <h1 id="typing-text"></h1>
            <p>Sua música, seu jeito. A plataforma definitiva para quem vive e respira áudio de alta qualidade.</p>
            <div class="hero-btns">
                <a href="/auth/registro.php" class="btn btn-register">Começar Gratuitamente</a>
                <a href="/auth/login.php" class="btn btn-login" style="border: 1px solid white;">Explorar Músicas</a>
            </div>
        </div>
    </header>

    <section class="features container">
        <h2 class="section-title">Por que o <span>Duck?</span></h2>
        <div class="features-grid">
            <article class="feature-card">
                <i class="fas fa-bolt"></i>
                <h3>Streaming Veloz</h3>
                <p>Carregamento instantâneo mesmo em conexões instáveis.</p>
            </article>
            <article class="feature-card">
                <i class="fas fa-compact-disc"></i>
                <h3>Alta Fidelidade</h3>
                <p>Ouça cada detalhe com nosso codec de áudio sem perdas.</p>
            </article>
            <article class="feature-card">
                <i class="fas fa-magic"></i>
                <h3>IA Discovery</h3>
                <p>Sugestões baseadas no seu humor, analisadas por nossa IA.</p>
            </article>
        </div>
    </section>

    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">Comunidade <span>Duck</span></h2>
            <div class="testimonial-track">
                <div class="testi-card">
                    <div class="user-info">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User">
                        <div>
                            <strong>Ricardo Silva</strong>
                            <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        </div>
                    </div>
                    <p>"A interface é mais limpa que a dos concorrentes. Simplesmente funciona."</p>
                </div>
                <div class="testi-card">
                    <div class="user-info">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User">
                        <div>
                            <strong>Ana Julia</strong>
                            <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                        </div>
                    </div>
                    <p>"As playlists de 'Mood' são assustadoramente precisas. Amo!"</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="social-links">
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-discord"></i></a>
        </div>
        <p>&copy; 2025 DuckMusic.</p>
    </footer>

    <script>
        const textElement = document.getElementById('typing-text');
        const textToType = "Duck Music";
        let charIndex = 0;

        function type() {
            if (charIndex < textToType.length) {
                textElement.textContent += textToType.charAt(charIndex);
                charIndex++;
                setTimeout(type, 150);
            }
        }

        const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = "1";
                    entry.target.style.transform = "translateY(0)";
                }
            });
        }, { threshold: 0.1 });

        document.addEventListener('DOMContentLoaded', () => {
            type();
            document.querySelectorAll('.feature-card, .testi-card').forEach(el => {
                el.style.opacity = "0";
                el.style.transform = "translateY(30px)";
                el.style.transition = "all 0.6s cubic-bezier(0.4, 0, 0.2, 1)";
                scrollObserver.observe(el);
            });
        });

        function doar() {
            alert("O DuckMusic é mantido pela comunidade. Em breve abriremos nosso sistema de apoio via PIX!");
        }
    </script>
</body>
</html>
