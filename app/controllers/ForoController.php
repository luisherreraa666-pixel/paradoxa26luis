<?php
// app/controllers/ForoController.php
// Foro: hilos, comentarios y reacciones. Lo puede usar cualquier usuario con sesión
// (el guard está en el constructor). Los admin además pueden moderar (borrar lo de otros).
//
// Rutas (todas vía index.php):
//   ?c=Foro&a=index                       lista de hilos
//   ?c=Foro&a=ver&id=N                    un hilo con sus comentarios
//   ?c=Foro&a=nuevoView                   formulario de hilo nuevo
//   ?c=Foro&a=crearPublicacion            (POST) publica el hilo
//   ?c=Foro&a=comentar                    (POST) comenta un hilo
//   ?c=Foro&a=reaccionar                  (POST) reacciona a un hilo o comentario (clic otra vez = quitar)
//   ?c=Foro&a=eliminarPublicacion         (POST) autor o admin
//   ?c=Foro&a=eliminarComentario          (POST) autor o admin

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../models/ForoModel.php';
require_once __DIR__ . '/../helpers/Formato.php';
require_once __DIR__ . '/../helpers/FotoPerfil.php';
require_once __DIR__ . '/../helpers/ImagenHilo.php';

class ForoController {
    const MAX_TITULO     = 150;
    const MAX_CONTENIDO  = 5000;
    const MAX_COMENTARIO = 500; // el límite de la columna comentarios.contenido (varchar(500))
    const HILOS_POR_PAGINA = 50;

    private $foro;
    private $usuario; // fila del usuario logueado

    public function __construct() {
        global $conexion;
        requiereLogin();
        $this->usuario = usuarioActual();
        $this->foro    = new ForoModel($conexion);
    }

    // ---------- utilidades internas ----------

    private function miId() {
        return (int) $this->usuario['id'];
    }

    private function esAdmin() {
        return $this->usuario['rol'] === 'admin';
    }

    // Texto enviado por el usuario, recortado; '' si no vino o vino como array (?campo[]=x).
    private function texto($origen, $clave) {
        $valor = $origen[$clave] ?? '';
        return is_string($valor) ? trim($valor) : '';
    }

    // Renderiza home/index.php (la ventana del foro) en el modo indicado: 'lista' | 'hilo' | 'nuevo'.
    private function render($modo, $vars = []) {
        $modoForo       = $modo;
        $tiposReaccion  = ForoModel::REACCIONES;
        $flash          = tomarFlash();
        $error          = null;
        extract($vars, EXTR_OVERWRITE);
        require __DIR__ . '/../views/home/index.php';
    }

    private function redirigir($consulta, $ancla = '') {
        header('Location: ' . BASE_URL . '/index.php?' . $consulta . $ancla);
        exit;
    }

