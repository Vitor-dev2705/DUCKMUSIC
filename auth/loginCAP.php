<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

$recaptcha_site_key = $_ENV['RECAPTCHA_SITE_KEY'] ?? '';
$recaptcha_secret_key = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';

if (!$recaptcha_site_key || !$recaptcha_secret_key) {
    die("Configuração do reCAPTCHA não foi encontrada.");
}

$limite_tentativas = 5;
$tempo_bloqueio_segundos = 15 * 60;
$erro = '';
$agora = time();

if (!isset($_SESSION['tentativas_login'])) $_SESSION['tentativas_login'] = 0;
if (!isset($_SESSION['tempo_bloqueio'])) $_SESSION['tempo_bloqueio'] = 0;

if ($agora < $_SESSION['tempo_bloqueio']) {
    $tempo_restante = $_SESSION['tempo_bloqueio'] - $agora;
    $minutos = floor($tempo_restante / 60);
    $segundos = $tempo_restante % 60;
    $erro = "Muitas tentativas. Tente novamente em {$minutos}m {$segundos}s.";
    $bloqueado = true;
} else {
    $bloqueado = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
            $erro = "Falha na validação. Tente novamente.";
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
            $recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);
            $recaptcha_json = json_decode($recaptcha_result);

            if (!$recaptcha_json->success) {
                $erro = "Falha na verificação do reCAPTCHA. Tente novamente.";
            } else {
                $login = trim($_POST['login'] ?? '');
                $senha = trim($_POST['senha'] ?? '');

                if (!empty($login) && !empty($senha)) {
                    $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);
                    $campo = $is_email ? 'email' : 'nome_usuario';

                    $sql = "SELECT id, nome_usuario, senha, nivel_admin FROM usuarios WHERE $campo = ? LIMIT 1";
                    $usuario = buscarUm($sql, [$login]);

                    if ($usuario && password_verify($senha, $usuario['senha'])) {
                        session_regenerate_id(true);
                        $_SESSION['id_usuario'] = $usuario['id'];
                        $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                        $_SESSION['nivel_admin'] = $usuario['nivel_admin'];

                        unset($_SESSION['tentativas_login']);
                        unset($_SESSION['tempo_bloqueio']);

                        header("Location: /app.php");
                        exit();
                    } else {
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
    }
}

$csrf_token = gerarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login com Segurança - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="auth.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .g-recaptcha {
            display: flex;
            justify-content: center;
            margin: 15px 0;
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
            <div class="notification success"><?= htmlsafe($_SESSION['mensagem']) ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="notification error"><?= htmlsafe($erro) ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm" <?= $bloqueado ? 'style="opacity:0.6;"' : '' ?>>
            <input type="hidden" name="csrf_token" value="<?= htmlsafe($csrf_token) ?>">

            <div class="form-group">
                <input type="text"
                    class="form-control"
                    name="login"
                    placeholder="E-mail ou nome de usuário"
                    required
                    <?= $bloqueado ? 'disabled' : '' ?>
                    value="<?= htmlsafe($_POST['login'] ?? '') ?>">
            </div>

            <div class="form-group">
                <input type="password"
                    class="form-control"
                    id="senha"
                    name="senha"
                    placeholder="Senha"
                    required
                    <?= $bloqueado ? 'disabled' : '' ?>>
            </div>

            <div class="g-recaptcha" data-sitekey="<?= htmlsafe($recaptcha_site_key) ?>" style="<?= $bloqueado ? 'pointer-events: none; opacity: 0.6;' : '' ?>"></div>

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
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            if (!this.checkValidity()) return;

            btn.innerHTML = 'Validando... <span class="spinner"></span>';
            btn.style.pointerEvents = 'none';
        });
    </script>
</body>
</html>
