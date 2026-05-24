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
        $caminhoBancoCapa = $_POST['caminho_capa_supabase'] ?: '/assets/img/capa-padrao.svg';

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
        // CONFIGURACAO SUPABASE
        const SUPABASE_URL = 'https://shbqixwzxtwochcgkakv.supabase.co';
        const SUPABASE_KEY = 'sb_publishable_i8XXn8-TmGMO2KK-A5zNPg_I9_HsHyg';
        const sb = supabase.createClient(SUPABASE_URL, SUPABASE_KEY);

        async function startUpload() {
            const btn = document.getElementById('btnSubmit');
            const audioFile = document.getElementById('arquivo_musica').files[0];
            const capaFile = document.getElementById('arquivo_capa').files[0];
            const progBar = document.getElementById('progress-bar');

            if (!audioFile) return alert("Selecione a musica!");

            // Valida campos obrigatorios
            var titulo = document.getElementById('titulo').value.trim();
            var artista = document.getElementById('artista').value.trim();
            var duracao = document.getElementById('duracao').value.trim();
            if (!titulo || !artista || !duracao) return alert("Preencha titulo, artista e duracao!");

            btn.disabled = true;
            btn.innerText = "Enviando...";
            document.getElementById('progress-container').style.display = 'block';
            progBar.style.width = '10%';

            try {
                // 1. Upload do Audio para Supabase Storage
                var audioName = Date.now() + '_' + audioFile.name.replace(/\s/g, '_');
                progBar.style.width = '20%';

                var uploadResult = await sb.storage
                    .from('musicas')
                    .upload(audioName, audioFile, {
                        cacheControl: '3600',
                        upsert: false
                    });

                if (uploadResult.error) throw uploadResult.error;
                progBar.style.width = '60%';

                var audioUrlResult = sb.storage.from('musicas').getPublicUrl(audioName);
                var publicAudioUrl = audioUrlResult.data.publicUrl;

                // 2. Upload da Capa (se houver)
                var publicCapaUrl = '';
                if (capaFile) {
                    var capaName = Date.now() + '_capa_' + capaFile.name.replace(/\s/g, '_');
                    var capaResult = await sb.storage
                        .from('capas')
                        .upload(capaName, capaFile, {
                            cacheControl: '3600',
                            upsert: false
                        });

                    if (!capaResult.error) {
                        var capaUrlResult = sb.storage.from('capas').getPublicUrl(capaName);
                        publicCapaUrl = capaUrlResult.data.publicUrl;
                    }
                }
                progBar.style.width = '80%';

                // 3. Enviar URLs + metadados para o PHP salvar no banco
                finalizarNoPHP(publicAudioUrl, publicCapaUrl);
                progBar.style.width = '100%';

            } catch (err) {
                console.error('Supabase error:', err);
                alert("Erro no upload: " + (err.message || err.error || 'Erro desconhecido'));
                btn.disabled = false;
                btn.innerText = "TENTAR NOVAMENTE";
                progBar.style.width = '0%';
            }
        }

        function finalizarNoPHP(urlAudio, urlCapa) {
            var formData = new FormData();

            formData.append('titulo', document.getElementById('titulo').value);
            formData.append('artista', document.getElementById('artista').value);
            formData.append('album', document.getElementById('album').value);
            formData.append('duracao', document.getElementById('duracao').value);
            formData.append('genero', document.getElementById('genero').value);
            formData.append('caminho_audio_supabase', urlAudio);
            formData.append('caminho_capa_supabase', urlCapa);

            fetch('/api/adicionar_musica.php', { method: 'POST', credentials: 'same-origin', body: formData })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                // Extrai mensagem de sucesso/erro do HTML retornado
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var msgOk = doc.querySelector('.msg.ok');
                var msgErr = doc.querySelector('.msg.erro');
                if (msgOk) {
                    alert(msgOk.textContent);
                    // Limpa formulario
                    document.getElementById('uploadForm').reset();
                    document.getElementById('t1').innerText = 'Selecionar musica (Sem limite de tamanho)';
                    document.getElementById('t2').innerText = 'Selecionar imagem';
                    document.getElementById('progress-container').style.display = 'none';
                    document.getElementById('progress-bar').style.width = '0%';
                } else if (msgErr) {
                    alert(msgErr.textContent);
                }
                var btn = document.getElementById('btnSubmit');
                btn.disabled = false;
                btn.innerText = 'SUBIR PARA O SUPABASE';
            })
            .catch(function(err) {
                alert('Erro ao salvar no banco: ' + err.message);
                var btn = document.getElementById('btnSubmit');
                btn.disabled = false;
                btn.innerText = 'TENTAR NOVAMENTE';
            });
        }

        // Feedback visual dos nomes dos arquivos
        document.getElementById('arquivo_musica').onchange = function() { document.getElementById('t1').innerText = this.files[0].name; };
        document.getElementById('arquivo_capa').onchange = function() { document.getElementById('t2').innerText = this.files[0].name; };
    </script>
</body>
</html>