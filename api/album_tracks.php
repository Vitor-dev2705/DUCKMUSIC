<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/deezer.php';

header('Content-Type: application/json');

$albumId = $_GET['id'] ?? '';
if (empty($albumId) || !is_numeric($albumId)) {
    echo json_encode([]);
    exit;
}

$data = deezerGet("album/{$albumId}/tracks", ['limit' => 50]);
$albumInfo = deezerGet("album/{$albumId}");

$coverMedium = $albumInfo['cover_medium'] ?? '/assets/img/capa-padrao.svg';
$coverBig = $albumInfo['cover_big'] ?? $coverMedium;
$artistName = $albumInfo['artist']['name'] ?? 'Artista';

$tracks = [];
if (!empty($data['data'])) {
    foreach ($data['data'] as $t) {
        if (empty($t['preview'])) continue;
        $tracks[] = [
            'id'      => 'dz_' . $t['id'],
            'audio'   => $t['preview'],
            'titulo'  => $t['title'] ?? $t['title_short'] ?? 'Sem titulo',
            'artista' => $t['artist']['name'] ?? $artistName,
            'capa'    => $coverMedium
        ];
    }
}

echo json_encode($tracks);
