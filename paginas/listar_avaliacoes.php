<?php
require_once __DIR__ . '/../includes/init.php';
$id_musica = intval($_GET['musica_id'] ?? 0);
$avaliacoes = buscarTodos("SELECT a.*, u.nome_usuario FROM avaliacoes a JOIN usuarios u ON a.id_usuario=u.id WHERE a.id_musica=? ORDER BY a.data_avaliacao DESC", [$id_musica]);
$media = buscarUm("SELECT AVG(nota) as media, COUNT(*) as total FROM avaliacoes WHERE id_musica=?", [$id_musica]);
?>
<div style="margin-bottom:12px;">
    <strong>Média:</strong>
    <?php
    $media_val = $media['media'] ? round($media['media'],1) : 0;
    for($i=1;$i<=5;$i++) echo '<i class="fa-star '.($i <= round($media_val) ? 'fas' : 'far').'" style="color:#e7b93c"></i>';
    echo " ({$media_val}/5) - {$media['total']} avaliação(ões)";
    ?>
</div>
<?php
foreach ($avaliacoes as $av) {
    echo "<div style='margin-bottom:10px;'><strong>".htmlspecialchars($av['nome_usuario'])."</strong> ";
    for($i=1;$i<=5;$i++) echo '<i class="fa-star '.($i <= $av['nota'] ? 'fas' : 'far').'" style="color:#e7b93c"></i>';
    echo "<br>".nl2br(htmlspecialchars($av['comentario']))."<br><small style='color:#aaa;'>".date('d/m/Y H:i', strtotime($av['data_avaliacao']))."</small></div>";
}
if (empty($avaliacoes)) echo "<div style='color:#888;'>Nenhuma avaliação ainda.</div>";