<?php
/**
 * PetDay - Script para Eliminar Documento de Cartilla Sanitaria
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$cartillaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$petId = filter_input(INPUT_GET, 'pet_id', FILTER_VALIDATE_INT);

if (!$cartillaId || !$petId) {
    $_SESSION['error_message'] = "ID de cartilla o mascota no proporcionado.";
    header('Location: ../pets/manage_pets.php');
    exit;
}

// Verificar que el documento pertenece a una mascota del usuario
$record = getCartillaRecordById($cartillaId);
if (!$record || !isUserPetOwner($userId, $record['id_mascota'])) {
    $_SESSION['error_message'] = "Acceso denegado o documento no encontrado.";
    header('Location: ../pets/pet_profile.php?id=' . $petId);
    exit;
}

// Eliminar el archivo físico si existe
$filePath = $record['archivo_path'];
if (file_exists($filePath)) {
    if (!unlink($filePath)) {
        $_SESSION['error_message'] = "Error al eliminar el archivo físico.";
        header('Location: cartilla_sanitaria.php?id=' . $petId);
        exit;
    }
}

// Eliminar el registro de la base de datos
if (deleteCartillaRecord($cartillaId)) {
    $_SESSION['success_message'] = "Documento eliminado exitosamente.";
} else {
    $_SESSION['error_message'] = "Error al eliminar el registro de la base de datos.";
}

header('Location: cartilla_sanitaria.php?id=' . $petId);
exit;
