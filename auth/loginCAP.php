<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

$recaptcha_site_key = "6LeSVQwrAAAAADtbNuI7KKCwl34FCpTc4t5bALD6";
$recaptcha_secret_key = "6LeSVQwrAAAAADc70E14pIhl33ceH5frCSN6SFMg";

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = "Falha na validacao. Tente novamente.";
    } elseif (empty($_POST['g-recaptcha-response'])) {
        $erro = "Por favor, complete o reCAPTCHA.";
    } else {
        $recaptcha_response = $_POST['g-recaptcha-response'];
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_data = [
            'secret' => $recaptcha_secret_key,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        $recaptcha_options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($recaptcha_data)
            ]
        ];

        $recaptcha_context = stream_context_create($recaptcha_options);
        $recaptcha_result = @file_get_contents($recaptcha_url, false, $recaptcha_context);
        $recaptcha_json = json_decode($recaptcha_result);

        if (!$recaptcha_json || !$recaptcha_json->success) {
            $erro = "Falha na verificacao do reCAPTCHA. Tente novamente.";
        } else {
            $login = trim($_POST['login'] ?? '');
            $senha = $_POST['senha'] ?? '';

            $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'nome_usuario';

            $usuario = buscarUm("SELECT id, nome_usuario, senha, nivel_admin FROM usuarios WHERE $campo = ? LIMIT 1", [$login]);

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $usuario['id'];
                $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                $_SESSION['nivel_admin'] = $usuario['nivel_admin'];
                unset($_SESSION['csrf_token']);

                header("Location: /app.php");
                exit();
            } else {
                $erro = "Credenciais invalidas.";
            }
        }
    }
}

$csrf_token = gerarTokenCSRF();
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .g-recaptcha { display: flex; justify-content: center; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-compact-disc"></i>
            <h1>DuckMusic</h1>
        </div>

        <?php if ($erro): ?>
            <div class="notification error"><?= htmlsafe($erro) ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= htmlsafe($csrf_token) ?>">

            <div class="form-group">
                <input type="text" class="form-control" name="login"
                    placeholder="E-mail ou nome de usuario" required
                    value="<?= isset($_POST['login']) ? htmlsafe($_POST['login']) : '' ?>">
            </div>

            <div class="form-group">
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required>
                <i class="fas fa-eye toggle-password" id="toggleIcon" onclick="togglePassword()"></i>
            </div>

            <div class="g-recaptcha" data-sitekey="<?= htmlsafe($recaptcha_site_key) ?>"></div>

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
