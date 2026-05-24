<?php
// 1. Configurações de erro para Debug (ajuda a ver se o banco falhar)
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/init.php';

// 2. Verificação de Sessão
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sessão expirada.']);
    exit();
}

// 3. Verificação do Método (Evita o erro da Imagem 2)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido. Use POST via formulário.']);
    exit();
}

// 4. Recebimento de Dados (Nomes vindos do seu HTML)
$nome_playlist = trim($_POST['playlist_nome'] ?? '');
$descricao_playlist = trim($_POST['playlist_descricao'] ?? '');
$id_usuario = $_SESSION['id_usuario'];

// 5. Validação
if (empty($nome_playlist)) {
    echo json_encode(['status' => 'error', 'message' => 'O nome da playlist é obrigatório.']);
    exit();
}

// 6. Execução no Banco de Dados
try {
    // Usando buscarUm para verificar duplicidade (conforme seu db_connection.php)
    $sqlCheck = "SELECT id FROM playlists WHERE nome = ? AND id_usuario_criador = ? LIMIT 1";
    $existe = buscarUm($sqlCheck, [$nome_playlist, $id_usuario]);

    if ($existe) {
        echo json_encode(['status' => 'error', 'message' => 'Você já tem uma playlist com este nome.']);
        exit();
    }

    // Usando a função inserir() do seu db_connection.php que já retorna o ID
    $sqlInsert = "INSERT INTO playlists (nome, descricao, id_usuario_criador, data_criacao) VALUES (?, ?, ?, NOW())";
    $novoId = inserir($sqlInsert, [$nome_playlist, $descricao_playlist, $id_usuario]);

    if ($novoId) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Playlist criada com sucesso!',
            'playlist_id' => $novoId
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao processar inserção no banco.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro interno: ' . $e->getMessage()]);
}

exit();