<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/deezer.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario_logado = $_SESSION['id_usuario'];
$usuario = buscarUm("SELECT *, possui_estrela_apoio FROM usuarios WHERE id = ?", [$id_usuario_logado]);
if (!$usuario) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$usuario_permitido = in_array($usuario['nivel_admin'], [1, 2]);

$favoritas_local = buscarTodos("SELECT id_musica FROM favoritos WHERE id_usuario = ?", [$id_usuario_logado]);
$favoritas_dz = buscarTodos("SELECT deezer_id FROM favoritos_deezer WHERE id_usuario = ?", [$id_usuario_logado]);
$_SESSION['favoritas_ids'] = array_merge(
    array_map('strval', array_column($favoritas_local, 'id_musica')),
    array_column($favoritas_dz, 'deezer_id')
);

$minhas_playlists = buscarTodos("SELECT id, nome FROM playlists WHERE id_usuario_criador = ? ORDER BY nome ASC", [$id_usuario_logado]);

// === DEEZER API ===
$chart = deezerChart(10);
$funk = deezerBuscar('funk brasileiro', 10);
$rap = deezerBuscar('rap brasileiro', 10);
$sertanejo = deezerBuscar('sertanejo universitario', 10);

function renderCard($musica) {
    $id = htmlspecialchars($musica['id']);
    $audio = htmlspecialchars($musica['caminho_arquivo']);
    $titulo = htmlspecialchars($musica['titulo']);
    $artista = htmlspecialchars($musica['nome_artista']);
    $capa = htmlspecialchars($musica['caminho_capa']);
    ?>
    <div class="card"
         data-id="<?= $id ?>"
         data-audio="<?= $audio ?>"
         data-titulo="<?= $titulo ?>"
         data-artista="<?= $artista ?>"
         data-capa="<?= $capa ?>">
        <img src="<?= $capa ?>" class="card-img" alt="<?= $titulo ?>" onerror="this.src='/assets/img/capa-padrao.svg'">
        <div class="card-content">
            <h3><?= $titulo ?></h3>
            <p><?= $artista ?></p>
        </div>
        <button class="btn-add-playlist" data-id="<?= $id ?>" title="Adicionar a playlist">
            <i class="fas fa-plus"></i>
        </button>
        <button class="btn-fav" data-id="<?= $id ?>" title="Favoritar">
            <i class="far fa-heart"></i>
        </button>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/dashboard.css">
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-headphones-simple"></i><span>DuckMusic</span></div>
        <div class="menu">
            <a href="dashboard.php" class="menu-item active"><i class="fas fa-home"></i> <span>Inicio</span></a>
            <a href="explorar.php" class="menu-item"><i class="fas fa-search"></i> <span>Explorar</span></a>
            <a href="biblioteca.php" class="menu-item"><i class="fas fa-book"></i> <span>Biblioteca</span></a>
            <?php if ($usuario_permitido): ?>
                <a href="/api/adicionar_musica.php" class="menu-item"><i class="fas fa-plus-circle"></i> <span>Adicionar</span></a>
            <?php endif; ?>
            <?php if (isset($usuario['possui_estrela_apoio']) && $usuario['possui_estrela_apoio']): ?>
                <a href="iniciar_doacao.php" class="menu-item"><i class="fas fa-hand-holding-dollar"></i><span>Apoiador</span><i class="fas fa-star" title="Apoiador!"></i></a>
            <?php else: ?>
                <a href="iniciar_doacao.php" class="menu-item"><i class="fas fa-hand-holding-dollar"></i> <span>Apoiar</span></a>
            <?php endif; ?>
        </div>
        <div class="playlists">
            <h3>Playlists <button class="btn-trigger-modal-playlist" title="Criar Nova Playlist"><i class="fas fa-plus"></i></button></h3>
            <div>
                <?php if (!empty($minhas_playlists)): ?>
                    <?php foreach ($minhas_playlists as $pl): ?>
                        <a href="ver_playlist.php?id=<?= $pl['id'] ?>" class="playlist-item"><i class="fas fa-music"></i><span><?= htmlspecialchars($pl['nome']) ?></span></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="sem-playlists-aviso">Nenhuma playlist.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h1>Bem-vindo, <?= htmlspecialchars($usuario['nome_usuario']) ?>!</h1>
                <p>Descubra novas batidas ou curta seus classicos.</p>
            </div>
            <div class="user-menu">
                <a href="configuracoes.php" class="btn" title="Configuracoes"><i class="fas fa-cog"></i></a>
                <a href="logout.php" class="btn" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
                <div class="user-avatar">
                    <?php
                    $avatarPath = $usuario['avatar'] ?? 'avatar-padrao.jpg';
                    $use_placeholder = (strpos($avatarPath, 'http') !== 0) || $avatarPath === 'avatar-padrao.jpg';
                    if ($use_placeholder): ?>
                        <div class="avatar-placeholder"><?= strtoupper(substr($usuario['nome_usuario'], 0, 1)) ?></div>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Avatar">
                    <?php endif; ?>
                    <?php if (isset($usuario['possui_estrela_apoio']) && $usuario['possui_estrela_apoio']): ?>
                        <i class="fas fa-star" title="Apoiador!"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-fire"></i> Em Alta</h2>
        <div class="cards-container">
            <?php foreach ($chart as $m): renderCard($m); endforeach; ?>
            <?php if (empty($chart)): ?><p>Nenhuma musica encontrada.</p><?php endif; ?>
        </div>

        <h2 class="section-title"><i class="fas fa-bolt"></i> Funk</h2>
        <div class="cards-container">
            <?php foreach ($funk as $m): renderCard($m); endforeach; ?>
            <?php if (empty($funk)): ?><p>Carregando...</p><?php endif; ?>
        </div>

        <h2 class="section-title"><i class="fas fa-microphone-lines"></i> Rap</h2>
        <div class="cards-container">
            <?php foreach ($rap as $m): renderCard($m); endforeach; ?>
            <?php if (empty($rap)): ?><p>Carregando...</p><?php endif; ?>
        </div>

        <h2 class="section-title"><i class="fas fa-guitar"></i> Sertanejo</h2>
        <div class="cards-container">
            <?php foreach ($sertanejo as $m): renderCard($m); endforeach; ?>
            <?php if (empty($sertanejo)): ?><p>Carregando...</p><?php endif; ?>
        </div>
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
            <button class="player-fav-btn" id="player-fav-btn" data-id="" title="Favoritar"><i class="far fa-heart"></i></button>
        </div>
        <div class="player-controls">
            <button id="btn-prev" title="Anterior"><i class="fas fa-backward-step"></i></button>
            <button id="btn-play" title="Tocar"><i class="fas fa-play"></i></button>
            <button id="btn-pause" title="Pausar" style="display:none;"><i class="fas fa-pause"></i></button>
            <button id="btn-next" title="Proxima"><i class="fas fa-forward-step"></i></button>
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
        <audio id="audio-element" src=""></audio>
    </div>

    <div id="modalCriarPlaylist" class="modal">
        <div class="modal-container">
            <div class="modal-header"><h2>Nova Playlist</h2><span class="close-btn" id="closeModalCriarPlaylist">&times;</span></div>
            <form id="formCriarPlaylist">
                <div class="modal-body">
                    <div class="form-group"><label>Nome da Playlist</label><input type="text" name="playlist_nome" id="playlist_nome" placeholder="Ex: Melhores do Rock" required></div>
                    <div id="modalFeedback"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-secondary" id="btnCancelarCriarPlaylist">Cancelar</button>
                    <button type="submit" class="btn-modal btn-modal-primary" id="btnSalvarPlaylist">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>
    <script>
        window.APP_DATA = {
            favoritasIds: <?= json_encode($_SESSION['favoritas_ids'] ?? []) ?>,
            currentVolume: <?= json_encode($_SESSION['user_volume'] ?? 0.8) ?>
        };
    </script>
    <script src="/js/dashboard.js"></script>
</body>
</html>
