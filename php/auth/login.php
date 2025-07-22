<?php
/**
 * PetDay - Sistema de Login
 * Página de inicio de sesión para usuarios
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

// Variables para el header.php
$isLoggedIn = false;
$user = null;

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, ingrese un email válido.';
    } else {
        // Buscar usuario en la base de datos
        $user = fetchOne('SELECT * FROM usuarios WHERE email = ?', [$email]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_verified'] == 1) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_name'] = $user['nombre_completo'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['login_time'] = time();
                
                // Redirigir al dashboard
                header('Location: ../../index.php');
                exit();
            } else {
                $error = 'Tu cuenta no ha sido verificada. Por favor, revisa tu correo electrónico para el enlace de verificación.';
            }
        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    }
}

// Mensaje si viene de un registro exitoso
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success = '¡Registro completado! Ya puedes iniciar sesión.';
}

// Mostrar mensajes de verificación de correo
if (isset($_SESSION['verification_message'])) {
    $messageType = $_SESSION['verification_message_type'] ?? 'info';
    $message = $_SESSION['verification_message'];
    if ($messageType === 'success') {
        $success = $message;
    } else {
        $error = $message;
    }
    unset($_SESSION['verification_message']);
    unset($_SESSION['verification_message_type']);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PetDay</title>
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
                    <h2>¡Bienvenido de vuelta!</h2>
                    <p>Inicia sesión para continuar gestionando la rutina de tus mascotas.</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" class="auth-form" novalidate>
                        <div class="form-group">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-large w-100">Iniciar Sesión</button>
                        </div>
                    </form>

                    <div class="auth-form-footer">
                        ¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a>.
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>