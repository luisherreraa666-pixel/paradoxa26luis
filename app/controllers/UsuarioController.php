<?php
// app/controllers/UsuarioController.php
// CRUD de usuarios. Solo para administradores: el guard está en el constructor,
// así que se aplica a TODAS las acciones (incluidas las que se agreguen después).
//
// Rutas (todas vía index.php):
//   ?c=Usuario&a=listar            listado + búsqueda (?q=)
//   ?c=Usuario&a=crearView         formulario de alta
//   ?c=Usuario&a=crear             (POST) guarda el alta
//   ?c=Usuario&a=editarView&id=N   formulario de edición
//   ?c=Usuario&a=actualizar        (POST) guarda la edición
//   ?c=Usuario&a=eliminar          (POST) elimina

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/ValidadorUsuario.php';

class UsuarioController {
    private $userModel;
    private $actor; // el admin que está operando (fila de usuarios)

    public function __construct() {
        global $conexion;
        requiereRol('admin');
        $this->actor     = usuarioActual();
        $this->userModel = new UserModel($conexion);
    }

    // ---------- utilidades internas ----------

    private function render($vista, $vars = []) {
        $actor   = $this->actor;
        $actorId = (int) $this->actor['id'];
        extract($vars, EXTR_OVERWRITE);
        require __DIR__ . '/../views/admin/' . $vista . '.php';
    }

    private function redirigir($consulta) {
        header('Location: ' . BASE_URL . '/index.php?' . $consulta);
        exit;
    }

