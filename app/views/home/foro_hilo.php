<a class="volver" href="<?= BASE_URL ?>/index.php?c=Foro&amp;a=index">← Volver a los hilos</a>

<?php if ($flash): ?>
  <div class="msg-<?= h($flash['tipo']) ?>"><?= h($flash['texto']) ?></div>
<?php endif; ?>

<div class="panel" id="publicacion">
  <h2><?= h($hilo['titulo']) ?></h2>
  <div class="meta"><?= avatarPerfil($hilo['autor_foto'] ?? null, $hilo['autor'], 24) ?><?= h($hilo['autor']) ?> · <?= h($hilo['hace']) ?></div>
  <div class="mensaje-texto"><?= nl2br(h($hilo['contenido'])) ?></div>
  <?php if (!empty($hilo['imagen'])): ?>
    <a href="<?= h($hilo['imagen']) ?>" target="_blank" rel="noopener"><img class="hilo-img" src="<?= h($hilo['imagen']) ?>" alt="Imagen del hilo: <?= h($hilo['titulo']) ?>"></a>
  <?php endif; ?>
  <div class="barra-mensaje">
    <?php $objetivo = 'publicacion'; $idObjetivo = $hilo['id_publicacion']; $item = $hilo; require __DIR__ . '/foro_reacciones.php'; ?>
    <?php if ($hilo['puede_eliminar']): ?>
      <form method="POST" action="<?= BASE_URL ?>/index.php?c=Foro&amp;a=eliminarPublicacion"
            onsubmit="return confirm('¿Eliminar este hilo? Se borran también todos sus comentarios.')">
        <?= csrfCampo() ?>
        <input type="hidden" name="id" value="<?= (int) $hilo['id_publicacion'] ?>">
        <button type="submit" class="btn-mini">Eliminar hilo</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="panel" id="comentarios">
  <h2><?= h($txtComentarios) ?></h2>
  <?php if (count($comentarios) === 0): ?>
    <p>Todavía nadie comentó. ¡Sé el primero!</p>
  <?php endif; ?>
  <?php foreach ($comentarios as $c): ?>
    <div class="comentario" id="comentario-<?= (int) $c['id_comentario'] ?>">
      <div class="meta"><?= avatarPerfil($c['autor_foto'] ?? null, $c['autor'], 20) ?><?= h($c['autor']) ?> · <?= h($c['hace']) ?></div>
      <div class="mensaje-texto"><?= nl2br(h($c['contenido'])) ?></div>
      <div class="barra-mensaje">
        <?php $objetivo = 'comentario'; $idObjetivo = $c['id_comentario']; $item = $c; require __DIR__ . '/foro_reacciones.php'; ?>
        <?php if ($c['puede_eliminar']): ?>
          <form method="POST" action="<?= BASE_URL ?>/index.php?c=Foro&amp;a=eliminarComentario"
                onsubmit="return confirm('¿Eliminar este comentario?')">
            <?= csrfCampo() ?>
            <input type="hidden" name="id" value="<?= (int) $c['id_comentario'] ?>">
            <button type="submit" class="btn-mini">Eliminar</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel" id="nuevo-comentario">
  <h2>Tu comentario</h2>
  <?php if ($errorComentario): ?>
    <div class="msg-error"><?= h($errorComentario) ?></div>
  <?php endif; ?>
  <form method="POST" action="<?= BASE_URL ?>/index.php?c=Foro&amp;a=comentar">
    <?= csrfCampo() ?>
    <input type="hidden" name="id_publicacion" value="<?= (int) $hilo['id_publicacion'] ?>">
    <div class="field">
      <label for="comentarioTexto">Comentar como <?= h($alias) ?></label>
      <textarea id="comentarioTexto" name="contenido" rows="3" required maxlength="<?= (int) $maxComentario ?>" placeholder="Escribí tu comentario..."><?= h($textoComentario) ?></textarea>
    </div>
    <div class="button-row">
      <button type="submit" class="primary">Comentar</button>
    </div>
  </form>
</div>

<?php if ($errorComentario): ?>
  <script>document.getElementById('nuevo-comentario').scrollIntoView();</script>
<?php endif; ?>
