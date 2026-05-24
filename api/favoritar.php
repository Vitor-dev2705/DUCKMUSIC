<?php
ob_start();
require_once __DIR__ . '/../includes/init.php';
ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario nao logado']);
    exit;
}

$musica_id = $_POST['musica_id'] ?? null;
if (!$musica_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID da musica invalido']);
    exit;
}

try {
    $id_usuario = $_SESSION['id_usuario'];
    $strId = strval($musica_id);

    // === DEEZER TRACK (dz_XXXXX) ===
    if (strpos($strId, 'dz_') === 0) {
        $titulo  = trim($_POST['titulo'] ?? '');
        $artista = trim($_POST['artista'] ?? '');
        $capa    = trim($_POST['capa'] ?? '');
        $audio   = trim($_POST['audio'] ?? '');

        $existe = buscarUm("SELECT 1 FROM favoritos_deezer WHERE id_usuario = ? AND deezer_id = ?", [$id_usuario, $strId]);

        if ($existe) {
            excluir("DELETE FROM favoritos_deezer WHERE id_usuario = ? AND deezer_id = ?", [$id_usuario, $strId]);
            echo json_encode(['status' => 'success', 'favoritado' => false]);
        } else {
            inserir("INSERT INTO favoritos_deezer (id_usuario, deezer_id, titulo, artista, capa_url, audio_url) VALUES (?, ?, ?, ?, ?, ?)",
                [$id_usuario, $strId, $titulo, $artista, $capa, $audio]);
            echo json_encode(['status' => 'success', 'favoritado' => true]);
        }

    // === LOCAL TRACK (numeric ID) ===
    } else {
        if (!is_numeric($strId)) {
            echo json_encode(['status' => 'error', 'message' => 'ID invalido']);
            exit;
        }
        $id_musica = intval($strId);

        $existe = buscarUm("SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_musica = ?", [$id_usuario, $id_musica]);

        if ($existe) {
            excluir("DELETE FROM favoritos WHERE id_usuario = ? AND id_musica = ?", [$id_usuario, $id_musica]);
            echo json_encode(['status' => 'success', 'favoritado' => false]);
        } else {
            inserir("INSERT INTO favoritos (id_usuario, id_musica) VALUES (?, ?)", [$id_usuario, $id_musica]);
            echo json_encode(['status' => 'success', 'favoritado' => true]);
        }
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}

ob_end_flush();
exit;
