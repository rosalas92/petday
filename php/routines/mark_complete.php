<?php
/**
 * PetDay - Script para Marcar Rutina como Completada (AJAX)
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $routineId = filter_input(INPUT_POST, 'routine_id', FILTER_VALIDATE_INT);

    if (!$routineId) {
        $response['message'] = 'ID de rutina inválido.';
        echo json_encode($response);
        exit;
    }

    // Opcional: Verificar que la rutina pertenece al usuario
    $routine = getRoutineById($routineId);
    if (!$routine || !isUserPetOwner($_SESSION['user_id'], $routine['id_mascota'])) {
        $response['message'] = 'No tienes permiso para marcar esta rutina.';
        echo json_encode($response);
        exit;
    }

    if (markRoutineComplete($routineId)) {
        $response['success'] = true;
        $response['message'] = 'Rutina marcada como completada.';
    } else {
        $response['message'] = 'Error al marcar la rutina como completada o ya estaba completada hoy.';
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
?>
