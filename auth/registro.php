<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/email_helper.php';

$erro = '';
$dados = [
    'nome_completo'   => '',
    'nome_usuario'    => '',
    'email'           => '',
    'data_nascimento' => '',
    'telefone'        => ''
];

$dataAtual  = new DateTime();
$dataMaxima = $dataAtual->modify('-13 years')->format('Y-m-d');
$dataMinima = (new DateTime())->modify('-150 years')->format('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome_completo'   => trim($_POST['nome_completo'] ?? ''),
        'nome_usuario'    => trim($_POST['nome_usuario'] ?? ''),
        'email'           => strtolower(trim($_POST['email'] ?? '')),
        'data_nascimento' => $_POST['data_nascimento'] ?? '',
        'telefone'        => preg_replace('/\D/', '', $_POST['telefone'] ?? '')
    ];

    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validacoes
    if (empty($dados['nome_completo'])) {
        $erro = "Nome completo e obrigatorio.";
    } elseif (strlen($dados['nome_usuario']) < 4) {
        $erro = "Nome de usuario deve ter pelo menos 4 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $dados['nome_usuario'])) {
        $erro = "Nome de usuario pode conter apenas letras, numeros e underscore.";
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail invalido.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $senha)) {
        $erro = "A senha precisa conter 1 maiuscula, 1 numero e 1 caractere especial.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas nao coincidem.";
    } elseif (!preg_match('/^\d{10,11}$/', $dados['telefone'])) {
        $erro = "Telefone invalido. Use apenas numeros (DDD + numero).";
    } elseif (!validarDataNascimento($dados['data_nascimento'])) {
        $erro = "Data de nascimento invalida. Idade minima: 13 anos.";
    }

    if (empty($erro)) {
        $userExiste = buscarUm("SELECT id FROM usuarios WHERE LOWER(nome_usuario) = LOWER(?)", [$dados['nome_usuario']]);
        $emailExiste = buscarUm("SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?)", [$dados['email']]);

        if ($userExiste && $emailExiste) {
            $erro = "Nome de usuario e e-mail ja estao em uso.";
        } elseif ($userExiste) {
            $erro = "Nome de usuario ja esta em uso. Escolha outro.";
        } elseif ($emailExiste) {
            $erro = "E-mail ja esta cadastrado. Use outro ou faca login.";
        }

        if (empty($erro)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $codigo = gerarCodigoVerificacao();
            $expira = date('Y-m-d H:i:s', time() + 900); // 15 min

            try {
                $novoId = inserir("INSERT INTO usuarios (nome_completo, nome_usuario, email, senha, data_nascimento, telefone, email_verificado, codigo_verificacao, codigo_expira_em)
                         VALUES (?, ?, ?, ?, ?, ?, false, ?, ?)", [
                    $dados['nome_completo'],
                    $dados['nome_usuario'],
                    $dados['email'],
                    $senha_hash,
                    $dados['data_nascimento'],
                    $dados['telefone'],
                    $codigo,
                    $expira
                ]);

                // Envia email com codigo
                enviarCodigoVerificacao($dados['email'], $codigo, $dados['nome_completo']);

                $_SESSION['verificar_email'] = $dados['email'];
                session_write_close();
                header("Location: verificar_email.php");
                exit();
            } catch (Exception $e) {
                $erro = "Erro ao criar conta. Tente novamente.";
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
    <title>Registro - DuckMusic</title>
    <meta name="theme-color" content="#121212">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth.css">
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

        <form method="POST" id="registroForm">
            <div class="form-group">
                <input type="text" class="form-control" name="nome_completo"
                    placeholder="Nome completo" required
                    value="<?= htmlsafe($dados['nome_completo']) ?>">
            </div>

            <div class="form-group">
                <input type="text" class="form-control" name="nome_usuario"
                    placeholder="Nome de usuario" required autocapitalize="none"
                    value="<?= htmlsafe($dados['nome_usuario']) ?>">
            </div>

            <div class="form-group">
                <input type="email" class="form-control" name="email"
                    placeholder="E-mail" required autocapitalize="none"
                    value="<?= htmlsafe($dados['email']) ?>">
            </div>

            <div class="form-group">
                <input type="password" class="form-control" id="senha" name="senha"
                    placeholder="Senha" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('senha', this)"></i>
            </div>

            <div class="form-group">
                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha"
                    placeholder="Confirmar senha" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('confirmar_senha', this)"></i>
            </div>

            <div class="form-group">
                <label for="data_nascimento">Data de nascimento</label>
                <input type="date" class="form-control" name="data_nascimento"
                    min="<?= $dataMinima ?>" max="<?= $dataMaxima ?>" required
                    value="<?= htmlsafe($dados['data_nascimento']) ?>">
            </div>

            <div class="form-group">
                <input type="tel" class="form-control" name="telefone"
                    placeholder="Telefone (com DDD)" required
                    value="<?= htmlsafe($dados['telefone']) ?>">
            </div>

            <button type="submit" class="btn" id="registroBtn">Registrar</button>
        </form>

        <div class="auth-links">
            <a href="login.php">Ja tem uma conta? Entrar</a>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, icon) {
            var s = document.getElementById(fieldId);
            if (s.type === 'password') {
                s.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                s.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.getElementById('registroForm').addEventListener('submit', function() {
            var btn = document.getElementById('registroBtn');
            if (!this.checkValidity()) return;
            btn.innerHTML = 'Criando conta... <span class="spinner"></span>';
            btn.style.pointerEvents = 'none';
        });
    </script>
</body>
</html>
