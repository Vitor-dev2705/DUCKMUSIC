<?php
require_once __DIR__ . '/includes/init.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: auth/login.php");
    exit();
}

$id_usuario_logado = $_SESSION['id_usuario'];
$usuario = buscarUm("SELECT * FROM usuarios WHERE id = ?", [$id_usuario_logado]);

if (!$usuario) {
    session_destroy();
    header("Location: auth/login.php");
    exit();
}

$usuario_permitido = in_array($usuario['nivel_admin'], [1, 2]);

$favoritas_db = buscarTodos("SELECT id_musica FROM favoritos WHERE id_usuario = ?", [$id_usuario_logado]);
$_SESSION['favoritas_ids'] = array_column($favoritas_db, 'id_musica');

$minhas_playlists = buscarTodos("SELECT id, nome FROM playlists WHERE id_usuario_criador = ? ORDER BY nome ASC", [$id_usuario_logado]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuckMusic</title>
    <meta name="theme-color" content="#1a1a2e">
    <meta name="description" content="DuckMusic - Sua plataforma de streaming de musica">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/biblioteca.css">
    <link rel="stylesheet" href="/css/playlist.css">
    <style>
        #content { min-height: calc(100vh - 180px); flex: 1; margin-left: 280px; padding: 2rem; padding-bottom: 12rem; }
        .player { display: none; }
        .loading-spinner {
            display: flex; align-items: center; justify-content: center;
            min-height: 300px; color: var(--color-text-muted);
        }
        .loading-spinner i { font-size: 2rem; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .nav-link { cursor: pointer; }
        #progress-bar { cursor: pointer; }
        /* Shuffle/Repeat active */
        #btn-shuffle.active, #btn-repeat.active { color: var(--color-primary, #8e44ad); }
        #btn-repeat.repeat-one::after { content: '1'; font-size: 0.6rem; position: absolute; margin-top: -8px; margin-left: 2px; font-weight: bold; color: var(--color-primary, #8e44ad); }
        #btn-repeat { position: relative; }
        /* Explorar search inline */
        .search-box { margin-bottom: 30px; position: relative; max-width: 600px; }
        .search-box input { width: 100%; padding: 15px 50px; border-radius: 30px; border: none; background: #282828; color: white; font-size: 1rem; }
        .search-box i { position: absolute; left: 20px; top: 18px; color: #b3b3b3; }
        /* Responsive fix for SPA content */
        @media (max-width: 992px) { #content { margin-left: 80px; padding: 1rem; } }
        @media (max-width: 576px) { #content { margin-left: 0; padding: 0.8rem; padding-bottom: 14rem; } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><i class="fas fa-headphones-simple"></i><span>DuckMusic</span></div>
    <div class="menu">
        <a href="/paginas/dashboard.php" class="menu-item nav-link active" data-page="dashboard"><i class="fas fa-home"></i> <span>Inicio</span></a>
        <a href="/paginas/explorar.php" class="menu-item nav-link" data-page="explorar"><i class="fas fa-search"></i> <span>Explorar</span></a>
        <a href="/paginas/biblioteca.php" class="menu-item nav-link" data-page="biblioteca"><i class="fas fa-book"></i> <span>Biblioteca</span></a>
        <?php if ($usuario_permitido): ?>
        <a href="/api/adicionar_musica.php" class="menu-item nav-link" data-page="adicionar"><i class="fas fa-plus-circle"></i> <span>Adicionar</span></a>
        <?php endif; ?>
        <?php if ($usuario['possui_estrela_apoio']): ?>
        <a href="/paginas/iniciar_doacao.php" class="menu-item nav-link"><i class="fas fa-hand-holding-dollar"></i><span>Apoiador</span><i class="fas fa-star" style="color:gold;margin-left:auto"></i></a>
        <?php else: ?>
        <a href="/paginas/iniciar_doacao.php" class="menu-item nav-link"><i class="fas fa-hand-holding-dollar"></i> <span>Apoiar</span></a>
        <?php endif; ?>
    </div>
    <div class="playlists">
        <h3>Playlists <button class="btn-trigger-modal-playlist" title="Criar Nova Playlist"><i class="fas fa-plus"></i></button></h3>
        <div id="sidebar-playlists">
            <?php if (!empty($minhas_playlists)): ?>
                <?php foreach ($minhas_playlists as $pl): ?>
                <a href="/api/ver_playlist.php?id=<?= $pl['id'] ?>" class="playlist-item nav-link"><i class="fas fa-music"></i><span><?= htmlspecialchars($pl['nome']) ?></span></a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="sem-playlists-aviso">Nenhuma playlist.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="main-content" id="content">
    <div class="loading-spinner"><i class="fas fa-spinner"></i></div>
</div>

<div class="player" id="player">
    <div class="player-top">
        <div class="player-song">
            <img src="" class="player-song-img" id="player-img" alt="Capa">
            <div class="player-song-details">
                <h4 id="player-title">Selecione uma musica</h4>
                <p id="player-artist">DuckMusic</p>
            </div>
        </div>
        <button class="player-fav-btn" id="player-fav-btn" data-id=""><i class="far fa-heart"></i></button>
    </div>
    <div class="player-controls">
        <button id="btn-shuffle" title="Aleatório"><i class="fas fa-shuffle"></i></button>
        <button id="btn-prev" title="Anterior"><i class="fas fa-backward-step"></i></button>
        <button id="btn-play" title="Tocar"><i class="fas fa-play"></i></button>
        <button id="btn-pause" title="Pausar" style="display:none;"><i class="fas fa-pause"></i></button>
        <button id="btn-next" title="Proxima"><i class="fas fa-forward-step"></i></button>
        <button id="btn-repeat" title="Repetir"><i class="fas fa-repeat"></i></button>
    </div>
    <div class="progress-container">
        <span class="time" id="current-time">0:00</span>
        <div class="progress-bar" id="progress-bar"><div class="progress" id="progress"></div></div>
        <span class="time" id="duration">0:00</span>
        <div class="volume-controls">
            <i class="fas fa-volume-high" id="volume-icon"></i>
            <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.8">
        </div>
    </div>
</div>

<audio id="audio-element" preload="auto"></audio>

<div id="modalCriarPlaylist" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nova Playlist</h2>
            <span class="close-btn" id="closeModalCriarPlaylist">&times;</span>
        </div>
        <form id="formCriarPlaylist">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nome da Playlist</label>
                    <input type="text" name="playlist_nome" id="playlist_nome" placeholder="Ex: Melhores do Rock" required>
                </div>
                <div id="modalFeedback"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-secondary" id="btnCancelarCriarPlaylist">Cancelar</button>
                <button type="submit" class="btn-modal btn-modal-primary">Criar</button>
            </div>
        </form>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
window.APP_DATA = {
    favoritasIds: <?= json_encode($_SESSION['favoritas_ids'] ?? []) ?>,
    usuario: <?= json_encode(['nome' => $usuario['nome_usuario'], 'admin' => $usuario['nivel_admin']]) ?>
};
</script>
<script src="/js/app.js"></script>
</body>
</html>
