<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

$erro = '';

if (!isset($_SESSION['redefinir_senha_usuario'])) {
    $_SESSION['mensagem'] = "Acesso não autorizado.";
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['redefinir_senha_usuario'];
$usuario = buscarUm("SELECT email FROM usuarios WHERE id = ?", [$id_usuario]);

if (!$usuario) {
    $_SESSION['mensagem'] = "Usuário não encontrado.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = "Falha na validação. Tente novamente.";
    } else {
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        if (strlen($nova_senha) < 8) {
            $erro = "A senha deve ter pelo menos 8 caracteres.";
        } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $nova_senha)) {
            $erro = "A senha precisa conter pelo menos 1 letra maiúscula, 1 número e 1 caractere especial.";
        } elseif ($nova_senha !== $confirmar_senha) {
            $erro = "As senhas não coincidem.";
        } else {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            if ($stmt->execute([$senha_hash, $id_usuario])) {
                unset($_SESSION['redefinir_senha_usuario']);
                $_SESSION['mensagem'] = "Senha redefinida com sucesso!";
                header("Location: login.php");
                exit();
            } else {
                $erro = "Erro ao redefinir senha. Tente novamente.";
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
    <title>Redefinir Senha - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth.css">
    <style>
        .info-box {
            background: var(--lighter-dark);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            color: var(--light-text);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-key"></i>
            <h1>Redefinir Senha</h1>
        </div>

        <div class="info-box">
            Redefinindo senha para: <strong><?= htmlsafe($usuario['email']) ?></strong>
        </div>

        <?php if ($erro): ?>
            <div class="notification error"><?= htmlsafe($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlsafe($csrf_token) ?>">

            <div class="form-group">
                <input type="password" class="form-control" name="nova_senha" placeholder="Nova senha" required>
            </div>

            <div class="form-group">
                <input type="password" class="form-control" name="confirmar_senha" placeholder="Confirme a nova senha" required>
            </div>

            <button type="submit" class="btn">Redefinir Senha</button>
        </form>

        <div class="auth-links">
            <a href="login.php">Voltar para o login</a>
        </div>
    </div>
</body>
</html>
