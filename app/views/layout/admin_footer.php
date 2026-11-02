      </div>

      <div class="bottom-bar">
        <div class="status-pill">Admin: <?= h($actor['nombre_usuario_anonimo']) ?></div>
        <a class="status-pill" href="<?= BASE_URL ?>/app/views/admin/index.php">Panel</a>
        <a class="status-pill" href="<?= BASE_URL ?>/index.php?c=Foro&amp;a=index">Ir al foro</a>
        <a class="status-pill" href="<?= BASE_URL ?>/index.php?c=Auth&amp;a=logout">Cerrar sesión</a>
      </div>
    </div>
  </div>

  <div class="taskbar">
    <div id="reloj" style="margin-left: auto; padding-right: 10px; color: #808080;">--:-- --</div>
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

  <script src="<?= BASE_URL ?>/app/public/js/fondo.js"></script>
</body>
</html>
