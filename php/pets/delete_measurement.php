<?php
session_start();
require_once __DIR__ . '/../../config/database_config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$measurementId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$measurementId) {
    header('Location: pet_profile.php?status=error&message=ID de medida no proporcionado.');
    exit;
}

try {
    $pdo = getConnection();
    
    // Obtener el ID de la mascota asociada a esta medida para la redirección
    $stmt = $pdo->prepare("SELECT id_mascota FROM medidas WHERE id_medida = :measurement_id");
    $stmt->execute(['measurement_id' => $measurementId]);
    $petId = $stmt->fetchColumn();

    if (!$petId) {
        header('Location: pet_profile.php?status=error&message=Medida no encontrada.');
        exit;
    }

    // Verificar que el usuario es propietario de la mascota
    if (!isUserPetOwner($_SESSION['user_id'], $petId)) {
        header('Location: pet_profile.php?id=' . $petId . '&status=error&message=Acceso denegado.');
        exit;
    }

    // Eliminar la medida
    $stmt = $pdo->prepare("DELETE FROM medidas WHERE id_medida = :measurement_id");
    $stmt->execute(['measurement_id' => $measurementId]);

    header('Location: pet_profile.php?id=' . $petId . '&status=success&message=Medida eliminada correctamente.');
    exit;

} catch (PDOException $e) {
    error_log("Error al eliminar medida: " . $e->getMessage());
    header('Location: pet_profile.php?id=' . $petId . '&status=error&message=Error al eliminar la medida.');
    exit;
}
?>