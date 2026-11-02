# Registro de cambios

Todo lo agregado o modificado sobre el proyecto original, en orden. Regla que se respetó en todas las rondas:
**no se tocó el código del envío de correo de verificación** (`AuthController::enviarcorreo`, `AuthController::verificaremail`,
`UserModel::actualizarestado`, `linkcorreo.html`, PHPMailer en `vendor/`) **ni la estructura de la base de datos** en la ronda 2.

---

## Ronda 3 — Cuadros de diálogo propios (adiós `alert()` / `confirm()`)

Los cuadros del navegador se reemplazaron por ventanas con la estética de PHYLLO OS (barra de título, bordes en relieve, botones estilo Windows 98; roja en el panel admin). No cambió ninguna lógica de PHP ni de base de datos.

- **Nuevo** `app/public/js/dialogo.js`: `Dialogo.aviso(texto)` y `Dialogo.confirmar(texto, opciones, alAceptar)`. Esc cancela, Enter acepta, Tab da vueltas entre botones.
- **Formularios:** `onsubmit="return confirm(...)"` pasó a `data-confirmar="..."` (y `data-confirmar-ok="Eliminar"` para el texto del botón). El script intercepta el envío y lo deja salir solo si se acepta.
- `app/public/css/style.css`: sección 4 con los estilos `.dlg-*`.
- Vistas tocadas: `home/index.php` (quitar foto, aviso de 2 MB), `home/foro_hilo.php` (eliminar hilo/comentario), `home/foro_nuevo.php` (aviso de 2 MB), `admin/usuarios.php` (eliminar usuario), `layout/admin_header.php` (colores rojos) y `layout/admin_footer.php` (carga del script).

---

## Ronda 2 — Verificación obligatoria e imágenes en hilos

### 1. Cuenta sin validar: no entra ni al login ni al foro

**Comportamiento**
- `usuarios.estado_verificado = 0`: no se puede iniciar sesión. Aparece el error:
  *"Tenés que validar tu cuenta desde el correo (Gmail) que te enviamos para poder iniciar sesión y entrar al foro."*
- `usuarios.estado_verificado = 1`: inicia sesión y entra al foro con normalidad.
- El chequeo se hace **después** de comprobar la contraseña: quien no conoce la clave de una cuenta no puede averiguar si está validada o no.
- Defensa extra: si una sesión ya abierta pertenece a una cuenta con estado 0 (por ejemplo, un admin la puso en 0 después), en el siguiente
  request se cierra la sesión y se redirige al login con el mismo aviso. Esto cubre el foro, el perfil y el panel admin.

**Archivos**
| Archivo | Cambio |
|---|---|
| `config/app.php` | Nueva constante `MSG_CUENTA_NO_VERIFICADA` con el texto del aviso. |
| `app/controllers/AuthController.php` | Solo el método `login()`: bloque nuevo que rechaza si `estado_verificado` no es 1. `enviarcorreo()`, `verificaremail()` y `register()` no se tocaron. |
| `config/auth.php` | `usuarioActual()` ahora lee también `estado_verificado`; si no es 1 invalida la sesión y deja el aviso en `$_SESSION['login_error']`. |
| `app/views/auth/login.php` | Muestra (una sola vez) el aviso de `$_SESSION['login_error']` en el cartel de error del login. |

### 2. Imagen opcional en los hilos

**Comportamiento**
- En **Nuevo hilo** hay un campo *Imagen (opcional)* con vista previa. JPG, PNG, GIF o WebP, hasta 2 MB.
- La imagen se valida antes de crear el hilo (si es inválida, el hilo no se publica y se muestra el error).
- Se muestra en la **lista de hilos** (miniatura de hasta 160 px de alto) y en el **hilo completo** (tamaño completo; al hacer clic se abre en otra pestaña).
- Si es más grande de 1200 px de lado se reduce manteniendo la proporción; se reprocesa con GD (descarta metadatos y contenido oculto). Los GIF animados quedan como imagen fija.
- Se borra sola cuando se elimina el hilo, o cuando un admin elimina al usuario autor.

**Sin cambios en la base de datos:** la imagen se guarda como archivo en `app/public/uploads/hilos/` con el id del hilo como nombre
(`h12.jpg`, `h12.png`, ...). El hilo "tiene imagen" si existe ese archivo. No hay columna ni migración nueva.

**Archivos**
| Archivo | Cambio |
|---|---|
| `app/helpers/ImagenHilo.php` | **Nuevo.** Valida, guarda, busca y borra la imagen de un hilo. |
| `app/controllers/ForoController.php` | `crearPublicacion()` valida y guarda la imagen; `eliminarPublicacion()` la borra; `index()` y `mostrarHilo()` agregan `imagen` a cada hilo. |
| `app/models/UserModel.php` | `eliminar()` borra también las imágenes de los hilos del usuario eliminado. |
| `app/views/home/foro_nuevo.php` | Formulario `multipart`, campo de archivo y vista previa. |
| `app/views/home/foro_lista.php` | Miniatura dentro de cada tarjeta de hilo. |
| `app/views/home/foro_hilo.php` | Imagen debajo del mensaje del hilo. |
| `app/views/home/index.php` | CSS de `.hilo-img`. |
| `app/public/uploads/hilos/` | **Nueva carpeta** (PHP necesita permiso de escritura). Hereda el `.htaccess` de `uploads/` que impide ejecutar código. |

### Para probar
1. Cuenta con `estado_verificado = 0` → login con la contraseña correcta → debe mostrar el aviso y no entrar.
2. Poner `estado_verificado = 1` → debe entrar al foro.
3. Publicar un hilo con imagen → se ve en la lista y en el hilo; eliminar el hilo → desaparece el archivo de `uploads/hilos/`.

---

## Ronda 1 — Ícono, "Sobre nosotros" y fotos de perfil

### Ícono de la pestaña
- Nuevos `app/public/img/favicon.png`, `favicon.ico` y `apple-touch-icon.png`, generados con la imagen del insecto.
- Se agregaron los `<link rel="icon">` en `index.php` (puzzle), `auth/login.php`, `home/index.php`, `admin/index.php`, `layout/admin_header.php` y `layout/header.php`.

### Sobre nosotros
- Nuevo botón **Sobre nosotros** en el menú lateral del foro y nueva sección `#viewSobre` en `app/views/home/index.php`
  (quiénes somos, objetivos, horarios y contacto; es HTML estático, se edita ahí mismo).
- El foro puede abrirse directamente en esa pestaña con `?vista=sobre` (y en Mi cuenta con `?vista=cuenta`).

### Fotos de perfil
- Cada usuario sube, cambia o quita su foto desde **Mi cuenta**. Se recorta en cuadrado de 256 px y se guarda en `app/public/uploads/avatars/`.
- Se muestra junto al alias en la lista de hilos, en los hilos, en los comentarios y en la barra inferior. Sin foto: cuadrado con la inicial del alias.
- Archivos: `app/controllers/PerfilController.php` (nuevo), `app/helpers/FotoPerfil.php` (nuevo), `app/models/UserModel.php`
  (`actualizarFoto()` y limpieza en `eliminar()`), `app/models/ForoModel.php` (el autor trae su foto), `config/auth.php`,
  vistas del foro, `app/public/uploads/.htaccess` (bloquea ejecución de código).
- Base de datos: columna `usuarios.foto_perfil` — `sql/migracion_4_foto_perfil.sql` (y ya incluida en `sql/paradoxa26.sql`).
