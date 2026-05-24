<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sessao expirada.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metodo invalido.']);
    exit;
}

$id_playlist = intval($_POST['id_playlist'] ?? 0);
$musica_id   = trim($_POST['musica_id'] ?? '');
$id_usuario  = $_SESSION['id_usuario'];

if (!$id_playlist || empty($musica_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
    exit;
}

// Verifica se a playlist pertence ao usuario
$playlist = buscarUm("SELECT id FROM playlists WHERE id = ? AND id_usuario_criador = ?", [$id_playlist, $id_usuario]);
if (!$playlist) {
    echo json_encode(['status' => 'error', 'message' => 'Playlist nao encontrada.']);
    exit;
}

try {
    // === DEEZER TRACK ===
    if (strpos($musica_id, 'dz_') === 0) {
        $titulo  = trim($_POST['titulo'] ?? '');
        $artista = trim($_POST['artista'] ?? '');
        $capa    = trim($_POST['capa'] ?? '');
        $audio   = trim($_POST['audio'] ?? '');

        $existe = buscarUm("SELECT 1 FROM playlist_deezer_tracks WHERE id_playlist = ? AND deezer_id = ?", [$id_playlist, $musica_id]);
        if ($existe) {
            echo json_encode(['status' => 'error', 'message' => 'Musica ja esta na playlist.']);
            exit;
        }

        inserir("INSERT INTO playlist_deezer_tracks (id_playlist, deezer_id, titulo, artista, capa_url, audio_url, id_usuario_adicionou) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$id_playlist, $musica_id, $titulo, $artista, $capa, $audio, $id_usuario]);

    // === LOCAL TRACK ===
    } else {
        $id_musica = intval($musica_id);
        $existe = buscarUm("SELECT 1 FROM musicas_playlists WHERE id_playlist = ? AND id_musica = ?", [$id_playlist, $id_musica]);
        if ($existe) {
            echo json_encode(['status' => 'error', 'message' => 'Musica ja esta na playlist.']);
            exit;
        }

        inserir("INSERT INTO musicas_playlists (id_playlist, id_musica, id_usuario_adicionou) VALUES (?, ?, ?)",
            [$id_playlist, $id_musica, $id_usuario]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Adicionada a playlist!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
exit;
