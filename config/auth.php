<?php
// config/auth.php
// Sesión, roles y permisos. Incluirlo en toda página o controller que requiera login.
//
// La fuente de verdad es la base de datos: si un admin cambia el rol de alguien o
// elimina su cuenta, el cambio se aplica en el siguiente request (no hay que esperar
// a que venza la sesión).
//
// Uso típico:
//   requiereLogin();            // cualquier usuario con sesión
//   requiereRol('admin');       // solo administradores
//   if (tieneRol('admin')) {…}  // mostrar/ocultar cosas según el rol

require_once __DIR__ . '/conexion.php'; // abre la BD y carga app.php (BASE_URL, sesión, h())

// Fila del usuario logueado (id, nombre_usuario_real, correo, nombre_usuario_anonimo, rol, foto_perfil, biografia, estado_verificado)
// o null si no hay sesión. Si la cuenta ya no existe o no validó su correo, la sesión se invalida.
function usuarioActual() {
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    if (empty($_SESSION['usuario_id'])) {
        return $cache = null;
    }

    global $conexion;
    $id = (int) $_SESSION['usuario_id'];
    $stmt = $conexion->prepare(
        "SELECT id, nombre_usuario_real, correo, nombre_usuario_anonimo, rol, foto_perfil, biografia, estado_verificado FROM usuarios WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fila) { // la cuenta fue eliminada mientras tenía la sesión abierta
        unset($_SESSION['usuario_id'], $_SESSION['alias'], $_SESSION['correo'], $_SESSION['rol']);
        return $cache = null;
    }

    // Cuenta sin validar por correo: se cierra la sesión y se manda al login con el aviso.
    // Cubre TODAS las páginas protegidas (foro, perfil, panel admin) porque todas pasan por acá.
    if ((int) $fila['estado_verificado'] !== 1) {
        unset($_SESSION['usuario_id'], $_SESSION['alias'], $_SESSION['correo'], $_SESSION['rol']);
        $_SESSION['login_error'] = MSG_CUENTA_NO_VERIFICADA;
        return $cache = null;
    }

    // Se refrescan por si un admin cambió el alias, el correo o el rol.
    $_SESSION['alias']  = $fila['nombre_usuario_anonimo'];
    $_SESSION['correo'] = $fila['correo'];
    $_SESSION['rol']    = $fila['rol'];
    return $cache = $fila;
}

function requiereLogin() {
    if (usuarioActual() === null) {
        header('Location: ' . BASE_URL . '/app/views/auth/login.php');
        exit;
    }
}

function tieneRol(...$roles) {
    $u = usuarioActual();
    return $u !== null && in_array($u['rol'], $roles, true);
}

function requiereRol(...$roles) {
    requiereLogin();
    if (!tieneRol(...$roles)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        exit('<p style="font-family: Tahoma, Arial, sans-serif;">Acceso denegado: no tenés permisos para ver esta página.</p>'
           . '<p style="font-family: Tahoma, Arial, sans-serif;"><a href="' . h(BASE_URL) . '/index.php?c=Foro&amp;a=index">Volver al foro</a></p>');
    }
}

// ---- Mensajes que sobreviven a una redirección (aviso de "guardado", "eliminado", errores) ----
function flash($tipo, $texto) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto]; // $tipo: 'ok' | 'error'
}

function tomarFlash() {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---- CSRF: obligatorio en los formularios POST que modifican datos de otros usuarios ----
function csrfToken() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo() {
    return '<input type="hidden" name="csrf" value="' . h(csrfToken()) . '">';
}

function csrfValido() {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}
