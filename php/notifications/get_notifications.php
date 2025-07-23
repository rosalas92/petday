<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database_config.php';
require_once __DIR__ . '/../includes/functions.php';

$response = ['success' => false, 'message' => '', 'notifications' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$userPets = getUserPets($userId);

$allNotifications = [];

foreach ($userPets as $pet) {
    $petId = $pet['id_mascota'];

    // Get today's routines for the pet
    $todayRoutines = getTodayRoutines($petId);
    foreach ($todayRoutines as $routine) {
        // Check if routine is already completed today
        if (!isRoutineCompletedToday($routine['id_rutina'])) {
            $allNotifications[] = [
                'type' => 'routine',
                'id' => $routine['id_rutina'],
                'pet_name' => $pet['nombre'],
                'title' => $routine['nombre_actividad'],
                'description' => $routine['descripcion'],
                'time' => $routine['hora_programada'],
                'date' => date('Y-m-d'), // Today's date
                'message' => '¡Es hora de la rutina de ' . $pet['nombre'] . ': ' . $routine['nombre_actividad'] . '!',
                'icon' => getActivityIcon($routine['tipo_actividad'])
            ];
        }
    }

    // Get upcoming events for the pet (e.g., next 7 days)
    $upcomingEvents = getUpcomingEvents($petId, 7); // Get events for the next 7 days
    foreach ($upcomingEvents as $event) {
        // Only add if the event is in the future or today
        $eventDateTime = new DateTime($event['fecha_evento']);
        $now = new DateTime();
        if ($eventDateTime >= $now) {
            $allNotifications[] = [
                'type' => 'event',
                'id' => $event['id_evento'],
                'pet_name' => $pet['nombre'],
                'title' => $event['titulo'],
                'description' => $event['descripcion'],
                'time' => date('H:i', strtotime($event['fecha_evento'])),
                'date' => date('Y-m-d', strtotime($event['fecha_evento'])),
                'message' => '¡Próximo evento para ' . $pet['nombre'] . ': ' . $event['titulo'] . ' el ' . date('d/m/Y H:i', strtotime($event['fecha_evento'])) . '!',
                'icon' => '📅' // Generic event icon
            ];
        }
    }
}

// Sort notifications by date and time
usort($allNotifications, function($a, $b) {
    $dateTimeA = $a['date'] . ' ' . ($a['type'] === 'routine' ? $a['time'] : $a['time']);
    $dateTimeB = $b['date'] . ' ' . ($b['type'] === 'routine' ? $b['time'] : $b['time']);
    return strtotime($dateTimeA) - strtotime($dateTimeB);
});

$response['success'] = true;
$response['notifications'] = $allNotifications;
echo json_encode($response);
?>