<?php
// Esta vista la renderiza UsuarioController (que ya verificó que sos admin).
// Si alguien la abre directo por URL, se lo manda al controller.
if (!isset($actor)) {
    require_once __DIR__ . '/../../../config/app.php';
    header('Location: ' . BASE_URL . '/index.php?c=Usuario&a=listar');
    exit;
}
require __DIR__ . '/../layout/admin_header.php';
?>
<div class="admin-card">
  <?php if ($modo === 'crear'): ?>
    <h3>Nuevo usuario</h3>
  <?php else: ?>
    <h3>Editar usuario #<?= (int) $usuario['id'] ?></h3>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="msg-error"><?= h($error) ?></div>
  <?php endif; ?>

  <?php if ($modo === 'crear'): ?>
    <form action="<?= BASE_URL ?>/index.php?c=Usuario&amp;a=crear" method="POST">
  <?php else: ?>
    <form action="<?= BASE_URL ?>/index.php?c=Usuario&amp;a=actualizar" method="POST">
      <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
  <?php endif; ?>
    <?= csrfCampo() ?>
    <div class="form-grid">
      <div class="field">
        <label for="f-nombre">Nombre completo:</label>
        <input type="text" id="f-nombre" name="nombre" required maxlength="50" value="<?= h($old['nombre']) ?>" />
      </div>
      <div class="field">
        <label for="f-alias">Alias público:</label>
        <input type="text" id="f-alias" name="alias" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_.\-]{3,30}" value="<?= h($old['alias']) ?>" />
        <div class="field-hint">Letras, números, guion, guion bajo o punto (3 a 30).</div>
      </div>
      <div class="field">
        <label for="f-email">Correo electrónico:</label>
        <input type="email" id="f-email" name="email" required maxlength="100" value="<?= h($old['email']) ?>" />
      </div>
      <div class="field">
        <label for="f-rol">Rol:</label>
        <select id="f-rol" name="rol" <?php if ($editandoPropio): ?>disabled<?php endif; ?>>
          <option value="usuario" <?php if ($old['rol'] === 'usuario'): ?>selected<?php endif; ?>>Usuario</option>
          <option value="admin" <?php if ($old['rol'] === 'admin'): ?>selected<?php endif; ?>>Administrador</option>
        </select>
        <?php if ($editandoPropio): ?>
          <div class="field-hint">No podés cambiar tu propio rol.</div>
        <?php endif; ?>
      </div>
      <div class="field">
        <label for="f-password">Contraseña:</label>
        <input type="password" id="f-password" name="password" minlength="8" maxlength="72" autocomplete="new-password" <?php if ($modo === 'crear'): ?>required<?php endif; ?> />
        <?php if ($modo === 'editar'): ?>
          <div class="field-hint">Dejala vacía para no cambiarla.</div>
        <?php else: ?>
          <div class="field-hint">Mínimo 8 caracteres.</div>
        <?php endif; ?>
      </div>
      <div class="field">
        <label for="f-password2">Repetir contraseña:</label>
        <input type="password" id="f-password2" name="password2" minlength="8" maxlength="72" autocomplete="new-password" <?php if ($modo === 'crear'): ?>required<?php endif; ?> />
      </div>
    </div>

    <div class="button-row">
      <button type="submit">Guardar</button>
      <a class="btn" href="<?= BASE_URL ?>/index.php?c=Usuario&amp;a=listar">Cancelar</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
