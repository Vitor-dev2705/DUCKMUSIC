<?php
require_once __DIR__ . '/../includes/init.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$erro = '';
$sucesso = '';
$generos_disponiveis = ["FUNK", "RAP", "FORRÓ", "SERTANEJO", "ELETROFUNK", "OUTROS"];

// Lógica de Salvamento (Apenas quando o JS enviar os dados processados)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['caminho_audio_supabase'])) {
    try {
        $titulo = $_POST['titulo'];
        $artista_nome = $_POST['artista'];
        $album_titulo = $_POST['album'];
        $duracao = $_POST['duracao'];
        $genero_nome = $_POST['genero'];
        $caminhoBancoAudio = $_POST['caminho_audio_supabase'];
        $caminhoBancoCapa = $_POST['caminho_capa_supabase'] ?: 'assets/img/capa-padrao.jpg';

        $db->beginTransaction();

        // --- LÓGICA DO ARTISTA ---
        $resArt = buscarUm("SELECT id FROM artistas WHERE nome = ?", [$artista_nome]);
        $id_artista = $resArt ? $resArt['id'] : null;

        if (!$id_artista) {
            $id_artista = inserir("INSERT INTO artistas (nome) VALUES (?)", [$artista_nome]);
        }

        // --- LÓGICA DO GÊNERO ---
        $resGen = buscarUm("SELECT id FROM generos WHERE nome = ?", [$genero_nome]);
        $id_genero = $resGen ? $resGen['id'] : null;

        if (!$id_genero) {
            $id_genero = inserir("INSERT INTO generos (nome) VALUES (?)", [$genero_nome]);
        }

        // --- LÓGICA DO ÁLBUM ---
        $id_album = null;
        if (!empty($album_titulo)) {
            $resAlb = buscarUm("SELECT id FROM albuns WHERE titulo = ? AND id_artista = ?", [$album_titulo, $id_artista]);
            $id_album = $resAlb ? $resAlb['id'] : null;

            if (!$id_album) {
                $id_album = inserir("INSERT INTO albuns (titulo, id_artista) VALUES (?, ?)", [$album_titulo, $id_artista]);
            }
        }

        // Conversão de duração
        if (strpos($duracao, ':') !== false) {
            $partes = explode(':', $duracao);
            $duracao_segundos = (intval($partes[0]) * 60) + intval($partes[1]);
        } else {
            $duracao_segundos = intval($duracao);
        }

        // Inserção Final
        inserir("INSERT INTO musicas (titulo, id_artista, id_album, id_genero, duracao, caminho_arquivo, caminho_capa, id_usuario_upload)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$titulo, $id_artista, $id_album, $id_genero, $duracao_segundos, $caminhoBancoAudio, $caminhoBancoCapa, $_SESSION['id_usuario']]);

        $db->commit();
        $sucesso = "Música integrada ao Supabase e salva com sucesso!";
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $erro = "Erro no banco: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Música | DuckMusic</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <style>
        /* MANTENHA SEU CSS IGUAL - Vou omitir aqui para focar no código funcional */
        :root { --p: #8e44ad; --s: #e74c3c; --bg: #1a1a2e; --card: #1f283a; --txt: #ecf0f1; }
        body { background: var(--bg); color: var(--txt); font-family: 'Poppins', sans-serif; padding: 20px; }
        .back-nav { max-width: 600px; margin: 0 auto 20px; }
        .btn-back { text-decoration: none; color: var(--txt); background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 8px; }
        .container { background: var(--card); max-width: 600px; border-radius: 15px; margin: 0 auto; border: 1px solid #3c4a63; overflow: hidden; }
        .header { background: linear-gradient(90deg, var(--p), var(--s)); padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 0.85rem; color: #bdc3c7; }
        input, select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #3c4a63; background: #16213e; color: white; box-sizing: border-box; }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, var(--p), var(--s)); border: none; color: white; font-weight: bold; border-radius: 8px; cursor: pointer; }
        .btn:disabled { background: #555; cursor: not-allowed; }
        .msg { padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .erro { background: rgba(231,76,60,0.2); border: 1px solid var(--s); color: #ff7675; }
        .ok { background: rgba(46,204,113,0.2); border: 1px solid #2ecc71; color: #55efc4; }
        .file-box { border: 2px dashed #3c4a63; padding: 15px; text-align: center; border-radius: 8px; position: relative; }
        input[type="file"] { position: absolute; opacity: 0; left: 0; top: 0; width: 100%; height: 100%; cursor: pointer; }
        #progress-bar { width: 0%; height: 5px; background: var(--p); margin-top: 10px; transition: 0.3s; }
    </style>
</head>
<body>

    <nav class="back-nav"><a href="../paginas/dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a></nav>

    <div class="container">
        <div class="header"><h1><i class="fas fa-plus-circle"></i> DuckMusic + Supabase</h1></div>
        <div class="content">
            <?php if ($erro) echo "<div class='msg erro'>$erro</div>"; ?>
            <?php if ($sucesso) echo "<div class='msg ok'>$sucesso</div>"; ?>

            <form id="uploadForm">
                <div class="group"><label>Título</label><input type="text" id="titulo" name="titulo" required></div>
                <div class="group"><label>Artista</label><input type="text" id="artista" name="artista" required></div>
                <div class="group"><label>Álbum (Opcional)</label><input type="text" id="album" name="album"></div>
                <div style="display:flex; gap:10px">
                    <div class="group" style="flex:1"><label>Duração (mm:ss)</label><input type="text" id="duracao" name="duracao" placeholder="03:45" required></div>
                    <div class="group" style="flex:1">
                        <label>Gênero</label>
                        <select id="genero" name="genero">
                            <?php foreach ($generos_disponiveis as $g) echo "<option value='$g'>$g</option>"; ?>
                        </select>
                    </div>
                </div>

                <div class="group">
                    <label>Arquivo de Áudio</label>
                    <div class="file-box">
                        <span id="t1">Selecionar música (Sem limite de tamanho)</span>
                        <input type="file" id="arquivo_musica" accept="audio/*" required>
                    </div>
                    <div id="progress-container" style="background: #111; display:none;"><div id="progress-bar"></div></div>
                </div>

                <div class="group">
                    <label>Capa (Opcional)</label>
                    <div class="file-box">
                        <span id="t2">Selecionar imagem</span>
                        <input type="file" id="arquivo_capa" accept="image/*">
                    </div>
                </div>

                <button type="button" onclick="startUpload()" id="btnSubmit" class="btn">SUBIR PARA O SUPABASE</button>
            </form>
        </div>
    </div>

    <script>
        // CONFIGURAÇÃO SUPABASE
        const SUPABASE_URL = 'SUA_URL_AQUI';
        const SUPABASE_KEY = 'SUA_ANON_KEY_AQUI';
        const supabase = supabase.createClient(SUPABASE_URL, SUPABASE_KEY);

        async function startUpload() {
            const btn = document.getElementById('btnSubmit');
            const audioFile = document.getElementById('arquivo_musica').files[0];
            const capaFile = document.getElementById('arquivo_capa').files[0];
            
            if (!audioFile) return alert("Selecione a música!");

            btn.disabled = true;
            btn.innerText = "Enviando arquivo pesado...";
            document.getElementById('progress-container').style.display = 'block';

            try {
                // 1. Upload do Áudio
                const audioName = Date.now() + '_' + audioFile.name.replace(/\s/g, '_');
                const { data: audioData, error: audioError } = await supabase.storage
                    .from('musicas')
                    .upload(audioName, audioFile);

                if (audioError) throw audioError;
                const { data: audioUrl } = supabase.storage.from('musicas').getPublicUrl(audioName);

                // 2. Upload da Capa (se houver)
                let publicCapaUrl = '';
                if (capaFile) {
                    const capaName = Date.now() + '_capa_' + capaFile.name.replace(/\s/g, '_');
                    const { data: capaData, error: capaError } = await supabase.storage
                        .from('capas')
                        .upload(capaName, capaFile);
                    
                    if (!capaError) {
                        const { data: capaUrl } = supabase.storage.from('capas').getPublicUrl(capaName);
                        publicCapaUrl = capaUrl.publicUrl;
                    }
                }

                // 3. Enviar para o PHP via POST Oculto
                finalizarNoPHP(audioUrl.publicUrl, publicCapaUrl);

            } catch (err) {
                alert("Erro no Supabase: " + err.message);
                btn.disabled = false;
                btn.innerText = "TENTAR NOVAMENTE";
            }
        }

        function finalizarNoPHP(urlAudio, urlCapa) {
            const form = document.getElementById('uploadForm');
            const formData = new FormData();
            
            // Pega todos os campos de texto do formulário
            formData.append('titulo', document.getElementById('titulo').value);
            formData.append('artista', document.getElementById('artista').value);
            formData.append('album', document.getElementById('album').value);
            formData.append('duracao', document.getElementById('duracao').value);
            formData.append('genero', document.getElementById('genero').value);
            
            // Envia as URLs geradas pelo Supabase
            formData.append('caminho_audio_supabase', urlAudio);
            formData.append('caminho_capa_supabase', urlCapa);

            // Envia via Fetch para o próprio arquivo (PHP no topo processa)
            fetch('', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(html => {
                document.open();
                document.write(html);
                document.close();
            });
        }

        // Feedback visual dos nomes dos arquivos
        document.getElementById('arquivo_musica').onchange = function() { document.getElementById('t1').innerText = this.files[0].name; };
        document.getElementById('arquivo_capa').onchange = function() { document.getElementById('t2').innerText = this.files[0].name; };
    </script>
</body>
</html>