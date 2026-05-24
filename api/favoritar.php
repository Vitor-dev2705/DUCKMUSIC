<?php
// 1. Inicia o buffer para evitar que erros de texto quebrem o JSON do JavaScript
ob_start();

require_once __DIR__ . '/../includes/init.php';

// 2. Limpa o buffer de saída (remove qualquer espaço ou aviso do PHP)
ob_clean(); 
header('Content-Type: application/json; charset=utf-8');

// 3. Verificação de Segurança (Login)
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não logado']);
    exit;
}

$id_musica = $_POST['musica_id'] ?? null;

if (!$id_musica || !is_numeric($id_musica)) {
    echo json_encode(['status' => 'error', 'message' => 'ID da música inválido']);
    exit;
}

try {
    $id_usuario = $_SESSION['id_usuario'];
    $id_musica = intval($id_musica);

    // 4. USANDO SUAS FUNÇÕES EXATAS: buscarUm e executarQuery
    
    // Verifica se já favoritou
    $existe = buscarUm("SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_musica = ?", [$id_usuario, $id_musica]);

    if ($existe) {
        // Se já existe, usa excluir (que por dentro usa a sua executarQuery)
        excluir("DELETE FROM favoritos WHERE id_usuario = ? AND id_musica = ?", [$id_usuario, $id_musica]);
        echo json_encode(['status' => 'success', 'favoritado' => false]);
    } else {
        // Se não existe, usa inserir (que por dentro usa a sua executarQuery)
        inserir("INSERT INTO favoritos (id_usuario, id_musica) VALUES (?, ?)", [$id_usuario, $id_musica]);
        echo json_encode(['status' => 'success', 'favoritado' => true]);
    }

} catch (Exception $e) {
    // Retorna erro de banco como JSON para o JavaScript ler
    echo json_encode([
        'status' => 'error', 
        'message' => 'Erro no banco: ' . $e->getMessage()
    ]);
}

// 5. Finaliza o script
ob_end_flush();
exit;