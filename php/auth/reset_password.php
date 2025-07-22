<?php
/**
 * PetDay - Restablecer Contraseña
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

$message = '';
$messageType = '';
$showForm = false;
$token = $_GET['token'] ?? '';

// Variables para el header.php
$isLoggedIn = false;
$user = null;

if (empty($token)) {
    $message = 'Token de restablecimiento de contraseña no proporcionado.';
    $messageType = 'danger';
} else {
    // Buscar usuario por token y verificar expiración
    $user = fetchOne('SELECT id_usuario, reset_token_expires_at FROM usuarios WHERE reset_token = ?', [$token]);

    if (!$user) {
        $message = 'Token de restablecimiento de contraseña no válido.';
        $messageType = 'danger';
    } elseif (strtotime($user['reset_token_expires_at']) < time()) {
        $message = 'El token de restablecimiento de contraseña ha expirado. Por favor, solicita uno nuevo.';
        $messageType = 'danger';
    } else {
        $showForm = true; // Mostrar el formulario para establecer nueva contraseña

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['password'] ?? '';
            $confirmPassword = $_POST['password_confirm'] ?? '';

            if (strlen($newPassword) < 6) {
                $message = 'La nueva contraseña debe tener al menos 6 caracteres.';
                $messageType = 'danger';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'Las contraseñas no coinciden.';
                $messageType = 'danger';
            } else {
                // Actualizar contraseña y limpiar token
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                executeStatement('UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id_usuario = ?', [$hashedPassword, $user['id_usuario']]);

                $message = 'Tu contraseña ha sido restablecida con éxito. Ya puedes iniciar sesión con tu nueva contraseña.';
                $messageType = 'success';
                $showForm = false; // Ocultar el formulario después del éxito
            }
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
                    <h2>Restablecer Contraseña</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($showForm): ?>
                        <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="auth-form" novalidate>
                            <div class="form-group">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                                <small class="form-text text-muted">Mínimo 6 caracteres.</small>
                            </div>
                            <div class="form-group">
                                <label for="password_confirm" class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-large w-100">Restablecer Contraseña</button>
                            </div>
                        </form>
                    <?php endif; ?>

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