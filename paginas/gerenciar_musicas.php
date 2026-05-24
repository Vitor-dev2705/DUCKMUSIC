<?php
require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['nivel_admin'], [1, 2])) {
    header("Location: login.php");
    exit();
}

// Processar remoção de música
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_musica'])) {
    $musica_id = $_POST['musica_id'];
    $motivo = trim($_POST['motivo']);
    
    if (empty($motivo)) {
        $erro = "Por favor, informe o motivo da exclusão.";
    } else {
        // Buscar informações da música antes de excluir
        $musica = buscarUm("SELECT * FROM musicas WHERE id = ?", [$musica_id]);
        
        if ($musica) {
            try {
                $db->beginTransaction();
                
                // Registrar na tabela de exclusões
                $stmt = $db->prepare("INSERT INTO exclusoes_musicas (id_musica, titulo, artista, id_usuario_excluiu, motivo) 
                                     VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$musica_id, $musica['titulo'], $musica['artista'], $_SESSION['id_usuario'], $motivo]);
                
                // Marcar música como removida (soft delete)
                $stmt = $db->prepare("UPDATE musicas SET status = 0 WHERE id = ?");
                $stmt->execute([$musica_id]);
                
                // Remover de todas as playlists
                $stmt = $db->prepare("DELETE FROM musicas_playlists WHERE id_musica = ?");
                $stmt->execute([$musica_id]);
                
                // Remover dos favoritos
                $stmt = $db->prepare("DELETE FROM favoritos WHERE id_musica = ?");
                $stmt->execute([$musica_id]);
                
                $db->commit();
                $sucesso = "Música removida com sucesso!";
            } catch (Exception $e) {
                $db->rollBack();
                $erro = "Erro ao remover música: " . $e->getMessage();
            }
        } else {
            $erro = "Música não encontrada.";
        }
    }
}

