<?php
/**
 * PetDay - Solicitar Restablecimiento de Contraseña
 */

session_start();
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

$message = '';
$messageType = '';

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
            executeStatement('UPDATE usuarios SET reset_token = ?, reset_token_expires_at = ? WHERE id_usuario = ?', [$token, $expires, $user['id_usuario']]);

            // Enviar correo electrónico con el enlace de restablecimiento
            require '../../vendor/autoload.php'; // Incluir el autoloader de Composer

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                // Configuración del servidor SMTP (usar las mismas que para el registro)
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'tu_correo@gmail.com'; // Reemplaza con tu dirección de correo
                $mail->Password = 'tu_contraseña_de_aplicacion'; // Reemplaza con tu contraseña de aplicación o normal
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Destinatarios
                $mail->setFrom('no-reply@petday.com', 'PetDay');
                $mail->addAddress($email, $user['nombre_completo']);

                // Contenido del correo
                $mail->isHTML(false);
                $mail->Subject = "Restablecer tu contraseña de PetDay";
                $reset_link = "http://localhost/petday/php/auth/reset_password.php?token=" . $token;
                $mail->Body = "Hola " . htmlspecialchars($user['nombre_completo']) . ",\n\n";
                $mail->Body .= "Has solicitado restablecer tu contraseña en PetDay. Haz clic en el siguiente enlace para continuar:\n";
                $mail->Body .= $reset_link . "\n\n";
                $mail->Body .= "Este enlace expirará en 1 hora. Si no solicitaste esto, por favor ignora este correo.\n\n";
                $mail->Body .= "Atentamente,\nEl equipo de PetDay";

                $mail->send();
                $message = 'Se ha enviado un enlace de restablecimiento de contraseña a tu correo electrónico. Por favor, revisa tu bandeja de entrada.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Error al enviar el correo de restablecimiento: {$mail->ErrorInfo}";
                $messageType = 'danger';
                // error_log($e->getMessage());
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <a href="../../index.php" class="logo">
                    <span class="logo-icon">🐾</span>
                    <h1>PetDay</h1>
                </a>
                <nav class="main-nav">
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-outline">Iniciar Sesión</a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

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

    <footer class="main-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> PetDay. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>