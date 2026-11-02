<?php
// app/models/ForoModel.php
// Hilos (`publicaciones`), comentarios y reacciones del foro.
// Convención de la BD: en todas las tablas, la columna `id` es el usuario (autor / quien reacciona).

class ForoModel {
    // Tipos de reacción: clave guardada en la BD => [emoji, texto]. Para agregar uno basta sumarlo acá.
    const REACCIONES = [
        'me_gusta'   => ['👍', 'Me gusta'],
        'util'       => ['💡', 'Útil'],
        'asombro'    => ['😮', 'Asombro'],
        'sospechoso' => ['👁', 'Sospechoso'],
    ];

    // Dónde se guarda la reacción según el tipo de mensaje (nombres fijos: nunca vienen del usuario).
    private const OBJETIVOS = [
        'publicacion' => ['tabla' => 'reacciones_publicaciones', 'columna' => 'id_publicacion'],
        'comentario'  => ['tabla' => 'reacciones_comentarios',  'columna' => 'id_comentario'],
    ];

    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // ---------- Hilos ----------

    // Hilos más recientes primero, con cantidad de comentarios y de reacciones.
    public function listarPublicaciones($limite = 50) {
        $stmt = $this->db->prepare(
            "SELECT p.id_publicacion, p.titulo, p.contenido, p.id AS id_autor, u.nombre_usuario_anonimo AS autor, u.foto_perfil AS autor_foto,
                    TIMESTAMPDIFF(SECOND, p.fecha_publicacion, NOW()) AS segundos,
                    (SELECT COUNT(*) FROM comentarios c WHERE c.id_publicacion = p.id_publicacion) AS total_comentarios,
                    (SELECT COUNT(*) FROM reacciones_publicaciones r WHERE r.id_publicacion = p.id_publicacion) AS total_reacciones
             FROM publicaciones p
             INNER JOIN usuarios u ON u.id = p.id
             ORDER BY p.fecha_publicacion DESC, p.id_publicacion DESC
             LIMIT ?"
        );
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $filas;
    }

