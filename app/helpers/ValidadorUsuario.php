<?php
// app/helpers/ValidadorUsuario.php
// Reglas de validación de los datos de un usuario, compartidas entre el registro
// público (AuthController) y el alta/edición del admin (UsuarioController).

class ValidadorUsuario {
    // Valida lo que el propio usuario edita en "Mi cuenta": alias y descripción breve.
    // Devuelve el mensaje de error, o null si está todo bien.
    public static function validarPerfil($alias, $biografia) {
        if ($alias === '') {
            return 'El alias no puede estar vacío.';
        }
        if (!preg_match('/^[A-Za-z0-9_.\-]{3,30}$/', $alias)) {
            return 'El alias debe tener entre 3 y 30 caracteres: letras, números, guion, guion bajo o punto.';
        }
        if (mb_strlen($biografia) > 255) {
            return 'La descripción puede tener hasta 255 caracteres.';
        }
        return null;
    }

    // Devuelve el mensaje de error, o null si los datos son válidos.
    // $passwordObligatoria = false -> en una edición, dejar la contraseña vacía = no cambiarla.
    public static function validar($nombre, $alias, $email, $password, $password2, $passwordObligatoria = true) {
        if ($nombre === '' || $alias === '' || $email === ''
            || ($passwordObligatoria && ($password === '' || $password2 === ''))) {
            return 'Por favor completá todos los campos.';
        }
        if (mb_strlen($nombre) > 50) {
            return 'El nombre puede tener hasta 50 caracteres.';
        }
        if (!preg_match('/^[A-Za-z0-9_.\-]{3,30}$/', $alias)) {
            return 'El alias debe tener entre 3 y 30 caracteres: letras, números, guion, guion bajo o punto.';
        }
        if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Ingresá un correo electrónico válido.';
        }
        if ($passwordObligatoria || $password !== '' || $password2 !== '') {
            if (strlen($password) < 8) {
                return 'La contraseña debe tener al menos 8 caracteres.';
            }
            if (strlen($password) > 72) { // bcrypt ignora todo lo que pase de 72 bytes
                return 'La contraseña puede tener hasta 72 caracteres.';
            }
            if ($password !== $password2) {
                return 'Las contraseñas no coinciden.';
            }
        }
        return null;
    }
}
