# paradoxa26

## Puesta en marcha

1. Levantar **Apache y MySQL** en XAMPP y copiar el proyecto en `htdocs`.
2. Base de datos `paradoxa26` (config en `config/conexion.php`, usuario `root` sin contraseña):

   Importar **un solo archivo**: `sql/paradoxa26.sql` (phpMyAdmin → Importar). Ya trae todas las tablas, la cuenta
   de administrador y 3 hilos de ejemplo para el foro (borrar la sección final del archivo si no se los quiere).
   **Ojo:** si la base `paradoxa26` ya existe, reimportar borra y recrea sus tablas.

3. Abrir `http://localhost/paradoxa26/`. El administrador del sistema es la cuenta del puzzle (ver el comentario oculto en `login.php`).

Para que funcionen las fotos de perfil y las imágenes de hilos, PHP debe poder escribir en `app/public/uploads/avatars/` y `app/public/uploads/hilos/` (en XAMPP/Windows no hace falta configurar nada).

Para poder iniciar sesión, la cuenta debe tener `usuarios.estado_verificado = 1` (se pone solo al validar el correo). La cuenta del administrador del puzzle también: si está en 0, activala a mano en phpMyAdmin.

La estructura del código y las convenciones están en `app/README.txt`. El historial de cambios está en `CAMBIOS.md`.
