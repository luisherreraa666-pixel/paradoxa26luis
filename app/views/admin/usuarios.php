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
  <h3>Gestión de Usuarios</h3>

  <?php if ($flash): ?>
    <div class="msg-<?= h($flash['tipo']) ?>"><?= h($flash['texto']) ?></div>
  <?php endif; ?>

  <form class="toolbar" action="<?= BASE_URL ?>/index.php" method="GET">
    <input type="hidden" name="c" value="Usuario">
    <input type="hidden" name="a" value="listar">
    <input type="text" name="q" value="<?= h($busqueda) ?>" placeholder="Buscar por correo, alias o nombre" />
    <button type="submit">Buscar</button>
    <?php if ($busqueda !== ''): ?>
      <a class="btn" href="<?= BASE_URL ?>/index.php?c=Usuario&amp;a=listar">Limpiar</a>
    <?php endif; ?>
  </form>

  <?php if (count($usuarios) === 0): ?>
    <p>No se encontraron usuarios.</p>
  <?php else: ?>
    <div class="tabla-wrap">
      <table class="tabla-usuarios">
        <thead>
          <tr>
            <th>ID</th>
            <th>Alias</th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Registro</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td><?= (int) $u['id'] ?></td>
              <td><?= h($u['nombre_usuario_anonimo']) ?></td>
              <td><?= h($u['nombre_usuario_real']) ?></td>
              <td><?= h($u['correo']) ?></td>
              <td><span class="rol rol-<?= h($u['rol']) ?>"><?= h($u['rol']) ?></span></td>
              <td><?= h($u['fecha_registro']) ?></td>
              <td class="acciones">
                <a class="btn" href="<?= BASE_URL ?>/index.php?c=Usuario&amp;a=editarView&amp;id=<?= (int) $u['id'] ?>">Editar</a>
                <?php if ((int) $u['id'] === $actorId): ?>
                  <button type="button" disabled title="No podés eliminar tu propia cuenta">Eliminar</button>
                <?php else: ?>
                  <form method="POST" action="<?= BASE_URL ?>/index.php?c=Usuario&amp;a=eliminar"
                        data-msg="<?= h('¿Eliminar a ' . $u['nombre_usuario_anonimo'] . '? Se borran también sus publicaciones y comentarios. No se puede deshacer.') ?>"
                        onsubmit="return confirm(this.dataset.msg)">
                    <?= csrfCampo() ?>
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button type="submit">Eliminar</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="conteo"><?= count($usuarios) ?> usuario(s)</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