// Buscar todas as músicas ativas
$musicas = buscarTodos("
    SELECT m.*, u.nome_usuario as uploader 
    FROM musicas m
    LEFT JOIN usuarios u ON m.id_usuario_upload = u.id
    WHERE m.status = 1
    ORDER BY m.data_upload DESC
");

// Buscar estatísticas
$total_musicas = buscarUm("SELECT COUNT(*) as total FROM musicas WHERE status = 1")['total'];
$total_usuarios = buscarUm("SELECT COUNT(*) as total FROM usuarios WHERE status = 1")['total'];
$total_playlists = buscarUm("SELECT COUNT(*) as total FROM playlists WHERE status = 1")['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Músicas - DuckMusic</title>
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
            --danger: #e74c3c;
            --warning: #f39c12;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: var(--dark);
            color: var(--white);
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 240px;
            background: var(--dark);
            padding: 20px 10px;
            position: fixed;
            top: 0;
            bottom: 0;
        }
        
        .main-content {
            margin-left: 240px;
            flex: 1;
            padding: 40px;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-title {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .admin-subtitle {
            color: var(--light-text);
            font-size: 14px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--lighter-dark);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary);
        }
        
        .stat-label {
            color: var(--light-text);
            font-size: 14px;
        }
        
        .music-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .music-table th {
            text-align: left;
            padding: 15px 10px;
            color: var(--light-text);
            font-weight: 400;
            border-bottom: 1px solid var(--lighter-dark);
        }
        
        .music-table td {
            padding: 15px 10px;
            border-bottom: 1px solid var(--lighter-dark);
        }
        
        .music-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        
        .music-cover {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
        }
        
        .music-info {
            display: flex;
            align-items: center;
        }
        
        .music-text {
            margin-left: 15px;
        }
        
        .music-title {
            font-weight: 500;
        }
        
        .music-artist {
            font-size: 14px;
            color: var(--light-text);
        }
        
        .action-btn {
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            font-size: 16px;
            transition: var(--transition);
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .action-btn.delete {
            color: var(--danger);
        }
        
        .action-btn.delete:hover {
            background: rgba(231, 76, 60, 0.1);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--light-dark);
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: var(--light-text);
            cursor: pointer;
        }
        
        .modal-title {
            margin-bottom: 20px;
            font-size: 20px;
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
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn:hover {
            background: #1ed760;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-danger:hover {
            background: #e74c3c;
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
        
        .success {
            background: rgba(29, 185, 84, 0.1);
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .main-content {
                margin-left: 70px;
                padding: 20px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="admin-container">
            <div class="admin-header">
                <div>
                    <h1 class="admin-title">Gerenciar Músicas</h1>
                    <p class="admin-subtitle">Painel de administração - <?= $_SESSION['nivel_admin'] == 2 ? 'Administrador' : 'Moderador' ?></p>
                </div>
            </div>
            
            <?php if ($erro): ?>
                <div class="notification error"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="notification success"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_musicas ?></div>
                    <div class="stat-label">Músicas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $total_usuarios ?></div>
                    <div class="stat-label">Usuários</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $total_playlists ?></div>
                    <div class="stat-label">Playlists</div>
                </div>
            </div>
            
            <h2>Todas as Músicas</h2>
            <table class="music-table">
                <thead>
                    <tr>
                        <th>Música</th>
                        <th>Artista</th>
                        <th>Uploader</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($musicas as $musica): ?>
                        <tr>
                            <td>
                                <div class="music-info">
                                    <?php if ($musica['caminho_capa']): ?>
                                        <img src="<?= $musica['caminho_capa'] ?>" class="music-cover" alt="Capa">
                                    <?php else: ?>
                                        <img src="capa-padrao.jpg" class="music-cover" alt="Capa Padrão">
                                    <?php endif; ?>
                                    <div class="music-text">
                                        <div class="music-title"><?= htmlspecialchars($musica['titulo']) ?></div>
                                        <?php if ($musica['album']): ?>
                                            <div class="music-artist"><?= htmlspecialchars($musica['album']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($musica['artista']) ?></td>
                            <td><?= htmlspecialchars($musica['uploader'] ?? 'Sistema') ?></td>
                            <td><?= date('d/m/Y', strtotime($musica['data_upload'])) ?></td>
                            <td>
                                <button class="action-btn delete" data-musica="<?= $musica['id'] ?>" data-titulo="<?= htmlspecialchars($musica['titulo']) ?>" data-artista="<?= htmlspecialchars($musica['artista']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal de confirmação de exclusão -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 class="modal-title">Confirmar Exclusão</h2>
            
            <p>Tem certeza que deseja excluir a música <strong id="modalMusicTitle"></strong> de <strong id="modalMusicArtist"></strong>?</p>
            <p>Esta ação não pode ser desfeita e removerá a música de todas as playlists e favoritos.</p>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="musica_id" id="modalMusicId">
                
                <div class="form-group">
                    <label for="motivo">Motivo da exclusão</label>
                    <textarea id="motivo" name="motivo" class="form-control" required placeholder="Informe o motivo da exclusão..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn" id="cancelDelete">Cancelar</button>
                    <button type="submit" name="excluir_musica" class="btn btn-danger">Confirmar Exclusão</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal de exclusão
        const deleteModal = document.getElementById('deleteModal');
        const deleteButtons = document.querySelectorAll('.action-btn.delete');
        const closeModal = document.querySelector('.close-modal');
        const cancelDelete = document.getElementById('cancelDelete');
        const modalMusicTitle = document.getElementById('modalMusicTitle');
        const modalMusicArtist = document.getElementById('modalMusicArtist');
        const modalMusicId = document.getElementById('modalMusicId');
        const deleteForm = document.getElementById('deleteForm');
        
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const musicaId = this.getAttribute('data-musica');
                const musicaTitle = this.getAttribute('data-titulo');
                const musicaArtist = this.getAttribute('data-artista');
                
                modalMusicTitle.textContent = musicaTitle;
                modalMusicArtist.textContent = musicaArtist;
                modalMusicId.value = musicaId;
                
                deleteModal.style.display = 'flex';
            });
        });
        
        closeModal.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
        
        cancelDelete.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>