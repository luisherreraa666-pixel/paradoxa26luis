<?php
require_once __DIR__ . '/../../../config/auth.php';

// Esta vista la renderiza ForoController (que ya exigió sesión y trae los datos del foro).
// Si la abren directo por URL, se los manda al controller.
if (!isset($modoForo)) {
    header('Location: ' . BASE_URL . '/index.php?c=Foro&a=index');
    exit;
}
$usuario = usuarioActual();
$alias   = $usuario['nombre_usuario_anonimo'];
$esAdmin = tieneRol('admin');

// Pestaña que se abre al cargar: ?vista=cuenta | sobre (por defecto, el foro).
$vistasIniciales = ['cuenta' => 'viewCuenta', 'sobre' => 'viewSobre'];
$vistaPedida     = (isset($_GET['vista']) && is_string($_GET['vista'])) ? $_GET['vista'] : '';
$vistaInicial    = $vistasIniciales[$vistaPedida] ?? 'viewForo';
$titulosVista    = ['viewForo' => 'Foro', 'viewCuenta' => 'Mi cuenta', 'viewSobre' => 'Sobre nosotros'];
?>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PARADOXA26 — Foro</title>
  <link rel="icon" type="image/png" sizes="64x64" href="<?= BASE_URL ?>/app/public/img/favicon.png" />
  <link rel="shortcut icon" href="<?= BASE_URL ?>/app/public/img/favicon.ico" />
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/app/public/img/apple-touch-icon.png" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/app/public/css/style.css" />
  <style>
    body {
      overflow: hidden;
      position: relative;
      height: 100vh;
      width: 100vw;
      margin: 0;
      padding: 0;
      background: #000;
      font-family: Tahoma, Verdana, Arial, sans-serif;
    }
    #bgCanvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
    }
    .desktop {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1;
      pointer-events: none;
    }
    .window {
      position: absolute;
      cursor: default;
      z-index: 2;
      width: 1000px;
      border: 1px solid #0a246a;
      background: #d4d0c8;
      box-shadow: 0 0 0 1px #ffffff inset, 4px 4px 20px rgba(0, 0, 0, 0.35);
      pointer-events: auto;
    }
    .title-bar {
      height: 32px;
      background: linear-gradient(to right, #0a246a, #3a6ea5);
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 8px;
      font-size: 13px;
      font-weight: bold;
      user-select: none;
    }
    .title-left { display: flex; align-items: center; gap: 8px; }
    .title-icon {
      width: 16px;
      height: 16px;
      background: #d8e7f6;
      border: 1px solid #0b2347;
      display: inline-block;
      position: relative;
    }
    .title-icon::after {
      content: attr(data-letter);
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      color: #0a246a;
      font-size: 11px;
      font-weight: bold;
    }
    .window-controls { display: flex; gap: 4px; }
    .ctrl {
      width: 22px;
      height: 20px;
      border: 1px solid #ffffff;
      border-right-color: #404040;
      border-bottom-color: #404040;
      background: #d4d0c8;
      font-size: 12px;
      display: grid;
      place-items: center;
      line-height: 1;
      cursor: pointer;
    }
    .content-sidebar {
      display: flex;
      height: min(600px, calc(100vh - 140px));
      background: #d4d0c8;
    }
    .side-nav {
      width: 200px;
      background: #c0bdb5;
      border-right: 2px solid #808080;
      display: flex;
      flex-direction: column;
      padding: 10px 0;
    }
    .nav-item {
      padding: 8px 15px;
      font-size: 12px;
      text-align: left;
      background: none;
      border: none;
      cursor: pointer;
      color: #000;
      font-weight: normal;
    }
    .nav-item:hover { background: #b0aca2; }
    .nav-item.active {
      background: #efefef;
      font-weight: bold;
      box-shadow: inset 2px 2px 5px rgba(0,0,0,0.2);
    }
    .main-area {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background: #d4d0c8;
    }
    .view { display: none; }
    .view.active { display: block; }
    .panel {
      background: #efefef;
      border: 2px solid;
      border-color: #ffffff #808080 #808080 #ffffff;
      padding: 15px;
      margin-bottom: 20px;
      box-shadow: 1px 1px 5px rgba(0,0,0,0.1);
    }
    .panel h2 {
      margin: 0 0 10px 0;
      font-size: 18px;
      color: #0a246a;
    }
    .thread-card {
      background: #efefef;
      border: 2px solid;
      border-color: #ffffff #808080 #808080 #ffffff;
      padding: 12px;
      margin-bottom: 12px;
    }
    .thread-card h3 {
      margin: 0 0 5px 0;
      font-size: 14px;
      color: #0a246a;
    }
    .meta { font-size: 11px; color: #666; margin-bottom: 8px; }
    .field { margin-bottom: 12px; }
    .field label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px; }
    .field input, .field textarea {
      width: 100%;
      padding: 6px;
      font-size: 13px;
      border: 2px inset #c0c0c0;
      background: #fff;
      box-sizing: border-box;
    }
    .button-row button {
      padding: 6px 12px;
      font-size: 12px;
      border: 2px solid;
      border-color: #ffffff #404040 #404040 #ffffff;
      background: #d4d0c8;
      cursor: pointer;
    }
    .button-row button.primary {
      background: #efefef;
      font-weight: bold;
    }
    .bottom-bar {
      height: 30px;
      background: #d4d0c8;
      border-top: 2px solid #ffffff;
      display: flex;
      align-items: center;
      padding: 0 8px;
      gap: 10px;
      font-size: 11px;
    }
    .status-pill {
      border: 2px inset #c0c0c0;
      padding: 2px 8px;
      background: #efefef;
    }
    .taskbar {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 30px;
      background: #d4d0c8;
      border-top: 2px solid #ffffff;
      display: flex;
      align-items: center;
      padding: 0 5px;
      z-index: 10;
    }
    .start-button {
      padding: 2px 8px;
      border: 2px solid;
      border-color: #ffffff #404040 #404040 #ffffff;
      background: #d4d0c8;
      font-weight: bold;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    /* ----- Foro dinámico: hilos, comentarios y reacciones ----- */
    a.thread-card { display: block; color: inherit; text-decoration: none; cursor: pointer; }
    a.thread-card:hover { background: #f6f4ee; }
    .main-area { overflow-wrap: anywhere; } /* una palabra larguísima no debe romper el layout */
    .thread-card p { margin: 0; font-size: 13px; line-height: 1.4; color: #222; }
    .volver { display: inline-block; margin-bottom: 12px; font-size: 12px; color: #0a246a; }
    .mensaje-texto { font-size: 13px; line-height: 1.5; color: #111; margin: 6px 0 10px; }
    .comentario { padding: 10px 0; border-top: 1px solid #b0aca2; }
    .panel h2 + .comentario { border-top: none; }
    .barra-mensaje { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .barra-mensaje form { margin: 0; }
    .reacciones { display: flex; gap: 6px; flex-wrap: wrap; }
    .reacciones form { margin: 0; }
    .reaccion, .btn-mini { min-width: 0; padding: 2px 8px; font-size: 12px; border: 2px solid; border-color: #ffffff #404040 #404040 #ffffff; background: #d4d0c8; cursor: pointer; color: #000; }
    .reaccion.activa { background: #c8d8f0; border-color: #404040 #ffffff #ffffff #404040; font-weight: bold; }
    .btn-mini { color: #8b0000; }
    .msg-ok, .msg-error { padding: 8px 10px; margin-bottom: 14px; font-size: 12px; border: 2px inset #c0c0c0; background: #fff; }
    .msg-ok { color: #0b5e2b; }
    .msg-error { color: #8b0000; }
    /* ----- Avatares / foto de perfil ----- */
    .avatar { display: inline-block; vertical-align: middle; margin-right: 4px; border: 1px solid; border-color: #808080 #ffffff #ffffff #808080; background: #fff; object-fit: cover; flex-shrink: 0; }
    .avatar--inicial { color: #fff; font-weight: bold; text-align: center; font-family: Tahoma, Verdana, Arial, sans-serif; user-select: none; }
    .perfil-foto { display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap; }
    .perfil-foto-form { flex: 1; min-width: 220px; }
    .perfil-foto .avatar { margin-right: 0; border-width: 2px; }
    .perfil-foto-quitar { margin-top: 10px; }
    .perfil-foto-quitar form { margin: 0; }
    .field input[type="file"] { padding: 4px; background: #fff; }
    /* ----- Imagen adjunta a un hilo ----- */
    .hilo-img { display: block; max-width: 100%; height: auto; margin: 8px 0; border: 2px solid; border-color: #808080 #ffffff #ffffff #808080; background: #fff; }
    .hilo-img--mini { max-height: 160px; width: auto; }
    /* ----- Sobre nosotros ----- */
    .panel ul { margin: 8px 0 0 0; padding-left: 20px; font-size: 13px; line-height: 1.5; color: #222; }
    .panel ul li { margin-bottom: 4px; }
    .panel p + p { margin-top: 8px; }
    .datos { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; margin-top: 6px; }
    .datos th, .datos td { border: 1px solid #b0aca2; padding: 6px 10px; text-align: left; vertical-align: top; }
    .datos th { background: #d4d0c8; width: 38%; font-weight: bold; }
  </style>
</head>
<body onload="init()">
  <canvas id="bgCanvas"></canvas>
  <div class="desktop">
    <div id="mainWindow" class="window">
      <div id="windowHeader" class="title-bar">
        <div class="title-left">
          <span class="title-icon" data-letter="P"></span>
          <span>Paradoxa26 — <span id="viewTitle"><?= h($titulosVista[$vistaInicial]) ?></span></span>
        </div>
        <div class="window-controls">
          <div class="ctrl">_</div>
          <div class="ctrl">□</div>
          <div class="ctrl">×</div>
        </div>
      </div>
      <div class="content-sidebar">
        <nav class="side-nav">
          <button class="nav-item<?= $vistaInicial === 'viewForo' ? ' active' : '' ?>" data-view="viewForo">Foro</button>
          <button class="nav-item<?= $vistaInicial === 'viewCuenta' ? ' active' : '' ?>" data-view="viewCuenta">Mi cuenta</button>
          <button class="nav-item<?= $vistaInicial === 'viewSobre' ? ' active' : '' ?>" data-view="viewSobre">Sobre nosotros</button>
          <?php if ($esAdmin): ?>
          <button class="nav-item" type="button" onclick="location.href='<?= BASE_URL ?>/app/views/admin/index.php'">Panel de administración</button>
          <?php endif; ?>
          <button class="nav-item" type="button" onclick="location.href='<?= BASE_URL ?>/index.php?c=Auth&amp;a=logout'">Cerrar sesión</button>
        </nav>
        <main class="main-area">
          <section id="viewForo" class="view<?= $vistaInicial === 'viewForo' ? ' active' : '' ?>">
            <?php require __DIR__ . '/foro_' . $modoForo . '.php'; ?>
          </section>
          <section id="viewCuenta" class="view<?= $vistaInicial === 'viewCuenta' ? ' active' : '' ?>">
            <div class="panel">
              <h2>Mi cuenta</h2>
              <p>Tu alias es lo único visible para el resto de los investigadores.</p>
              <?php if ($flash && $vistaInicial === 'viewCuenta'): ?>
                <div class="msg-<?= h($flash['tipo']) ?>"><?= h($flash['texto']) ?></div>
              <?php endif; ?>
            </div>
            <div class="panel">
              <h2>Foto de perfil</h2>
              <div class="perfil-foto">
                <div id="fotoPreview"><?= avatarPerfil($usuario['foto_perfil'] ?? null, $alias, 96) ?></div>
                <div class="perfil-foto-form">
                  <form method="POST" enctype="multipart/form-data" action="<?= BASE_URL ?>/index.php?c=Perfil&amp;a=guardarFoto">
                    <?= csrfCampo() ?>
                    <div class="field">
                      <label for="fotoPerfil">Elegí una imagen</label>
                      <input type="file" id="fotoPerfil" name="foto" accept="image/png,image/jpeg,image/gif,image/webp" required />
                      <div class="field-hint">JPG, PNG, GIF o WebP, hasta 2 MB. Se recorta en cuadrado. La ven todos los usuarios junto a tu alias.</div>
                    </div>
                    <div class="button-row">
                      <button type="submit" class="primary">Guardar foto</button>
                    </div>
                  </form>
                  <?php if (!empty($usuario['foto_perfil'])): ?>
                  <div class="perfil-foto-quitar">
                    <form method="POST" action="<?= BASE_URL ?>/index.php?c=Perfil&amp;a=eliminarFoto" data-confirmar="¿Quitar tu foto de perfil?" data-confirmar-ok="Quitar">
                      <?= csrfCampo() ?>
                      <button type="submit" class="btn-mini">Quitar foto</button>
                    </form>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="panel">
              <form method="POST" action="<?= BASE_URL ?>/index.php?c=Perfil&amp;a=guardarDatos">
                <?= csrfCampo() ?>
                <div class="field">
                  <label for="aliasField">Alias público</label>
                  <input type="text" id="aliasField" name="alias" value="<?= htmlspecialchars($alias, ENT_QUOTES, 'UTF-8') ?>" minlength="3" maxlength="30" pattern="[A-Za-z0-9_.-]{3,30}" required />
                  <div class="field-hint">Este es el nombre que ven los demás usuarios del foro. De 3 a 30 caracteres: letras, números, guion, guion bajo o punto.</div>
                </div>
                <div class="field">
                  <label for="bioField">Descripción breve</label>
                  <textarea id="bioField" name="biografia" rows="3" maxlength="255" placeholder="Contá algo sobre tu forma de investigar (opcional)"><?= htmlspecialchars($usuario['biografia'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                  <div class="field-hint">Hasta 255 caracteres.</div>
                </div>
                <div class="button-row">
                  <button type="submit" class="primary">Guardar cambios</button>
                </div>
              </form>
            </div>
          </section>
          <section id="viewSobre" class="view<?= $vistaInicial === 'viewSobre' ? ' active' : '' ?>">
            <div class="panel">
              <h2>Sobre nosotros</h2>
              <p>Paradoxa26 es el espacio de trabajo compartido de Paradoxa: un lugar donde empleados e investigadores ponen en común lo que encuentran en los registros archivados, contrastan datos y avanzan juntos en el caso.</p>
              <p>Desde el nodo ARG-26 custodiamos y mantenemos disponibles esos registros, y los ofrecemos a través de PARADOX, nuestro entorno corporativo interno.</p>
            </div>
            <div class="panel">
              <h2>Nuestros objetivos</h2>
              <ul>
                <li>Preservar los registros archivados de la empresa de forma íntegra y trazable.</li>
                <li>Facilitar el trabajo colaborativo entre investigadores, cuidando el anonimato de cada alias.</li>
                <li>Proteger la confidencialidad de la información y de las cuentas de los usuarios.</li>
                <li>Mejorar de forma continua PARADOX y sus servicios de acceso, archivo y comunicaciones.</li>
              </ul>
            </div>
            <div class="panel">
              <h2>Horarios</h2>
              <table class="datos">
                <tr><th>Foro y portal de acceso</th><td>Todos los días, las 24 horas</td></tr>
                <tr><th>Soporte y administración</th><td>Lunes a viernes, de 9:00 a 18:00 (hora de Argentina)</td></tr>
                <tr><th>Sincronización del archivo</th><td>Todos los días a las 02:13 AM</td></tr>
                <tr><th>Respuesta a consultas</th><td>Dentro de las 48 horas hábiles</td></tr>
              </table>
            </div>
            <div class="panel">
              <h2>Contacto</h2>
              <table class="datos">
                <tr><th>Correo</th><td><a href="mailto:paradoxa266@gmail.com">paradoxa266@gmail.com</a></td></tr>
                <tr><th>Región / nodo</th><td>ARG-26 (Argentina)</td></tr>
                <tr><th>Sistema</th><td>PARADOX — Build 1.0</td></tr>
              </table>
              <p style="margin-top: 10px;">Si tenés un problema con tu cuenta o querés reportar contenido del foro, escribinos con tu alias y una breve descripción. Nunca te vamos a pedir tu contraseña.</p>
            </div>
          </section>
        </main>
      </div>
      <div class="bottom-bar">
        <div class="status-pill"><?= avatarPerfil($usuario['foto_perfil'] ?? null, $alias, 16) ?><?= htmlspecialchars($alias, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="status-pill">Conectado a P26-NET</div>
        <div class="status-pill">Build 1.0</div>
      </div>
    </div>
  </div>
  <div class="taskbar">
    <div id="reloj" style="margin-left: auto; padding-right: 10px; color: #808080;">
      --:-- --
    </div>
  </div>
  <script>
    (function () {
      var reloj = document.getElementById('reloj');
      function actualizar() {
        var ahora = new Date();
        reloj.textContent = ahora.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        // Vuelve a actualizar justo cuando cambie el minuto
        setTimeout(actualizar, (60 - ahora.getSeconds()) * 1000 - ahora.getMilliseconds());
      }
      if (reloj) actualizar();
    })();
  </script>
  <canvas id="bgCanvas"></canvas>
  <script>
    const navItems = document.querySelectorAll('.nav-item[data-view]');
    const views = document.querySelectorAll('.view');
    const viewTitle = document.getElementById('viewTitle');
    const titles = { viewForo: 'Foro', viewCuenta: 'Mi cuenta', viewSobre: 'Sobre nosotros' };
    navItems.forEach(item => {
      item.addEventListener('click', () => {
        navItems.forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        const target = item.getAttribute('data-view');
        views.forEach(v => v.classList.remove('active'));
        document.getElementById(target).classList.add('active');
        viewTitle.textContent = titles[target];
      });
    });

    // Vista previa de la foto elegida (antes de guardarla) y aviso si pesa más de 2 MB.
    const fotoInput = document.getElementById('fotoPerfil');
    if (fotoInput) {
      fotoInput.addEventListener('change', () => {
        const archivo = fotoInput.files[0];
        if (!archivo) return;
        if (archivo.size > 2 * 1024 * 1024) {
          fotoInput.value = '';
          Dialogo.aviso('La imagen pesa más de 2 MB. Elegí una más liviana.', { titulo: 'Imagen demasiado pesada' });
          return;
        }
        if (!archivo.type.startsWith('image/')) return;
        const caja = document.getElementById('fotoPreview');
        const img = document.createElement('img');
        img.className = 'avatar';
        img.alt = 'Vista previa';
        img.style.cssText = 'width:96px;height:96px';
        img.src = URL.createObjectURL(archivo);
        caja.innerHTML = '';
        caja.appendChild(img);
      });
    }

    const canvas = document.getElementById('bgCanvas');
    const ctx = canvas.getContext('2d');
    let width, height, dpr;
    let points = [];
    let mouse = { x: -1000, y: -1000 };
    const spacing = 50;
    function resize() {
      dpr = window.devicePixelRatio || 1;
      width = window.innerWidth;
      height = window.innerHeight;
      canvas.width = width * dpr;
      canvas.height = height * dpr;
      ctx.scale(dpr, dpr);
      initPoints();
    }
    function initPoints() {
      points = [];
      const cols = Math.ceil(width / spacing) + 1;
      const rows = Math.ceil(height / spacing) + 1;
      for (let y = 0; y <= rows; y++) {
        for (let x = 0; x <= cols; x++) {
          points.push({ baseX: x * spacing + (y % 2) * (spacing / 2), baseY: y * spacing * 0.8660254, x: x * spacing + (y % 2) * (spacing / 2), y: y * spacing * 0.8660254 });
        }
      }
    }
    function animate() {
      ctx.fillStyle = '#0a246a';
      ctx.fillRect(0, 0, width, height);
      const time = Date.now() * 0.001;
      const cols = Math.ceil(width / spacing) + 1;
      const rows = Math.ceil(height / spacing) + 1;
      points.forEach((p) => {
        const waveX = Math.sin(time + p.baseY * 0.01) * 15;
        const waveY = Math.cos(time + p.baseX * 0.01) * 15;
        const dx = p.baseX - mouse.x;
        const dy = p.baseY - mouse.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        const forceRadius = 200;
        let offsetX = 0, offsetY = 0;
        if (dist < forceRadius) {
          const force = (forceRadius - dist) / forceRadius;
          offsetX = (dx / dist) * force * 30;
          offsetY = (dy / dist) * force * 30;
        }
        const targetX = p.baseX + waveX + offsetX;
        const targetY = p.baseY + waveY + offsetY;
        p.x += (targetX - p.x) * 0.1;
        p.y += (targetY - p.y) * 0.1;
      });
      ctx.strokeStyle = 'rgba(160, 200, 255, 0.2)';
      ctx.lineWidth = 1;
      for (let y = 0; y < rows; y++) {
        for (let x = 0; x < cols; x++) {
          const p1 = points[y * (cols + 1) + x];
          const p2 = points[y * (cols + 1) + (x + 1)];
          const p3 = points[(y + 1) * (cols + 1) + x];
          const p4 = points[(y + 1) * (cols + 1) + (x + 1)];
          if (!p1 || !p2 || !p3 || !p4) continue;
          ctx.beginPath();
          ctx.moveTo(p1.x, p1.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.lineTo(p4.x, p4.y);
          ctx.lineTo(p3.x, p3.y);
          ctx.closePath();
          ctx.stroke();
        }
      }
      requestAnimationFrame(animate);
    }
    function init() {
      resize();
      const windowEl = document.getElementById('mainWindow');
      windowEl.style.left = Math.max(0, (window.innerWidth - windowEl.offsetWidth) / 2) + 'px';
      windowEl.style.top = Math.max(0, (window.innerHeight - windowEl.offsetHeight) / 2) + 'px';
      animate();
    }
    window.addEventListener('resize', resize);
    window.addEventListener('mousemove', (e) => {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
    });
  </script>
  <script src="<?= BASE_URL ?>/app/public/js/dialogo.js"></script>
</body>
</html>