    public function obtenerPublicacion($id) {
        $stmt = $this->db->prepare(
            "SELECT p.id_publicacion, p.titulo, p.contenido, p.id AS id_autor, u.nombre_usuario_anonimo AS autor, u.foto_perfil AS autor_foto,
                    TIMESTAMPDIFF(SECOND, p.fecha_publicacion, NOW()) AS segundos
             FROM publicaciones p
             INNER JOIN usuarios u ON u.id = p.id
             WHERE p.id_publicacion = ? LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    // Devuelve el id del hilo creado.
    public function crearPublicacion($idUsuario, $titulo, $contenido) {
        $stmt = $this->db->prepare("INSERT INTO publicaciones (titulo, contenido, id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $titulo, $contenido, $idUsuario);
        $stmt->execute();
        $nuevoId = $stmt->insert_id;
        $stmt->close();
        return $nuevoId;
    }

    // Borra el hilo; sus comentarios y todas las reacciones se borran solos (ON DELETE CASCADE).
    public function eliminarPublicacion($id) {
        $stmt = $this->db->prepare("DELETE FROM publicaciones WHERE id_publicacion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // ---------- Comentarios ----------

    // Del más viejo al más nuevo (se leen como una conversación).
    public function listarComentarios($idPublicacion) {
        $stmt = $this->db->prepare(
            "SELECT c.id_comentario, c.id_publicacion, c.contenido, c.id AS id_autor, u.nombre_usuario_anonimo AS autor, u.foto_perfil AS autor_foto,
                    TIMESTAMPDIFF(SECOND, c.fecha_comentario, NOW()) AS segundos
             FROM comentarios c
             INNER JOIN usuarios u ON u.id = c.id
             WHERE c.id_publicacion = ?
             ORDER BY c.fecha_comentario ASC, c.id_comentario ASC"
        );
        $stmt->bind_param("i", $idPublicacion);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $filas;
    }

    public function obtenerComentario($id) {
        $stmt = $this->db->prepare(
            "SELECT id_comentario, id_publicacion, id AS id_autor FROM comentarios WHERE id_comentario = ? LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    // Devuelve el id del comentario creado.
    public function crearComentario($idPublicacion, $idUsuario, $contenido) {
        $stmt = $this->db->prepare("INSERT INTO comentarios (id_publicacion, id, contenido) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $idPublicacion, $idUsuario, $contenido);
        $stmt->execute();
        $nuevoId = $stmt->insert_id;
        $stmt->close();
        return $nuevoId;
    }

    public function eliminarComentario($id) {
        $stmt = $this->db->prepare("DELETE FROM comentarios WHERE id_comentario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // ---------- Reacciones ----------

    // A cada fila (hilo o comentario) le agrega:
    //   'reacciones' => [tipo => cantidad] con TODOS los tipos (0 si nadie reaccionó)
    //   'mia'        => tipo con el que reaccionó $idUsuario, o '' si no reaccionó
    // Hace 2 consultas en total sin importar cuántas filas haya. $objetivo: 'publicacion' | 'comentario'.
    public function adjuntarReacciones($objetivo, array $filas, $campoId, $idUsuario) {
        $def    = self::OBJETIVOS[$objetivo];
        $vacias = array_fill_keys(array_keys(self::REACCIONES), 0);

        foreach ($filas as $i => $fila) {
            $filas[$i]['reacciones'] = $vacias;
            $filas[$i]['mia']        = '';
        }
        if (!$filas) {
            return $filas;
        }

        $lista = implode(',', array_map('intval', array_column($filas, $campoId))); // enteros: seguro
        $tabla = $def['tabla'];
        $col   = $def['columna'];

        $conteos = [];
        $res = $this->db->query(
            "SELECT $col AS objetivo, tipo, COUNT(*) AS total FROM $tabla WHERE $col IN ($lista) GROUP BY $col, tipo"
        );
        foreach ($res->fetch_all(MYSQLI_ASSOC) as $r) {
            $conteos[(int) $r['objetivo']][$r['tipo']] = (int) $r['total'];
        }

        $mias = [];
        $stmt = $this->db->prepare("SELECT $col AS objetivo, tipo FROM $tabla WHERE id = ? AND $col IN ($lista)");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $mias[(int) $r['objetivo']] = $r['tipo'];
        }
        $stmt->close();

        foreach ($filas as $i => $fila) {
            $oid = (int) $fila[$campoId];
            foreach (($conteos[$oid] ?? []) as $tipo => $total) {
                if (isset($vacias[$tipo])) { // ignora tipos viejos que ya no existan en REACCIONES
                    $filas[$i]['reacciones'][$tipo] = $total;
                }
            }
            $filas[$i]['mia'] = $mias[$oid] ?? '';
        }
        return $filas;
    }

    // Reacciona como un interruptor: sin reacción previa -> la agrega; mismo tipo -> la quita;
    // otro tipo -> la cambia. Devuelve 'agregada' | 'quitada' | 'cambiada'.
    public function reaccionar($objetivo, $idObjetivo, $idUsuario, $tipo) {
        $def   = self::OBJETIVOS[$objetivo];
        $tabla = $def['tabla'];
        $col   = $def['columna'];

        $stmt = $this->db->prepare("SELECT tipo FROM $tabla WHERE $col = ? AND id = ? LIMIT 1");
        $stmt->bind_param("ii", $idObjetivo, $idUsuario);
        $stmt->execute();
        $previa = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $actual = $previa ? $previa['tipo'] : null;

        if ($actual === $tipo) {
            $stmt = $this->db->prepare("DELETE FROM $tabla WHERE $col = ? AND id = ?");
            $stmt->bind_param("ii", $idObjetivo, $idUsuario);
            $stmt->execute();
            $stmt->close();
            return 'quitada';
        }

        // ON DUPLICATE KEY: si dos clics llegan a la vez, el segundo actualiza en vez de fallar.
        $stmt = $this->db->prepare(
            "INSERT INTO $tabla ($col, id, tipo) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE tipo = ?"
        );
        $stmt->bind_param("iiss", $idObjetivo, $idUsuario, $tipo, $tipo);
        $stmt->execute();
        $stmt->close();
        return $actual === null ? 'agregada' : 'cambiada';
    }
}
