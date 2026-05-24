<?php
require_once __DIR__ . '/../includes/init.php';

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

// Inicializa a sessão de favoritas
if (!isset($_SESSION['favoritas_ids'])) {
    $favoritas_db = buscarTodos("SELECT id_musica FROM favoritos WHERE id_usuario = ?", [$id_usuario_logado]);
    $_SESSION['favoritas_ids'] = array_column($favoritas_db, 'id_musica');
} else {
    $_SESSION['favoritas_ids'] = is_array($_SESSION['favoritas_ids']) ? $_SESSION['favoritas_ids'] : [];
}

// Consulta otimizada para musicas_recentes e mais_curtidas
$musicas = buscarTodos("
    SELECT m.*, art.nome as nome_artista, 
           (SELECT COUNT(f.id_musica) FROM favoritos f WHERE f.id_musica = m.id) as total_favoritos,
           (SELECT titulo FROM albuns a WHERE a.id = m.id_album) as album_titulo
    FROM musicas m
    LEFT JOIN artistas art ON m.id_artista = art.id
    ORDER BY m.data_upload DESC
");

// Separar em arrays
$musicas_recentes = array_slice($musicas, 0, 10);
$mais_curtidas = [];
usort($musicas, function ($a, $b) {
    return $b['total_favoritos'] - $a['total_favoritos'];
});
$mais_curtidas = array_slice($musicas, 0, 5);
$tocados_recentemente = array_slice($musicas_recentes, 0, 5);

// Buscar playlists do usuário
$minhas_playlists = buscarTodos("SELECT id, nome FROM playlists WHERE id_usuario_criador = ? ORDER BY nome ASC", [$id_usuario_logado]);

// Função para verificar capa da música
function verificarCapa($caminho)
{
    $caminhoCompleto = '../' . $caminho;
    return !empty($caminho) && file_exists($caminhoCompleto) ? $caminhoCompleto : 'capa-padrao.jpg';
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
            <a href="dashboard.php" class="menu-item active"><i class="fas fa-home"></i> <span>Início</span></a>
            <a href="explorar.php" class="menu-item"><i class="fas fa-search"></i> <span>Explorar</span></a>
            <a href="biblioteca.php" class="menu-item"><i class="fas fa-book"></i> <span>Biblioteca</span></a>
            <?php if ($usuario_permitido): ?>
                <a href="/api/adicionar_musica.php" class="menu-item"><i class="fas fa-plus-circle"></i> <span>Adicionar Música</span></a>
            <?php endif; ?>
            <?php if (isset($usuario['possui_estrela_apoio']) && $usuario['possui_estrela_apoio']): ?>
                <a href="iniciar_doacao.php" class="menu-item">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span>Apoiador</span>
                    <i class="fas fa-star" title="Você é um Apoiador!"></i>
                </a>
            <?php else: ?>
                <a href="iniciar_doacao.php" class="menu-item">
                    <i class="fas fa-hand-holding-dollar"></i> <span>Apoiar</span>
                </a>
            <?php endif; ?>
        </div>
        <div class="playlists">
            <h3>
                Playlists
                <button class="btn-trigger-modal-playlist"
                    title="Criar Nova Playlist">
                    <i class="fas fa-plus"></i>
                </button>
            </h3>
            <div>
                <?php if (!empty($minhas_playlists)): ?>
                    <?php foreach ($minhas_playlists as $playlist_sidebar): ?>
                        <a href="ver_playlist.php?id=<?= $playlist_sidebar['id'] ?>" class="playlist-item" data-playlist-id="<?= $playlist_sidebar['id'] ?>">
                            <i class="fas fa-list-music"></i>
                            <span><?= htmlspecialchars($playlist_sidebar['nome']) ?></span>
                        </a>
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
                <p>Descubra novas batidas ou curta seus clássicos.</p>
            </div>
            <div class="user-menu">
                <a href="configuracoes.php" class="btn" title="Configurações"><i class="fas fa-cog"></i></a>
                <a href="logout.php" class="btn" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
                <div class="user-avatar">
                    <?php
                    $avatarPath = $usuario['avatar'] ?? 'avatar-padrao.jpg';
                    $is_url = strpos($avatarPath, 'http') === 0;
                    $final_avatar_src = $avatarPath;
                    $use_placeholder = false;

                    if (!$is_url && !file_exists($avatarPath)) {
                        $final_avatar_src = 'avatar-padrao.jpg';
                        $use_placeholder = true;
                    } else if ($is_url && $avatarPath === 'avatar-padrao.jpg') {
                        $use_placeholder = true;
                    }

                    if ($use_placeholder || $final_avatar_src === 'avatar-padrao.jpg'): ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($usuario['nome_usuario'], 0, 1)) ?>
                        </div>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($final_avatar_src) ?>" alt="Avatar">
                    <?php endif; ?>

                    <?php if (isset($usuario['possui_estrela_apoio']) && $usuario['possui_estrela_apoio']): ?>
                        <i class="fas fa-star" title="Apoiador!"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-fire"></i> Mais Curtidas</h2>
        <div class="cards-container">
            <?php foreach ($mais_curtidas as $musica):
                $isFavorita = in_array($musica['id'], $_SESSION['favoritas_ids']);
                $caminhoCapa = verificarCapa($musica['caminho_capa']);
            ?>
                <div class="card"
                    data-id="<?= $musica['id'] ?>"
                    data-audio="<?= htmlspecialchars('../' . $musica['caminho_arquivo']) ?>"
                    data-titulo="<?= htmlspecialchars($musica['titulo']) ?>"
                    data-artista="<?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?>"
                    data-capa="<?= htmlspecialchars($caminhoCapa) ?>">
                    <img src="<?= htmlspecialchars($caminhoCapa) ?>" class="card-img" alt="<?= htmlspecialchars($musica['titulo']) ?>">
                    <div class="card-content">
                        <h3><?= htmlspecialchars($musica['titulo']) ?></h3>
                        <p><?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></p>
                    </div>
                    <span class="card-fav-count"><i class="fas fa-heart"></i> <?= $musica['total_favoritos'] ?></span>
                    <button class="btn-fav" data-id="<?= $musica['id'] ?>" title="Favoritar"><i class="fa<?= $isFavorita ? 's' : 'r' ?> fa-heart <?= $isFavorita ? 'favorito' : '' ?>"></i></button>
                </div>
            <?php endforeach; ?>
            <?php if (empty($mais_curtidas)): ?>
                <p>Nenhuma música curtida ainda.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-title"><i class="fas fa-bolt"></i> Novos Lançamentos</h2>
        <div class="cards-container">
            <?php foreach ($musicas_recentes as $musica):
                $isFavorita = in_array($musica['id'], $_SESSION['favoritas_ids']);
                $caminhoCapa = verificarCapa($musica['caminho_capa']);
            ?>
                <div class="card"
                    data-id="<?= $musica['id'] ?>"
                    data-audio="<?= htmlspecialchars('../' . $musica['caminho_arquivo']) ?>"
                    data-titulo="<?= htmlspecialchars($musica['titulo']) ?>"
                    data-artista="<?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?>"
                    data-capa="<?= htmlspecialchars($caminhoCapa) ?>">
                    <img src="<?= htmlspecialchars($caminhoCapa) ?>" class="card-img" alt="<?= htmlspecialchars($musica['titulo']) ?>">
                    <div class="card-content">
                        <h3><?= htmlspecialchars($musica['titulo']) ?></h3>
                        <p><?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></p>
                    </div>
                    <button class="btn-fav" data-id="<?= $musica['id'] ?>" title="Favoritar"><i class="fa<?= $isFavorita ? 's' : 'r' ?> fa-heart <?= $isFavorita ? 'favorito' : '' ?>"></i></button>
                </div>
            <?php endforeach; ?>
            <?php if (empty($musicas_recentes)): ?>
                <p>Nenhum lançamento recente.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-title"><i class="fas fa-history"></i> Tocados Recentemente</h2>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Música</th>
                        <th>Artista</th>
                        <th>Álbum</th>
                        <th>Duração</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tocados_recentemente as $index => $musica):
                        $isFavorita = in_array($musica['id'], $_SESSION['favoritas_ids']);
                        $caminhoCapa = verificarCapa($musica['caminho_capa']);
                    ?>
                        <tr class="song-row"
                            data-id="<?= $musica['id'] ?>"
                            data-audio="<?= htmlspecialchars('../' . $musica['caminho_arquivo']) ?>"
                            data-titulo="<?= htmlspecialchars($musica['titulo']) ?>"
                            data-artista="<?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?>"
                            data-capa="<?= htmlspecialchars($caminhoCapa) ?>">
                            <td><?= $index + 1 ?></td>
                            <td>
                                <div class="song">
                                    <img src="<?= htmlspecialchars($caminhoCapa) ?>" class="song-img" alt="<?= htmlspecialchars($musica['titulo']) ?>">
                                    <div><strong><?= htmlspecialchars($musica['titulo']) ?></strong><br><small><?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></small></div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></td>
                            <td><?= htmlspecialchars($musica['album_titulo']) ?></td>
                            <td><?= isset($musica['duracao']) && is_numeric($musica['duracao']) ? gmdate("i:s", $musica['duracao']) : 'N/A' ?></td>
                            <td><button class="btn-fav" data-id="<?= $musica['id'] ?>" title="Favoritar"><i class="fa<?= $isFavorita ? 's' : 'r' ?> fa-heart <?= $isFavorita ? 'favorito' : '' ?>"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tocados_recentemente)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">Nenhuma música tocada recentemente.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="player" id="player">
        <div class="player-top">
            <div class="player-song">
                <img src="capa-padrao.jpg" class="player-song-img" id="player-img" alt="Capa da música">
                <div class="player-song-details">
                    <h4 id="player-title">Selecione uma música</h4>
                    <p id="player-artist">DuckMusic</p>
                </div>
            </div>
            <button class="player-fav-btn" id="player-fav-btn" data-id="" title="Favoritar"><i class="far fa-heart"></i></button>
        </div>

        <div class="player-controls">
            <button id="btn-prev" title="Anterior"><i class="fas fa-backward-step"></i></button>
            <button id="btn-play" title="Tocar"><i class="fas fa-play"></i></button>
            <button id="btn-pause" title="Pausar" style="display:none;"><i class="fas fa-pause"></i></button>
            <button id="btn-next" title="Próxima"><i class="fas fa-forward-step"></i></button>
        </div>

        <div class="progress-container">
            <span class="time" id="current-time">0:00</span>
            <div class="progress-bar" id="progress-bar">
                <div class="progress" id="progress"></div>
            </div>
            <span class="time" id="duration">0:00</span>

            <div class="volume-controls">
                <i class="fas fa-volume-high" id="volume-icon" title="Mutar/Desmutar"></i>
                <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.8" title="Volume">
            </div>
        </div>
        <audio id="audio-element" src=""></audio>
    </div>

    <div id="modalCriarPlaylist" class="modal">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Nova Playlist</h2>
                <span class="close-btn" id="closeModalCriarPlaylist">&times;</span>
            </div>
            <form id="formCriarPlaylist">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome da Playlist</label>
                        <input type="text" name="playlist_nome" id="playlist_nome" placeholder="Ex: Melhores do Rock"
                            required>
                    </div>
                    <div id="modalFeedback"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-secondary"
                        id="btnCancelarCriarPlaylist">Cancelar</button>
                    <button type="submit" class="btn-modal btn-modal-primary" id="btnSalvarPlaylist">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>
    <script>
        window.APP_DATA = {
            favoritasIds: <?php echo json_encode($_SESSION['favoritas_ids'] ?? []); ?>,
            currentVolume: <?php echo json_encode($_SESSION['user_volume'] ?? 0.8); ?>
        };
    </script>
    <script src="/js/dashboard.js"></script>
</body>

</html>