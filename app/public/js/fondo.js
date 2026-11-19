// app/public/js/fondo.js
// Fondo animado (teselado "retro-fluido") + ventana principal arrastrable y centrada.
// Espera en la página: #bgCanvas, #mainWindow y #windowHeader (la barra de título).
(function () {
  const canvas   = document.getElementById('bgCanvas');
  const windowEl = document.getElementById('mainWindow');
  const headerEl = document.getElementById('windowHeader');
  if (!canvas || !windowEl || !headerEl) return;

  // --- Ventana: arrastrar y centrar ---
  let isDragging = false, offsetX = 0, offsetY = 0;

  headerEl.addEventListener('mousedown', (e) => {
    isDragging = true;
    offsetX = e.clientX - windowEl.offsetLeft;
    offsetY = e.clientY - windowEl.offsetTop;
    windowEl.style.zIndex = 100;
  });
  document.addEventListener('mousemove', (e) => {
    if (!isDragging) return;
    windowEl.style.left = (e.clientX - offsetX) + 'px';
    windowEl.style.top  = (e.clientY - offsetY) + 'px';
  });
  document.addEventListener('mouseup', () => { isDragging = false; });

  function centerWindow() {
    // Math.max: que la barra de título nunca quede fuera de pantalla.
    windowEl.style.left = Math.max(0, (window.innerWidth  - windowEl.offsetWidth)  / 2) + 'px';
    windowEl.style.top  = Math.max(0, (window.innerHeight - windowEl.offsetHeight) / 2) + 'px';
  }

  // --- Fondo animado ---
  const ctx = canvas.getContext('2d');
  const spacing = 50;
  let width, height, dpr, points = [];
  const mouse = { x: -1000, y: -1000 };

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

  function resize() {
    dpr = window.devicePixelRatio || 1;
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width * dpr;
    canvas.height = height * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    initPoints();
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
      let offX = 0, offY = 0;
      if (dist < forceRadius && dist > 0) {
        const force = (forceRadius - dist) / forceRadius;
        offX = (dx / dist) * force * 30;
        offY = (dy / dist) * force * 30;
      }
      p.x += (p.baseX + waveX + offX - p.x) * 0.1;
      p.y += (p.baseY + waveY + offY - p.y) * 0.1;
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

  window.addEventListener('resize', resize);
  window.addEventListener('mousemove', (e) => { mouse.x = e.clientX; mouse.y = e.clientY; });

  resize();
  centerWindow();
  animate();
})();
