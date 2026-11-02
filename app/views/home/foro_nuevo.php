<a class="volver" href="<?= BASE_URL ?>/index.php?c=Foro&amp;a=index">← Volver a los hilos</a>

<div class="panel">
  <h2>Nuevo hilo</h2>
  <p>Vas a publicar como <strong><?= h($alias) ?></strong>. Nadie ve tu nombre real ni tu correo.</p>

  <?php if ($error): ?>
    <div class="msg-error"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" action="<?= BASE_URL ?>/index.php?c=Foro&amp;a=crearPublicacion">
    <?= csrfCampo() ?>
    <div class="field">
      <label for="hiloTitulo">Título</label>
      <input type="text" id="hiloTitulo" name="titulo" required minlength="3" maxlength="<?= (int) $maxTitulo ?>" placeholder="Resumí de qué trata el hilo" value="<?= h($old['titulo']) ?>" />
    </div>
    <div class="field">
      <label for="hiloContenido">Mensaje</label>
      <textarea id="hiloContenido" name="contenido" rows="8" required maxlength="<?= (int) $maxContenido ?>" placeholder="Compartí lo que encontraste o preguntá lo que te falta..."><?= h($old['contenido']) ?></textarea>
    </div>
    <div class="field">
      <label for="hiloImagen">Imagen (opcional)</label>
      <input type="file" id="hiloImagen" name="imagen" accept="image/png,image/jpeg,image/gif,image/webp" />
      <div class="field-hint">JPG, PNG, GIF o WebP, hasta 2 MB. Se muestra en el hilo una vez publicado.</div>
      <div id="hiloImagenPreview"></div>
    </div>
    <div class="button-row">
      <button type="submit" class="primary">Publicar hilo</button>
      <button type="button" onclick="location.href='<?= BASE_URL ?>/index.php?c=Foro&amp;a=index'">Cancelar</button>
    </div>
  </form>
</div>

<script>
  // Vista previa de la imagen elegida y aviso si pesa más de 2 MB.
  (function () {
    var input = document.getElementById('hiloImagen');
    var caja  = document.getElementById('hiloImagenPreview');
    if (!input) return;
    input.addEventListener('change', function () {
      caja.innerHTML = '';
      var archivo = input.files[0];
      if (!archivo) return;
      if (archivo.size > 2 * 1024 * 1024) {
        input.value = '';
        Dialogo.aviso('La imagen pesa más de 2 MB. Elegí una más liviana.', { titulo: 'Imagen demasiado pesada' });
        return;
      }
      if (!archivo.type.startsWith('image/')) return;
      var img = document.createElement('img');
      img.className = 'hilo-img hilo-img--mini';
      img.alt = 'Vista previa';
      img.src = URL.createObjectURL(archivo);
      caja.appendChild(img);
    });
  })();
</script>
