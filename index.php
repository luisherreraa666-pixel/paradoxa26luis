<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PHYLLO OS</title>
  <link rel="stylesheet" href="app/public/css/style.css" />
  <style>
    body {
      overflow: hidden;
      position: relative;
      height: 100vh;
      width: 100vw;
      margin: 0;
      padding: 0;
      background: #000;
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
      pointer-events: auto;
    }
    .title-bar {
      cursor: move;
      user-select: none;
    }
    /* --- Decryptor Window (acertijo escondido) --- */
    .hidden-artifact {
      position: absolute;
      top: 25%;
      left: 30%;
      width: 450px;
      height: 200px;
      background: var(--color-panel);
      border: 1px solid var(--color-titlebar-start);
      box-shadow: 0 0 0 1px #ffffff inset, 4px 4px 15px rgba(0, 0, 0, 0.5);
      z-index: 0;
      pointer-events: auto;
      font-family: var(--font-system);
      display: none;
      overflow: hidden;
    }
    .hidden-artifact.visible {
      display: block;
      z-index: 10;
    }
    .hidden-artifact .title-bar {
      height: 32px;
      background: linear-gradient(to right, var(--color-titlebar-start), var(--color-titlebar-end));
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 8px;
      font-size: 13px;
      font-weight: bold;
    }
    .hidden-artifact .title-left {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .hidden-artifact .window-controls {
      display: flex;
      gap: 4px;
    }
    .hidden-artifact .ctrl {
      width: 22px;
      height: 20px;
      border: 1px solid #ffffff;
      border-right-color: #404040;
      border-bottom-color: #404040;
      background: var(--color-panel);
      font-size: 12px;
      display: grid;
      place-items: center;
      line-height: 1;
      cursor: pointer;
    }
    .hidden-artifact .content {
      padding: 12px;
      font-size: 13px;
      color: var(--color-text);
      background: var(--color-panel);
      display: flex;
      flex-direction: column;
      height: calc(100% - 32px);
      box-sizing: border-box;
    }
    .hidden-artifact .input-group {
      display: flex;
      gap: 10px;
      margin-bottom: 10px;
      align-items: flex-start;
    }
    .hidden-artifact .field {
      display: flex;
      flex-direction: column;
    }
    .hidden-artifact .field.text-field {
      flex: 1;
    }
    .hidden-artifact .field.shift-field {
      width: 60px;
    }
    .hidden-artifact label {
      display: block;
      font-size: 11px;
      font-weight: bold;
      margin-bottom: 3px;
    }
    .hidden-artifact input {
      padding: 4px 6px;
      font-size: 12px;
      border: 2px inset #c0c0c0;
      background: var(--color-input);
      box-sizing: border-box;
      width: 100%;
    }
    .hidden-artifact .btn-decrypt {
      display: block;
      width: 100%;
      padding: 6px;
      font-size: 12px;
      cursor: pointer;
      background: var(--color-panel);
      border: 2px solid;
      border-color: var(--border-raised-light) var(--border-raised-darker) var(--border-raised-darker) var(--border-raised-light);
      font-weight: bold;
      margin-bottom: 10px;
    }
    .hidden-artifact .btn-decrypt:active {
      border-color: var(--border-raised-darker) var(--border-raised-light) var(--border-raised-light) var(--border-raised-darker);
    }
    .hidden-artifact #decryptedResult {
      padding: 8px;
      background: #000;
      color: #0f0;
      font-family: var(--font-mono);
      font-size: 11px;
      flex: 1;
      border: 2px inset #808080;
      word-break: break-all;
      white-space: pre-wrap;
      overflow-y: auto;
    }
    .taskbar {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 30px;
      background: var(--color-panel);
      border-top: 2px solid var(--border-raised-light);
      display: flex;
      align-items: center;
      padding: 0 5px;
      z-index: 10;
      font-size: 12px;
      pointer-events: auto;
    }
    .start-button {
      padding: 2px 8px;
      border: 2px solid;
      border-color: var(--border-raised-light) var(--border-raised-darker) var(--border-raised-darker) var(--border-raised-light);
      background: var(--color-panel);
      font-weight: bold;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .start-button:active {
      border-color: var(--border-raised-darker) var(--border-raised-light) var(--border-raised-light) var(--border-raised-darker);
    }
  </style>
</head>
<body onload="init()">
  <!--
      Ibjm Jmzjdi
      vtfs: wjdups-lbabnbspw
      fnqmpzff je: F-2619
      qbttxpse: npui_26
      tubuvt: bsdijwfe
      sfnpwfs bouft ef sfwjtjpo nbuvujob
    -->

  <canvas id="bgCanvas"></canvas>

  <div id="decryptorWindow" class="hidden-artifact">
    <div id="decryptorHeader" class="title-bar">
      <div class="title-left">
        <span class="title-icon" data-letter="D"></span>
        <span>DECRYPTOR_TOOL.EXE</span>
      </div>
      <div class="window-controls">
        <div class="ctrl">_</div>
        <div class="ctrl">□</div>
        <div class="ctrl" onclick="document.getElementById('decryptorWindow').classList.remove('visible')">×</div>
      </div>
    </div>
    <div class="content">
      <div id="decryptedResult" style="margin-bottom: 15px; padding: 10px; background: #000; color: #0f0; font-family: var(--font-mono); font-size: 11px; border: 2px inset #808080; word-break: break-all; white-space: pre-wrap;">
        <strong style="color: #aaa;">DECRYPTED_DATA:</strong><br>
        Esperando entrada...
      </div>
      <div class="input-group">
        <div class="field text-field">
          <label>Texto Cifrado:</label>
          <input type="text" id="cipherText" value="sfwjtbs fm dvfsqp ef mb qpmjmmb" placeholder="Ingrese texto..." />
        </div>
        <div class="field shift-field">
          <label>Shift:</label>
          <input type="text" id="shiftValue" value="1" readonly />
        </div>
      </div>
      <button class="btn-decrypt" onclick="decryptCaesar()">DESCIFRAR</button>
    </div>
  </div>

  <div id="notepadWindow" class="window" style="top: 60px; left: 60px; width: 340px;">
    <div id="notepadHeader" class="title-bar">
      <div class="title-left">
        <span class="title-icon" data-letter="N"></span>
        <span>notas.txt - bloc de notas</span>
      </div>
      <div class="window-controls">
        <div class="ctrl">_</div>
        <div class="ctrl">□</div>
        <div class="ctrl" id="closeNotepadBtn">×</div>
      </div>
    </div>
    <div class="menu-bar">
      <span style="text-decoration: line-through; opacity: 0.5;">archivo</span>
      <span style="text-decoration: line-through; opacity: 0.5;">editar</span>
      <span style="text-decoration: line-through; opacity: 0.5;">formato</span>
      <span style="text-decoration: line-through; opacity: 0.5;">ver</span>
      <span style="text-decoration: line-through; opacity: 0.5;">ayuda</span>
    </div>
    <div style="background: #fff; color: #000; padding: 14px; font-family: 'Courier New', monospace; font-size: 13px; min-height: 90px;">
      los controles de esta ventana no siempre hacen lo que dicen.
    </div>
  </div>

  <div class="desktop">
    <div id="mainWindow" class="window">
      <div id="windowHeader" class="title-bar">
        <div class="title-left">
          <span class="title-icon"></span>
          <span>Portal de acceso para empleados</span>
        </div>
        <div class="window-controls">
          <div class="ctrl">_</div>
          <div class="ctrl">□</div>
          <div class="ctrl" id="closeLoginBtn">×</div>
        </div>
      </div>

      <div class="menu-bar">
        <span>Archivos</span>
        <span>Editar</span>
        <span>Vista</span>
        <span>Herramientas</span>
        <span>Seguridad</span>
        <span>Ayuda</span>
      </div>

      <div class="content">
        <section class="left-panel">
          <div class="banner">
            <h1>PHYLLO OS</h1>
            <p>
              Entorno corporativo interno para la identificación de empleados, el acceso a archivos y las comunicaciones administrativas.
            </p>
          </div>

          <div class="status-box">
            <strong>System Notice</strong><br>
            Este terminal proporciona acceso a determinados recursos internos de la empresa. Los empleados autorizados pueden seguir utilizando sus credenciales de empleado asignadas..

            <div class="terminal">
              SYSTEM STATUS: ONLINE<br>
              REGION NODE: ARG-26<br>
              AUTH SERVICE: RUNNING<br>
              ARCHIVE SYNC: 02:13 AM<br>
              LAST USER SESSION: INCOMPLETE
            </div>
          </div>

          <div class="notice-box">
            <strong>Advertencia de confidencialidad</strong><br>
            El acceso, la divulgación o la duplicación no autorizados de los registros almacenados en este entorno están estrictamente prohibidos y pueden ser objeto de revisión interna.
          </div>
        </section>

        <section class="right-panel">
          <div class="login-box">
            <h2>Employee Login</h2>
            <p>
              Introduzca su código de identificación de empleado y su contraseña para continuar.
            </p>

            <form id="loginForm">
              <div class="field">
                <label for="employeeId">Employee ID</label>
                <input type="text" id="employeeId" autocomplete="off" />
              </div>

              <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Password" autocomplete="off" />
              </div>

              <div class="button-row">
                <button type="submit">Log In</button>
                <button type="button" id="hintBtn">Asistencia</button>
              </div>
            </form>

            <div class="message" id="message"></div>
          </div>

          <div class="terminal" id="syncCountdown" style="margin-top: 18px;">
            NEXT ARCHIVE SYNC IN:<br>
            <span id="countdownDisplay">--:--:--</span>
          </div>
        </section>
      </div>

      <div class="bottom-bar">
        <div class="status-pill">Connected to P26-NET</div>
        <div class="status-pill">Security Layer Enabled</div>
        <div class="status-pill">Build 5.2.6</div>
        <button type="button" class="status-pill" id="audioToggle" style="cursor: pointer;">🔈 Audio</button>
      </div>
    </div>
  </div>

  <div class="taskbar">
    <div class="start-button">
      <span style="font-size: 14px;">🪟</span> Inicio
    </div>
    <div style="margin-left: auto; padding-right: 10px; color: var(--color-muted);">
      12:00 PM
    </div>
  </div>

  <audio id="bgMusic" src="app/public/audio/backgroundtheme.mp3" loop preload="auto"></audio>
  <audio id="bootSound" src="app/public/audio/phylloos.mp3" preload="auto"></audio>
  <audio id="loginStartupSound" src="app/public/audio/loginstartup.mp3" preload="auto"></audio>
  <audio id="postLoginSound" src="app/public/audio/postlogin.mp3" preload="auto"></audio>
  <audio id="errorSound" src="app/public/audio/error.mp3" preload="auto"></audio>
  <audio id="interactSound" src="app/public/audio/interact.mp3" preload="auto"></audio>

  <script>
    // --- Window Logic (Main Window) ---
    const windowEl = document.getElementById('mainWindow');
    const headerEl = document.getElementById('windowHeader');
    let isDragging = false;
    let offsetX, offsetY;

    headerEl.addEventListener('mousedown', (e) => {
      isDragging = true;
      offsetX = e.clientX - windowEl.offsetLeft;
      offsetY = e.clientY - windowEl.offsetTop;
      windowEl.style.zIndex = 100;
    });

    document.addEventListener('mousemove', (e) => {
      if (!isDragging) return;
      windowEl.style.left = (e.clientX - offsetX) + 'px';
      windowEl.style.top = (e.clientY - offsetY) + 'px';
      windowEl.style.margin = '0';
    });

    document.addEventListener('mouseup', () => {
      isDragging = false;
    });

    function centerWindow() {
      const x = (window.innerWidth - windowEl.offsetWidth) / 2;
      const y = (window.innerHeight - windowEl.offsetHeight) / 2;
      windowEl.style.left = x + 'px';
      windowEl.style.top = y + 'px';
      windowEl.style.margin = '0';
    }

    // --- Decryptor Window Logic ---
    const decryptorEl = document.getElementById('decryptorWindow');
    const decryptorHeader = document.getElementById('decryptorHeader');
    let isDraggingDec = false;
    let offXDec, offYDec;

    decryptorHeader.addEventListener('mousedown', (e) => {
      isDraggingDec = true;
      offXDec = e.clientX - decryptorEl.offsetLeft;
      offYDec = e.clientY - decryptorEl.offsetTop;
      decryptorEl.style.zIndex = 100;
    });

    document.addEventListener('mousemove', (e) => {
      if (!isDraggingDec) return;
      decryptorEl.style.left = (e.clientX - offXDec) + 'px';
      decryptorEl.style.top = (e.clientY - offYDec) + 'px';
      decryptorEl.style.margin = '0';
    });

    document.addEventListener('mouseup', () => {
      isDraggingDec = false;
    });

    document.getElementById('closeLoginBtn').addEventListener('click', () => {
      decryptorEl.classList.add('visible');
      decryptorEl.style.left = (window.innerWidth / 2 - 225) + 'px';
      decryptorEl.style.top = (window.innerHeight / 2 - 100) + 'px';
    });

    // --- Notepad Window Logic ---
    // A diferencia de la × de la ventana principal (que no cierra nada,
    // abre el decryptor), acá la × sí cierra la ventana de verdad.
    const notepadEl = document.getElementById('notepadWindow');
    const notepadHeader = document.getElementById('notepadHeader');
    let isDraggingNotepad = false;
    let offXNotepad, offYNotepad;

    notepadHeader.addEventListener('mousedown', (e) => {
      isDraggingNotepad = true;
      offXNotepad = e.clientX - notepadEl.offsetLeft;
      offYNotepad = e.clientY - notepadEl.offsetTop;
      notepadEl.style.zIndex = 100;
    });

    document.addEventListener('mousemove', (e) => {
      if (!isDraggingNotepad) return;
      notepadEl.style.left = (e.clientX - offXNotepad) + 'px';
      notepadEl.style.top = (e.clientY - offYNotepad) + 'px';
    });

    document.addEventListener('mouseup', () => {
      isDraggingNotepad = false;
    });

    document.getElementById('closeNotepadBtn').addEventListener('click', () => {
      notepadEl.style.display = 'none';
    });

    // --- Caesar Cipher Logic ---
    function decryptCaesar() {
      const text = document.getElementById('cipherText').value;
      const shift = parseInt(document.getElementById('shiftValue').value) || 0;
      const resultEl = document.getElementById('decryptedResult');

      let result = "";
      for (let i = 0; i < text.length; i++) {
        let char = text[i];
        if (char.match(/[a-z]/i)) {
          const code = text.charCodeAt(i);
          let shiftBase = (code >= 65 && code <= 90) ? 65 : 97;
          char = String.fromCharCode(((code - shiftBase - shift + 26) % 26 + 26) % 26 + shiftBase);
        }
        result += char;
      }
      resultEl.textContent = result || "Sin resultado";
    }

    // --- Retro-Fluid Tessellation Background Logic ---
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
          points.push({
            baseX: x * spacing,
            baseY: y * spacing,
            x: x * spacing,
            y: y * spacing
          });
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

          if (p1 && p2 && p3) {
            ctx.beginPath();
            ctx.moveTo(p1.x, p1.y);
            ctx.lineTo(p2.x, p2.y);
            ctx.lineTo(p3.x, p3.y);
            ctx.closePath();
            ctx.stroke();
          }
          if (p2 && p3 && p4) {
            ctx.beginPath();
            ctx.moveTo(p2.x, p2.y);
            ctx.lineTo(p3.x, p3.y);
            ctx.lineTo(p4.x, p4.y);
            ctx.closePath();
            ctx.stroke();
          }
        }
      }

      requestAnimationFrame(animate);
    }

    // --- ARCHIVE SYNC Countdown ---
    // Hora recurrente 02:13 AM (canon). Cuando llega a cero, arranca de
    // nuevo para el próximo ciclo.
    // TODO: enganchar acá el evento al llegar a cero (todavía no definido).
    function getNextSyncTarget() {
      const now = new Date();
      const target = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 2, 13, 0, 0);
      if (target <= now) {
        target.setDate(target.getDate() + 1);
      }
      return target;
    }

    let syncTarget = getNextSyncTarget();
    const countdownDisplay = document.getElementById('countdownDisplay');

    function updateCountdown() {
      const now = new Date();
      let diff = Math.floor((syncTarget - now) / 1000);
      if (diff <= 0) {
        syncTarget = getNextSyncTarget();
        diff = Math.floor((syncTarget - now) / 1000);
      }
      const h = String(Math.floor(diff / 3600)).padStart(2, '0');
      const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
      const s = String(diff % 60).padStart(2, '0');
      countdownDisplay.textContent = h + ':' + m + ':' + s;
    }

    function init() {
      resize();
      centerWindow();
      animate();
      updateCountdown();
      setInterval(updateCountdown, 1000);
    }

    window.addEventListener('resize', resize);
    window.addEventListener('mousemove', (e) => {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
    });

    // --- Audio ---
    const form = document.getElementById('loginForm');
    const employeeIdInput = document.getElementById('employeeId');
    const passwordInput = document.getElementById('password');
    const message = document.getElementById('message');
    const hintBtn = document.getElementById('hintBtn');

    const validEmployeeId = 'E-2619';
    const validPassword = 'moth_26';

    const bgMusic = document.getElementById('bgMusic');
    const bootSound = document.getElementById('bootSound');
    const loginStartupSound = document.getElementById('loginStartupSound');
    const postLoginSound = document.getElementById('postLoginSound');
    const errorSound = document.getElementById('errorSound');
    const interactSound = document.getElementById('interactSound');
    const audioToggle = document.getElementById('audioToggle');
    const allSounds = [bgMusic, bootSound, loginStartupSound, postLoginSound, errorSound, interactSound];
    bgMusic.volume = 0.5;

    function stopSound(sound) {
      sound.pause();
      sound.currentTime = 0;
    }

    function playSound(sound) {
      sound.currentTime = 0;
      sound.play();
    }

    function tryPlayIntro() {
      bootSound.play().catch(() => {});
      bgMusic.play().catch(() => {
        // El navegador bloqueó el autoplay con sonido: reintentar en la
        // primera interacción del usuario con la página.
        document.addEventListener('click', tryPlayIntro, { once: true });
        document.addEventListener('keydown', tryPlayIntro, { once: true });
      });
    }
    tryPlayIntro();

    audioToggle.addEventListener('click', function () {
      const muted = !bgMusic.muted;
      allSounds.forEach(sound => { sound.muted = muted; });
      audioToggle.textContent = muted ? '🔇 Audio' : '🔈 Audio';
    });

    // Sonido de interacción genérico para todos los controles tipo botón
    // (ventanas, decryptor), no solo "Asistencia".
    document.querySelectorAll('.ctrl, .btn-decrypt').forEach(function (el) {
      el.addEventListener('click', function () {
        playSound(interactSound);
      });
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      // Cortar cualquier instancia previa de postLogin/error para que
      // nunca quede sonando por encima del intento nuevo.
      stopSound(postLoginSound);
      stopSound(errorSound);
      playSound(loginStartupSound);

      const employeeId = employeeIdInput.value.trim();
      const password = passwordInput.value.trim();

      if (employeeId === validEmployeeId && password === validPassword) {
        playSound(postLoginSound);

        message.classList.add('success');
        message.innerHTML = 'Acceso concedido.<br>Cargando ambiente archivado...';

        setTimeout(() => {
          message.innerHTML = 'Sesión inicializada. Módulo de archivo disponible.';
        }, 1200);

        setTimeout(() => {
          window.location.href = 'app/views/auth/login.php';
        }, 2400);
      } else {
        playSound(errorSound);

        message.classList.remove('success');
        message.textContent = 'Acceso negado. Credenciales invalidas.';
      }
    });

    hintBtn.addEventListener('click', function () {
      playSound(interactSound);

      message.classList.remove('success');
      message.textContent = 'Para recuperar las credenciales, revise el código fuente de la página o póngase en contacto con el administrador del sistema.';
    });
  </script>
</body>
</html>
