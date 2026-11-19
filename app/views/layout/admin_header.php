<?php
// Shell del panel de administración (ventana roja de PARADOX).
// Variables esperadas: $tituloVentana. Se cierra con layout/admin_footer.php.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PARADOX — <?= h($tituloVentana) ?></title>
  <link rel="icon" type="image/png" sizes="64x64" href="<?= BASE_URL ?>/app/public/img/favicon.png" />
  <link rel="shortcut icon" href="<?= BASE_URL ?>/app/public/img/favicon.ico" />
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/app/public/img/apple-touch-icon.png" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/app/public/css/style.css" />
  <style>
    :root { --dlg-acento: #8b0000; --dlg-barra-fin: #4a0000; --dlg-icono-bg: #ffcccc; }
    body { overflow: hidden; position: relative; height: 100vh; width: 100vw; margin: 0; padding: 0; background: #000; font-family: Tahoma, Verdana, Arial, sans-serif; }
    #bgCanvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }
    .desktop { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 1; pointer-events: none; }
    .window { position: absolute; cursor: default; z-index: 2; width: 900px; max-width: 100vw; border: 1px solid #0a246a; background: #d4d0c8; box-shadow: 0 0 0 1px #ffffff inset, 4px 4px 20px rgba(0, 0, 0, 0.35); pointer-events: auto; }
    .title-bar { height: 32px; background: linear-gradient(to right, #8b0000, #4a0000); color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 8px; font-size: 13px; font-weight: bold; cursor: move; user-select: none; }
    .title-left { display: flex; align-items: center; gap: 8px; }
    .title-icon { width: 16px; height: 16px; background: #ffcccc; border: 1px solid #8b0000; display: inline-block; position: relative; }
    .title-icon::after { content: attr(data-letter); position: absolute; inset: 0; display: grid; place-items: center; color: #8b0000; font-size: 11px; font-weight: bold; }
    .window-controls { display: flex; gap: 4px; }
    .ctrl { width: 22px; height: 20px; border: 1px solid #ffffff; border-right-color: #404040; border-bottom-color: #404040; background: #d4d0c8; font-size: 12px; display: grid; place-items: center; line-height: 1; cursor: pointer; }
    /* El contenido hace scroll interno: la ventana nunca se sale de la pantalla */
    .content-wrapper { display: flex; flex-direction: column; padding: 20px; background: #c9c5bd; min-height: min(420px, calc(100vh - 160px)); max-height: calc(100vh - 160px); overflow-y: auto; }
    .admin-card { background: #efefef; border: 2px solid; border-color: #ffffff #808080 #808080 #ffffff; padding: 15px; box-shadow: 1px 1px 5px rgba(0,0,0,0.1); }
    .admin-card h3 { margin: 0 0 10px 0; font-size: 16px; color: #8b0000; border-bottom: 1px solid #808080; padding-bottom: 5px; }
    .field { margin-bottom: 10px; }
    .field label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; }
    .field input, .field select { width: 100%; padding: 5px; font-size: 13px; border: 2px inset #c0c0c0; background: #fff; box-sizing: border-box; }
    .field-hint { font-size: 11px; color: #555555; margin-top: 3px; }
    .btn, button { min-width: 0; padding: 6px 12px; font-size: 12px; border: 2px solid; border-color: #ffffff #404040 #404040 #ffffff; background: #d4d0c8; cursor: pointer; color: #000; text-decoration: none; display: inline-block; }
    .btn:active, button:active { border-color: #404040 #ffffff #ffffff #404040; }
    button:disabled { color: #808080; cursor: not-allowed; }
    .button-row { display: flex; gap: 10px; margin-top: 14px; }
    .msg-ok, .msg-error { padding: 8px 10px; margin-bottom: 12px; font-size: 12px; border: 2px inset #c0c0c0; background: #fff; }
    .msg-ok { color: #0b5e2b; }
    .msg-error { color: #8b0000; }
    .toolbar { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap; }
    .toolbar input[type="text"] { flex: 1; min-width: 200px; padding: 5px; font-size: 13px; border: 2px inset #c0c0c0; background: #fff; box-sizing: border-box; }
    .toolbar .spacer { margin-left: auto; }
    .tabla-wrap { overflow-x: auto; }
    .tabla-usuarios { width: 100%; border-collapse: collapse; font-size: 12px; background: #fff; }
    .tabla-usuarios th { background: #d4d0c8; border: 2px solid; border-color: #ffffff #808080 #808080 #ffffff; text-align: left; padding: 5px 8px; white-space: nowrap; }
    .tabla-usuarios td { border-bottom: 1px solid #c0c0c0; padding: 5px 8px; vertical-align: middle; }
    .tabla-usuarios tr:hover td { background: #f3efe6; }
    .acciones { display: flex; gap: 6px; align-items: center; white-space: nowrap; }
    .acciones form { margin: 0; }
    .rol { display: inline-block; padding: 1px 6px; border: 1px solid #808080; background: #efefef; font-size: 11px; }
    .rol-admin { color: #8b0000; border-color: #8b0000; font-weight: bold; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px; }
    .conteo { font-size: 11px; color: #444; margin-top: 8px; }
    .bottom-bar { height: 30px; background: #d4d0c8; border-top: 2px solid #ffffff; display: flex; align-items: center; padding: 0 8px; gap: 10px; font-size: 11px; }
    .status-pill { border: 2px inset #c0c0c0; padding: 2px 8px; background: #efefef; color: #000; text-decoration: none; }
    a.status-pill:hover { background: #fff; }
    .taskbar { position: absolute; bottom: 0; left: 0; right: 0; height: 30px; background: #d4d0c8; border-top: 2px solid #ffffff; display: flex; align-items: center; padding: 0 5px; z-index: 10; font-size: 12px; }
    /* --- Estilo extra del panel --- */
    .window { border-radius: 2px; box-shadow: 0 0 0 1px #ffffff inset, 0 12px 40px rgba(0, 0, 0, 0.55); }
    .title-bar { background: linear-gradient(to right, #8b0000 0%, #5a0000 60%, #2e0000 100%); letter-spacing: 0.3px; text-shadow: 1px 1px 0 rgba(0,0,0,0.4); }
    .content-wrapper { background: linear-gradient(180deg, #cdc9c1 0%, #c2beb6 100%); }
    .admin-card { box-shadow: 2px 2px 0 rgba(0,0,0,0.18); }
    .admin-card h3 { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
    .card-head { display: flex; align-items: center; gap: 12px; padding-bottom: 12px; margin-bottom: 14px; border-bottom: 1px solid #b5b1a9; box-shadow: 0 1px 0 #ffffff; }
    .card-icon { width: 40px; height: 40px; display: grid; place-items: center; font-size: 20px; background: #d4d0c8; border: 2px solid; border-color: #ffffff #808080 #808080 #ffffff; }
    .card-sub { margin: 2px 0 0 0; font-size: 12px; color: #555555; }
    .field input:focus, .toolbar input[type="text"]:focus { outline: 2px solid #8b0000; outline-offset: -2px; }
    .btn:hover, button:hover { background: #e4e0d8; }
    .btn-primary { background: #efefef; font-weight: bold; color: #8b0000; }
    .btn-primary:hover { background: #fff; }
    .button-row .spacer { margin-left: auto; }
  </style>
</head>
<body>
  <canvas id="bgCanvas"></canvas>

  <div class="desktop">
    <div id="mainWindow" class="window">
      <div id="windowHeader" class="title-bar">
        <div class="title-left">
          <span class="title-icon" data-letter="A"></span>
          <span>PARADOX — <?= h($tituloVentana) ?></span>
        </div>
        <div class="window-controls">
          <div class="ctrl">_</div>
          <div class="ctrl">□</div>
          <div class="ctrl">×</div>
        </div>
      </div>

      <div class="content-wrapper">
