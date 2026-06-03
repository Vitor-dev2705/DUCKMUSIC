<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/validation.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF token
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = "Token CSRF inválido.";
    } else {
        // Dados do formulário
        $login = strtolower(trim($_POST['login'] ?? ''));
        $senha = trim($_POST['senha'] ?? '');

        // Controle de tentativas de login
        verificarTentativasLogin($login);

        if (!empty($login) && !empty($senha)) {
            $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);
            $campo_sql = $is_email ? 'LOWER(email)' : 'LOWER(nome_usuario)';

            $usuario = buscarUm(
                "SELECT id, nome_usuario, email, senha, nivel_admin, email_verificado FROM usuarios WHERE $campo_sql = ? LIMIT 1",
                [$login]
            );

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Verifica se o e‑mail já foi confirmado
                if (!$usuario['email_verificado']) {
                    $_SESSION['verificar_email'] = $usuario['email'];
                    session_write_close();
                    header('Location: verificar_email.php');
                    exit();
                }

                // Login bem‑sucedido
                session_regenerate_id(true);
                limparTentativas($login);
                $_SESSION['id_usuario'] = $usuario['id'];
                $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                $_SESSION['nivel_admin'] = $usuario['nivel_admin'];
                session_write_close();
                header('Location: /app.php');
                exit();
            } else {
                $erro = "Usuário ou senha incorretos.";
            }
        } else {
            $erro = "Preencha todos os campos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-compact-disc"></i>
            <h1>DuckMusic</h1>
        </div>
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="notification success"><?= htmlsafe($_SESSION['mensagem']) ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="notification error" role="alert" aria-live="polite"><?= htmlsafe($erro) ?></div>
        <?php endif; ?>
        <form method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCSRF() ?>" />
            <div class="form-group">
                <input type="text" class="form-control" name="login" placeholder="E-mail ou nome de usuário" required value="<?= isset($_POST['login']) ? htmlsafe($_POST['login']) : '' ?>">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required>
                <i class="fas fa-eye toggle-password" id="toggleIcon" onclick="togglePassword()"></i>
            </div>
            <button type="submit" class="btn" id="loginBtn">Entrar</button>
        </form>
        <div class="auth-links">
            <a href="registro.php">Criar uma conta</a> |
            <a href="esqueci_senha.php">Esqueci minha senha</a>
        </div>
    </div>
    <script>
        function togglePassword() {
            var s = document.getElementById('senha');
            var i = document.getElementById('toggleIcon');
            if (s.type === 'password') {
                s.type = 'text';
                i.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                s.type = 'password';
                i.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        document.getElementById('loginForm').addEventListener('submit', function() {
            var btn = document.getElementById('loginBtn');
            if (!this.checkValidity()) return;
            btn.innerHTML = 'Validando... <span class="spinner"></span>';
            btn.style.pointerEvents = 'none';
        });
    </script>
</body>
</html>
