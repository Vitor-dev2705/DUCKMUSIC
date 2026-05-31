<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/deezer.php';

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

$nome_artista = trim($_GET['nome'] ?? '');
if (empty($nome_artista)) {
    echo '<div class="main-content"><p style="padding:40px;text-align:center;color:#b3b3b3">Artista nao encontrado.</p></div>';
    exit();
}

// Buscar artista na API Deezer
$busca = deezerGet('search/artist', ['q' => $nome_artista, 'limit' => 1]);
$artista = $busca['data'][0] ?? null;

$topTracks = [];
$albums = [];
$artistImg = '/assets/img/capa-padrao.svg';
$artistName = htmlspecialchars($nome_artista);
$fansCount = 0;

if ($artista) {
    $artistId = $artista['id'];
    $artistName = htmlspecialchars($artista['name']);
    $artistImg = $artista['picture_xl'] ?? $artista['picture_big'] ?? $artista['picture_medium'] ?? $artistImg;
    $fansCount = $artista['nb_fan'] ?? 0;

    // Top tracks
    $topData = deezerGet("artist/{$artistId}/top", ['limit' => 10]);
    $topTracks = formatarTracks($topData['data'] ?? []);

    // Albums
    $albumData = deezerGet("artist/{$artistId}/albums", ['limit' => 20]);
    $albums = $albumData['data'] ?? [];
}

function formatFans($n) {
    if ($n >= 1000000) return number_format($n / 1000000, 1, ',', '.') . ' mi';
    if ($n >= 1000) return number_format($n / 1000, 0, ',', '.') . ' mil';
    return number_format($n, 0, ',', '.');
}

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
        <div class="card-img-wrap">
            <img src="<?= $capa ?>" class="card-img" alt="<?= $titulo ?>" onerror="this.src='/assets/img/capa-padrao.svg'">
            <div class="card-play-btn"><i class="fas fa-play"></i></div>
        </div>
        <div class="card-content">
            <h3><?= $titulo ?></h3>
            <p><?= $artista ?></p>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $artistName ?> - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/dashboard.css">
</head>
<body>
<div class="main-content">

    <!-- Banner do Artista -->
    <div class="artist-banner" style="background-image: url('<?= htmlspecialchars($artistImg) ?>')">
        <div class="artist-banner-overlay">
            <div class="artist-banner-info">
                <h1 class="artist-name"><?= $artistName ?></h1>
                <?php if ($fansCount > 0): ?>
                    <p class="artist-fans"><?= formatFans($fansCount) ?> ouvintes mensais</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Acoes -->
    <div class="artist-actions">
        <button class="artist-play-btn" id="artist-play-all" title="Tocar todas"><i class="fas fa-play"></i></button>
        <button class="artist-shuffle-btn" id="artist-shuffle" title="Aleatorio"><i class="fas fa-shuffle"></i></button>
    </div>

    <!-- Populares -->
    <?php if (!empty($topTracks)): ?>
        <h2 class="section-title">Populares</h2>
        <div class="artist-tracks">
            <?php foreach ($topTracks as $i => $t): ?>
                <div class="artist-track-row"
                     data-id="<?= htmlspecialchars($t['id']) ?>"
                     data-audio="<?= htmlspecialchars($t['caminho_arquivo']) ?>"
                     data-titulo="<?= htmlspecialchars($t['titulo']) ?>"
                     data-artista="<?= htmlspecialchars($t['nome_artista']) ?>"
                     data-capa="<?= htmlspecialchars($t['caminho_capa']) ?>">
                    <span class="track-num"><?= $i + 1 ?></span>
                    <img src="<?= htmlspecialchars($t['caminho_capa']) ?>" class="track-cover" alt="" onerror="this.src='/assets/img/capa-padrao.svg'">
                    <div class="track-info">
                        <h4><?= htmlspecialchars($t['titulo']) ?></h4>
                        <?php if (!empty($t['album_titulo'])): ?>
                            <p><?= htmlspecialchars($t['album_titulo']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="track-duration"><?= gmdate('i:s', $t['duracao'] ?? 30) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Discografia -->
    <?php if (!empty($albums)): ?>
        <h2 class="section-title">Discografia</h2>
        <div class="cards-container">
            <?php foreach ($albums as $album):
                $albumCapa = $album['cover_medium'] ?? $album['cover'] ?? '/assets/img/capa-padrao.svg';
                $albumTitulo = htmlspecialchars($album['title'] ?? 'Album');
                $albumAno = '';
                if (!empty($album['release_date'])) {
                    $albumAno = date('Y', strtotime($album['release_date']));
                }
                $albumType = $album['record_type'] ?? 'album';
            ?>
                <div class="card album-card"
                     data-album-id="<?= $album['id'] ?? '' ?>"
                     data-artista="<?= $artistName ?>">
                    <div class="card-img-wrap">
                        <img src="<?= htmlspecialchars($albumCapa) ?>" class="card-img" alt="<?= $albumTitulo ?>" onerror="this.src='/assets/img/capa-padrao.svg'">
                        <div class="card-play-btn"><i class="fas fa-play"></i></div>
                    </div>
                    <div class="card-content">
                        <h3><?= $albumTitulo ?></h3>
                        <p><?= $albumAno ?> · <?= ucfirst($albumType) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($topTracks) && empty($albums)): ?>
        <div style="text-align:center;padding:60px 20px;color:#b3b3b3">
            <i class="fas fa-user-slash" style="font-size:3rem;margin-bottom:16px;display:block"></i>
            <h2>Artista nao encontrado</h2>
            <p>Nao encontramos "<?= $artistName ?>" no catalogo.</p>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
