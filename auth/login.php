<?php
require_once __DIR__ . '/../includes/init.php';

// Configurações
$limite_tentativas = 5;
$tempo_bloqueio_segundos = 15 * 60;
$erro = '';
$agora = time();

// Inicializa variáveis de controle
if (!isset($_SESSION['tentativas_login'])) $_SESSION['tentativas_login'] = 0;
if (!isset($_SESSION['tempo_bloqueio'])) $_SESSION['tempo_bloqueio'] = 0;

// Verifica se o usuário ainda está no período de bloqueio
if ($agora < $_SESSION['tempo_bloqueio']) {
    $tempo_restante = $_SESSION['tempo_bloqueio'] - $agora;
    $minutos = floor($tempo_restante / 60);
    $segundos = $tempo_restante % 60;
    $erro = "Muitas tentativas. Tente novamente em {$minutos}m {$segundos}s.";
    $bloqueado = true;
} else {
    $bloqueado = false;

    // Processa o formulário apenas se for POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = trim($_POST['login'] ?? '');
        $senha = trim($_POST['senha'] ?? '');

        if (!empty($login) && !empty($senha)) {
            // Determina se é email ou username de forma segura
            $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);
            $campo = $is_email ? 'email' : 'nome_usuario';

            // Query preparada (Certifique-se que sua função buscarUm usa PDO ou MySQLi prepare)
            $sql = "SELECT id, nome_usuario, senha, nivel_admin FROM usuarios WHERE $campo = ? LIMIT 1";
            $usuario = buscarUm($sql, [$login]);

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Login com sucesso
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $usuario['id'];
                $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                $_SESSION['nivel_admin'] = $usuario['nivel_admin'];

                // Reset de segurança
                unset($_SESSION['tentativas_login']);
                unset($_SESSION['tempo_bloqueio']);

                header("Location: /app.php");
                exit();
            } else {
                // Falha no login
                $_SESSION['tentativas_login']++;

                if ($_SESSION['tentativas_login'] >= $limite_tentativas) {
                    $_SESSION['tempo_bloqueio'] = time() + $tempo_bloqueio_segundos;
                    $erro = "Muitas tentativas inválidas. Bloqueado por 15 minutos.";
                    $bloqueado = true;
                } else {
                    $restantes = $limite_tentativas - $_SESSION['tentativas_login'];
                    $erro = "Credenciais inválidas. Restam {$restantes} tentativa(s).";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Login - DuckMusic</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: rgb(229, 255, 0);
            --dark: #121212;
            --light-dark: #181818;
            --lighter-dark: #282828;
            --light-text: #b3b3b3;
            --white: #ffffff;
            --transition: all 0.3s ease;
        }

        body {
            background: var(--dark);
            color: var(--white);
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .auth-container {
            background: var(--light-dark);
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: var(--transition);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            width: 60px;
            height: 60px;
        }

        .logo h1 {
            margin-top: 10px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: var(--lighter-dark);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            color: var(--white);
            font-size: 14px;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--light-text);
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: black;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn:hover {
            background: #1ed760;
            transform: translateY(-2px);
        }

        .auth-links {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }

        .auth-links a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .notification {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .error {
            background: rgba(255, 51, 51, 0.1);
            border: 1px solid #ff3333;
            color: #ff6b6b;
            animation: shake 0.4s;
        }

        .success {
            background: rgba(29, 185, 84, 0.1);
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        @keyframes shake {
            0% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            50% {
                transform: translateX(5px);
            }

            75% {
                transform: translateX(-5px);
            }

            100% {
                transform: translateX(0);
            }
        }

        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(0, 0, 0, 0.3);
            border-radius: 50%;
            border-top-color: black;
            animation: spin 1s linear infinite;
            vertical-align: middle;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="logo">
            <img src="../assets/logo.png" alt="DuckMusic Logo">
            <h1>DuckMusic</h1>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="notification success"><?= htmlspecialchars($_SESSION['mensagem']) ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="notification error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm" <?= $bloqueado ? 'style="opacity:0.6; cursor:not-allowed;"' : '' ?>>
            <div class="form-group">
                <input type="text"
                    class="form-control"
                    name="login"
                    placeholder="E-mail ou nome de usuário"
                    required
                    <?= $bloqueado ? 'disabled' : '' ?>
                    value="<?= isset($_POST['login']) ? htmlspecialchars($_POST['login']) : '' ?>">
            </div>

            <div class="form-group">
                <input type="password"
                    class="form-control"
                    id="senha"
                    name="senha"
                    placeholder="Senha"
                    required
                    <?= $bloqueado ? 'disabled' : '' ?>>

                <i class="fas fa-eye toggle-password"
                    id="toggleIcon"
                    onclick="togglePassword()"
                    style="<?= $bloqueado ? 'display:none;' : '' ?>"></i>
            </div>

            <button type="submit" class="btn" id="loginBtn" <?= $bloqueado ? 'disabled' : '' ?>>
                <?= $bloqueado ? 'Bloqueado' : 'Entrar' ?>
            </button>
        </form>

        <div class="auth-links">
            <a href="registro.php">Criar uma conta</a> |
            <a href="esqueci_senha.php">Esqueci minha senha</a>
        </div>
    </div>

    <script>
        // Toggle Password melhorado
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const icon = document.getElementById('toggleIcon');

            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Feedback visual ao enviar
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            if (!this.checkValidity()) return;

            btn.innerHTML = 'Validando... <span class="spinner"></span>';
            btn.style.pointerEvents = 'none';
        });
    </script>
</body>

</html>