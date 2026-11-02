<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/ValidadorUsuario.php';

class AuthController {
    private $userModel;

    public function __construct() {
        global $conexion;
        $this->userModel = new UserModel($conexion);
    }

    // Renderiza el login/registro con las variables que necesita la vista:
    // $tab ('login'|'register'), $error, $exito, $old (valores a repoblar).
    private function render($vars = []) {
        $tab   = 'login';
        $error = null;
        $exito = null;
        $old   = [];
        extract($vars, EXTR_OVERWRITE);
        require __DIR__ . '/../views/auth/login.php';
    }

    private function redirigir($ruta) {
        header('Location: ' . BASE_URL . $ruta);
        exit;
    }
    private function enviarcorreo($nombre,$email, $token) {
    $mail = new PHPMailer(true);
    try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';                     // Servidor SMTP de Gmail
    $mail->SMTPAuth   = true;                                   // Habilitar autenticación SMTP
    $mail->Username   = 'paradoxa266@gmail.com';                  // Tu correo de Gmail
    $mail->Password   = 'yret lneh adip aqbg';                  // Tu Contraseña de Aplicación de 16 dígitos
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;          // Encriptación SSL (también puedes usar STARTTLS con puerto 587)
    $mail->Port       = 587;                                    // Puerto SSL (o 587 para TLS)
    //Recipients
    $mail->setFrom('paradoxa266@gmail.com', 'Paradoxa');
    $mail->addAddress($email, $nombre);     //Add a recipient             //Name is optional
    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'Paradoxa26 -- Validacion de cuenta';
    $mail->Body    = 'Hola ' . $nombre . '<br> Ingresa al siguiente link para validar tu cuenta <br> <a href=' . BASE_URL . '/index.php?c=Auth&amp;a=verificaremail&t=' . $token . '&e=' . $email .'>Haz click aqui</a>';
    $mail->AltBody = '';

    $mail->send();
} catch (Exception $e) {
    return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
return null;
    }
public function verificaremail() {
        $email    = strtolower(trim($_GET['e'] ?? ''));
        $token = $_GET['t'] ?? ''; // sin trim: los espacios cuentan
 if ($email === '' || $token === '') {
    $this->render(['tab' => 'login', 'error' => 'Activacion incorrecta.']);
    return;
        }
        if (!$this->userModel->obtenerPorCorreo($email)) {
            return $fallo('Correo invalido.');
        }
 $user = $this->userModel->obtenerPorCorreo($email);
        if ($token != $user['token'] ) {
        $this->render(['tab' => 'login', 'error' => 'Token incorrecto.', 'old' => ['email' => $email]]);
        return;
        }
        if ($user['estado_verificado'] == 1 ) {
        $this->render(['tab' => 'login', 'error' => 'La cuenta ya esta activada.', 'old' => ['email' => $email]]);
        return;
        }
        try {
            $this->userModel->actualizarestado( $email, $token);
        } catch (mysqli_sql_exception $e) {
            //error_log('[paradoxa26] Error al activar cuenta: ' . $e->getMessage());
            // 1062 = clave duplicada (por si dos personas se registran a la vez)
            $this->render(['tab' => 'login', 'error' => 'Error a .'. $e->getMessage(), 'old' => ['email' => $email]]);
            return;
        }
         $this->render(['tab' => 'login', 'exito' => 'cuenta activada correctamenete inicie sesion']);

        exit;
    }
    public function loginView() {
        $exito = (($_GET['registro'] ?? '') === 'ok')
            ? 'Cuenta creada correctamente. Ahora revisa tu bandeja de entrada para activar tu cuenta.'
            : null;
        $this->render(['tab' => 'login', 'exito' => $exito]);
    }

    public function registerView() {
        $this->render(['tab' => 'register']);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('/index.php?c=Auth&a=loginView');
        }

        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? ''; // sin trim: los espacios cuentan

        if ($email === '' || $password === '') {
            $this->render(['tab' => 'login', 'error' => 'Por favor completá todos los campos.']);
            return;
        }

        $user = $this->userModel->obtenerPorCorreo($email);
        if (!$user || !password_verify($password, $user['contrasenia'])) {
            $this->render(['tab' => 'login', 'error' => 'Credenciales incorrectas.', 'old' => ['email' => $email]]);
            return;
        }

        // Cuenta sin validar por correo (estado_verificado = 0): no se inicia sesión ni se entra al foro.
        // Se chequea DESPUÉS de la contraseña para no revelar el estado de una cuenta a quien no la conoce.
        if ((int) $user['estado_verificado'] !== 1) {
            $this->render(['tab' => 'login', 'error' => MSG_CUENTA_NO_VERIFICADA, 'old' => ['email' => $email]]);
            return;
        }

        // Sesión nueva (evita fijación de sesión) y datos que usa el foro.
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $user['id'];
        $_SESSION['alias']      = $user['nombre_usuario_anonimo'];
        $_SESSION['correo']     = $user['correo'];

        // Cada rol tiene su pantalla de inicio: los admin al panel, el resto al foro.
        if ($user['rol'] === 'admin') {
            $this->redirigir('/app/views/admin/index.php');
        }
        $this->redirigir('/index.php?c=Foro&a=index'); // el foro
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('/index.php?c=Auth&a=registerView');
        }

        $nombre    = trim($_POST['nombre'] ?? '');
        $alias     = trim($_POST['alias'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        // Se repueblan los campos del formulario si hay error (la contraseña nunca).
        $old = ['nombre' => $nombre, 'alias' => $alias, 'email' => $email];
        $fallo = function ($mensaje) use ($old) {
            $this->render(['tab' => 'register', 'error' => $mensaje, 'old' => $old]);
        };

        $error = ValidadorUsuario::validar($nombre, $alias, $email, $password, $password2, true);
        if ($error !== null) {
            return $fallo($error);
        }
        if ($this->userModel->obtenerPorCorreo($email)) {
            return $fallo('El correo electrónico ya está registrado.');
        }
        if ($this->userModel->existeAlias($alias)) {
            return $fallo('Ese alias ya está en uso, elegí otro.');
        }
        $token = md5(rand());
        try {
            $this->userModel->registrar($nombre, $email, $password, $alias, $token);
        } catch (mysqli_sql_exception $e) {
            error_log('[paradoxa26] Error al registrar usuario: ' . $e->getMessage());
            // 1062 = clave duplicada (por si dos personas se registran a la vez)
            return $fallo($e->getCode() === 1062
                ? 'El correo o el alias ya están registrados.'
                : 'Ocurrió un error al registrar el usuario.');
        }
        $error1 = $this->enviarcorreo($nombre,$email,$token);
        if ($error1 !== null) {
        return $fallo($error1);
        }
        //mandar correo acva

        // Redirección (PRG): la página se recarga limpia en el login y F5 no reenvía el formulario.
        $this->redirigir('/index.php?c=Auth&a=loginView&registro=ok');
    }

    public function logout() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->redirigir('/app/views/auth/login.php');
    }
}
