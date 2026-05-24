<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuckMusic - Descubra milhões de músicas</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #a7fa00;
            --primary-hover: #8ed400;
            --bg-dark: #0b0b0b;
            --bg-card: #181818;
            --bg-card-hover: #282828;
            --text-main: #ffffff;
            --text-muted: #b3b3b3;
            --accent-red: #ff6b6b;
            --border-radius: 12px;
            --nav-height: 80px;
        }

        /* Reset e Base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        /* Utilidades */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 5%; }
        .btn {
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
            cursor: pointer;
            border: none;
        }

        /* Navbar */
        .navbar {
            height: var(--nav-height);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 5%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .auth-group { display: flex; gap: 1rem; align-items: center; }

        .btn-login { color: var(--text-main); border: 1px solid rgba(255,255,255,0.3); }
        .btn-login:hover { border-color: var(--primary); color: var(--primary); }

        .btn-register { background: var(--primary); color: var(--bg-dark); }
        .btn-register:hover { transform: scale(1.05); background: var(--primary-hover); }

        .btn-donate { background: var(--accent-red); color: white; font-size: 0.9rem; }

        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: linear-gradient(rgba(0,0,0,0.6), var(--bg-dark)), 
                        url('https://images.unsplash.com/photo-1493225255756-d9584f8606e9?auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
        }

        .hero-content h1 {
            font-size: clamp(2.5rem, 8vw, 5rem);
            line-height: 1.1;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: -2px;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 2.5rem;
        }

        .hero-btns { display: flex; gap: 1rem; justify-content: center; }

        /* Grid de Recursos */
        .features { padding: 100px 0; background: var(--bg-dark); }
        .section-title { text-align: center; font-size: 2.5rem; margin-bottom: 3rem; }
        .section-title span { color: var(--primary); }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--bg-card);
            padding: 3rem 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .feature-card:hover {
            background: var(--bg-card-hover);
            transform: translateY(-10px);
            border-color: rgba(167, 250, 0, 0.2);
        }

        .feature-card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: block;
        }

        /* Depoimentos */
        .testimonials { padding: 100px 0; background: #0f0f0f; }
        .testimonial-track {
            display: flex;  
            gap: 2rem;
            overflow-x: auto;
            padding: 20px 0;
            scrollbar-width: none; /* Firefox */
        }
        .testimonial-track::-webkit-scrollbar { display: none; }

        .testi-card {
            min-width: 320px;
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--border-radius);
            position: relative;
        }

        .user-info { display: flex; align-items: center; gap: 15px; margin-bottom: 1rem; }
        .user-info img { width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--primary); }
        .stars { color: #f1c40f; font-size: 0.8rem; margin-top: 5px; }

        /* Footer */
        .footer {
            padding: 4rem 0;
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
        }

        .social-links { margin-bottom: 2rem; display: flex; justify-content: center; gap: 2rem; }
        .social-links a { color: var(--text-muted); font-size: 1.5rem; transition: 0.3s; }
        .social-links a:hover { color: var(--primary); transform: translateY(-3px); }

        /* Responsividade */
        @media (max-width: 768px) {
            .auth-group { display: none; } /* Idealmente um menu hamburguer aqui */
            .hero-btns { flex-direction: column; }
            .hero-content h1 { font-size: 3rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo"><span>🦆</span> DuckMusic</div>
        <div class="auth-group">
            <a href="/auth/login.php" class="btn btn-login">Entrar</a>
            <a href="/auth/register.php" class="btn btn-register">Registrar</a>
            <button onclick="doar()" class="btn btn-donate"><i class="fas fa-heart"></i> Apoiar</button>
        </div>
    </nav>

    <header class="hero">
        <div class="container hero-content">
            <h1 id="typing-text"></h1>
            <p>Sua música, seu jeito. A plataforma definitiva para quem vive e respira áudio de alta qualidade.</p>
            <div class="hero-btns">
                <a href="/auth/registro.php" class="btn btn-register">Começar Gratuitamente</a>
                <a href="/uploads/musicas/" class="btn btn-login" style="border: 1px solid white;">Explorar Músicas</a>
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
        <p>&copy; 2025 DuckMusic. Desenvolvido com <span style="color: var(--primary);">❤</span> para músicos.</p>
    </footer>

    <script>
        // Sistema de Digitação Otimizado
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

        // Observer para Animações ao Scroll
        const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = "1";
                    entry.target.style.transform = "translateY(0)";
                }
            });
        }, { threshold: 0.1 });

        // Inicialização
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
            alert("🎸 O DuckMusic é mantido pela comunidade. Em breve abriremos nosso sistema de apoio via PIX!");
        }
    </script>
</body>
</html>