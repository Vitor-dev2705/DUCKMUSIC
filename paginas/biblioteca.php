<?php
require_once __DIR__ . '/../includes/init.php';

// Redireciona para o SPA se acessada diretamente
if (empty($_SERVER['HTTP_X_SPA'])) {
    if (isset($_SESSION['id_usuario'])) {
        header("Location: /app.php");
        exit();
    }
}

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

// Favoritas locais
$favoritas_locais = buscarTodos("
    SELECT m.*, art.nome as nome_artista, f.data_favoritado
    FROM musicas m
    JOIN favoritos f ON m.id = f.id_musica
    LEFT JOIN artistas art ON m.id_artista = art.id
    WHERE f.id_usuario = ?
    ORDER BY f.data_favoritado DESC
", [$id_usuario_logado]);

// Favoritas Deezer
$favoritas_deezer = buscarTodos("
    SELECT deezer_id, titulo, artista, capa_url, audio_url, data_favoritado
    FROM favoritos_deezer
    WHERE id_usuario = ?
    ORDER BY data_favoritado DESC
", [$id_usuario_logado]);

// Playlists do usuario
$playlists = buscarTodos("
    SELECT p.*,
           (SELECT COUNT(*) FROM musicas_playlists mp WHERE mp.id_playlist = p.id) +
           (SELECT COUNT(*) FROM playlist_deezer_tracks pdt WHERE pdt.id_playlist = p.id) as total_musicas
    FROM playlists p
    WHERE p.id_usuario_criador = ?
    ORDER BY p.data_criacao DESC
", [$id_usuario_logado]);

// Musicas enviadas pelo usuario
$minhas_musicas = buscarTodos("
    SELECT m.*, art.nome as nome_artista
    FROM musicas m
    LEFT JOIN artistas art ON m.id_artista = art.id
    WHERE m.id_usuario_upload = ?
    ORDER BY m.data_upload DESC
", [$id_usuario_logado]);

// Unifica IDs de favoritos para session
$favoritas_ids_local = array_column($favoritas_locais, 'id');
$favoritas_ids_deezer = array_column($favoritas_deezer, 'deezer_id');
$_SESSION['favoritas_ids'] = array_merge(
    array_map('strval', $favoritas_ids_local),
    $favoritas_ids_deezer
);

$minhas_playlists = $playlists;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Biblioteca - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/dashboard.css">
    <style>
        .library-tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .library-tab {
            padding: 10px 24px; border-radius: 25px; background: rgba(60,74,99,0.4);
            color: var(--color-text-muted); cursor: pointer; font-weight: 500;
            transition: all 0.3s; border: 1px solid transparent; font-size: 0.95rem;
        }
        .library-tab:hover { background: rgba(142,68,173,0.2); color: var(--color-text-light); }
        .library-tab.active { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
        .library-content { display: none; }
        .library-content.active { display: block; }
        .empty-library-message {
            text-align: center; padding: 60px 20px; color: var(--color-text-muted);
            grid-column: 1 / -1;
        }
        .empty-library-message i { font-size: 3rem; margin-bottom: 15px; display: block; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-headphones-simple"></i><span>DuckMusic</span></div>
        <div class="menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> <span>Inicio</span></a>
            <a href="explorar.php" class="menu-item"><i class="fas fa-search"></i> <span>Explorar</span></a>
            <a href="biblioteca.php" class="menu-item active"><i class="fas fa-book"></i> <span>Biblioteca</span></a>
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
                <?php if (!empty($playlists)): ?>
                    <?php foreach ($playlists as $pl): ?>
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
                <h1><i class="fas fa-book"></i> Minha Biblioteca</h1>
                <p>Suas favoritas, playlists e uploads.</p>
            </div>
            <div class="user-menu">
                <a href="configuracoes.php" class="btn" title="Configuracoes"><i class="fas fa-cog"></i></a>
                <a href="logout.php" class="btn" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>

        <div class="library-tabs">
            <div class="library-tab active" data-tab="favoritas"><i class="fas fa-heart"></i> Favoritas</div>
            <div class="library-tab" data-tab="playlists"><i class="fas fa-list"></i> Playlists</div>
            <div class="library-tab" data-tab="minhas-musicas"><i class="fas fa-cloud-upload-alt"></i> Uploads</div>
        </div>

        <!-- FAVORITAS -->
        <div class="library-content active" id="favoritas">
            <h2 class="section-title"><i class="fas fa-heart"></i> Suas favoritas</h2>
            <div class="cards-container">
                <?php
                // Favoritas locais
                foreach ($favoritas_locais as $m):
                    $capa = resolverMidia($m['caminho_capa'], '/assets/img/capa-padrao.svg');
                    $audioUrl = resolverMidia($m['caminho_arquivo']);
                ?>
                    <div class="card"
                         data-id="<?= $m['id'] ?>"
                         data-audio="<?= htmlspecialchars($audioUrl) ?>"
                         data-titulo="<?= htmlspecialchars($m['titulo']) ?>"
                         data-artista="<?= htmlspecialchars($m['nome_artista'] ?: 'Desconhecido') ?>"
                         data-capa="<?= htmlspecialchars($capa) ?>">
                        <img src="<?= htmlspecialchars($capa) ?>" class="card-img" onerror="this.src='/assets/img/capa-padrao.svg'">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($m['titulo']) ?></h3>
                            <p><?= htmlspecialchars($m['nome_artista'] ?: 'Desconhecido') ?></p>
                        </div>
                        <button class="btn-add-playlist" data-id="<?= $m['id'] ?>" title="Adicionar a playlist"><i class="fas fa-plus"></i></button>
                        <button class="btn-fav" data-id="<?= $m['id'] ?>"><i class="fas fa-heart favorito"></i></button>
                    </div>
                <?php endforeach; ?>

                <?php
                // Favoritas Deezer
                foreach ($favoritas_deezer as $dz):
                ?>
                    <div class="card"
                         data-id="<?= htmlspecialchars($dz['deezer_id']) ?>"
                         data-audio="<?= htmlspecialchars($dz['audio_url']) ?>"
                         data-titulo="<?= htmlspecialchars($dz['titulo']) ?>"
                         data-artista="<?= htmlspecialchars($dz['artista']) ?>"
                         data-capa="<?= htmlspecialchars($dz['capa_url']) ?>">
                        <img src="<?= htmlspecialchars($dz['capa_url']) ?>" class="card-img" onerror="this.src='/assets/img/capa-padrao.svg'">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($dz['titulo']) ?></h3>
                            <p><?= htmlspecialchars($dz['artista']) ?></p>
                        </div>
                        <button class="btn-add-playlist" data-id="<?= htmlspecialchars($dz['deezer_id']) ?>" title="Adicionar a playlist"><i class="fas fa-plus"></i></button>
                        <button class="btn-fav" data-id="<?= htmlspecialchars($dz['deezer_id']) ?>"><i class="fas fa-heart favorito"></i></button>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($favoritas_locais) && empty($favoritas_deezer)): ?>
                    <div class="empty-library-message">
                        <i class="fas fa-heart-broken"></i>
                        <p>Voce ainda nao favoritou nenhuma musica.</p>
                        <p style="font-size:0.85rem; margin-top:8px;">Clique no <i class="far fa-heart"></i> nos cards para favoritar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PLAYLISTS -->
        <div class="library-content" id="playlists">
            <button class="btn-modal btn-modal-primary btn-trigger-modal-playlist" style="margin-bottom:20px;">
                <i class="fas fa-plus"></i> Criar Nova Playlist
            </button>
            <div class="cards-container">
                <?php foreach ($playlists as $pl): ?>
                    <a href="/api/ver_playlist.php?id=<?= $pl['id'] ?>" class="card" style="text-decoration:none;">
                        <div style="width:100%;height:200px;background:linear-gradient(135deg, var(--color-primary), var(--color-secondary));display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-music" style="font-size:3rem;color:#fff;"></i>
                        </div>
                        <div class="card-content">
                            <h3><?= htmlspecialchars($pl['nome']) ?></h3>
                            <p><?= $pl['total_musicas'] ?> musica(s)</p>
                        </div>
                    </a>
                <?php endforeach; ?>

                <?php if (empty($playlists)): ?>
                    <div class="empty-library-message">
                        <i class="fas fa-list"></i>
                        <p>Nenhuma playlist criada.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- UPLOADS -->
        <div class="library-content" id="minhas-musicas">
            <h2 class="section-title"><i class="fas fa-cloud-upload-alt"></i> Seus uploads</h2>
            <div class="cards-container">
                <?php foreach ($minhas_musicas as $m):
                    $capa = resolverMidia($m['caminho_capa'], '/assets/img/capa-padrao.svg');
                    $audioUrl = resolverMidia($m['caminho_arquivo']);
                    $isFav = in_array(strval($m['id']), $_SESSION['favoritas_ids']);
                ?>
                    <div class="card"
                         data-id="<?= $m['id'] ?>"
                         data-audio="<?= htmlspecialchars($audioUrl) ?>"
                         data-titulo="<?= htmlspecialchars($m['titulo']) ?>"
                         data-artista="<?= htmlspecialchars($m['nome_artista'] ?: 'Desconhecido') ?>"
                         data-capa="<?= htmlspecialchars($capa) ?>">
                        <img src="<?= htmlspecialchars($capa) ?>" class="card-img" onerror="this.src='/assets/img/capa-padrao.svg'">
                        <div class="card-content">
                            <h3><?= htmlspecialchars($m['titulo']) ?></h3>
                            <p><?= htmlspecialchars($m['nome_artista'] ?: 'Desconhecido') ?></p>
                        </div>
                        <button class="btn-add-playlist" data-id="<?= $m['id'] ?>" title="Adicionar a playlist"><i class="fas fa-plus"></i></button>
                        <button class="btn-fav" data-id="<?= $m['id'] ?>"><i class="<?= $isFav ? 'fas' : 'far' ?> fa-heart <?= $isFav ? 'favorito' : '' ?>"></i></button>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($minhas_musicas)): ?>
                    <div class="empty-library-message">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Voce ainda nao enviou nenhuma musica.</p>
                    </div>
                <?php endif; ?>
            </div>
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