    private function exigirPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('c=Foro&a=index');
        }
    }

    private function exigirCsrf() {
        if (!csrfValido()) {
            flash('error', 'El formulario expiró. Volvé a intentarlo.');
            $this->redirigir('c=Foro&a=index');
        }
    }

    // Muestra un hilo (se usa desde ver() y desde comentar() cuando hay un error que mostrar).
    private function mostrarHilo($id, $errorComentario = null, $textoComentario = '') {
        $hilo = $this->foro->obtenerPublicacion($id);
        if (!$hilo) {
            flash('error', 'El hilo no existe o fue eliminado.');
            $this->redirigir('c=Foro&a=index');
        }

        $yo      = $this->miId();
        $esAdmin = $this->esAdmin();

        $comentarios = $this->foro->listarComentarios($id);
        $hilo        = $this->foro->adjuntarReacciones('publicacion', [$hilo], 'id_publicacion', $yo)[0];
        $comentarios = $this->foro->adjuntarReacciones('comentario', $comentarios, 'id_comentario', $yo);

        // Datos listos para mostrar: las vistas no calculan nada.
        $hilo['hace']          = tiempoRelativo($hilo['segundos']);
        $hilo['imagen']        = ImagenHilo::url($hilo['id_publicacion']);
        $hilo['puede_eliminar'] = ((int) $hilo['id_autor'] === $yo) || $esAdmin;
        foreach ($comentarios as $i => $c) {
            $comentarios[$i]['hace']           = tiempoRelativo($c['segundos']);
            $comentarios[$i]['puede_eliminar'] = ((int) $c['id_autor'] === $yo) || $esAdmin;
        }

        $this->render('hilo', [
            'hilo'            => $hilo,
            'comentarios'     => $comentarios,
            'txtComentarios'  => pluralizar(count($comentarios), 'comentario', 'comentarios'),
            'errorComentario' => $errorComentario,
            'textoComentario' => $textoComentario,
            'maxComentario'   => self::MAX_COMENTARIO,
        ]);
    }

    // ---------- Lectura ----------

    public function index() {
        $hilos = $this->foro->listarPublicaciones(self::HILOS_POR_PAGINA);
        foreach ($hilos as $i => $h) {
            $hilos[$i]['hace']           = tiempoRelativo($h['segundos']);
            $hilos[$i]['imagen']         = ImagenHilo::url($h['id_publicacion']);
            $hilos[$i]['extracto']       = mb_strimwidth(preg_replace('/\s+/u', ' ', $h['contenido']), 0, 180, '…', 'UTF-8');
            $hilos[$i]['txt_respuestas'] = pluralizar($h['total_comentarios'], 'respuesta', 'respuestas');
            $hilos[$i]['txt_reacciones'] = pluralizar($h['total_reacciones'], 'reacción', 'reacciones');
        }
        $this->render('lista', ['hilos' => $hilos]);
    }

    public function ver() {
        $this->mostrarHilo((int) ($_GET['id'] ?? 0));
    }

    // ---------- Hilos ----------

    public function nuevoView() {
        $this->render('nuevo', [
            'old'          => ['titulo' => '', 'contenido' => ''],
            'maxTitulo'    => self::MAX_TITULO,
            'maxContenido' => self::MAX_CONTENIDO,
        ]);
    }

    public function crearPublicacion() {
        $this->exigirPost();
        // Si el archivo supera post_max_size, PHP descarta todo el POST (incluido el token CSRF).
        if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            flash('error', 'La imagen es demasiado pesada (máximo 2 MB).');
            $this->redirigir('c=Foro&a=nuevoView');
        }
        $this->exigirCsrf();

        $titulo    = $this->texto($_POST, 'titulo');
        $contenido = $this->texto($_POST, 'contenido');
        $old       = ['titulo' => $titulo, 'contenido' => $contenido];

        $error = null;
        if ($titulo === '' || $contenido === '') {
            $error = 'Completá el título y el mensaje.';
        } elseif (mb_strlen($titulo) < 3) {
            $error = 'El título debe tener al menos 3 caracteres.';
        } elseif (mb_strlen($titulo) > self::MAX_TITULO) {
            $error = 'El título puede tener hasta ' . self::MAX_TITULO . ' caracteres.';
        } elseif (mb_strlen($contenido) > self::MAX_CONTENIDO) {
            $error = 'El mensaje puede tener hasta ' . self::MAX_CONTENIDO . ' caracteres.';
        }

        // Imagen opcional: se valida antes de crear el hilo para no dejar un hilo a medias si es inválida.
        $imagen    = $_FILES['imagen'] ?? null;
        $hayImagen = ImagenHilo::hayArchivo($imagen);
        if ($error === null && $hayImagen) {
            $error = ImagenHilo::validar($imagen);
        }

        $nuevoId = null;
        if ($error === null) {
            try {
                $nuevoId = $this->foro->crearPublicacion($this->miId(), $titulo, $contenido);
            } catch (mysqli_sql_exception $e) {
                error_log('[paradoxa26] Error al crear hilo: ' . $e->getMessage());
                $error = 'Ocurrió un error al publicar el hilo.';
            }
        }

        if ($error !== null) {
            $this->render('nuevo', [
                'old'          => $old,
                'error'        => $error,
                'maxTitulo'    => self::MAX_TITULO,
                'maxContenido' => self::MAX_CONTENIDO,
            ]);
            return;
        }

        if ($hayImagen && !ImagenHilo::guardar($imagen, $nuevoId)) {
            flash('error', 'El hilo se publicó, pero no se pudo guardar la imagen.');
        } else {
            flash('ok', 'Hilo publicado.');
        }
        $this->redirigir('c=Foro&a=ver&id=' . (int) $nuevoId);
    }

    public function eliminarPublicacion() {
        $this->exigirPost();
        $this->exigirCsrf();

        $id   = (int) $this->texto($_POST, 'id');
        $hilo = $this->foro->obtenerPublicacion($id);
        if (!$hilo) {
            flash('error', 'El hilo no existe o ya fue eliminado.');
            $this->redirigir('c=Foro&a=index');
        }
        if ((int) $hilo['id_autor'] !== $this->miId() && !$this->esAdmin()) {
            flash('error', 'Solo el autor o un administrador pueden eliminar este hilo.');
            $this->redirigir('c=Foro&a=ver&id=' . $id);
        }

        try {
            $this->foro->eliminarPublicacion($id);
        } catch (mysqli_sql_exception $e) {
            error_log('[paradoxa26] Error al eliminar hilo: ' . $e->getMessage());
            flash('error', 'No se pudo eliminar el hilo.');
            $this->redirigir('c=Foro&a=ver&id=' . $id);
        }

        ImagenHilo::borrar($id); // también se borra la imagen adjunta, si tenía
        flash('ok', 'Hilo eliminado.');
        $this->redirigir('c=Foro&a=index');
    }

    // ---------- Comentarios ----------

    public function comentar() {
        $this->exigirPost();
        $this->exigirCsrf();

        $id        = (int) $this->texto($_POST, 'id_publicacion');
        $contenido = $this->texto($_POST, 'contenido');

        if (!$this->foro->obtenerPublicacion($id)) {
            flash('error', 'El hilo no existe o fue eliminado.');
            $this->redirigir('c=Foro&a=index');
        }

        if ($contenido === '') {
            $this->mostrarHilo($id, 'Escribí un comentario antes de enviar.', '');
            return;
        }
        if (mb_strlen($contenido) > self::MAX_COMENTARIO) {
            $this->mostrarHilo($id, 'El comentario puede tener hasta ' . self::MAX_COMENTARIO . ' caracteres.', $contenido);
            return;
        }

        try {
            $nuevoId = $this->foro->crearComentario($id, $this->miId(), $contenido);
        } catch (mysqli_sql_exception $e) {
            error_log('[paradoxa26] Error al comentar: ' . $e->getMessage());
            $this->mostrarHilo($id, 'Ocurrió un error al publicar el comentario.', $contenido);
            return;
        }

        $this->redirigir('c=Foro&a=ver&id=' . $id, '#comentario-' . (int) $nuevoId);
    }

    public function eliminarComentario() {
        $this->exigirPost();
        $this->exigirCsrf();

        $id         = (int) $this->texto($_POST, 'id');
        $comentario = $this->foro->obtenerComentario($id);
        if (!$comentario) {
            flash('error', 'El comentario no existe o ya fue eliminado.');
            $this->redirigir('c=Foro&a=index');
        }
        $idHilo = (int) $comentario['id_publicacion'];
        if ((int) $comentario['id_autor'] !== $this->miId() && !$this->esAdmin()) {
            flash('error', 'Solo el autor o un administrador pueden eliminar este comentario.');
            $this->redirigir('c=Foro&a=ver&id=' . $idHilo);
        }

        try {
            $this->foro->eliminarComentario($id);
        } catch (mysqli_sql_exception $e) {
            error_log('[paradoxa26] Error al eliminar comentario: ' . $e->getMessage());
            flash('error', 'No se pudo eliminar el comentario.');
            $this->redirigir('c=Foro&a=ver&id=' . $idHilo);
        }

        flash('ok', 'Comentario eliminado.');
        $this->redirigir('c=Foro&a=ver&id=' . $idHilo, '#comentarios');
    }

    // ---------- Reacciones ----------

    public function reaccionar() {
        $this->exigirPost();
        $this->exigirCsrf();

        $objetivo   = $this->texto($_POST, 'objetivo');
        $idObjetivo = (int) $this->texto($_POST, 'id');
        $tipo       = $this->texto($_POST, 'tipo');

        if (($objetivo !== 'publicacion' && $objetivo !== 'comentario') || !isset(ForoModel::REACCIONES[$tipo])) {
            flash('error', 'Reacción inválida.');
            $this->redirigir('c=Foro&a=index');
        }

        // Se resuelve a qué hilo pertenece el mensaje (para volver a él) y se comprueba que exista.
        if ($objetivo === 'publicacion') {
            $existe = $this->foro->obtenerPublicacion($idObjetivo);
            $idHilo = $existe ? $idObjetivo : 0;
            $ancla  = '#publicacion';
        } else {
            $existe = $this->foro->obtenerComentario($idObjetivo);
            $idHilo = $existe ? (int) $existe['id_publicacion'] : 0;
            $ancla  = '#comentario-' . $idObjetivo;
        }
        if (!$existe) {
            flash('error', 'El mensaje no existe o fue eliminado.');
            $this->redirigir('c=Foro&a=index');
        }

        try {
            $this->foro->reaccionar($objetivo, $idObjetivo, $this->miId(), $tipo);
        } catch (mysqli_sql_exception $e) {
            error_log('[paradoxa26] Error al reaccionar: ' . $e->getMessage());
            flash('error', 'No se pudo registrar la reacción.');
        }

        $this->redirigir('c=Foro&a=ver&id=' . $idHilo, $ancla);
    }
}
