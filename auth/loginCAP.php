<?php
require_once __DIR__ . '/../includes/init.php';

$recaptcha_site_key = "6LeSVQwrAAAAADtbNuI7KKCwl34FCpTc4t5bALD6"; 
$recaptcha_secret_key = "6LeSVQwrAAAAADc70E14pIhl33ceH5frCSN6SFMg"; 

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['g-recaptcha-response'])) {
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
            $login = trim($_POST['login']);
            $senha = $_POST['senha'];

            $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'nome_usuario';

            $stmt = $db->prepare("SELECT id, nome_usuario, senha FROM usuarios WHERE $campo = ?");
            $stmt->execute([$login]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['id_usuario'] = $usuario['id'];
                $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
                header("Location: ../paginas/dashboard.php");
                exit();
            } else {
                $erro = "Credenciais inválidas.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DuckMusic</title>
    <link rel="shortcut icon" href="../" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a3a 100%);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-box h2 {
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            background: #4ecdc4;
            color: black;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
        }

        .notification {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .notification.error {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid #ff6b6b;
        }

        .links {
            margin-top: 1rem;
        }

        .links a {
            color: #4ecdc4;
        }

        /* Estilo para o reCAPTCHA */
        .g-recaptcha {
            display: flex;
            justify-content: center;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        
        <?php if ($erro): ?>
            <div class="notification error"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="login" placeholder="E-mail ou nome de usuário" required>
            </div>
            <div class="form-group">
                <input type="password" name="senha" placeholder="Senha" required>
            </div>
            
            <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
            
            <button type="submit" class="btn">Entrar</button>
        </form>

        <div class="links">
            <a href="registro.php">Criar uma conta</a> | 
            <a href="esqueci_senha.php">Esqueci minha senha</a>
        </div>
    </div>
</body>
</html>