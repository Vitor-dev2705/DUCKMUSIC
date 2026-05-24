<?php
require_once __DIR__ . '/../includes/init.php';
if (!isset($_SESSION['id_usuario'])) exit('login');
$id_usuario = $_SESSION['id_usuario'];
$id_musica = intval($_POST['id_musica'] ?? 0);
$nota = intval($_POST['estrelas'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

if ($id_musica && $comentario && $nota >= 1 && $nota <= 5) {
    $existe = buscarUm("SELECT id FROM avaliacoes WHERE id_usuario=? AND id_musica=?", [$id_usuario, $id_musica]);
    if ($existe) {
        atualizar("UPDATE avaliacoes SET nota=?, comentario=?, data_avaliacao=NOW() WHERE id=?", [$nota, $comentario, $existe['id']]);
    } else {
        inserir("INSERT INTO avaliacoes (id_usuario, id_musica, nota, comentario) VALUES (?, ?, ?, ?)", [$id_usuario, $id_musica, $nota, $comentario]);
    }
    echo "ok";
} else {
    echo "erro";
}
