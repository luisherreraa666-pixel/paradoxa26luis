<?php
// Widget de reacciones, reutilizado en hilos y comentarios (ver foro_hilo.php).
// Variables esperadas: $objetivo ('publicacion' | 'comentario'), $idObjetivo, $item (con 'reacciones' y 'mia')
// y $tiposReaccion (ForoModel::REACCIONES). Un clic reacciona; otro clic sobre la misma la quita.
?>
<div class="reacciones">
  <?php foreach ($tiposReaccion as $tipo => $datos): ?>
    <form class="form-reaccion" method="POST" action="<?= BASE_URL ?>/index.php?c=Foro&amp;a=reaccionar">
      <?= csrfCampo() ?>
      <input type="hidden" name="objetivo" value="<?= h($objetivo) ?>">
      <input type="hidden" name="id" value="<?= (int) $idObjetivo ?>">
      <input type="hidden" name="tipo" value="<?= h($tipo) ?>">
      <button type="submit" class="reaccion<?php if ($item['mia'] === $tipo): ?> activa<?php endif; ?>" title="<?= h($datos[1]) ?>"><?= h($datos[0]) ?> <span><?= (int) $item['reacciones'][$tipo] ?></span></button>
    </form>
  <?php endforeach; ?>
</div>
