<?php
/**
 * PetDay - Solicitar Restablecimiento de Contraseña
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

$message = '';
$messageType = '';

// Variables para el header.php
$isLoggedIn = false;
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Por favor, introduce un correo electrónico válido.';
        $messageType = 'danger';
    } else {
        $user = fetchOne('SELECT id_usuario, nombre_completo, email FROM usuarios WHERE email = ?', [$email]);

        if ($user) {
            // Generar token y fecha de expiración
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora

            // Guardar token en la base de datos
            executeQuery('UPDATE usuarios SET reset_token = ?, reset_token_expires_at = ? WHERE id_usuario = ?', [$token, $expires, $user['id_usuario']]);

            // Enviar correo electrónico con el enlace de restablecimiento
            require_once __DIR__ . '/../../vendor/autoload.php'; // Incluir el autoloader de Composer

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
                $mail->addAddress($email, $user['nombre_completo']);

                // Contenido del correo
                $mail->isHTML(true);
                $mail->Subject = "Restablecer tu contraseña de PetDay";
                $reset_link = URL_ADMIN . "/php/auth/reset_password.php?token=" . $token;
                $mail->Body = "<p>Hola " . htmlspecialchars($user['nombre_completo']) . ",</p>";
                $mail->Body .= "<p>Has solicitado restablecer tu contraseña en PetDay. Haz clic en el siguiente enlace para continuar:</p>";
                $mail->Body .= '<p><a href="' . $reset_link . '">' . $reset_link . '</a></p>';
                $mail->Body .= "<p>Este enlace expirará en 1 hora. Si no solicitaste esto, por favor ignora este correo.</p>";
                $mail->Body .= "<p>Atentamente,<br>El equipo de PetDay</p>";
                $mail->AltBody = 'Para restablecer tu contraseña, copia y pega este enlace en tu navegador: ' . $reset_link;

                $mail->send();
                $message = 'Se ha enviado un enlace de restablecimiento de contraseña a tu correo electrónico. Por favor, revisa tu bandeja de entrada.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Error al enviar el correo de restablecimiento: {$mail->ErrorInfo}";
                $messageType = 'danger';
                error_log('PHPMailer Error: ' . $e->getMessage());
            }
        } else {
            $message = 'Si tu correo electrónico está registrado, recibirás un enlace de restablecimiento.';
            $messageType = 'info';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - PetDay</title>
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
                    <h2>¿Olvidaste tu contraseña?</h2>
                    <p>Introduce tu correo electrónico y te enviaremos un enlace para restablecerla.</p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form action="forgot_password.php" method="POST" class="auth-form" novalidate>
                        <div class="form-group">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-large w-100">Enviar Enlace de Restablecimiento</button>
                        </div>
                    </form>

                    <div class="auth-form-footer">
                        <a href="login.php">Volver al inicio de sesión</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>