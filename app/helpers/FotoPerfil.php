<?php
// app/helpers/FotoPerfil.php
// Fotos de perfil: valida la imagen subida, la recorta en cuadrado, la guarda en
// app/public/uploads/avatars/ y arma el HTML del avatar. Sin acceso a la base de datos
// (de eso se encarga UserModel::actualizarFoto).
//
// En la BD (usuarios.foto_perfil) solo se guarda el NOMBRE del archivo, nunca una ruta.

class FotoPerfil {
    const MAX_BYTES  = 2097152;   // 2 MB
    const LADO       = 256;       // las fotos se guardan de 256x256 px
    const MAX_PIXELS = 25000000;  // 25 MP: evita imágenes "bomba" que agoten la memoria al procesarlas
    const PATRON     = '/^u\d+_[a-f0-9]{12}\.(jpg|png|gif|webp)$/';

    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    // Carpeta física donde se guardan las fotos.
    public static function directorio() {
        return dirname(__DIR__) . '/public/uploads/avatars';
    }

    // true si el valor guardado en la BD tiene el formato de un archivo nuestro.
    public static function esNombreValido($nombre) {
        return is_string($nombre) && preg_match(self::PATRON, $nombre) === 1;
    }

    // URL pública de la foto, o null si el usuario no tiene.
    public static function url($nombre) {
        return self::esNombreValido($nombre)
            ? BASE_URL . '/app/public/uploads/avatars/' . $nombre
            : null;
    }

    // Borra el archivo de una foto (si existe). Solo acepta nombres con nuestro formato.
    public static function borrarArchivo($nombre) {
        if (!self::esNombreValido($nombre)) {
            return;
        }
        $ruta = self::directorio() . '/' . $nombre;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    // Procesa $_FILES['foto']. Devuelve ['nombre' => 'u3_ab12cd34ef56.png'] o ['error' => 'mensaje'].
    public static function guardar($archivo, $idUsuario) {
        if (!is_array($archivo) || !isset($archivo['error'])) {
            return ['error' => 'No se recibió ninguna imagen.'];
        }
        switch ($archivo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['error' => 'Elegí una imagen antes de guardar.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['error' => 'La imagen es demasiado pesada (máximo 2 MB).'];
            default:
                return ['error' => 'No se pudo subir la imagen. Probá de nuevo.'];
        }

        $tmp = $archivo['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            return ['error' => 'No se pudo subir la imagen. Probá de nuevo.'];
        }
        if ((int) $archivo['size'] > self::MAX_BYTES) {
            return ['error' => 'La imagen es demasiado pesada (máximo 2 MB).'];
        }

        // getimagesize lee la cabecera real del archivo: no nos fiamos del nombre ni del tipo que declara el navegador.
        $info = @getimagesize($tmp);
        if ($info === false || !array_key_exists($info['mime'], self::TIPOS)) {
            return ['error' => 'El archivo no es una imagen válida. Usá JPG, PNG, GIF o WebP.'];
        }
        $ancho = (int) $info[0];
        $alto  = (int) $info[1];
        if ($ancho < 1 || $alto < 1 || $ancho * $alto > self::MAX_PIXELS) {
            return ['error' => 'Las dimensiones de la imagen no son válidas o son demasiado grandes.'];
        }

        $dir = self::directorio();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            error_log('[paradoxa26] No se pudo crear la carpeta de avatares: ' . $dir);
            return ['error' => 'No se pudo guardar la imagen en el servidor.'];
        }

        $sufijo = bin2hex(random_bytes(6)); // nombre nuevo en cada subida: evita cachés y adivinar rutas
        $mime   = $info['mime'];

        if (function_exists('imagecreatefromstring')) {
            // Se reprocesa la imagen: recorte cuadrado + reescalado. Además descarta metadatos y cualquier
            // contenido raro que pudiera venir pegado al archivo.
            $origen = @imagecreatefromstring((string) file_get_contents($tmp));
            if ($origen === false) {
                return ['error' => 'No se pudo procesar la imagen. Probá con otra.'];
            }

            // Fotos de celular: respetar la orientación EXIF.
            if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data($tmp);
                $giros = [3 => 180, 6 => -90, 8 => 90];
                if (!empty($exif['Orientation']) && isset($giros[$exif['Orientation']])) {
                    $girada = imagerotate($origen, $giros[$exif['Orientation']], 0);
                    if ($girada !== false) {
                        $origen = $girada;
                    }
                }
            }

            $w    = imagesx($origen);
            $h    = imagesy($origen);
            $lado = min($w, $h);
            $sx   = (int) (($w - $lado) / 2);
            $sy   = (int) (($h - $lado) / 2);

            $destino = imagecreatetruecolor(self::LADO, self::LADO);
            if ($mime === 'image/jpeg') {
                imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
            } else {
                imagealphablending($destino, false);
                imagesavealpha($destino, true);
                imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
            }
            imagecopyresampled($destino, $origen, 0, 0, $sx, $sy, self::LADO, self::LADO, $lado, $lado);

            $ext    = ($mime === 'image/jpeg') ? 'jpg' : 'png';
            $nombre = 'u' . (int) $idUsuario . '_' . $sufijo . '.' . $ext;
            $ruta   = $dir . '/' . $nombre;
            $ok     = ($ext === 'jpg') ? imagejpeg($destino, $ruta, 88) : imagepng($destino, $ruta, 6);
            imagedestroy($origen);
            imagedestroy($destino);

            if (!$ok) {
                return ['error' => 'No se pudo guardar la imagen en el servidor.'];
            }
            return ['nombre' => $nombre];
        }

        // Sin la extensión GD de PHP no se puede recortar: se guarda tal cual (ya validada arriba) y
        // el CSS la muestra recortada en cuadrado.
        $nombre = 'u' . (int) $idUsuario . '_' . $sufijo . '.' . self::TIPOS[$mime];
        if (!move_uploaded_file($tmp, $dir . '/' . $nombre)) {
            return ['error' => 'No se pudo guardar la imagen en el servidor.'];
        }
        return ['nombre' => $nombre];
    }
}

// HTML del avatar: la foto si el usuario subió una; si no, un cuadrado con la inicial del alias
// (el color sale del alias, así cada usuario se distingue). $tam = lado en píxeles.
function avatarPerfil($foto, $alias, $tam = 32) {
    $tam = (int) $tam;
    $url = FotoPerfil::url($foto);
    if ($url !== null) {
        return '<img class="avatar" src="' . h($url) . '" alt="Foto de ' . h($alias) . '" width="' . $tam . '" height="' . $tam
             . '" style="width:' . $tam . 'px;height:' . $tam . 'px">';
    }
    $colores = ['#0a246a', '#8b0000', '#006400', '#4b0082', '#7a4b00', '#005f73', '#7a1f5c', '#3a3a3a'];
    $color   = $colores[abs(crc32((string) $alias)) % count($colores)];
    $inicial = mb_strtoupper(mb_substr((string) $alias, 0, 1, 'UTF-8'), 'UTF-8');
    return '<span class="avatar avatar--inicial" aria-hidden="true" style="width:' . $tam . 'px;height:' . $tam . 'px;line-height:' . ($tam - 2)
         . 'px;font-size:' . max(9, (int) round($tam * 0.5)) . 'px;background:' . $color . '">' . h($inicial) . '</span>';
}
