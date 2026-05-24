<?php
require_once __DIR__ . '/../includes/init.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $data_nascimento = $_POST['data_nascimento'];

    $usuario = buscarUm("SELECT id FROM usuarios WHERE email = ? AND data_nascimento = ?", 
                       [$email, $data_nascimento]);

    if ($usuario) {
        // Armazena o ID do usuário na sessão para validação na próxima página
        $_SESSION['redefinir_senha_usuario'] = $usuario['id'];
        header("Location: redefinir_senha.php");
        exit();
    } else {
        $erro = "Dados não encontrados. Verifique seu e-mail e data de nascimento.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Mesmo estilo do login.php */
        :root {
            --primary:rgb(204, 255, 0);
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
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-compact-disc"></i>
            <h1>Recuperar Senha</h1>
        </div>
        
        <?php if ($erro): ?>
            <div class="notification error"><?= $erro ?></div>
        <?php endif; ?>
        
        <form method="POST">
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