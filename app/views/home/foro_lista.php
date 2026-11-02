<div class="panel">
  <h2>Hilos activos del caso</h2>
  <p>Anónimo entre anónimos. Compartí lo que encontraste, preguntá lo que te falta.</p>
  <div class="button-row">
    <button type="button" class="primary" onclick="location.href='<?= BASE_URL ?>/index.php?c=Foro&amp;a=nuevoView'">+ Nuevo hilo</button>
  </div>
</div>

<?php if ($flash): ?>
  <div class="msg-<?= h($flash['tipo']) ?>"><?= h($flash['texto']) ?></div>
<?php endif; ?>

<?php if (count($hilos) === 0): ?>
  <div class="panel">Todavía no hay hilos. ¡Abrí el primero!</div>
<?php endif; ?>

<?php foreach ($hilos as $hilo): ?>
  <a class="thread-card" href="<?= BASE_URL ?>/index.php?c=Foro&amp;a=ver&amp;id=<?= (int) $hilo['id_publicacion'] ?>">
    <h3><?= h($hilo['titulo']) ?></h3>
    <div class="meta"><?= avatarPerfil($hilo['autor_foto'] ?? null, $hilo['autor'], 20) ?><?= h($hilo['autor']) ?> · <?= h($hilo['hace']) ?> · <?= h($hilo['txt_respuestas']) ?> · <?= h($hilo['txt_reacciones']) ?></div>
    <p><?= h($hilo['extracto']) ?></p>
    <?php if (!empty($hilo['imagen'])): ?>
      <img class="hilo-img hilo-img--mini" src="<?= h($hilo['imagen']) ?>" alt="Imagen del hilo: <?= h($hilo['titulo']) ?>" loading="lazy">
    <?php endif; ?>
  </a>
<?php endforeach; ?>
