<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Nao autenticado']);
    exit;
}

$userId = $_SESSION['id_usuario'];
$method = $_SERVER['REQUEST_METHOD'];

// Garantir que as colunas extras existem
try {
    $pdo = conectarBanco();
    $pdo->exec("ALTER TABLE historico_reproducao ADD COLUMN IF NOT EXISTS deezer_id VARCHAR(30) DEFAULT NULL");
    $pdo->exec("ALTER TABLE historico_reproducao ADD COLUMN IF NOT EXISTS titulo VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE historico_reproducao ADD COLUMN IF NOT EXISTS artista VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE historico_reproducao ADD COLUMN IF NOT EXISTS capa TEXT DEFAULT NULL");
    $pdo->exec("ALTER TABLE historico_reproducao ADD COLUMN IF NOT EXISTS audio TEXT DEFAULT NULL");
} catch (Exception $e) {
    // Colunas ja existem, ignora
}

if ($method === 'POST') {
    // Salvar musica no historico
    $data = json_decode(file_get_contents('php://input'), true);
    $trackId = $data['id'] ?? '';
    $titulo = $data['titulo'] ?? '';
    $artista = $data['artista'] ?? '';
    $capa = $data['capa'] ?? '';
    $audio = $data['audio'] ?? '';

    if (empty($trackId) || empty($titulo)) {
        echo json_encode(['erro' => 'Dados incompletos']);
        exit;
    }

    $isDeezer = strpos($trackId, 'dz_') === 0;
    $deezerIdVal = $isDeezer ? $trackId : null;
    $musicaIdVal = !$isDeezer && is_numeric($trackId) ? (int)$trackId : null;

    // Remove entrada anterior da mesma musica (evita duplicatas)
    if ($isDeezer) {
        atualizar("DELETE FROM historico_reproducao WHERE id_usuario = ? AND deezer_id = ?", [$userId, $deezerIdVal]);
    } else if ($musicaIdVal) {
        atualizar("DELETE FROM historico_reproducao WHERE id_usuario = ? AND id_musica = ?", [$userId, $musicaIdVal]);
    }

    // Insere nova entrada
    inserir("INSERT INTO historico_reproducao (id_usuario, id_musica, deezer_id, titulo, artista, capa, audio) VALUES (?, ?, ?, ?, ?, ?, ?)", [
        $userId,
        $musicaIdVal,
        $deezerIdVal,
        $titulo,
        $artista,
        $capa,
        $audio
    ]);

    echo json_encode(['ok' => true]);

} elseif ($method === 'GET') {
    // Buscar historico recente
    $limite = min((int)($_GET['limit'] ?? 8), 20);

    $rows = buscarTodos(
        "SELECT deezer_id, id_musica, titulo, artista, capa, audio, data_reproducao
         FROM historico_reproducao
         WHERE id_usuario = ?
         ORDER BY data_reproducao DESC
         LIMIT ?",
        [$userId, $limite]
    );

    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'id' => $r['deezer_id'] ?: (string)$r['id_musica'],
            'titulo' => $r['titulo'],
            'artista' => $r['artista'],
            'capa' => $r['capa'],
            'audio' => $r['audio']
        ];
    }

    echo json_encode($result);
}
