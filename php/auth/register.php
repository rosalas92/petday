<?php
/**
 * PetDay - Página de Registro de Usuarios
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

$errors = [];
$successMessage = '';

// Variables para el header.php
$isLoggedIn = false;
$user = null;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // --- Validación de datos ---
    if (empty($nombre_completo)) {
        $errors[] = 'El nombre completo es obligatorio.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo electrónico no es válido.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    // Verificar si el email ya existe
    if (empty($errors)) {
        $user = fetchOne('SELECT id_usuario FROM usuarios WHERE email = ?', [$email]);
        if ($user) {
            $errors[] = 'El correo electrónico ya está registrado.';
        }
    }

    // --- Si no hay errores, registrar al usuario ---
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $verification_token = bin2hex(random_bytes(16)); // Generar un token único

            $sql = 'INSERT INTO usuarios (nombre_completo, email, password_hash, token, is_verified) VALUES (?, ?, ?, ?, ?)';
            insertAndGetId($sql, [$nombre_completo, $email, $password_hash, $verification_token, 0]);
            
            // Enviar correo de verificación usando PHPMailer
            require_once __DIR__ . '/../../config/config.php';
            require_once __DIR__ . '/../../vendor/autoload.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                // Configuración del servidor SMTP desde config.php
                $mail->isSMTP();
                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USER;
                $mail->Password = MAIL_PASS;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                // Configuración para depuración (opcional)
                if (defined('DEBUG_MAIL') && DEBUG_MAIL) {
                    $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
                }

                // Destinatarios
                $mail->setFrom(MAIL_USER, 'PetDay');
                $mail->addAddress($email, $nombre_completo);

                // Contenido del correo
                $mail->isHTML(true);
                $mail->Subject = "Verifica tu correo electrónico para PetDay";
                $verification_link = URL_ADMIN . "/php/auth/verify_email.php?token=" . $verification_token;
                $mail->Body = "<p>Hola " . htmlspecialchars($nombre_completo) . ",</p>";
                $mail->Body .= "<p>Gracias por registrarte en PetDay. Por favor, haz clic en el siguiente enlace para verificar tu correo electrónico:</p>";
                $mail->Body .= '<p><a href="' . $verification_link . '">' . $verification_link . '</a></p>';
                $mail->Body .= "<p>Si no te registraste en PetDay, por favor ignora este correo.</p>";
                $mail->Body .= "<p>Atentamente,<br>El equipo de PetDay</p>";
                $mail->AltBody = 'Para verificar tu correo, copia y pega este enlace en tu navegador: ' . $verification_link;

                $mail->send();
                $successMessage = '¡Registro completado con éxito! Se ha enviado un enlace de verificación a tu correo electrónico. Por favor, verifica tu bandeja de entrada para activar tu cuenta.';
            } catch (Exception $e) {
                $errors[] = "Error al enviar el correo de verificación: {$mail->ErrorInfo}";
                // Opcional: loggear el error real para depuración
                error_log('PHPMailer Error: ' . $e->getMessage());
            }

        } catch (PDOException $e) {
            $errors[] = 'Error al registrar el usuario. Por favor, inténtalo de nuevo.';
            // Opcional: loggear el error real para depuración
            // error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.1">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="auth-form-section">
            <div class="container">
                <div class="auth-form-container">
                    <h2>Crea tu cuenta en PetDay</h2>
                    <p>Únete a nuestra comunidad y empieza a gestionar la vida de tus mascotas.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>¡Oops!</strong> Por favor, corrige los siguientes errores:
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <?php echo $successMessage; ?>
                        </div>
                    <?php else: ?>
                        <form action="register.php" method="POST" class="auth-form" novalidate>
                            <div class="form-group">
                                <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                <input type="text" id="nombre_completo" name="nombre_completo" class="form-control" required value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                                <small class="form-text text-muted">Mínimo 6 caracteres.</small>
                            </div>
                            <div class="form-group">
                                <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-large w-100">Crear Cuenta</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="auth-form-footer">
                        ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>.
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
