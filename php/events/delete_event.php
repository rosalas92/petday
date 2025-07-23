<?php
/**
 * PetDay - Eliminar Evento
 */

session_start();
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_GET['id'])) {
    $eventId = intval($_GET['id']);
    $userId = $_SESSION['user_id'];

    // Verificar que el evento pertenece al usuario
    $event = fetchOne("SELECT id_evento FROM eventos WHERE id_evento = ? AND id_mascota IN (SELECT id_mascota FROM mascotas WHERE id_usuario = ?)", [$eventId, $userId]);

    if ($event) {
        try {
            beginTransaction();
            executeQuery("DELETE FROM eventos WHERE id_evento = ?", [$eventId]);
            commit();
            $_SESSION['message'] = 'Evento eliminado exitosamente.';
            $_SESSION['message_type'] = 'success';
        } catch (Exception $e) {
            rollback();
            error_log("Error al eliminar evento: " . $e->getMessage());
            $_SESSION['message'] = 'Error al eliminar el evento.';
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'Evento no encontrado o no tienes permiso para eliminarlo.';
        $_SESSION['message_type'] = 'error';
    }
} else {
    $_SESSION['message'] = 'ID de evento no especificado.';
    $_SESSION['message_type'] = 'error';
}

header('Location: ../calendar/calendar.php');
exit;
?>