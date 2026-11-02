<?php
// app/helpers/Formato.php
// Funciones de presentación (textos para mostrar). Sin acceso a datos ni HTML.

// "hace 5 minutos", "hace 2 horas"… a partir de los segundos transcurridos.
// Los segundos los calcula la propia BD (TIMESTAMPDIFF ... NOW()), así no hay
// desfases entre la zona horaria de MySQL y la de PHP.
function tiempoRelativo($segundos) {
    $s = max(0, (int) $segundos);
    if ($s < 60) {
        return 'hace un momento';
    }
    if ($s < 3600) {
        $n = intdiv($s, 60);
        return 'hace ' . $n . ($n === 1 ? ' minuto' : ' minutos');
    }
    if ($s < 86400) {
        $n = intdiv($s, 3600);
        return 'hace ' . $n . ($n === 1 ? ' hora' : ' horas');
    }
    if ($s < 2592000) { // 30 días
        $n = intdiv($s, 86400);
        return 'hace ' . $n . ($n === 1 ? ' día' : ' días');
    }
    if ($s < 31536000) { // 365 días
        $n = intdiv($s, 2592000);
        return 'hace ' . $n . ($n === 1 ? ' mes' : ' meses');
    }
    $n = intdiv($s, 31536000);
    return 'hace ' . $n . ($n === 1 ? ' año' : ' años');
}

// pluralizar(1, 'respuesta', 'respuestas') -> "1 respuesta"; pluralizar(3, ...) -> "3 respuestas"
function pluralizar($cantidad, $singular, $plural) {
    $n = (int) $cantidad;
    return $n . ' ' . ($n === 1 ? $singular : $plural);
}
