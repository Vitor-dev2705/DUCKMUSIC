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
    header("Location: /auth/login.php");
    exit();
}

$id_usuario_logado = $_SESSION['id_usuario'];
$playlist_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$playlist_id) {
    header("Location: /app.php");
    exit();
}

$playlist = buscarUm("SELECT * FROM playlists WHERE id = ? AND id_usuario_criador = ?", [$playlist_id, $id_usuario_logado]);
if (!$playlist) {
    header("Location: /app.php");
    exit();
}

// Musicas locais da playlist
$musicas_locais = buscarTodos("
    SELECT m.*, art.nome as nome_artista, alb.titulo as album_titulo
    FROM musicas_playlists mp
    JOIN musicas m ON mp.id_musica = m.id
    LEFT JOIN artistas art ON m.id_artista = art.id
    LEFT JOIN albuns alb ON m.id_album = alb.id
    WHERE mp.id_playlist = ?
    ORDER BY mp.data_adicao ASC
", [$playlist_id]);

// Musicas Deezer da playlist
$musicas_deezer = buscarTodos("
    SELECT deezer_id, titulo, artista, capa_url, audio_url, data_adicao
    FROM playlist_deezer_tracks
    WHERE id_playlist = ?
    ORDER BY data_adicao ASC
", [$playlist_id]);

$total_musicas = count($musicas_locais) + count($musicas_deezer);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($playlist['nome']) ?> - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/dashboard.css">
</head>
<body>

<div class="main-content">
    <div class="header">
        <div>
            <h1><i class="fas fa-music"></i> <?= htmlspecialchars($playlist['nome']) ?></h1>
            <p><?= $total_musicas ?> musica(s) na playlist</p>
        </div>
    </div>

    <div class="cards-container">
        <?php
        // Render local tracks
        foreach ($musicas_locais as $m):
            $capa = resolverMidia($m['caminho_capa'], '/assets/img/capa-padrao.svg');
            $audioUrl = resolverMidia($m['caminho_arquivo']);
        ?>
            <div class="card"
                 data-id="<?= $m['id'] ?>"
                 data-audio="<?= htmlspecialchars($audioUrl) ?>"
                 data-titulo="<?= htmlspecialchars($m['titulo']) ?>"
                 data-artista="<?= htmlspecialchars($m['nome_artista'] ?: 'Desconhecido') ?>"
                 data-capa="<?= htmlspecialchars($capa) ?>">
                <div class="card-img-wrap">
                    <img src="<?= htmlspecialchars($capa) ?>" class="card-img" alt="<?= htmlspecialchars($m['titulo']) ?>" onerror="this.src='/assets/img/capa-padrao.svg'">
                    <div class="card-play-btn"><i class="fas fa-play"></i></div>
                </div>
                <div class="card-content">
                    <h3><?= htmlspecialchars($m['titulo']) ?></h3>
                    <p><?= htmlspecialchars($m['nome_artista'] ?: 'Desconhecido') ?></p>
                </div>
                <button class="btn-fav" data-id="<?= $m['id'] ?>" title="Favoritar"><i class="far fa-heart"></i></button>
            </div>
        <?php endforeach; ?>

        <?php
        // Render Deezer tracks
        foreach ($musicas_deezer as $dz):
        ?>
            <div class="card"
                 data-id="<?= htmlspecialchars($dz['deezer_id']) ?>"
                 data-audio="<?= htmlspecialchars($dz['audio_url']) ?>"
                 data-titulo="<?= htmlspecialchars($dz['titulo']) ?>"
                 data-artista="<?= htmlspecialchars($dz['artista']) ?>"
                 data-capa="<?= htmlspecialchars($dz['capa_url']) ?>">
                <div class="card-img-wrap">
                    <img src="<?= htmlspecialchars($dz['capa_url']) ?>" class="card-img" alt="<?= htmlspecialchars($dz['titulo']) ?>" onerror="this.src='/assets/img/capa-padrao.svg'">
                    <div class="card-play-btn"><i class="fas fa-play"></i></div>
                </div>
                <div class="card-content">
                    <h3><?= htmlspecialchars($dz['titulo']) ?></h3>
                    <p><?= htmlspecialchars($dz['artista']) ?></p>
                </div>
                <button class="btn-fav" data-id="<?= htmlspecialchars($dz['deezer_id']) ?>" title="Favoritar"><i class="far fa-heart"></i></button>
            </div>
        <?php endforeach; ?>

        <?php if ($total_musicas === 0): ?>
            <div class="empty-library-message">
                <i class="fas fa-music"></i>
                <p>Nenhuma musica adicionada ainda.</p>
                <p style="font-size:0.85rem; margin-top:8px; color:var(--sp-text-subdued);">Explore e adicione musicas pelo botao <i class="fas fa-plus"></i> nos cards.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    window.APP_DATA = window.APP_DATA || {};
    window.APP_DATA.favoritasIds = window.APP_DATA.favoritasIds || [];
</script>
</body>
</html>
