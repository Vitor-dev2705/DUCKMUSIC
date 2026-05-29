<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/email_helper.php';

$email = $_SESSION['verificar_email'] ?? ($_GET['email'] ?? '');
$erro = '';
$sucesso = '';

if (empty($email)) {
    header("Location: registro.php");
    exit();
}

// Reenviar codigo
if (isset($_GET['reenviar']) && !empty($email)) {
    $usuario = buscarUm("SELECT id, nome_completo FROM usuarios WHERE LOWER(email) = LOWER(?) AND email_verificado = false", [$email]);
    if ($usuario) {
        $codigo = gerarCodigoVerificacao();
        $expira = date('Y-m-d H:i:s', time() + 900);
        atualizar("UPDATE usuarios SET codigo_verificacao = ?, codigo_expira_em = ? WHERE id = ?", [$codigo, $expira, $usuario['id']]);
        enviarCodigoVerificacao($email, $codigo, $usuario['nome_completo']);
        $sucesso = "Novo codigo enviado para $email";
    }
}

// Verificar codigo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_digitado = trim($_POST['codigo'] ?? '');

    if (strlen($codigo_digitado) !== 6 || !ctype_digit($codigo_digitado)) {
        $erro = "Codigo invalido. Digite os 6 numeros.";
    } else {
        $usuario = buscarUm(
            "SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?) AND codigo_verificacao = ? AND codigo_expira_em > NOW()",
            [$email, $codigo_digitado]
        );

        if ($usuario) {
            atualizar("UPDATE usuarios SET email_verificado = true, codigo_verificacao = NULL, codigo_expira_em = NULL WHERE id = ?", [$usuario['id']]);
            unset($_SESSION['verificar_email']);
            $_SESSION['mensagem'] = "E-mail verificado! Faca login.";
            session_write_close();
            header("Location: login.php");
            exit();
        } else {
            // Checar se expirou
            $expirado = buscarUm("SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?) AND codigo_verificacao = ? AND codigo_expira_em <= NOW()", [$email, $codigo_digitado]);
            if ($expirado) {
                $erro = "Codigo expirado. Clique em reenviar.";
            } else {
                $erro = "Codigo incorreto.";
            }
        }
    }
}

$emailMascarado = preg_replace('/^(.{2})(.*)(@.*)$/', '$1***$3', $email);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar E-mail - DuckMusic</title>
    <meta name="theme-color" content="#121212">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth.css">
    <style>
        .code-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 24px 0;
        }
        .code-input {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            background: #282828;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border-color 0.2s;
        }
        .code-input:focus {
            border-color: #1db954;
        }
        .verify-info {
            text-align: center;
            color: #b3b3b3;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .verify-info strong {
            color: #fff;
        }
        .verify-icon {
            text-align: center;
            margin-bottom: 16px;
        }
        .verify-icon i {
            font-size: 48px;
            color: #1db954;
        }
        .resend-link {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
        }
        .resend-link a {
            color: #1db954;
            text-decoration: none;
            font-weight: 500;
        }
        .resend-link a:hover {
            text-decoration: underline;
        }
        /* Hidden real input */
        .hidden-code-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="verify-icon">
            <i class="fas fa-envelope-circle-check"></i>
        </div>

        <div class="logo">
            <h1 style="font-size:1.3rem;">Verificar E-mail</h1>
        </div>

        <p class="verify-info">Enviamos um codigo de 6 digitos para<br><strong><?= htmlsafe($emailMascarado) ?></strong></p>

        <?php if ($sucesso): ?>
            <div class="notification success"><?= htmlsafe($sucesso) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="notification error"><?= htmlsafe($erro) ?></div>
        <?php endif; ?>

        <form method="POST" id="verifyForm">
            <input type="hidden" name="codigo" id="codigoHidden" value="">

            <div class="code-inputs" id="codeInputs">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
            </div>

            <button type="submit" class="btn" id="verifyBtn">Verificar</button>
        </form>

        <div class="resend-link">
            Nao recebeu? <a href="?reenviar=1&email=<?= urlencode($email) ?>">Reenviar codigo</a>
        </div>

        <div class="auth-links">
            <a href="registro.php">Voltar ao registro</a>
        </div>
    </div>

    <script>
        var inputs = document.querySelectorAll('.code-input');
        var hidden = document.getElementById('codigoHidden');
        var form = document.getElementById('verifyForm');

        function updateHidden() {
            var code = '';
            inputs.forEach(function(inp) { code += inp.value; });
            hidden.value = code;
        }

        inputs.forEach(function(input, index) {
            input.addEventListener('input', function(e) {
                var val = this.value.replace(/\D/g, '');
                this.value = val.charAt(0) || '';

                if (val && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateHidden();

                // Auto-submit quando preencher todos
                if (hidden.value.length === 6) {
                    form.submit();
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';
                    updateHidden();
                }
            });

            // Suportar paste do codigo inteiro
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                var paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (paste.length >= 6) {
                    for (var i = 0; i < 6; i++) {
                        inputs[i].value = paste.charAt(i);
                    }
                    inputs[5].focus();
                    updateHidden();
                    form.submit();
                }
            });
        });

        // Focus no primeiro input
        inputs[0].focus();
    </script>
</body>
</html>
