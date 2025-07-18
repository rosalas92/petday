<?php
/**
 * PetDay - Script para Eliminar Registro Médico
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

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$recordId) {
    header('Location: ../pets/manage_pets.php?status=error');
    exit;
}

// Obtener el registro médico para saber a qué perfil de mascota volver
$record = getMedicalRecordById($recordId);
if (!$record || !isUserPetOwner($userId, $record['id_mascota'])) {
    header('Location: ../pets/manage_pets.php?status=error');
    exit;
}

if (deleteMedicalRecord($recordId, $userId)) {
    header('Location: ../pets/pet_profile.php?id=' . $record['id_mascota'] . '&status=medical_record_deleted');
    exit;
} else {
    header('Location: ../pets/pet_profile.php?id=' . $record['id_mascota'] . '&status=error');
    exit;
}
?>
