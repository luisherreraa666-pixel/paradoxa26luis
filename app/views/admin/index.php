<?php
require_once __DIR__ . '/../../../config/auth.php';
requiereRol('admin'); // solo administradores (sin sesión te manda al login; si no sos admin, 403)

$actor         = usuarioActual();
$tituloVentana = 'Admin Command Center';

require __DIR__ . '/../layout/admin_header.php';
?>
<style>
  .window { width: 620px; }
  .content-wrapper { min-height: 0; }
</style>

<div class="admin-card">
  <div class="card-head">
    <span class="card-icon">👥</span>
    <div>
      <h3>Gestión de Usuarios</h3>
      <p class="card-sub">Buscá, revisá y administrá las cuentas registradas.</p>
    </div>
  </div>

  <form action="<?= BASE_URL ?>/index.php" method="GET">
    <input type="hidden" name="c" value="Usuario">
    <input type="hidden" name="a" value="listar">
    <div class="field">
      <label for="q">Buscar usuario (correo, alias o nombre)</label>
      <input type="text" id="q" name="q" placeholder="usuario@paradoxa.corp" autofocus />
    </div>
    <div class="button-row">
      <button type="submit" class="btn-primary">Buscar</button>
      <a class="btn" href="<?= BASE_URL ?>/index.php?c=Usuario&amp;a=listar">Ver todos</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../layout/admin_footer.php'; ?>
