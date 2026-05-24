<?php
require_once __DIR__ . '/../includes/init.php';

$erro = '';

// Verifica se o usuário está autorizado (veio da página de recuperação)
if (!isset($_SESSION['redefinir_senha_usuario'])) {
    $_SESSION['mensagem'] = "Acesso não autorizado.";
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['redefinir_senha_usuario'];

// Busca email do usuário para mostrar na tela
$usuario = buscarUm("SELECT email FROM usuarios WHERE id = ?", [$id_usuario]);

if (!$usuario) {
    $_SESSION['mensagem'] = "Usuário não encontrado.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (strlen($nova_senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        // Atualizar senha
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        if ($stmt->execute([$senha_hash, $id_usuario])) {
            // Limpa a sessão e redireciona
            unset($_SESSION['redefinir_senha_usuario']);
            $_SESSION['mensagem'] = "Senha redefinida com sucesso!";
            header("Location: login.php");
            exit();
        } else {
            $erro = "Erro ao redefinir senha. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<style>
        /* Mesmo estilo do login.php */
        :root {
            --primary: #1DB954;
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
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo i {
            font-size: 50px;
            color: var(--primary);
        }
        
        .logo h1 {
            margin-top: 10px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--light-text);
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
        }
        
        .info-box {
            background: var(--lighter-dark);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
<body>
    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-compact-disc"></i>
            <h1>Redefinir Senha</h1>
        </div>
        
        <div class="info-box">
            Redefinindo senha para: <strong><?= htmlspecialchars($usuario['email']) ?></strong>
        </div>
        
        <?php if ($erro): ?>
            <div class="notification error"><?= $erro ?></div>
        <?php endif; ?>
        
        <form method="POST">
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

























<style>
        /* Mesmo estilo do login.php */
        :root {
            --primary: #1DB954;
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
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo i {
            font-size: 50px;
            color: var(--primary);
        }
        
        .logo h1 {
            margin-top: 10px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--light-text);
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
        }
        
        .info-box {
            background: var(--lighter-dark);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>