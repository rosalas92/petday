<?php
/**
 * PetDay - Script para Eliminar Mascota
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

// Redirigir si no está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener ID de la mascota de la URL
$petId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$petId) {
    header('Location: manage_pets.php?status=error');
    exit;
}

// Intentar eliminar la mascota
if (deletePet($petId, $userId)) {
    // Éxito: redirigir a la página de gestión con mensaje
    header('Location: manage_pets.php?status=deleted');
    exit;
} else {
    // Error: redirigir con mensaje de error
    header('Location: manage_pets.php?status=error');
    exit;
}
?>