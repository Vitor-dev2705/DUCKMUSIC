<?php
require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario_logado = $_SESSION['id_usuario'];
$usuario = buscarUm("SELECT * FROM usuarios WHERE id = ?", [$id_usuario_logado]);

// Buscar músicas favoritas do usuário
$favoritas = buscarTodos("
    SELECT m.*, art.nome as nome_artista, f.data_favoritado
    FROM musicas m
    JOIN favoritos f ON m.id = f.id_musica
    LEFT JOIN artistas art ON m.id_artista = art.id
    WHERE f.id_usuario = ?
    ORDER BY f.data_favoritado DESC
", [$id_usuario_logado]);

// Buscar playlists do usuário
$playlists = buscarTodos("
    SELECT p.*,
            COUNT(mp.id_musica) as total_musicas
    FROM playlists p
    LEFT JOIN musicas_playlists mp ON p.id = mp.id_playlist
    WHERE p.id_usuario_criador = ?
    GROUP BY p.id
    ORDER BY p.data_criacao DESC
", [$id_usuario_logado]);

// Buscar músicas enviadas pelo usuário
$minhas_musicas = buscarTodos("
    SELECT m.*, art.nome as nome_artista
    FROM musicas m
    LEFT JOIN artistas art ON m.id_artista = art.id
    WHERE m.id_usuario_upload = ?
    ORDER BY m.data_upload DESC
", [$id_usuario_logado]);

// Atualizar a sessão de favoritos
$favoritas_ids_db = buscarTodos("SELECT id_musica FROM favoritos WHERE id_usuario = ?", [$id_usuario_logado]);
$_SESSION['favoritas_ids'] = array_column($favoritas_ids_db ?? [], 'id_musica');

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Biblioteca - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/biblioteca.css">
</head>

<body>
    <div class="main-container">
        <div class="sidebar">
            <div class="logo"><i class="fas fa-headphones-simple"></i><span>DuckMusic</span></div>
            <div class="menu">
                <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> <span>Início</span></a>
                <a href="explorar.php" class="menu-item"><i class="fas fa-search"></i> <span>Explorar</span></a>
                <a href="biblioteca.php" class="menu-item active"><i class="fas fa-book"></i>
                    <span>Biblioteca</span></a>
                <?php if ($usuario['nivel_admin'] >= 1): ?>
                    <a href="adicionar_musica.php" class="menu-item"><i class="fas fa-plus-circle"></i> <span>Adicionar
                            Música</span></a>
                <?php endif; ?>
                <a href="iniciar_doacao.php" class="menu-item">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span><?= ($usuario['possui_estrela_apoio'] ?? false) ? 'Apoiador <i class="fas fa-star"></i>' : 'Apoiar' ?></span>
                </a>
            </div>
            <div class="playlists">
                <h3>
                    Playlists
                    <button id="btnAbrirModalCriarPlaylistSidebar" class="btn-trigger-modal-playlist"
                        title="Criar Nova Playlist">
                        <i class="fas fa-plus"></i>
                    </button>
                </h3>

                <div id="listaMinhasPlaylists">
                    <?php if (!empty($playlists)): ?>
                        <?php foreach ($playlists as $playlist_sidebar): ?>
                            <a href="ver_playlist.php?id=<?= (int)$playlist_sidebar['id'] ?>" class="playlist-item">
                                <i class="fas fa-music"></i> <span><?= htmlspecialchars($playlist_sidebar['nome']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="sem-playlists-aviso">
                            <p>Nenhuma playlist criada.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="greeting">
                    <h1>Minha Biblioteca</h1>
                    <p>Gerencie sua coleção musical.</p>
                </div>
                <div class="user-menu">
                    <a href="configuracoes.php" class="btn"><i class="fas fa-cog"></i></a>
                    <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i></a>
                    <div class="user-avatar">
                        <?php if (empty($usuario['avatar']) || $usuario['avatar'] == 'avatar-padrao.jpg'): ?>
                            <div class="avatar-placeholder"><?= strtoupper(substr($usuario['nome_usuario'], 0, 1)) ?></div>
                        <?php else: ?>
                            <img src="../<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="library-tabs">
                <div class="library-tab active" data-tab="favoritas">Músicas Favoritas</div>
                <div class="library-tab" data-tab="playlists">Minhas Playlists</div>
                <div class="library-tab" data-tab="minhas-musicas">Meus Uploads</div>
            </div>

            <div class="library-content active" id="favoritas">
                <h2 class="section-title"><i class="fas fa-heart"></i> Suas favoritas</h2>
                <div class="cards-container">
                    <?php if (!empty($favoritas)): ?>
                        <?php foreach ($favoritas as $musica):
                            $capa = !empty($musica['caminho_capa']) ? '../' . $musica['caminho_capa'] : '';
                        ?>
                            <div class="card" data-id="<?= $musica['id'] ?>" data-audio="../<?= $musica['caminho_arquivo'] ?>"
                                data-titulo="<?= htmlspecialchars($musica['titulo']) ?>"
                                data-artista="<?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?>"
                                data-capa="<?= $capa ?>">
                                <div class="card-img-container">
                                    <img src="<?= $capa ?>" class="card-img" onerror="this.src=''">
                                    <div class="card-overlay"><i class="fas fa-play"></i></div>
                                </div>
                                <div class="card-content">
                                    <h3 class="card-title"><?= htmlspecialchars($musica['titulo']) ?></h3>
                                    <p class="card-subtitle">
                                        <?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></p>
                                </div>
                                <button class="btn-fav" data-id="<?= $musica['id'] ?>">
                                    <i class="fas fa-heart favorito"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-library-message">
                            <i class="fas fa-heart-broken"></i>
                            <p>Você ainda não favoritou nenhuma música.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="library-content" id="playlists">
                <button class="create-playlist-btn btn-trigger-modal-playlist" id="btnAbrirModalCriarPlaylist">
                    <i class="fas fa-plus"></i> Criar Nova Playlist
                </button>
                <div class="cards-container">
                    <?php foreach ($playlists as $playlist): ?>
                        <a href="/paginas/ver_playlist.php?id=<?= $playlist['id'] ?>" class="card">
                            <div class="card-img-container">
                                <img src="" class="card-img"
                                    onerror="this.src=''">
                            </div>
                            <div class="card-content">
                                <h3 class="card-title"><?= htmlspecialchars($playlist['nome']) ?></h3>
                                <p class="card-subtitle"><?= $playlist['total_musicas'] ?> músicas</p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="library-content" id="minhas-musicas">
                <h2 class="section-title"><i class="fas fa-cloud-upload-alt"></i> Seus envios</h2>
                <div class="cards-container">
                    <?php foreach ($minhas_musicas as $musica):
                        $isFavorita = in_array($musica['id'], $_SESSION['favoritas_ids']);
                        $capa = !empty($musica['caminho_capa']) ? '../' . $musica['caminho_capa'] : '';
                    ?>
                        <div class="card" data-id="<?= $musica['id'] ?>" data-audio="../<?= $musica['caminho_arquivo'] ?>"
                            data-titulo="<?= htmlspecialchars($musica['titulo']) ?>"
                            data-artista="<?= htmlspecialchars($musica['nome_artista']) ?>" data-capa="<?= $capa ?>">
                            <div class="card-img-container">
                                <img src="<?= $capa ?>" class="card-img" onerror="this.src=''">
                                <div class="card-overlay"><i class="fas fa-play"></i></div>
                            </div>
                            <div class="card-content">
                                <h3 class="card-title"><?= htmlspecialchars($musica['titulo']) ?></h3>
                                <p class="card-subtitle">
                                    <?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></p>
                            </div>
                            <button class="btn-fav" data-id="<?= $musica['id'] ?>">
                                <i
                                    class="<?= $isFavorita ? 'fas' : 'far' ?> fa-heart <?= $isFavorita ? 'favorito' : '' ?>"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
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



    <script src="/js/dashboard.js"></script>
</body>

</html>