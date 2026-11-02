<?php
// app/models/UserModel.php
// Acceso a la tabla `usuarios` (ver paradoxa26.sql).

require_once __DIR__ . '/../helpers/FotoPerfil.php';
require_once __DIR__ . '/../helpers/ImagenHilo.php';

class UserModel {
    const ROLES = ['usuario', 'admin'];

    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // Devuelve la fila del usuario con ese correo, o null si no existe.
    public function obtenerPorCorreo($correo) {
        $stmt = $this->db->prepare(
            "SELECT id, nombre_usuario_real, correo, contrasenia, nombre_usuario_anonimo, rol, fecha_registro, token, estado_verificado
             FROM usuarios WHERE correo = ? LIMIT 1"
        );
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    // Devuelve la fila del usuario con ese id (sin la contraseña), o null si no existe.
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare(
            "SELECT id, nombre_usuario_real, correo, nombre_usuario_anonimo, rol, fecha_registro, token, estado_verificado
             FROM usuarios WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    // true si ya hay un usuario con ese alias público (se puede excluir a un usuario, para las ediciones).
    public function existeAlias($alias, $excluirId = 0) {
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE nombre_usuario_anonimo = ? AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $alias, $excluirId);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    // Lista usuarios; si hay búsqueda filtra por correo, alias o nombre (coincidencia parcial).
    public function listar($busqueda = '') {
        $campos = "id, nombre_usuario_real, correo, nombre_usuario_anonimo, rol, fecha_registro, token, estado_verificado";

        if ($busqueda === '') {
            return $this->db->query("SELECT $campos FROM usuarios ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        }

        $like = '%' . addcslashes($busqueda, '%_\\') . '%';
        $stmt = $this->db->prepare(
            "SELECT $campos FROM usuarios
             WHERE correo LIKE ? OR nombre_usuario_anonimo LIKE ? OR nombre_usuario_real LIKE ?
             ORDER BY id"
        );
        $stmt->bind_param("sss", $like, $like, $like);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $filas;
    }

    // Inserta un usuario nuevo con la contraseña hasheada (bcrypt). El rol por defecto es 'usuario'.
    // Lanza mysqli_sql_exception si falla (ej. errno 1062 = correo/alias duplicado).
    public function registrar($nombreReal, $correo, $password, $alias, $token, $rol = 'usuario') {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nombre_usuario_real, correo, contrasenia, nombre_usuario_anonimo, rol, fecha_registro, token)
             VALUES (?, ?, ?, ?, ?, CURDATE(),'$token')"
        );
        $stmt->bind_param("sssss", $nombreReal, $correo, $hash, $alias, $rol);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // Actualiza un usuario. Si $password viene vacío/null, la contraseña no se toca.
    public function actualizar($id, $nombreReal, $correo, $alias, $rol, $password = null) {
        if ($password !== null && $password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare(
                "UPDATE usuarios SET nombre_usuario_real = ?, correo = ?, nombre_usuario_anonimo = ?, rol = ?, contrasenia = ?
                 WHERE id = ?"
            );
            $stmt->bind_param("sssssi", $nombreReal, $correo, $alias, $rol, $hash, $id);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE usuarios SET nombre_usuario_real = ?, correo = ?, nombre_usuario_anonimo = ?, rol = ?
                 WHERE id = ?"
            );
            $stmt->bind_param("ssssi", $nombreReal, $correo, $alias, $rol, $id);
        }
        $stmt->execute();
        $stmt->close();
        return true;
    }
        public function actualizarestado($correo, $token) {
                $stmt = $this->db->prepare(
                "UPDATE usuarios SET estado_verificado = 1
                 WHERE correo = '$correo' and token = '$token'"
            );
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // Guarda el alias y la descripción breve del propio usuario (biografia vacía = NULL).
    // Lanza mysqli_sql_exception si falla (ej. errno 1062 = alias duplicado).
    public function actualizarPerfil($id, $alias, $biografia) {
        $biografia = ($biografia === '') ? null : $biografia;
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre_usuario_anonimo = ?, biografia = ? WHERE id = ?");
        $stmt->bind_param("ssi", $alias, $biografia, $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // Guarda el nombre del archivo de la foto de perfil (o null para quitarla).
    public function actualizarFoto($id, $nombreArchivo) {
        $stmt = $this->db->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
        $stmt->bind_param("si", $nombreArchivo, $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // Elimina un usuario junto con todo lo que depende de él (sus publicaciones; los comentarios
    // y las reacciones se borran solos por ON DELETE CASCADE).
    // Todo en una transacción: o se borra todo o no se borra nada.
    // Al final también se borra el archivo de su foto de perfil, si tenía.
    public function eliminar($id) {
        $stmtFoto = $this->db->prepare("SELECT foto_perfil FROM usuarios WHERE id = ? LIMIT 1");
        $stmtFoto->bind_param("i", $id);
        $stmtFoto->execute();
        $filaFoto = $stmtFoto->get_result()->fetch_assoc();
        $stmtFoto->close();
        $fotoVieja = $filaFoto['foto_perfil'] ?? null;

        // Ids de sus hilos (se borran con el usuario): hay que borrar también sus imágenes adjuntas.
        $stmtHilos = $this->db->prepare("SELECT id_publicacion FROM publicaciones WHERE id = ?");
        $stmtHilos->bind_param("i", $id);
        $stmtHilos->execute();
        $idsHilos = array_column($stmtHilos->get_result()->fetch_all(MYSQLI_ASSOC), 'id_publicacion');
        $stmtHilos->close();

        $this->db->begin_transaction();
        try {
            foreach (['publicaciones', 'usuarios'] as $tabla) {
                $stmt = $this->db->prepare("DELETE FROM $tabla WHERE id = ?"); // nombres fijos de arriba, no input
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
            $this->db->commit();
        } catch (mysqli_sql_exception $e) {
            $this->db->rollback();
            throw $e;
        }
        FotoPerfil::borrarArchivo($fotoVieja);
        foreach ($idsHilos as $idHilo) {
            ImagenHilo::borrar($idHilo);
        }
        return true;
    }
}
