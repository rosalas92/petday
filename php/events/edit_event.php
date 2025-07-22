<?php
/**
 * PetDay - Página para Editar Evento
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
$user = getUserById($userId);
$errors = [];

// Variables para el header.php
$isLoggedIn = true;

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$eventId) {
    header('Location: ../pets/manage_pets.php');
    exit;
}

$eventData = getEventById($eventId);
if (!$eventData || !isUserPetOwner($userId, $eventData['id_mascota'])) {
    header('Location: ../pets/manage_pets.php?status=error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedEventData = [
        'titulo' => sanitizeInput($_POST['titulo'] ?? ''),
        'tipo_evento' => sanitizeInput($_POST['tipo_evento'] ?? ''),
        'fecha_evento' => sanitizeInput($_POST['fecha_evento'] ?? ''),
        'descripcion' => sanitizeInput($_POST['descripcion'] ?? '')
    ];

    $errors = validateEventData($updatedEventData);

    if (empty($errors)) {
        try {
            if (updateEvent($eventId, $updatedEventData)) {
                header('Location: ../pets/pet_profile.php?id=' . $eventData['id_mascota'] . '&status=event_updated');
                exit;
            } else {
                header('Location: ../pets/pet_profile.php?id=' . $eventData['id_mascota'] . '&status=nochange');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Error en la base de datos: " . $e->getMessage();
            logError($e->getMessage(), __FILE__, __LINE__);
        }
    }
    $eventData = array_merge($eventData, $updatedEventData);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Evento - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.3">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    <main class="main-content">
        <section class="edit-event-form">
            <div class="container">
                <div class="card" style="max-width: 700px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Editar Evento</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="edit_event.php?id=<?php echo $eventId; ?>" method="POST" novalidate>
                            <div class="form-group">
                                <label for="titulo" class="form-label">Título del Evento</label>
                                <input type="text" id="titulo" name="titulo" class="form-control" value="<?php echo htmlspecialchars($eventData['titulo']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="tipo_evento" class="form-label">Tipo de Evento</label>
                                <select id="tipo_evento" name="tipo_evento" class="form-control form-select" required>
                                    <option value="vacuna" <?php echo ($eventData['tipo_evento'] === 'vacuna') ? 'selected' : ''; ?>>Vacuna</option>
                                    <option value="veterinario" <?php echo ($eventData['tipo_evento'] === 'veterinario') ? 'selected' : ''; ?>>Visita al Veterinario</option>
                                    <option value="baño" <?php echo ($eventData['tipo_evento'] === 'baño') ? 'selected' : ''; ?>>Baño</option>
                                    <option value="corte_uñas" <?php echo ($eventData['tipo_evento'] === 'corte_uñas') ? 'selected' : ''; ?>>Corte de Uñas</option>
                                    <option value="desparasitacion" <?php echo ($eventData['tipo_evento'] === 'desparasitacion') ? 'selected' : ''; ?>>Desparasitación</option>
                                    <option value="revision" <?php echo ($eventData['tipo_evento'] === 'revision') ? 'selected' : ''; ?>>Revisión</option>
                                    <option value="otro" <?php echo ($eventData['tipo_evento'] === 'otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="fecha_evento" class="form-label">Fecha y Hora</label>
                                <input type="datetime-local" id="fecha_evento" name="fecha_evento" class="form-control" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($eventData['fecha_evento']))); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="descripcion" class="form-label">Descripción (opcional)</label>
                                <textarea id="descripcion" name="descripcion" class="form-control form-textarea"><?php echo htmlspecialchars($eventData['descripcion']); ?></textarea>
                            </div>

                            <div class="form-group text-right">
                                <a href="../pets/pet_profile.php?id=<?php echo $eventData['id_mascota']; ?>" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
