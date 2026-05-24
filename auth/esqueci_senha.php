<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = "Falha na validação. Tente novamente.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $data_nascimento = $_POST['data_nascimento'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "E-mail inválido.";
        } elseif (!validarDataNascimento($data_nascimento)) {
            $erro = "Data de nascimento inválida.";
        } else {
            $usuario = buscarUm("SELECT id FROM usuarios WHERE email = ? AND data_nascimento = ?",
                               [$email, $data_nascimento]);

            if ($usuario) {
                $_SESSION['redefinir_senha_usuario'] = $usuario['id'];
                header("Location: redefinir_senha.php");
                exit();
            } else {
                $erro = "Dados não encontrados. Verifique seu e-mail e data de nascimento.";
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
    <title>Recuperar Senha - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-lock"></i>
            <h1>Recuperar Senha</h1>
        </div>

        <?php if ($erro): ?>
            <div class="notification error"><?= htmlsafe($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlsafe($csrf_token) ?>">

            <div class="form-group">
                <input type="email" class="form-control" name="email" placeholder="Seu e-mail" required>
            </div>

            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento</label>
                <input type="date" class="form-control" name="data_nascimento" required>
            </div>

            <button type="submit" class="btn">Validar</button>
        </form>

        <div class="auth-links">
            <a href="login.php">Voltar para o login</a>
        </div>
    </div>
</body>
</html>