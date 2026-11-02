<?php
// config/app.php
// Configuración compartida: BASE_URL y sesión. No abre la base de datos,
// así las vistas (login.php, foro) pueden incluirlo sin depender de MySQL.

if (!defined('BASE_URL')) {
    // Ruta URL de la carpeta del proyecto respecto al document root.
    //   XAMPP con el proyecto en htdocs/paradoxa26  -> "/paradoxa26"
    //   proyecto servido como raíz (php -S, vhost)  -> ""
    // Si en tu entorno no se detecta bien, definila a mano:
    //   define('BASE_URL', '/paradoxa26');
    $raiz    = realpath(__DIR__ . '/..');
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $base    = '';

    if ($raiz !== false && $docRoot !== false) {
        $raiz    = str_replace('\\', '/', $raiz);
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        if ($docRoot !== '' && strncasecmp($raiz, $docRoot, strlen($docRoot)) === 0) {
            $base = substr($raiz, strlen($docRoot));
        }
    }

    $segmentos = array_filter(explode('/', $base), 'strlen');
    define('BASE_URL', $segmentos ? '/' . implode('/', array_map('rawurlencode', $segmentos)) : '');
}

// Aviso que se muestra cuando alguien intenta entrar con una cuenta que todavía no validó su correo
// (usuarios.estado_verificado = 0). Lo usan AuthController::login() y config/auth.php.
if (!defined('MSG_CUENTA_NO_VERIFICADA')) {
    define('MSG_CUENTA_NO_VERIFICADA', 'Tenés que validar tu cuenta desde el correo (Gmail) que te enviamos para poder iniciar sesión y entrar al foro.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Escapa texto para imprimirlo en HTML (usar siempre al mostrar datos de usuarios).
if (!function_exists('h')) {
    function h($valor) {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}
