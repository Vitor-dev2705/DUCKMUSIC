<?php
require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

$playlist_id = $_POST['playlist_id'] ?? null;
$musica_id = $_POST['musica_id'] ?? null;

if (!$playlist_id || !$musica_id) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

// Verificar se o usuário é dono da playlist
$playlist = buscarUm("SELECT id_usuario_criador FROM playlists WHERE id = ?", [$playlist_id]);

if (!$playlist || $playlist['id_usuario_criador'] != $_SESSION['id_usuario']) {
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para modificar esta playlist']);
    exit();
}

try {
    $stmt = $db->prepare("DELETE FROM musicas_playlists WHERE id_playlist = ? AND id_musica = ?");
    $stmt->execute([$playlist_id, $musica_id]);
    
    echo json_encode(['success' => true, 'message' => 'Música removida da playlist']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao remover música da playlist']);
}
?>