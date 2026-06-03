<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/email_helper.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/validation.php';

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
    // Verifica CSRF token
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = "Token CSRF inválido.";
    } else {
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
            $erro = "Nome completo é obrigatório.";
        } elseif (strlen($dados['nome_usuario']) < 4) {
            $erro = "Nome de usuário deve ter pelo menos 4 caracteres.";
        } elseif (!preg_match('/^[a-zA-Z0-9_ ]+$/', $dados['nome_usuario'])) {
            $erro = "Nome de usuário pode conter letras, números, underline e espaços.";
        } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erro = "E-mail inválido.";
        } else {
            $valSenha = validarSenha($senha, $dados['nome_usuario']);
            if (!$valSenha['ok']) {
                $erro = $valSenha['msg'];
            } elseif ($senha !== $confirmar_senha) {
                $erro = "As senhas não coincidem.";
            }
        }

        if (empty($erro) && !preg_match('/^\d{10,11}$/', $dados['telefone'])) {
            $erro = "Telefone inválido. Use apenas números (DDD + número).";
        } elseif (empty($erro) && !validarDataNascimento($dados['data_nascimento'])) {
            $erro = "Data de nascimento inválida. Idade mínima: 13 anos.";
        }

        if (empty($erro)) {
            $userExiste = buscarUm("SELECT id FROM usuarios WHERE LOWER(nome_usuario) = LOWER(?)", [$dados['nome_usuario']]);
            $emailExiste = buscarUm("SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?)", [$dados['email']]);

            if ($userExiste && $emailExiste) {
                $erro = "Nome de usuário e e-mail já estão em uso.";
            } elseif ($userExiste) {
                $erro = "Nome de usuário já está em uso. Escolha outro.";
            } elseif ($emailExiste) {
                $erro = "E-mail já está cadastrado. Use outro ou faça login.";
            }

            if (empty($erro)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                try {
                    $novoId = inserir("INSERT INTO usuarios (nome_completo, nome_usuario, email, senha, data_nascimento, telefone, email_verificado)
                             VALUES (?, ?, ?, ?, ?, ?, true)", [
                        $dados['nome_completo'],
                        $dados['nome_usuario'],
                        $dados['email'],
                        $senha_hash,
                        $dados['data_nascimento'],
                        $dados['telefone']
                    ]);

                    $_SESSION['mensagem'] = "Conta criada com sucesso! Faça login.";
                    session_write_close();
                    header("Location: login.php");
                    exit();
                } catch (Exception $e) {
                    $erro = "Erro ao criar conta. Tente novamente.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0a">
    <title>Criar Conta - DuckMusic</title>
    <meta name="description" content="Crie sua conta na DuckMusic e comece a ouvir músicas agora.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-compact-disc"></i>
            <h1>DuckMusic</h1>
            <p>Crie sua conta gratuita</p>
        </div>

        <?php if ($erro): ?>
            <div class="notification error" role="alert" aria-live="polite"><?= htmlsafe($erro) ?></div>
        <?php endif; ?>

        <form method="POST" id="registroForm" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCSRF() ?>" />

            <div class="form-group">
                <input type="text" class="form-control" id="nome_completo" name="nome_completo"
                    placeholder="Nome completo" required autocomplete="name"
                    value="<?= htmlsafe($dados['nome_completo']) ?>">
            </div>

            <div class="form-group">
                <input type="text" class="form-control" id="nome_usuario" name="nome_usuario"
                    placeholder="Nome de usuário" required autocapitalize="none" autocomplete="username"
                    title="Letras, números, underline e espaços"
                    value="<?= htmlsafe($dados['nome_usuario']) ?>">
            </div>

            <div class="form-group">
                <input type="email" class="form-control" id="email" name="email"
                    placeholder="E-mail" required autocapitalize="none" autocomplete="email"
                    value="<?= htmlsafe($dados['email']) ?>">
            </div>

            <div class="form-group">
                <input type="password" class="form-control" id="senha" name="senha"
                    placeholder="Senha" required autocomplete="new-password">
                <i class="fas fa-eye toggle-password" onclick="togglePassword('senha', this)"></i>
                <div class="password-strength"><div class="password-strength-bar" id="strengthBar"></div></div>
                <div class="password-hint" id="strengthHint">Mínimo 8 caracteres, 1 maiúscula, 1 número e 1 especial</div>
            </div>

            <div class="form-group">
                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha"
                    placeholder="Confirmar senha" required autocomplete="new-password">
                <i class="fas fa-eye toggle-password" onclick="togglePassword('confirmar_senha', this)"></i>
            </div>

            <div class="form-group">
                <label for="data_nascimento">Data de nascimento</label>
                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento"
                    min="<?= $dataMinima ?>" max="<?= $dataMaxima ?>" required
                    value="<?= htmlsafe($dados['data_nascimento']) ?>">
            </div>

            <div class="form-group">
                <input type="tel" class="form-control" id="telefone" name="telefone"
                    placeholder="Telefone (com DDD)" required autocomplete="tel"
                    value="<?= htmlsafe($dados['telefone']) ?>">
            </div>

            <button type="submit" class="btn" id="registroBtn">
                <span>Criar conta</span>
            </button>
        </form>

        <div class="auth-links">
            <a href="login.php">Já tem uma conta? <strong>Entrar</strong></a>
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

        // Password strength indicator
        var senhaInput = document.getElementById('senha');
        var bar = document.getElementById('strengthBar');
        var hint = document.getElementById('strengthHint');

        senhaInput.addEventListener('input', function() {
            var v = this.value;
            var score = 0;
            if (v.length >= 8) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[@$!%*?&]/.test(v)) score++;

            bar.className = 'password-strength-bar';
            if (score <= 1) {
                bar.classList.add('weak');
                hint.textContent = 'Senha fraca';
                hint.style.color = '#ff4444';
            } else if (score <= 3) {
                bar.classList.add('medium');
                hint.textContent = 'Senha média';
                hint.style.color = '#ffbb33';
            } else {
                bar.classList.add('strong');
                hint.textContent = 'Senha forte ✓';
                hint.style.color = '#1ed760';
            }

            if (v.length === 0) {
                bar.className = 'password-strength-bar';
                hint.textContent = 'Mínimo 8 caracteres, 1 maiúscula, 1 número e 1 especial';
                hint.style.color = '';
            }
        });

        document.getElementById('registroForm').addEventListener('submit', function() {
            var btn = document.getElementById('registroBtn');
            if (!this.checkValidity()) return;
            btn.innerHTML = 'Criando conta… <span class="spinner"></span>';
            btn.style.pointerEvents = 'none';
        });
    </script>
</body>
</html>
