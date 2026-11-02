<?php
// app/controllers/PerfilController.php
// Perfil del usuario logueado: por ahora, su foto de perfil. Lo puede usar cualquier usuario con
// sesión (el guard está en el constructor) y cada uno solo toca SU propia foto.
//
// Rutas (todas vía index.php, siempre por POST con CSRF; se usan desde "Mi cuenta" en el foro):
//   ?c=Perfil&a=guardarFoto     sube o reemplaza la foto (campo de archivo "foto")
//   ?c=Perfil&a=eliminarFoto    quita la foto (vuelve el avatar con la inicial)
//   ?c=Perfil&a=guardarDatos    guarda el alias público y la descripción breve

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/FotoPerfil.php';
require_once __DIR__ . '/../helpers/ValidadorUsuario.php';

class PerfilController {
    private $userModel;
    private $usuario; // fila del usuario logueado

    public function __construct() {
        global $conexion;
        requiereLogin();
        $this->usuario   = usuarioActual();
        $this->userModel = new UserModel($conexion);
    }

    // Vuelve a "Mi cuenta" (el foro abre esa pestaña con ?vista=cuenta y muestra el aviso flash).
    private function volver() {
        header('Location: ' . BASE_URL . '/index.php?c=Foro&a=index&vista=cuenta');
        exit;
    }

    private function exigirPostYCsrf() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->volver();
        }
        // Si el archivo supera post_max_size, PHP descarta todo el POST (incluido el token CSRF).
        if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            flash('error', 'La imagen es demasiado pesada (máximo 2 MB).');
            $this->volver();
        }
        if (!csrfValido()) {
            flash('error', 'El formulario expiró. Volvé a intentarlo.');
            $this->volver();
        }
    }

    public function guardarDatos() {
        $this->exigirPostYCsrf();

        $id        = (int) $this->usuario['id'];
        $alias     = trim($_POST['alias'] ?? '');
        $biografia = trim($_POST['biografia'] ?? '');
        $biografia = str_replace(["\r\n", "\r"], "\n", $biografia);

        $error = ValidadorUsuario::validarPerfil($alias, $biografia);
        if ($error === null && $this->userModel->existeAlias($alias, $id)) {
            $error = 'Ese alias ya está en uso, elegí otro.';
        }
        if ($error !== null) {
            flash('error', $error);
            $this->volver();
        }

        try {
            $this->userModel->actualizarPerfil($id, $alias, $biografia);
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) { // alias tomado justo en este momento
                flash('error', 'Ese alias ya está en uso, elegí otro.');
            } else {
                error_log('[paradoxa26] Error al guardar el perfil: ' . $e->getMessage());
                flash('error', 'No se pudieron guardar los cambios. Probá de nuevo.');
            }
            $this->volver();
        }

        flash('ok', 'Cambios guardados.');
        $this->volver();
    }

    public function guardarFoto() {
        $this->exigirPostYCsrf();

        $id        = (int) $this->usuario['id'];
        $anterior  = $this->usuario['foto_perfil'] ?? null;
        $resultado = FotoPerfil::guardar($_FILES['foto'] ?? null, $id);

        if (isset($resultado['error'])) {
            flash('error', $resultado['error']);
            $this->volver();
        }

        try {
            $this->userModel->actualizarFoto($id, $resultado['nombre']);
        } catch (mysqli_sql_exception $e) {
            error_log('[paradoxa26] Error al guardar la foto de perfil: ' . $e->getMessage());
            FotoPerfil::borrarArchivo($resultado['nombre']); // no dejar un archivo huérfano
            flash('error', 'No se pudo guardar la foto. Probá de nuevo.');
            $this->volver();
        }

        FotoPerfil::borrarArchivo($anterior); // la foto vieja ya no se usa
        flash('ok', 'Foto de perfil actualizada.');
        $this->volver();
    }

    public function eliminarFoto() {
        $this->exigirPostYCsrf();

        $id       = (int) $this->usuario['id'];
        $anterior = $this->usuario['foto_perfil'] ?? null;

        try {
            $this->userModel->actualizarFoto($id, null);
        } catch (mysqli_sql_exception $e) {
            error_log('[paradoxa26] Error al quitar la foto de perfil: ' . $e->getMessage());
            flash('error', 'No se pudo quitar la foto. Probá de nuevo.');
            $this->volver();
        }

        FotoPerfil::borrarArchivo($anterior);
        flash('ok', 'Quitaste tu foto de perfil.');
        $this->volver();
    }
}
