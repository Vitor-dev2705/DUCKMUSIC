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

$albumId = $_GET['id'] ?? '';
if (empty($albumId) || !is_numeric($albumId)) {
    echo '<div class="main-content"><p style="padding:40px;text-align:center;color:#b3b3b3">Album nao encontrado.</p></div>';
    exit();
}

$albumInfo = deezerGet("album/{$albumId}");
$tracksData = deezerGet("album/{$albumId}/tracks", ['limit' => 50]);

if (!$albumInfo || isset($albumInfo['error'])) {
    echo '<div class="main-content"><p style="padding:40px;text-align:center;color:#b3b3b3">Album nao encontrado.</p></div>';
    exit();
}

$albumTitle = htmlspecialchars($albumInfo['title'] ?? 'Album');
$artistName = htmlspecialchars($albumInfo['artist']['name'] ?? 'Artista');
$artistId = $albumInfo['artist']['id'] ?? 0;
$coverBig = $albumInfo['cover_xl'] ?? $albumInfo['cover_big'] ?? $albumInfo['cover_medium'] ?? '/assets/img/capa-padrao.svg';
$coverMedium = $albumInfo['cover_medium'] ?? $coverBig;
$releaseDate = $albumInfo['release_date'] ?? '';
$releaseYear = $releaseDate ? date('Y', strtotime($releaseDate)) : '';
$nbTracks = $albumInfo['nb_tracks'] ?? 0;
$duration = $albumInfo['duration'] ?? 0;
$recordType = ucfirst($albumInfo['record_type'] ?? 'album');
$artistPic = $albumInfo['artist']['picture_small'] ?? '';

$tracks = [];
if (!empty($tracksData['data'])) {
    foreach ($tracksData['data'] as $t) {
        if (empty($t['preview'])) continue;
        $tracks[] = [
            'id' => 'dz_' . $t['id'],
            'titulo' => $t['title'] ?? $t['title_short'] ?? 'Sem titulo',
            'artista' => $t['artist']['name'] ?? $artistName,
            'capa' => $coverMedium,
            'audio' => $t['preview'],
            'duracao' => $t['duration'] ?? 30
        ];
    }
}

function fmtDuration($s) {
    $h = floor($s / 3600);
    $m = floor(($s % 3600) / 60);
    if ($h > 0) return "{$h} h " . ($m > 0 ? "{$m} min" : '');
    return "{$m} min";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $albumTitle ?> - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/dashboard.css">
</head>
<body>
<div class="main-content">

    <!-- Botao Voltar -->
    <button class="btn-back" onclick="history.back()" title="Voltar"><i class="fas fa-arrow-left"></i></button>

    <!-- Header do Album -->
    <div class="album-header">
        <img src="<?= htmlspecialchars($coverBig) ?>" class="album-header-cover" alt="<?= $albumTitle ?>" onerror="this.src='/assets/img/capa-padrao.svg'">
        <div class="album-header-info">
            <span class="album-type"><?= $recordType ?></span>
            <h1 class="album-title"><?= $albumTitle ?></h1>
            <div class="album-meta">
                <?php if ($artistPic): ?>
                    <img src="<?= htmlspecialchars($artistPic) ?>" class="album-artist-pic" alt="">
                <?php endif; ?>
                <a href="/paginas/artista.php?nome=<?= urlencode($artistName) ?>" class="album-artist-link"><?= $artistName ?></a>
                <?php if ($releaseYear): ?>
                    <span class="album-meta-dot">·</span>
                    <span><?= $releaseYear ?></span>
                <?php endif; ?>
                <span class="album-meta-dot">·</span>
                <span><?= $nbTracks ?> musicas, <?= fmtDuration($duration) ?></span>
            </div>
        </div>
    </div>

    <!-- Acoes -->
    <div class="artist-actions">
        <button class="artist-play-btn" id="album-play-all" title="Tocar todas"><i class="fas fa-play"></i></button>
        <button class="artist-shuffle-btn" id="album-shuffle" title="Aleatorio"><i class="fas fa-shuffle"></i></button>
    </div>

    <!-- Lista de Musicas -->
    <?php if (!empty($tracks)): ?>
        <div class="artist-tracks">
            <?php foreach ($tracks as $i => $t): ?>
                <div class="artist-track-row"
                     data-id="<?= htmlspecialchars($t['id']) ?>"
                     data-audio="<?= htmlspecialchars($t['audio']) ?>"
                     data-titulo="<?= htmlspecialchars($t['titulo']) ?>"
                     data-artista="<?= htmlspecialchars($t['artista']) ?>"
                     data-capa="<?= htmlspecialchars($t['capa']) ?>">
                    <span class="track-num"><?= $i + 1 ?></span>
                    <div class="track-info">
                        <h4><?= htmlspecialchars($t['titulo']) ?></h4>
                        <p><?= htmlspecialchars($t['artista']) ?></p>
                    </div>
                    <button class="btn-fav track-fav" data-id="<?= htmlspecialchars($t['id']) ?>" title="Favoritar"><i class="far fa-heart"></i></button>
                    <span class="track-duration"><?= gmdate('i:s', $t['duracao']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#b3b3b3;padding:20px 0;">Nenhuma musica disponivel neste album.</p>
    <?php endif; ?>

    <!-- Info extra -->
    <div class="album-footer">
        <?php if ($releaseDate): ?>
            <p class="album-release"><?= date('d \d\e F \d\e Y', strtotime($releaseDate)) ?></p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