    private function exigirPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('c=Usuario&a=listar');
        }
    }

    private function exigirCsrf() {
        if (!csrfValido()) {
            flash('error', 'El formulario expiró. Volvé a intentarlo.');
            $this->redirigir('c=Usuario&a=listar');
        }
    }

    private function mostrarForm($modo, $old, $error = null, $usuario = null) {
        $this->render('usuario_form', [
            'tituloVentana'   => $modo === 'crear' ? 'Nuevo usuario' : 'Editar usuario',
            'modo'            => $modo,
            'old'             => $old,
            'error'           => $error,
            'usuario'         => $usuario,
            'editandoPropio'  => $usuario !== null && (int) $usuario['id'] === (int) $this->actor['id'],
        ]);
    }

    private function mensajeDeBD(mysqli_sql_exception $e, $generico) {
        error_log('[paradoxa26] ' . $generico . ' ' . $e->getMessage());
        // 1062 = clave duplicada (por si dos personas guardan a la vez)
        return $e->getCode() === 1062 ? 'El correo o el alias ya están registrados.' : $generico;
    }

    // ---------- READ ----------

    public function listar() {
        $busqueda = trim($_GET['q'] ?? '');
        $this->render('usuarios', [
            'tituloVentana' => 'Gestión de usuarios',
            'busqueda'      => $busqueda,
            'usuarios'      => $this->userModel->listar($busqueda),
            'flash'         => tomarFlash(),
        ]);
    }

    // ---------- CREATE ----------

    public function crearView() {
        $this->mostrarForm('crear', ['nombre' => '', 'alias' => '', 'email' => '', 'rol' => 'usuario']);
    }

    public function crear() {
        $this->exigirPost();
        $this->exigirCsrf();

        $nombre    = trim($_POST['nombre'] ?? '');
        $alias     = trim($_POST['alias'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $rol       = $_POST['rol'] ?? 'usuario';
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $old       = ['nombre' => $nombre, 'alias' => $alias, 'email' => $email, 'rol' => $rol];

        $error = ValidadorUsuario::validar($nombre, $alias, $email, $password, $password2, true);
        if ($error === null && !in_array($rol, UserModel::ROLES, true)) {
            $error = 'Rol inválido.';
        }
        if ($error === null && $this->userModel->obtenerPorCorreo($email)) {
            $error = 'El correo electrónico ya está registrado.';
        }
        if ($error === null && $this->userModel->existeAlias($alias)) {
            $error = 'Ese alias ya está en uso, elegí otro.';
        }
        if ($error !== null) {
            $this->mostrarForm('crear', $old, $error);
            return;
        }

        try {
            $this->userModel->registrar($nombre, $email, $password, $alias, $rol);
        } catch (mysqli_sql_exception $e) {
            $this->mostrarForm('crear', $old, $this->mensajeDeBD($e, 'Ocurrió un error al crear el usuario.'));
            return;
        }

        flash('ok', 'Usuario creado correctamente.');
        $this->redirigir('c=Usuario&a=listar');
    }

    // ---------- UPDATE ----------

    public function editarView() {
        $usuario = $this->userModel->obtenerPorId((int) ($_GET['id'] ?? 0));
        if (!$usuario) {
            flash('error', 'El usuario no existe.');
            $this->redirigir('c=Usuario&a=listar');
        }
        $this->mostrarForm('editar', [
            'nombre' => $usuario['nombre_usuario_real'],
            'alias'  => $usuario['nombre_usuario_anonimo'],
            'email'  => $usuario['correo'],
            'rol'    => $usuario['rol'],
        ], null, $usuario);
    }

    public function actualizar() {
        $this->exigirPost();
        $this->exigirCsrf();

        $id      = (int) ($_POST['id'] ?? 0);
        $usuario = $this->userModel->obtenerPorId($id);
        if (!$usuario) {
            flash('error', 'El usuario no existe.');
            $this->redirigir('c=Usuario&a=listar');
        }

        $nombre    = trim($_POST['nombre'] ?? '');
        $alias     = trim($_POST['alias'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        // Un admin no puede cambiarse su propio rol (evita quedarse sin administradores por accidente).
        $propio = ($id === (int) $this->actor['id']);
        $rol    = $propio ? $usuario['rol'] : ($_POST['rol'] ?? '');
        $old    = ['nombre' => $nombre, 'alias' => $alias, 'email' => $email, 'rol' => $rol];

        $error = ValidadorUsuario::validar($nombre, $alias, $email, $password, $password2, false);
        if ($error === null && !in_array($rol, UserModel::ROLES, true)) {
            $error = 'Rol inválido.';
        }
        if ($error === null) {
            $otro = $this->userModel->obtenerPorCorreo($email);
            if ($otro && (int) $otro['id'] !== $id) {
                $error = 'El correo electrónico ya está registrado.';
            }
        }
        if ($error === null && $this->userModel->existeAlias($alias, $id)) {
            $error = 'Ese alias ya está en uso, elegí otro.';
        }
        if ($error !== null) {
            $this->mostrarForm('editar', $old, $error, $usuario);
            return;
        }

        try {
            $this->userModel->actualizar($id, $nombre, $email, $alias, $rol, $password);
        } catch (mysqli_sql_exception $e) {
            $this->mostrarForm('editar', $old, $this->mensajeDeBD($e, 'Ocurrió un error al guardar los cambios.'), $usuario);
            return;
        }

        flash('ok', 'Usuario actualizado correctamente.');
        $this->redirigir('c=Usuario&a=listar');
    }

    // ---------- DELETE ----------

    public function eliminar() {
        $this->exigirPost();
        $this->exigirCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $this->actor['id']) {
            flash('error', 'No podés eliminar tu propia cuenta desde el panel.');
            $this->redirigir('c=Usuario&a=listar');
        }
        if (!$this->userModel->obtenerPorId($id)) {
            flash('error', 'El usuario no existe.');
            $this->redirigir('c=Usuario&a=listar');
        }

        try {
            $this->userModel->eliminar($id);
        } catch (mysqli_sql_exception $e) {
            error_log('[paradoxa26] Error al eliminar usuario: ' . $e->getMessage());
            flash('error', 'No se pudo eliminar el usuario.');
            $this->redirigir('c=Usuario&a=listar');
        }

        flash('ok', 'Usuario eliminado correctamente.');
        $this->redirigir('c=Usuario&a=listar');
    }
}
