<?php
require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

$id_usuario_logado = $_SESSION['id_usuario'];

// 1. Busca os IDs das favoritas para marcar o coração preenchido
if (!isset($_SESSION['favoritas_ids'])) {
    $favoritas_db = buscarTodos("SELECT id_musica FROM favoritos WHERE id_usuario = ?", [$id_usuario_logado]);
    $_SESSION['favoritas_ids'] = array_column($favoritas_db, 'id_musica');
}

// 2. Lógica de Busca (Se houver termo de pesquisa)
$termo = $_GET['busca'] ?? '';
if (!empty($termo)) {
    // Busca por título, artista ou álbum
    $musicas = buscarTodos("
        SELECT m.*, art.nome as nome_artista, a.titulo as album_titulo
        FROM musicas m
        LEFT JOIN artistas art ON m.id_artista = art.id
        LEFT JOIN albuns a ON m.id_album = a.id
        WHERE m.titulo ILIKE ? OR art.nome ILIKE ? OR a.titulo ILIKE ?
        ORDER BY m.titulo ASC
    ", ["%$termo%", "%$termo%", "%$termo%"]);
} else {
    // Se não houver busca, mostra músicas aleatórias ou as mais recentes
    $musicas = buscarTodos("
        SELECT m.*, art.nome as nome_artista, a.titulo as album_titulo
        FROM musicas m
        LEFT JOIN artistas art ON m.id_artista = art.id
        LEFT JOIN albuns a ON m.id_album = a.id
        ORDER BY RANDOM() LIMIT 20
    ");
}

function verificarCapa($caminho) {
    return resolverMidia($caminho, '/assets/img/capa-padrao.svg');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Explorar - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .search-box { margin-bottom: 30px; position: relative; max-width: 600px; }
        .search-box input { 
            width: 100%; padding: 15px 50px; border-radius: 30px; 
            border: none; background: #282828; color: white; font-size: 1rem;
        }
        .search-box i { position: absolute; left: 20px; top: 18px; color: #b3b3b3; }
        .explorar-container { padding: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><i class="fas fa-headphones-simple"></i><span>DuckMusic</span></div>
    <div class="menu">
        <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> <span>Início</span></a>
        <a href="explorar.php" class="menu-item active"><i class="fas fa-search"></i> <span>Explorar</span></a>
        <a href="biblioteca.php" class="menu-item"><i class="fas fa-book"></i> <span>Biblioteca</span></a>
    </div>
</div>

<div class="main-content">
    <div class="explorar-container">
        <h1>Explorar</h1>
        
        <form action="explorar.php" method="GET" class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="busca" placeholder="O que você quer ouvir?" value="<?= htmlspecialchars($termo) ?>">
        </form>

        <h2 class="section-title"><?= empty($termo) ? 'Sugestões para você' : 'Resultados para: ' . htmlspecialchars($termo) ?></h2>
        
        <div class="cards-container">
            <?php foreach ($musicas as $musica): 
                $isFavorita = in_array($musica['id'], $_SESSION['favoritas_ids']);
                $caminhoCapa = verificarCapa($musica['caminho_capa']);
            ?>
                <div class="card" 
                     data-id="<?= $musica['id'] ?>"
                     data-audio="<?= htmlspecialchars(resolverMidia($musica['caminho_arquivo'])) ?>"
                     data-titulo="<?= htmlspecialchars($musica['titulo']) ?>"
                     data-artista="<?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?>"
                     data-capa="<?= htmlspecialchars($caminhoCapa) ?>">
                    
                    <img src="<?= htmlspecialchars($caminhoCapa) ?>" class="card-img" alt="Capa">
                    <div class="card-content">
                        <h3><?= htmlspecialchars($musica['titulo']) ?></h3>
                        <p><?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></p>
                    </div>
                    <button class="btn-fav" data-id="<?= $musica['id'] ?>" title="Favoritar">
                        <i class="fa<?= $isFavorita ? 's' : 'r' ?> fa-heart <?= $isFavorita ? 'favorito' : '' ?>"></i>
                    </button>
                </div>
            <?php endforeach; ?>

            <?php if (empty($musicas)): ?>
                <p>Nenhuma música encontrada para "<?= htmlspecialchars($termo) ?>".</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="player" id="player">
    <div class="player-top">
        <div class="player-song">
            <img src="../img/capa-padrao.jpg" class="player-song-img" id="player-img">
            <div class="player-song-details">
                <h4 id="player-title">Selecione uma música</h4>
                <p id="player-artist">DuckMusic</p>
            </div>
        </div>
        <button class="player-fav-btn" id="player-fav-btn"><i class="far fa-heart"></i></button>
    </div>
    <div class="player-controls">
        <button id="btn-prev"><i class="fas fa-backward-step"></i></button>
        <button id="btn-play"><i class="fas fa-play"></i></button>
        <button id="btn-pause" style="display:none;"><i class="fas fa-pause"></i></button>
        <button id="btn-next"><i class="fas fa-forward-step"></i></button>
    </div>
    <div class="progress-container">
        <span id="current-time">0:00</span>
        <div class="progress-bar" id="progress-bar"><div class="progress" id="progress"></div></div>
        <span id="duration">0:00</span>
        <div class="volume-controls">
            <i class="fas fa-volume-high"></i>
            <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.8">
        </div>
    </div>
    <audio id="audio-element" src=""></audio>
</div>

<div id="toast" class="toast"></div>

<script>
    window.APP_DATA = {
        favoritasIds: <?php echo json_encode($_SESSION['favoritas_ids'] ?? []); ?>
    };
</script>
<script src="../js/dashboard.js"></script>
</body>
</html>