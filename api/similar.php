<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/deezer.php';

header('Content-Type: application/json');

$artista = trim($_GET['artista'] ?? '');
if (empty($artista)) {
    echo json_encode([]);
    exit;
}

$tracks = [];

// 1. Buscar artista no Deezer
$busca = deezerGet('search/artist', ['q' => $artista, 'limit' => 1]);
$artistaInfo = $busca['data'][0] ?? null;

if ($artistaInfo) {
    $artistId = $artistaInfo['id'];

    // 2. Pegar top tracks do mesmo artista
    $topData = deezerGet("artist/{$artistId}/top", ['limit' => 10]);
    $topTracks = formatarTracks($topData['data'] ?? []);
    $tracks = array_merge($tracks, $topTracks);

    // 3. Buscar artistas relacionados e pegar top tracks deles
    $related = deezerGet("artist/{$artistId}/related", ['limit' => 5]);
    if (!empty($related['data'])) {
        foreach ($related['data'] as $rel) {
            $relTop = deezerGet("artist/{$rel['id']}/top", ['limit' => 3]);
            $relTracks = formatarTracks($relTop['data'] ?? []);
            $tracks = array_merge($tracks, $relTracks);
            if (count($tracks) >= 20) break;
        }
    }
}

// Se nao encontrou artista, busca por termo
if (empty($tracks)) {
    $tracks = deezerBuscar($artista, 15);
}

// Embaralha e limita
shuffle($tracks);
$tracks = array_slice($tracks, 0, 15);

// Formata para o JS (mesma estrutura do queue)
$result = [];
foreach ($tracks as $t) {
    $result[] = [
        'id'      => $t['id'],
        'audio'   => $t['caminho_arquivo'],
        'titulo'  => $t['titulo'],
        'artista' => $t['nome_artista'],
        'capa'    => $t['caminho_capa']
    ];
}

echo json_encode($result);
