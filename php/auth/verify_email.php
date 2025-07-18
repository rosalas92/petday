<?php
/**
 * PetDay - Página de Verificación de Correo Electrónico
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

$message = '';
$messageType = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    try {
        // Buscar al usuario con el token proporcionado
        $user = fetchOne('SELECT id_usuario, is_verified FROM usuarios WHERE token = ?', [$token]);

        if ($user) {
            if ($user['is_verified'] == 0) {
                // Marcar al usuario como verificado y limpiar el token
                $updateSql = 'UPDATE usuarios SET is_verified = 1, token = NULL WHERE id_usuario = ?';
                executeQuery($updateSql, [$user['id_usuario']]);

                $message = '¡Tu correo electrónico ha sido verificado con éxito! Ya puedes iniciar sesión.';
                $messageType = 'success';
            } else {
                $message = 'Tu correo electrónico ya ha sido verificado previamente.';
                $messageType = 'info';
            }
        } else {
            $message = 'Token de verificación no válido o expirado.';
            $messageType = 'danger';
        }
    } catch (PDOException $e) {
        $message = 'Error en la base de datos durante la verificación. Por favor, inténtalo de nuevo.';
        $messageType = 'danger';
        logError($e->getMessage(), __FILE__, __LINE__);
    }
} else {
    $message = 'No se proporcionó un token de verificación.';
    $messageType = 'danger';
}

// Redirigir al login con el mensaje
$_SESSION['verification_message'] = $message;
$_SESSION['verification_message_type'] = $messageType;
header('Location: login.php');
exit;
?>