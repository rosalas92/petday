<?php
/**
 * PetDay - Eliminar Archivo Adjunto de Registro Médico
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database_config.php';
require_once __DIR__ . '/../includes/functions.php';

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

$record = getMedicalRecordById($recordId);

if (!$record || !isUserPetOwner($userId, $record['id_mascota'])) {
    header('Location: ../pets/manage_pets.php?status=error');
    exit;
}

if (deleteMedicalRecordAttachment($recordId)) {
    header('Location: ../pets/pet_profile.php?id=' . $record['id_mascota'] . '&status=attachment_deleted');
} else {
    header('Location: ../pets/pet_profile.php?id=' . $record['id_mascota'] . '&status=error');
}
exit;
?>