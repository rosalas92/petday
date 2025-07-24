<?php
session_start();
require_once __DIR__ . '/../../config/database_config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '' ];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    exit;
}

$routineId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$routineId) {
    $response['message'] = 'ID de rutina inválido.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = getConnection();
    // Obtener la última marca de completado para esta rutina
    $stmt = $pdo->prepare("SELECT fecha_completado FROM rutinas_completadas WHERE id_rutina = :routine_id ORDER BY fecha_completado DESC LIMIT 1");
    $stmt->execute(['routine_id' => $routineId]);
    $lastCompletion = $stmt->fetchColumn();

    // Obtener los días de la semana y la hora programada de la rutina
    $stmt = $pdo->prepare("SELECT dias_semana, hora_programada FROM rutinas WHERE id_rutina = :routine_id");
    $stmt->execute(['routine_id' => $routineId]);
    $routineDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$routineDetails) {
        $response['message'] = 'Rutina no encontrada.';
        echo json_encode($response);
        exit;
    }

    $diasSemana = explode(',', $routineDetails['dias_semana']);
    $horaProgramada = $routineDetails['hora_programada'];

    $today = new DateTime();
    $today->setTime(0, 0, 0); // Resetear la hora para comparación de fechas

    $status = 'pending'; // Estado por defecto

    // Verificar si la rutina está programada para hoy
    $dayOfWeekToday = strtolower($today->format('l'));
    $dayInSpanishToday = [
        'monday' => 'lunes',
        'tuesday' => 'martes',
        'wednesday' => 'miercoles',
        'thursday' => 'jueves',
        'friday' => 'viernes',
        'saturday' => 'sabado',
        'sunday' => 'domingo'
    ][$dayOfWeekToday];

    if (in_array($dayInSpanishToday, $diasSemana)) {
        // La rutina está programada para hoy
        if ($lastCompletion) {
            $lastCompletionDate = new DateTime($lastCompletion);
            $lastCompletionDate->setTime(0, 0, 0); // Resetear la hora para comparación de fechas

            if ($lastCompletionDate == $today) {
                $status = 'completed';
            } else {
                // Si la última completación no fue hoy, está pendiente o vencida
                $routineTime = new DateTime($horaProgramada);
                $currentTime = new DateTime();

                if ($currentTime > $routineTime) {
                    $status = 'overdue';
                } else {
                    $status = 'pending';
                }
            }
        } else {
            // Nunca se ha completado, verificar si ya pasó la hora de hoy
            $routineTime = new DateTime($horaProgramada);
            $currentTime = new DateTime();

            if ($currentTime > $routineTime) {
                $status = 'overdue';
            } else {
                $status = 'pending';
            }
        }
    } else {
        // La rutina no está programada para hoy, su estado es 'no_applicable'
        $status = 'not_applicable';
    }

    $response['success'] = true;
    $response['status'] = $status;

} catch (PDOException $e) {
    error_log("Error al obtener estado de rutina: " . $e->getMessage());
    $response['message'] = 'Error de base de datos.';
} catch (Exception $e) {
    error_log("Error general al obtener estado de rutina: " . $e->getMessage());
    $response['message'] = 'Error interno del servidor.';
}

echo json_encode($response);
?>