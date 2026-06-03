<?php
// A landing page não precisa de init.php estritamente, mas é bom para checar se o usuário já está logado
session_start();
if (isset($_SESSION['id_usuario'])) {
    header("Location: /app.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>DuckMusic - Descubra milhões de músicas</title>
    <meta name="theme-color" content="#0a0a0a">
    <meta name="description" content="Sua música, seu jeito. A plataforma definitiva para quem vive e respira áudio de alta qualidade.">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/landing.css">
</head>
<body>

    <nav class="navbar">
        <a href="/" class="logo">
            <i class="fas fa-compact-disc"></i>
            <span>DuckMusic</span>
        </a>
        <div class="auth-group">
            <a href="/auth/login.php" class="btn btn-login">Entrar</a>
            <a href="/auth/registro.php" class="btn btn-register">Criar Conta</a>
            <button onclick="doar()" class="btn btn-donate"><i class="fas fa-heart"></i> Apoiar</button>
        </div>
    </nav>

    <header class="hero">
        <div class="container hero-content">
            <h1 id="typing-text"></h1>
            <p>Sua música, seu jeito. A plataforma definitiva para quem vive e respira áudio de alta qualidade com um design que inspira.</p>
            <div class="hero-btns">
                <a href="/auth/registro.php" class="btn btn-register">Começar Gratuitamente</a>
                <a href="/auth/login.php" class="btn btn-login">Explorar Músicas</a>
            </div>
        </div>
    </header>

    <section class="features">
        <div class="container">
            <h2 class="section-title">Por que o <span>Duck?</span></h2>
            <div class="features-grid">
                <article class="feature-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Streaming Veloz</h3>
                    <p>Nossa infraestrutura garante carregamento instantâneo mesmo em conexões instáveis, sem engasgos.</p>
                </article>
                <article class="feature-card">
                    <i class="fas fa-compact-disc"></i>
                    <h3>Alta Fidelidade</h3>
                    <p>Ouça cada detalhe da sua música favorita com nosso codec de áudio sem perdas (Lossless).</p>
                </article>
                <article class="feature-card">
                    <i class="fas fa-magic"></i>
                    <h3>IA Discovery</h3>
                    <p>Sugestões baseadas no seu humor e rotina, analisadas de forma privada por nossa IA.</p>
                </article>
            </div>
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
                    <p>"A interface é mais limpa que a dos concorrentes e o modo escuro é perfeito para usar à noite. Simplesmente funciona."</p>
                </div>
                <div class="testi-card">
                    <div class="user-info">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User">
                        <div>
                            <strong>Ana Julia</strong>
                            <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                        </div>
                    </div>
                    <p>"As playlists geradas automaticamente são assustadoramente precisas. Encontrei várias bandas novas que eu amo!"</p>
                </div>
                <div class="testi-card">
                    <div class="user-info">
                        <img src="https://randomuser.me/api/portraits/men/85.jpg" alt="User">
                        <div>
                            <strong>Carlos Moura</strong>
                            <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        </div>
                    </div>
                    <p>"Eu precisava de um player que fosse leve no navegador. O DuckMusic bate todos de longe em performance."</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="social-links">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-discord"></i></a>
                <a href="#"><i class="fab fa-github"></i></a>
            </div>
            <p>&copy; <?= date('Y') ?> DuckMusic. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Typing effect for Hero
        const textElement = document.getElementById('typing-text');
        const textToType = "A Música Transforma.";
        let charIndex = 0;

        function type() {
            if (charIndex < textToType.length) {
                textElement.textContent += textToType.charAt(charIndex);
                charIndex++;
                setTimeout(type, 100);
            }
        }

        // Scroll animations for cards
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
            
            // Setup cards for scroll animation
            document.querySelectorAll('.feature-card, .testi-card').forEach((el, index) => {
                el.style.opacity = "0";
                el.style.transform = "translateY(40px)";
                el.style.transition = `all 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.1}s`;
                scrollObserver.observe(el);
            });
            
            // Navbar blur effect on scroll
            window.addEventListener('scroll', () => {
                const nav = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    nav.style.background = 'rgba(10, 10, 10, 0.9)';
                    nav.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.5)';
                } else {
                    nav.style.background = 'rgba(10, 10, 10, 0.7)';
                    nav.style.boxShadow = 'none';
                }
            });
        });

        function doar() {
            alert("O DuckMusic é mantido pela comunidade. Em breve abriremos nosso sistema de apoio via PIX!");
        }
    </script>
</body>
</html>
