<?php
require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario = buscarUm("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['id_usuario']]);

$erro = '';
$sucesso = '';

// Processar atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $nome_completo = trim($_POST['nome_completo']);
    $nome_usuario = trim($_POST['nome_usuario']);
    $email = trim($_POST['email']);
    $biografia = trim($_POST['biografia']);
    $generos_favoritos = trim($_POST['generos_favoritos']);

    // Validar dados
    if (empty($nome_completo) || empty($nome_usuario) || empty($email)) {
        $erro = "Nome completo, nome de usuário e email são obrigatórios.";
    } else {
        // Verificar se o nome de usuário ou email já existem (excluindo o usuário atual)
        $usuario_existente = buscarUm("SELECT id FROM usuarios WHERE (nome_usuario = ? OR email = ?) AND id != ?", 
                                    [$nome_usuario, $email, $_SESSION['id_usuario']]);
        
        if ($usuario_existente) {
            $erro = "Nome de usuário ou email já está em uso por outro usuário.";
        } else {
            // Atualizar no banco de dados
            atualizar("UPDATE usuarios SET nome_completo = ?, nome_usuario = ?, email = ?, biografia = ?, generos_favoritos = ? WHERE id = ?",
                [$nome_completo, $nome_usuario, $email, $biografia, $generos_favoritos, $_SESSION['id_usuario']]);
            
            $sucesso = "Perfil atualizado com sucesso!";
            $usuario = buscarUm("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['id_usuario']]);
        }
    }
}

// Processar upload de avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $caminhoUpload = 'uploads/avatars/';
        if (!is_dir($caminhoUpload)) {
            mkdir($caminhoUpload, 0777, true);
        }
        
        $extensao = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid() . '.' . $extensao;
        $caminhoArquivo = $caminhoUpload . $nomeArquivo;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $caminhoArquivo)) {
            // Atualizar no banco de dados
            $stmt = $db->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
            $stmt->execute([$caminhoArquivo, $_SESSION['id_usuario']]);
            
            $sucesso = "Avatar atualizado com sucesso!";
            $usuario = buscarUm("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['id_usuario']]);
        } else {
            $erro = "Erro ao fazer upload do avatar.";
        }
    } else {
        $erro = "Deu Erro no upload do arquivo MAIS EU VOU ARRUMAR.";
    }
}

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $erro = "Todos os campos de senha são obrigatórios.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $erro = "As novas senhas não coincidem.";
    } else {
        // Verificar senha atual
        $usuario_db = buscarUm("SELECT senha FROM usuarios WHERE id = ?", [$_SESSION['id_usuario']]);
        
        if (password_verify($senha_atual, $usuario_db['senha'])) {
            // Atualizar senha
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$nova_senha_hash, $_SESSION['id_usuario']]);
            
            $sucesso = "Senha alterada com sucesso!";
        } else {
            $erro = "Senha atual incorreta.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
        font-family: 'Poppins', sans-serif;
        background-color: var(--dark);
        color: var(--white);
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }

    .main-content {
        margin-left: 250px;
        padding: 30px;
    }

    .header {
        margin-bottom: 30px;
    }

    .greeting h1 {
        margin: 0;
        font-size: 28px;
    }

    .greeting p {
        margin: 5px 0 0;
        color: var(--light-text);
        font-size: 14px;
    }

    .settings-container {
        background-color: var(--light-dark);
        padding: 30px;
        border-radius: 10px;
        max-width: 800px;
        margin: 0 auto;
    }

    .settings-tabs {
        display: flex;
        margin-bottom: 30px;
        border-bottom: 1px solid var(--lighter-dark);
        gap: 20px;
    }

    .settings-tab {
        padding: 10px 0;
        cursor: pointer;
        color: var(--light-text);
        font-weight: 500;
        transition: var(--transition);
        border-bottom: 2px solid transparent;
    }

    .settings-tab.active {
        color: var(--white);
        border-bottom: 2px solid var(--primary);
    }

    .settings-tab:hover {
        color: var(--white);
    }

    .settings-content {
        display: none;
    }

    .settings-content.active {
        display: block;
    }

    .avatar-container {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
        gap: 20px;
    }

    .avatar-img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .avatar-upload {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .avatar-upload-btn {
        background: var(--lighter-dark);
        color: var(--white);
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
    }

    .avatar-upload-btn:hover {
        background: var(--primary);
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
        box-shadow: 0 0 0 2px rgba(29, 185, 84, 0.2);
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .btn {
        background: var(--primary);
        color: var(--white);
        border: none;
        padding: 12px 25px;
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn:hover {
        background:rgb(180, 240, 0);
        transform: translateY(-2px);
    }

    .notification {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-size: 14px;
        border: 1px solid transparent;
    }

    .error {
        background: rgba(255, 51, 51, 0.1);
        border-color: #ff3333;
        color: #ff6b6b;
    }

    .success {
        background: rgba(29, 185, 84, 0.1);
        border-color: var(--primary);
        color: var(--primary);
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
            padding: 20px;
        }

        .settings-container {
            padding: 20px;
        }

        .settings-tabs {
            flex-wrap: wrap;
            gap: 15px;
        }

        .avatar-container {
            flex-direction: column;
            align-items: start;
        }
    }
</style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <div class="greeting">
                <h1>Configurações</h1>
                <p>Gerencie sua conta e preferências</p>
            </div>
        </div>
        
        <div class="settings-container">
            <div class="settings-tabs">
                <div class="settings-tab active" data-tab="perfil">Perfil</div>
                <div class="settings-tab" data-tab="seguranca">Segurança</div>
                <div class="settings-tab" data-tab="preferencias">Preferências</div>
            </div>
            
            <div class="settings-content active" id="perfil">
                <?php if ($erro): ?>
                    <div class="notification error"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>
                
                <?php if ($sucesso): ?>
                    <div class="notification success"><?= htmlspecialchars($sucesso) ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="avatar-container">
                        <?php if ($usuario['avatar']): ?>
                            <img src="<?= $usuario['avatar'] ?>" class="avatar-img" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-img" style="background: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: bold;">
                                <?= strtoupper(substr($usuario['nome_usuario'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="avatar-upload">
                            <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
                            <button type="button" class="avatar-upload-btn" onclick="document.getElementById('avatar').click()">
                                <i class="fas fa-camera"></i> Alterar Avatar
                            </button>
                            <span style="font-size: 12px; color: var(--light-text);">Formatos: JPG, PNG (Max. 2MB)</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nome_completo">Nome Completo</label>
                        <input type="text" id="nome_completo" name="nome_completo" class="form-control" value="<?= htmlspecialchars($usuario['nome_completo']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nome_usuario">Nome de Usuário</label>
                        <input type="text" id="nome_usuario" name="nome_usuario" class="form-control" value="<?= htmlspecialchars($usuario['nome_usuario']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="biografia">Biografia</label>
                        <textarea id="biografia" name="biografia" class="form-control"><?= htmlspecialchars($usuario['biografia'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="generos_favoritos">Gêneros Musicais Favoritos (separados por vírgula)</label>
                        <input type="text" id="generos_favoritos" name="generos_favoritos" class="form-control" value="<?= htmlspecialchars($usuario['generos_favoritos'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" name="atualizar_perfil" class="btn">Salvar Alterações</button>
                </form>
            </div>
            
            <div class="settings-content" id="seguranca">
                <h2>Alterar Senha</h2>
                
                <?php if ($erro && isset($_POST['alterar_senha'])): ?>
                    <div class="notification error"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>
                
                <?php if ($sucesso && isset($_POST['alterar_senha'])): ?>
                    <div class="notification success"><?= htmlspecialchars($sucesso) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="senha_atual">Senha Atual</label>
                        <input type="password" id="senha_atual" name="senha_atual" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" id="nova_senha" name="nova_senha" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Nova Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="alterar_senha" class="btn">Alterar Senha</button>
                </form>
            </div>
            
            <div class="settings-content" id="preferencias">
                <h2>Preferências do Aplicativo</h2>
                <p>Personalize sua experiência no DuckMusic</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="tema">Tema</label>
                        <select id="tema" name="tema" class="form-control">
                            <option value="escuro" <?= ($usuario['tema'] ?? 'escuro') === 'escuro' ? 'selected' : '' ?>>Escuro</option>
                            <option value="claro" <?= ($usuario['tema'] ?? 'escuro') === 'claro' ? 'selected' : '' ?>>Claro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ordem_exibicao">Ordem de Exibição de Músicas</label>
                        <select id="ordem_exibicao" name="ordem_exibicao" class="form-control">
                            <option value="recentes" <?= ($usuario['ordem_exibicao'] ?? 'recentes') === 'recentes' ? 'selected' : '' ?>>Mais Recentes Primeiro</option>
                            <option value="antigas" <?= ($usuario['ordem_exibicao'] ?? 'recentes') === 'antigas' ? 'selected' : '' ?>>Mais Antigas Primeiro</option>
                            <option value="alfabetica" <?= ($usuario['ordem_exibicao'] ?? 'recentes') === 'alfabetica' ? 'selected' : '' ?>>Ordem Alfabética</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="salvar_preferencias" class="btn">Salvar Preferências</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Alternar entre abas de configurações
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const tabId = this.getAttribute('data-tab');
                document.querySelectorAll('.settings-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Mostrar preview do avatar antes de enviar
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.querySelector('.avatar-img').src = event.target.result;
                };
                reader.readAsDataURL(file);
                
                // Enviar automaticamente
                const form = new FormData();
                form.append('avatar', file);
                
                fetch('configuracoes.php', {
                    method: 'POST',
                    body: form
                }).then(response => {
                    window.location.reload();
                });
            }
        });
    </script>
</body>
</html>