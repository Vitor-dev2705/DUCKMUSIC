<?php
require_once __DIR__ . '/../includes/init.php';

$erro = '';
$dados = [
    'nome_completo'   => '',
    'nome_usuario'    => '',
    'email'           => '',
    'data_nascimento' => '',
    'telefone'        => ''
];

// Limites de data (13-150 anos)
$dataAtual  = new DateTime();
$dataMaxima = $dataAtual->modify('-13 years')->format('Y-m-d');
$dataMinima = (new DateTime())->modify('-150 years')->format('Y-m-d');

// Limite de tentativas por IP
$ip = $_SERVER['REMOTE_ADDR'];
$dataLimite = date('Y-m-d H:i:s', strtotime('-15 minutes'));

// Conta tentativas recentes
$stmtTentativas = $db->prepare("SELECT COUNT(*) FROM logs_registro WHERE ip = ? AND data_hora >= ?");
$stmtTentativas->execute([$ip, $dataLimite]);
$tentativas = $stmtTentativas->fetchColumn();

if ($tentativas >= 5) {
    $erro = "Limite de tentativas excedido. Tente novamente em 15 minutos.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erro)) {
    // Sanitiza os dados de entrada
    $dados = [
        'nome_completo'   => filter_input(INPUT_POST, 'nome_completo', FILTER_SANITIZE_SPECIAL_CHARS),
        'nome_usuario'    => filter_input(INPUT_POST, 'nome_usuario', FILTER_SANITIZE_SPECIAL_CHARS),
        'email'           => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'data_nascimento' => filter_input(INPUT_POST, 'data_nascimento'),
        'telefone'        => preg_replace('/\D/', '', $_POST['telefone'])
    ];

    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validações
    if (empty($dados['nome_completo'])) {
        $erro = "Nome completo é obrigatório.";
    } elseif (strlen($dados['nome_usuario']) < 4) {
        $erro = "Nome de usuário deve ter pelo menos 4 caracteres.";
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $senha)) {
        $erro = "A senha precisa conter pelo menos 1 letra maiúscula, 1 número e 1 caractere especial.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } elseif (!preg_match('/^\d{10,11}$/', $dados['telefone'])) {
        $erro = "Telefone inválido. Use apenas números (DDD + número).";
    }

    // Verifica se usuário ou email já existem
    if (empty($erro)) {
        $existente = buscarUm("SELECT id FROM usuarios WHERE nome_usuario = ? OR email = ?", 
                              [$dados['nome_usuario'], $dados['email']]);

        $dataHora  = date('Y-m-d H:i:s');
        $sucesso   = $existente ? 0 : 1;

        // Log da tentativa
        $stmtLog = $db->prepare("INSERT INTO logs_registro (nome_usuario, email, ip, sucesso, data_hora) 
                                 VALUES (?, ?, ?, ?, ?)");
        $stmtLog->execute([$dados['nome_usuario'], $dados['email'], $ip, $sucesso, $dataHora]);

        if (!$existente) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO usuarios (nome_completo, nome_usuario, email, senha, data_nascimento, telefone) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $dados['nome_completo'],
                $dados['nome_usuario'],
                $dados['email'],
                $senha_hash,
                $dados['data_nascimento'],
                $dados['telefone']
            ]);

            $_SESSION['mensagem'] = "Conta criada com sucesso! Faça login.";
            header("Location: login.php");
            exit();
        } else {
            $erro = "Nome de usuário ou e-mail já está em uso.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registro - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:rgb(255, 242, 0);
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
            padding: 20px;
        }
        .auth-container {
            background: var(--light-dark);
            padding: 40px;
            border-radius: 10px;
            max-width: 400px;
            width: 100%;
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
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: var(--lighter-dark);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
            color: var(--white);
        }
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }
        .btn {
            background: var(--primary);
            color: black;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn:hover {
            background: #1ed760;
        }
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
            <h1>DuckMusic</h1>
        </div>

        <?php if ($erro): ?>
            <div class="notification error"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group"><input type="text" class="form-control" name="nome_completo" placeholder="Nome completo" value="<?= htmlspecialchars($dados['nome_completo']) ?>" required></div>
            <div class="form-group"><input type="text" class="form-control" name="nome_usuario" placeholder="Nome de usuário" value="<?= htmlspecialchars($dados['nome_usuario']) ?>" required></div>
            <div class="form-group"><input type="email" class="form-control" name="email" placeholder="E-mail" value="<?= htmlspecialchars($dados['email']) ?>" required></div>
            <div class="form-group"><input type="password" class="form-control" name="senha" placeholder="Senha" required></div>
            <div class="form-group"><input type="password" class="form-control" name="confirmar_senha" placeholder="Confirmar senha" required></div>
            <div class="form-group"><label>Data de nascimento</label><input type="date" class="form-control" name="data_nascimento" value="<?= htmlspecialchars($dados['data_nascimento']) ?>" min="<?= $dataMinima ?>" max="<?= $dataMaxima ?>" required></div>
            <div class="form-group"><input type="tel" class="form-control" name="telefone" placeholder="Telefone (com DDD)" value="<?= htmlspecialchars($dados['telefone']) ?>" required></div>
            <button type="submit" class="btn">Registrar</button>
        </form>
    </div>
</body>
</html>