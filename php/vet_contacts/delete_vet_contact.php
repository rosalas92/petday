<?php
/**
 * PetDay - Script para Eliminar Contacto Veterinario
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

$contactId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$contactId) {
    header('Location: manage_vet_contacts.php?status=error');
    exit;
}

// Verificar que el contacto pertenece al usuario antes de intentar eliminar
$contact = getVetContactById($contactId, $userId);
if (!$contact) {
    header('Location: manage_vet_contacts.php?status=error');
    exit;
}

if (deleteVetContact($contactId, $userId)) {
    header('Location: manage_vet_contacts.php?status=deleted');
    exit;
} else {
    header('Location: manage_vet_contacts.php?status=error');
    exit;
}
?>
