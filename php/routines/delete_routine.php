<?php
/**
 * PetDay - Script para Eliminar Rutina
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

$routineId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$routineId) {
    header('Location: ../pets/manage_pets.php?status=error');
    exit;
}

// Antes de eliminar, obtener la rutina para saber a qué perfil de mascota volver
$routine = getRoutineById($routineId);
if (!$routine || !isUserPetOwner($userId, $routine['id_mascota'])) {
    header('Location: ../pets/manage_pets.php?status=error');
    exit;
}

if (deleteRoutine($routineId)) {
    header('Location: ../pets/pet_profile.php?id=' . $routine['id_mascota'] . '&status=routine_deleted');
    exit;
} else {
    header('Location: ../pets/pet_profile.php?id=' . $routine['id_mascota'] . '&status=error');
    exit;
}
?>
