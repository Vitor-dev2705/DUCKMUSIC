<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/deezer.php';

header('Content-Type: application/json');

$trackId = $_GET['id'] ?? '';
if (empty($trackId)) {
    echo json_encode(['erro' => 'ID ausente']);
    exit;
}

// Remove prefixo dz_
$dzId = str_replace('dz_', '', $trackId);
if (!is_numeric($dzId)) {
    echo json_encode(['erro' => 'ID invalido']);
    exit;
}

$data = deezerGet("track/{$dzId}");
if (!$data || isset($data['error']) || empty($data['album']['id'])) {
    echo json_encode(['erro' => 'Album nao encontrado']);
    exit;
}

echo json_encode([
    'album_id' => $data['album']['id'],
    'album_title' => $data['album']['title'] ?? ''
]);
