Estructura MVC de app/

controllers/  Un archivo por controlador. El nombre del archivo coincide con
              el de la clase (AuthController.php -> class AuthController).
              Reciben la petición, hablan con los models y eligen qué view
              devolver. No arman HTML acá.

models/       Acceso a datos (mysqli). Un archivo por entidad
              (UserModel.php -> class UserModel). No conocen HTML ni $_POST.

views/        Solo HTML/PHP de presentación. views/layout/header.php y
              footer.php son el shell compartido (doctype, <head>,
              apertura/cierre de <body>); las demás views los incluyen con
              require_once __DIR__ . '/../layout/header.php' (y footer.php)
              en vez de duplicar el <head> en cada archivo.

public/       Assets estáticos (css/, js/). Nada de PHP acá.

Front controller: index.php (arriba del todo) enruta ?c=Controlador&a=accion
-> ControladorController::accion(). Ej: /index.php?c=Auth&a=login. Sin el
parámetro "c" muestra el puzzle de PHYLLO OS. Solo se pueden invocar métodos
públicos de los controllers.

config/auth.php: sesión, roles y permisos. En cualquier página/controller:
    requiereLogin();         -> exige sesión
    requiereRol('admin');    -> exige ese rol (403 si no lo tiene)
    tieneRol('admin');       -> true/false, para mostrar u ocultar cosas
    usuarioActual();         -> fila del usuario logueado (id, alias, correo, rol)
Consulta la BD en cada request: si un admin cambia un rol o elimina una cuenta,
se aplica enseguida. También trae flash() (avisos tras redirecciones) y los
helpers CSRF (csrfCampo() en el <form>, csrfValido() en el controller: usarlos
en todo POST que modifique datos).

helpers/      Clases de apoyo sin acceso a datos ni HTML (ej. ValidadorUsuario,
              las reglas de alias/correo/contraseña del registro y del CRUD).

Foro (ForoController + ForoModel, vistas en views/home/): hilos, comentarios y
reacciones guardados en publicaciones, comentarios, reacciones_publicaciones y
reacciones_comentarios. Cualquier usuario con sesión puede publicar, comentar y
reaccionar; borrar hilos/comentarios solo el autor o un admin. Rutas:
?c=Foro&a=index | ver&id=N | nuevoView, y por POST (con CSRF): crearPublicacion,
comentar, reaccionar, eliminarPublicacion, eliminarComentario.
Reacciones: una por usuario y mensaje; clic en otro tipo la cambia, clic en el
mismo la quita. Los tipos están en ForoModel::REACCIONES (agregar uno = sumar una
línea ahí, la BD guarda la clave como texto). Los controllers preparan los datos
ya listos para mostrar (tiempos, textos, permisos) para que las views no calculen.
En la BD la columna `id` es SIEMPRE el usuario (autor / quien reacciona).

Perfil (PerfilController + helpers/FotoPerfil.php): cada usuario puede subir su foto de perfil
desde "Mi cuenta" en el foro. Rutas (POST con CSRF): ?c=Perfil&a=guardarFoto | eliminarFoto.
La imagen se valida (JPG/PNG/GIF/WebP, hasta 2 MB), se recorta en cuadrado de 256x256 y se guarda en
public/uploads/avatars/; en la BD (usuarios.foto_perfil) solo va el nombre del archivo. Sin foto se
muestra un avatar con la inicial del alias. Para dibujarlo en cualquier vista: avatarPerfil($foto, $alias, $px).
Requiere que PHP pueda escribir en public/uploads/avatars/ (la columna usuarios.foto_perfil ya viene en sql/paradoxa26.sql).

"Sobre nosotros": pestaña del menú lateral del foro (home/index.php, section #viewSobre). Es HTML
estático (misión, objetivos, horarios y contacto): se edita directo en esa vista.

Verificación de cuenta: usuarios.estado_verificado debe ser 1 para iniciar sesión y entrar al foro.
AuthController::login() rechaza las cuentas con 0 (con MSG_CUENTA_NO_VERIFICADA, de config/app.php) y
config/auth.php::usuarioActual() cierra la sesión de una cuenta que pase a 0, así que requiereLogin() y
requiereRol() lo aplican en todas las páginas protegidas. El envío del correo de validación no se tocó.

Imágenes en hilos (helpers/ImagenHilo.php): el formulario de nuevo hilo acepta una imagen opcional
(JPG/PNG/GIF/WebP, hasta 2 MB; se reduce a 1200 px de lado máximo). Se guarda en public/uploads/hilos/
como h{id_del_hilo}.{ext}: NO hay columna en la BD, el hilo tiene imagen si existe ese archivo.
Se borra al eliminar el hilo o al eliminar a su autor. PHP necesita poder escribir en esa carpeta.

Roles: columna usuarios.rol ('usuario' | 'admin'). El registro público siempre
crea 'usuario'; solo un admin puede asignar 'admin' desde el CRUD de usuarios
(?c=Usuario&a=listar). Para agregar un rol: ampliar el ENUM en la BD y
UserModel::ROLES.

config/app.php define BASE_URL (carpeta del proyecto respecto al document root)
y arranca la sesión; las views lo usan para armar rutas de css/audio/links que
funcionen tanto si las renderiza un controller como si se abren directo.

Para correr el proyecto hoy hace falta levantar XAMPP primero (Apache +
MySQL), config/conexion.php apunta a la base local `paradoxa26`
(importar sql/paradoxa26.sql, que ya trae todo).
