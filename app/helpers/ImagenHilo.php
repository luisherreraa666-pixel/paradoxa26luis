<?php
// app/helpers/ImagenHilo.php
// Imagen opcional adjunta a un hilo del foro. Sin acceso a la base de datos: el archivo se guarda en
// app/public/uploads/hilos/ con el id del hilo como nombre (h12.jpg, h12.png...), así que el hilo
// "tiene imagen" si existe ese archivo. No hace falta ninguna columna en la BD.

class ImagenHilo {
    const MAX_BYTES  = 2097152;   // 2 MB
    const MAX_LADO   = 1200;      // si es más grande, se reduce (manteniendo proporción) a este lado máximo
    const MAX_PIXELS = 25000000;  // 25 MP: evita imágenes "bomba" que agoten la memoria al procesarlas

    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    public static function directorio() {
        return dirname(__DIR__) . '/public/uploads/hilos';
    }

    // true si el usuario eligió un archivo en el formulario (el campo es opcional).
    public static function hayArchivo($archivo) {
        return is_array($archivo)
            && isset($archivo['error'])
            && $archivo['error'] !== UPLOAD_ERR_NO_FILE;
    }

    // Devuelve un mensaje de error, o null si la imagen es válida. Se llama ANTES de crear el hilo.
    public static function validar($archivo) {
        if (!self::hayArchivo($archivo)) {
            return null;
        }
        switch ($archivo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'La imagen es demasiado pesada (máximo 2 MB).';
            default:
                return 'No se pudo subir la imagen. Probá de nuevo.';
        }
        $tmp = $archivo['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            return 'No se pudo subir la imagen. Probá de nuevo.';
        }
        if ((int) $archivo['size'] > self::MAX_BYTES) {
            return 'La imagen es demasiado pesada (máximo 2 MB).';
        }
        // getimagesize lee la cabecera real: no nos fiamos del nombre ni del tipo que declara el navegador.
        $info = @getimagesize($tmp);
        if ($info === false || !array_key_exists($info['mime'], self::TIPOS)) {
            return 'El archivo no es una imagen válida. Usá JPG, PNG, GIF o WebP.';
        }
        $ancho = (int) $info[0];
        $alto  = (int) $info[1];
        if ($ancho < 1 || $alto < 1 || $ancho * $alto > self::MAX_PIXELS) {
            return 'Las dimensiones de la imagen no son válidas o son demasiado grandes.';
        }
        return null;
    }

    // Guarda la imagen (ya validada) del hilo $idPublicacion. true si se guardó.
    public static function guardar($archivo, $idPublicacion) {
        $tmp  = $archivo['tmp_name'] ?? '';
        $info = @getimagesize($tmp);
        if ($info === false || !array_key_exists($info['mime'], self::TIPOS)) {
            return false;
        }
        $dir = self::directorio();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            error_log('[paradoxa26] No se pudo crear la carpeta de imágenes de hilos: ' . $dir);
            return false;
        }
        self::borrar($idPublicacion); // por si quedaba algo con ese id

        $mime = $info['mime'];
        $base = $dir . '/h' . (int) $idPublicacion;

        if (function_exists('imagecreatefromstring')) {
            // Se reprocesa: reduce si es muy grande y descarta metadatos o contenido raro pegado al archivo.
            $origen = @imagecreatefromstring((string) file_get_contents($tmp));
            if ($origen === false) {
                return false;
            }
            if ($mime === 'image/jpeg' && function_exists('exif_read_data')) { // fotos de celular: orientación
                $exif  = @exif_read_data($tmp);
                $giros = [3 => 180, 6 => -90, 8 => 90];
                if (!empty($exif['Orientation']) && isset($giros[$exif['Orientation']])) {
                    $girada = imagerotate($origen, $giros[$exif['Orientation']], 0);
                    if ($girada !== false) {
                        $origen = $girada;
                    }
                }
            }
            $w = imagesx($origen);
            $h = imagesy($origen);
            $escala = min(1, self::MAX_LADO / max($w, $h));
            $nw = max(1, (int) round($w * $escala));
            $nh = max(1, (int) round($h * $escala));

            $destino = imagecreatetruecolor($nw, $nh);
            if ($mime === 'image/jpeg') {
                imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
            } else {
                imagealphablending($destino, false);
                imagesavealpha($destino, true);
                imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
            }
            imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nw, $nh, $w, $h);

            $ok = ($mime === 'image/jpeg')
                ? imagejpeg($destino, $base . '.jpg', 88)
                : imagepng($destino, $base . '.png', 6);
            imagedestroy($origen);
            imagedestroy($destino);
            return (bool) $ok;
        }

        // Sin la extensión GD de PHP no se puede reducir: se guarda tal cual (ya validada).
        return move_uploaded_file($tmp, $base . '.' . self::TIPOS[$mime]);
    }

    // URL pública de la imagen del hilo, o null si no tiene.
    public static function url($idPublicacion) {
        $id = (int) $idPublicacion;
        foreach (['jpg', 'png', 'gif', 'webp'] as $ext) {
            if (is_file(self::directorio() . '/h' . $id . '.' . $ext)) {
                return BASE_URL . '/app/public/uploads/hilos/h' . $id . '.' . $ext;
            }
        }
        return null;
    }

    // Borra la imagen del hilo (si tenía).
    public static function borrar($idPublicacion) {
        $id = (int) $idPublicacion;
        foreach (['jpg', 'png', 'gif', 'webp'] as $ext) {
            $ruta = self::directorio() . '/h' . $id . '.' . $ext;
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }
    }
}
