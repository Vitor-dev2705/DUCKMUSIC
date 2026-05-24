<?php
require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php"); // Ajuste para sua página de login
    exit();
}

$id_usuario_logado = $_SESSION['id_usuario'];
$playlist_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$playlist_id) {
    // Redirecionar para dashboard ou mostrar erro se o ID for inválido
    header("Location: dashboard.php"); // Ajuste para sua dashboard
    exit();
}

// Buscar detalhes da playlist e verificar se pertence ao usuário logado
$playlist = buscarUm("SELECT * FROM playlists WHERE id = ? AND id_usuario_criador = ?", [$playlist_id, $id_usuario_logado]);

if (!$playlist) {
    // Playlist não encontrada ou não pertence ao usuário
    // Poderia mostrar uma mensagem de erro ou redirecionar
    echo "Playlist não encontrada ou acesso não permitido.";
    // Para uma melhor UX, crie uma página de erro ou redirecione para a dashboard com uma mensagem.
    // header("Location: dashboard.php?erro=playlist_nao_encontrada");
    exit();
}

// Buscar músicas da playlist
$musicas_da_playlist = buscarTodos("
    SELECT m.*, art.nome as nome_artista, alb.titulo as album_titulo
    FROM musicas_playlists mp
    JOIN musicas m ON mp.id_musica = m.id
    LEFT JOIN artistas art ON m.id_artista = art.id
    LEFT JOIN albuns alb ON m.id_album = alb.id
    WHERE mp.id_playlist = ?
    ORDER BY mp.data_adicao ASC
", [$playlist_id]);

// Incluir o usuário para o cabeçalho (se você tiver um cabeçalho padrão)
$usuario = buscarUm("SELECT *, possui_estrela_apoio FROM usuarios WHERE id = ?", [$id_usuario_logado]);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($playlist['nome']) ?> - DuckMusic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Cole aqui TODO o CSS da sua dashboard.php para manter o estilo consistente */
        /* Ou, idealmente, mova o CSS para um arquivo .css separado e inclua em ambas as páginas */
        :root {
            --bg-deep-dark: #1A1D24; --bg-dark: #242831; --bg-medium: #2E333E; --bg-light-hover: #3A404C;
            --text-primary: #F0F0F0; --text-secondary: #A0A0B0;
            --accent-yellow: #FFD700; --accent-yellow-darker: #EAA900; --accent-purple: #7F5AF0; --accent-purple-darker: #6A45D4;
            --font-family: 'Poppins', sans-serif; --transition-speed: 0.2s; --card-border-radius: 12px; --player-border-radius: 16px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg-deep-dark); color: var(--text-primary); font-family: var(--font-family); line-height: 1.6; }
        /* ... (COLE O RESTANTE DO SEU CSS DA DASHBOARD AQUI) ... */
        /* Adicione estilos específicos para a página da playlist se necessário */
        .playlist-header { margin-bottom: 30px; display:flex; align-items:center; gap: 20px;}
        .playlist-cover-placeholder { width: 150px; height: 150px; background: var(--bg-medium); border-radius: var(--card-border-radius); display:flex; align-items:center; justify-content:center; font-size:3em; color: var(--text-secondary); }
        .playlist-header img { width: 150px; height: 150px; object-fit: cover; border-radius: var(--card-border-radius); }
        .playlist-info h1 { font-size: 2.5em; color: var(--text-primary); margin-bottom: 5px;}
        .playlist-info p { color: var(--text-secondary); margin-bottom: 10px; }
        .playlist-info .owner { font-size: 0.9em; }
        .playlist-actions button { background: var(--accent-purple); color:white; border:none; padding: 8px 15px; border-radius:5px; cursor:pointer; margin-right:10px; }
        .playlist-actions button:hover { background: var(--accent-purple-darker); }
        .playlist-actions .btn-delete { background: #E74C3C; }
        .playlist-actions .btn-delete:hover { background: #C0392B; }


        /* Cole o restante do seu CSS da dashboard aqui */
        .sidebar { width: 240px; background: var(--bg-dark); position: fixed; top: 0; bottom: 0; left: 0; padding: 32px 0 0 0; z-index: 100; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .logo { color: var(--accent-yellow); font-size: 1.8em; font-weight: 700; display: flex; align-items: center; gap: 10px; padding: 0 28px 32px 28px; }
        .logo i { font-size: 1.5em; }
        .menu { margin-bottom: 40px; }
        .menu-item { display: flex; align-items: center; gap: 16px; color: var(--text-secondary); text-decoration: none; font-size: 1.05em; font-weight: 500; padding: 14px 28px; border-left: 4px solid transparent; transition: var(--transition-speed); }
        .menu-item.active, .menu-item:hover { color: var(--text-primary); background: var(--bg-light-hover); border-left-color: var(--accent-yellow); }
        .menu-item .fa-star { color: var(--accent-yellow); margin-left: auto; }
        .playlists { padding: 0 28px; }
        .playlists h3 { display: flex; justify-content: space-between; align-items: center; color: var(--text-secondary); font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        #btnAbrirModalCriarPlaylist { background: none; border: none; color: var(--accent-yellow); font-size: 0.9em; cursor: pointer; padding: 5px; }
        #btnAbrirModalCriarPlaylist:hover { color: var(--accent-yellow-darker); }
        .sem-playlists-aviso { font-size: 0.85em; color: var(--text-secondary); padding: 5px 0; }
        .playlist-item { display:flex; align-items:center; gap: 8px; color: var(--text-secondary); padding: 8px 0; border-radius: 4px; cursor: pointer; transition: var(--transition-speed); font-size: 0.95em; text-decoration:none; }
        .playlist-item:hover { background: var(--bg-light-hover); color: var(--text-primary); padding-left: 8px; }
        .main-content { margin-left: 250px; padding: 40px 4vw 80px 4vw; min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { font-size: 1.8em; font-weight: 600; }
        .header p { color: var(--text-secondary); font-size: 0.95em; }
        .user-menu { display: flex; align-items: center; gap: 20px; }
        .user-avatar { display:flex; align-items: center; }
        .user-avatar img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent-purple); }
        .user-avatar .avatar-placeholder { width: 42px; height: 42px; border-radius: 50%; background: var(--accent-purple); color: var(--text-primary); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.2em; border: 3px solid var(--accent-purple); }
        .user-avatar .fa-star { color: var(--accent-yellow); font-size: 1.2em; margin-left: 10px; }
        .btn { background: none; border: none; color: var(--text-secondary); font-size: 1.4em; cursor: pointer; transition: var(--transition-speed); }
        .btn:hover { color: var(--accent-yellow); }
        .table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-bottom: 32px; }
        .table th, .table td { padding: 15px 10px; text-align: left; vertical-align: middle; }
        .table tr { background: var(--bg-dark); border-radius: 8px; transition: background var(--transition-speed); cursor:pointer; }
        .table tr:hover { background: var(--bg-light-hover); }
        .table td:first-child, .table th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .table td:last-child, .table th:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
        .table th { color: var(--accent-purple); font-weight: 600; font-size: 0.9em; text-transform: uppercase; }
        .song-img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; margin-right: 12px; }
        .song { display: flex; align-items: center; }
        .song strong { font-weight: 500; }
        .song small { color: var(--text-secondary); font-size: 0.85em; }
        .player { background: var(--bg-dark); border-radius: var(--player-border-radius); box-shadow: 0 10px 30px rgba(0,0,0,0.3); padding: 25px 30px 20px 30px; max-width: 580px; margin: 40px auto 0 auto; display: none; flex-direction: column; border: 1px solid var(--bg-medium); position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); width: calc(100% - 40px); max-width: 580px; z-index: 1000; } /* Player fixo */
        .player-song { display: flex; align-items: center; gap: 25px; margin-bottom: 15px; }
        .player-song-img { width: 80px; height: 80px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.25); transition: transform 0.8s linear; }
        .girando { animation: girar 4s linear infinite; }
        @keyframes girar { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .player-song-details h4 { font-size: 1.2em; margin-bottom: 4px; color: var(--text-primary); }
        .player-song-details p { font-size: 0.95em; color: var(--text-secondary); margin-bottom: 8px; }
        .player-fav-btn { background: none; border: none; font-size: 1.8em; cursor: pointer; margin-left: auto; padding: 5px; }
        .player-fav-btn i { color: var(--text-secondary); }
        .player-fav-btn i.favorito, .player-fav-btn:hover i { color: var(--accent-yellow); }
        .player-controls { margin-top: 15px; justify-content: center; display: flex; align-items: center; gap: 20px; }
        .player-controls button { background: none; border: none; color: var(--text-secondary); font-size: 1.5em; margin: 0 8px; cursor: pointer; transition: var(--transition-speed); }
        .player-controls button:hover { color: var(--text-primary); }
        #btn-play, #btn-pause { background: var(--accent-purple); color: var(--text-primary); border: none; border-radius: 50%; width: 55px; height: 55px; font-size: 1.6em; box-shadow: 0 3px 10px rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center; transition: background var(--transition-speed); }
        #btn-play:hover, #btn-pause:hover { background: var(--accent-purple-darker); }
        #btn-pause { display: none; }
        .progress-container { display: flex; align-items: center; gap: 12px; margin-top: 20px; }
        .progress-bar { flex: 1; height: 8px; background: var(--bg-medium); border-radius: 4px; cursor: pointer; }
        .progress { height: 100%; background: var(--accent-yellow); border-radius: 4px; transition: width 0.1s linear; }
        .time { font-size: 0.85em; color: var(--text-secondary); min-width: 35px; text-align: center; }
        #estrelas .fa-star { font-size: 1.5em; color: var(--text-secondary); cursor: pointer; transition: var(--transition-speed); margin: 0 1px; }
        #estrelas .fa-star.fas, #estrelas .fa-star:hover { color: var(--accent-yellow); transform: scale(1.1); }
        #media-estrelas { font-size: 0.9em; color: var(--accent-yellow); margin-top: 2px; }
        #media-estrelas .far { color: var(--text-secondary); }
        #media-estrelas .fas { color: var(--accent-yellow); }
        #media-estrelas span { font-size: 0.9em; margin-left: 5px; }
        .volume-controls { display: flex; align-items: center; gap: 10px; margin-left: 15px; }
        #volume-icon { color: var(--text-secondary); font-size: 1.1em; cursor: pointer; transition: color var(--transition-speed); }
        #volume-icon:hover { color: var(--text-primary); }
        #volume-slider { width: 70px; height: 6px; -webkit-appearance: none; appearance: none; background: var(--bg-medium); border-radius: 3px; outline: none; cursor: pointer; }
        #volume-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 14px; height: 14px; background: var(--accent-yellow); border-radius: 50%; cursor: pointer; }
        #volume-slider::-moz-range-thumb { width: 14px; height: 14px; background: var(--accent-yellow); border-radius: 50%; cursor: pointer; border: none; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); padding-top: 60px;}
        .modal-content { background-color: var(--bg-dark); margin: 5% auto; padding: 25px; border: 1px solid var(--bg-medium); width: 80%; max-width: 500px; border-radius: var(--card-border-radius); color: var(--text-primary); box-shadow: 0 5px 25px rgba(0,0,0,0.3);}
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--bg-medium);}
        .modal-header h2 { color: var(--accent-purple); font-size: 1.4em;}
        .close-btn { color: var(--text-secondary); font-size: 1.8em; font-weight: bold; cursor: pointer;}
        .close-btn:hover, .close-btn:focus { color: var(--text-primary);}
        .modal-body .form-group { margin-bottom: 15px;}
        .modal-body label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-secondary);}
        .modal-body input[type="text"], .modal-body textarea { width: 100%; padding: 10px; background-color: var(--bg-medium); border: 1px solid var(--bg-light-hover); border-radius: 5px; color: var(--text-primary); font-family: var(--font-family);}
        .modal-body textarea { resize: vertical; min-height: 80px;}
        .modal-footer { padding-top: 15px; text-align: right;}
        .modal-footer .btn-modal { padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; font-weight: 600; transition: var(--transition-speed);}
        .btn-modal-primary { background-color: var(--accent-purple); color: var(--text-primary);}
        .btn-modal-primary:hover { background-color: var(--accent-purple-darker);}
        .btn-modal-secondary { background-color: var(--bg-light-hover); color: var(--text-secondary); margin-right: 10px;}
        .btn-modal-secondary:hover { background-color: var(--bg-medium); color: var(--text-primary);}
        #modalFeedback { margin-top: 10px; font-size: 0.9em; }
        .feedback-success { color: var(--accent-yellow); }
        .feedback-error { color: #E74C3C; }
        @media (max-width: 900px) { .main-content { margin-left: 78px; padding: 25px 3vw 80px 3vw; } .sidebar { width: 68px; } .logo span, .menu-item span, .playlists h3, .playlist-item:not(:hover) span { display: none; } .sidebar .logo { justify-content: center; padding-left: 0; padding-right: 0; font-size: 1.5em; } .menu-item { justify-content: center; padding-left: 0; padding-right: 0; } .playlist-item:hover span { display:inline; margin-left: 5px;} .playlist-item:hover { padding-left:0; text-align:center;} }
        @media (max-width: 768px) { .header h1 { font-size: 1.6em; } .section-title { font-size: 1.3em; } .volume-controls { margin-left: 10px;} #volume-slider { width: 60px;} }
        @media (max-width: 600px) { .player { padding: 20px 15px 15px 15px; } .player-song-img { width: 60px; height: 60px; } .player-song-details h4 { font-size: 1.1em; } .player-song-details p { font-size: 0.9em; } .player-fav-btn { font-size: 1.6em; } .player-controls button { font-size: 1.3em; margin: 0 5px;} #btn-play, #btn-pause { width: 50px; height: 50px; } #estrelas .fa-star { font-size: 1.3em; } .progress-container { flex-wrap: wrap; gap: 8px; } .volume-controls { margin-left: 0; width: 100%; justify-content: center; margin-top: 5px;} #volume-slider { width: 100px;} }

    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fas fa-headphones-simple"></i><span> DuckMusic</span></div>
        <div class="menu">
            <a href="dashboard.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''); ?>"><i class="fas fa-home"></i> <span>Início</span></a>
            <a href="explorar.php" class="menu-item"><i class="fas fa-search"></i> <span>Explorar</span></a>
            <a href="biblioteca.php" class="menu-item"><i class="fas fa-book"></i> <span>Biblioteca</span></a> 
            <?php if ($usuario_permitido): ?>
                <a href="adicionar_musica.php" class="menu-item"><i class="fas fa-plus-circle"></i> <span>Adicionar Música</span></a>
            <?php endif; ?>
             <?php if (isset($usuario['possui_estrela_apoio']) && $usuario['possui_estrela_apoio']): ?>
                <a href="iniciar_doacao.php" class="menu-item">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span>Apoiador</span>
                    <i class="fas fa-star" title="Você é um Apoiador!"></i>
                </a>
            <?php else: ?>
                <a href="iniciar_doacao.php" class="menu-item">
                    <i class="fas fa-hand-holding-dollar"></i> <span>Apoiar</span>
                </a>
            <?php endif; ?>
        </div>
        <div class="playlists">
            <h3>Playlists <button id="btnAbrirModalCriarPlaylist" title="Criar Nova Playlist"><i class="fas fa-plus"></i></button></h3>
            <div id="listaMinhasPlaylists">
                <?php 
                // Buscar playlists do usuário logado - Idealmente, esta query já estaria no topo do script.
                // Para este exemplo, vamos assumir que $minhas_playlists já foi buscada.
                // $minhas_playlists_sidebar = buscarTodos("SELECT id, nome FROM playlists WHERE id_usuario_criador = ? ORDER BY nome ASC", [$_SESSION['id_usuario']]);
                global $minhas_playlists; // Usando a variável já buscada no topo
                ?>
                <?php if (!empty($minhas_playlists)): ?>
                    <?php foreach ($minhas_playlists as $pl_sidebar): ?>
                        <a href="ver_playlist.php?id=<?= $pl_sidebar['id'] ?>" class="playlist-item" data-playlist-id="<?= $pl_sidebar['id'] ?>">
                            <i class="fas fa-list-music"></i>
                            <span><?= htmlspecialchars($pl_sidebar['nome']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="sem-playlists-aviso" style="padding: 0 28px; font-size:0.9em; color: var(--text-secondary);">Nenhuma playlist criada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="main-content" style="padding-bottom: 150px;"> <div class="playlist-header">
             <?php if (!empty($playlist['caminho_capa']) && file_exists($playlist['caminho_capa'])): ?>
                <img src="<?= htmlspecialchars($playlist['caminho_capa']) ?>" alt="Capa da Playlist <?= htmlspecialchars($playlist['nome']) ?>">
            <?php else: ?>
                <div class="playlist-cover-placeholder"><i class="fas fa-music"></i></div>
            <?php endif; ?>
            <div class="playlist-info">
                <h1><?= htmlspecialchars($playlist['nome']) ?></h1>
                <?php if (!empty($playlist['descricao'])): ?>
                    <p><?= htmlspecialchars($playlist['descricao']) ?></p>
                <?php endif; ?>
                <p class="owner">Criada por: <?= htmlspecialchars($usuario['nome_usuario']) ?> </p>
                <div class="playlist-actions">
                    </div>
            </div>
        </div>

        <?php if (!empty($musicas_da_playlist)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Artista</th>
                        <th>Álbum</th>
                        <th>Duração</th>
                        <th>Adicionado em</th>
                        <th>Ações</th> </tr>
                </thead>
                <tbody>
                    <?php foreach ($musicas_da_playlist as $index => $musica): ?>
                        <?php
                            $caminhoCapaMusica = (!empty($musica['caminho_capa']) && file_exists($musica['caminho_capa'])) ? $musica['caminho_capa'] : 'capa-padrao.jpg';
                        ?>
                        <tr class="song-row" 
                            data-id="<?= $musica['id'] ?>" 
                            data-audio="<?= htmlspecialchars($musica['caminho_arquivo']) ?>" 
                            data-titulo="<?= htmlspecialchars($musica['titulo']) ?>" 
                            data-artista="<?= htmlspecialchars($musica['nome_artista'] ?: 'Desconhecido') ?>" 
                            data-capa="<?= htmlspecialchars($caminhoCapaMusica) ?>">
                            <td><?= $index + 1 ?></td>
                            <td>
                                <div class="song">
                                    <img src="<?= htmlspecialchars($caminhoCapaMusica) ?>" class="song-img" alt="Capa de <?= htmlspecialchars($musica['titulo']) ?>">
                                    <div>
                                        <strong><?= htmlspecialchars($musica['titulo']) ?></strong><br>
                                        <small><?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($musica['nome_artista'] ?: 'Artista Desconhecido') ?></td>
                            <td><?= htmlspecialchars($musica['album_titulo'] ?: 'Desconhecido') ?></td>
                            <td><?= isset($musica['duracao']) && is_numeric($musica['duracao']) ? gmdate("i:s", $musica['duracao']) : 'N/A' ?></td>
                            <td><?= isset($musica['data_adicao']) ? date("d/m/Y", strtotime($musica['data_adicao'])) : 'N/A' ?></td>
                            <td>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Esta playlist ainda não tem músicas. Comece a adicionar!</p>
        <?php endif; ?>

    </div> <script>
        // Adiciona a mesma lógica de player da dashboard.php aqui para as músicas desta página
        // Esta é uma simplificação. Uma abordagem melhor seria ter o JS do player em um arquivo separado.
        const audioPlayerGlobal = document.getElementById('audio-element') || new Audio(); // Reutiliza ou cria
        const playerGlobal = document.getElementById('player'); // Assumindo que o player HTML está na página
        
        // ... (Copie e cole a lógica do player da sua dashboard.php aqui, adaptando os seletores se o player não estiver nesta página) ...
        // Exemplo:
        document.querySelectorAll('.song-row').forEach((item, idx) => {
            item.addEventListener('click', e => {
                // if (e.target.closest('.btn-remover-musica-playlist')) return;
                
                // Esta parte precisa da lógica completa do `loadSong` e das variáveis globais do player
                // que estão na sua dashboard.php. A melhor forma seria modularizar o player.
                // Por hora, este clique pode não iniciar o player global corretamente sem essa lógica aqui.
                console.log("Clicou na música:", item.dataset.titulo);

                // Para demonstração, se você tiver o HTML do player global na página:
                if (typeof loadSong === "function") { // Verifica se a função loadSong da dashboard está acessível
                    // Precisamos recriar o 'musicasArray' para esta página específica
                    const musicasDaPaginaAtual = Array.from(document.querySelectorAll('.song-row')).map(i => ({
                        id: i.dataset.id, audio: i.dataset.audio, titulo: i.dataset.titulo, artista: i.dataset.artista, capa: i.dataset.capa
                    }));
                    // Encontrar o índice correto no array da página atual
                    let musicaClicadaIndex = -1;
                    for(let i=0; i<musicasDaPaginaAtual.length; i++){
                        if(musicasDaPaginaAtual[i].id === item.dataset.id){
                            musicaClicadaIndex = i;
                            break;
                        }
                    }
                    if(musicaClicadaIndex !== -1){
                         // Atualizar o musicasArray global do player se for o caso, ou ter um player local
                         window.musicasArray = musicasDaPaginaAtual; // Sobrescreve o array global (cuidado!)
                         loadSong(musicaClicadaIndex);
                    }
                } else {
                    alert(`Tocar: ${item.dataset.titulo}`);
                }
            });
        });
    </script>
</body>
</html>