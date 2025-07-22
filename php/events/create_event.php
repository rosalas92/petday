<?php
/**
 * PetDay - Página para Crear Nuevo Evento
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
$userPets = getUserPets($userId);
$errors = [];
$eventData = [];

// Variables para el header.php
$isLoggedIn = true;

$selectedPetId = filter_input(INPUT_GET, 'pet_id', FILTER_VALIDATE_INT);
if ($selectedPetId) {
    $eventData['id_mascota'] = $selectedPetId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventData = [
        'id_mascota' => filter_input(INPUT_POST, 'id_mascota', FILTER_VALIDATE_INT),
        'titulo' => sanitizeInput($_POST['titulo'] ?? ''),
        'tipo_evento' => sanitizeInput($_POST['tipo_evento'] ?? ''),
        'fecha_evento' => sanitizeInput($_POST['fecha_evento'] ?? ''),
        'descripcion' => sanitizeInput($_POST['descripcion'] ?? '')
    ];

    $errors = validateEventData($eventData);
    if (empty($eventData['id_mascota']) || !isUserPetOwner($userId, $eventData['id_mascota'])) {
        $errors[] = "Debes seleccionar una de tus mascotas.";
    }

    if (empty($errors)) {
        try {
            $eventId = createEvent($eventData);
            if ($eventId) {
                header('Location: ../pets/pet_profile.php?id=' . $eventData['id_mascota'] . '&status=event_success');
                exit;
            } else {
                $errors[] = "Error al guardar el evento.";
            }
        } catch (Exception $e) {
            $errors[] = "Error en la base de datos: " . $e->getMessage();
            logError($e->getMessage(), __FILE__, __LINE__);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Evento - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.3">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    <main class="main-content">
        <section class="create-event-form">
            <div class="container">
                <div class="card" style="max-width: 700px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Crear Nuevo Evento</h2>
                        <p class="card-text">Añade una cita o recordatorio importante.</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="create_event.php" method="POST" novalidate>
                            <div class="form-group">
                                <label for="id_mascota" class="form-label">Mascota</label>
                                <select id="id_mascota" name="id_mascota" class="form-control form-select" required>
                                    <option value="">Selecciona una mascota</option>
                                    <?php foreach ($userPets as $pet): ?>
                                        <option value="<?php echo $pet['id_mascota']; ?>" <?php echo ($eventData['id_mascota'] ?? '') == $pet['id_mascota'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pet['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="titulo" class="form-label">Título del Evento</label>
                                <input type="text" id="titulo" name="titulo" class="form-control" value="<?php echo htmlspecialchars($eventData['titulo'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="tipo_evento" class="form-label">Tipo de Evento</label>
                                <select id="tipo_evento" name="tipo_evento" class="form-control form-select" required>
                                    <option value="">Selecciona un tipo</option>
                                    <option value="vacuna" <?php echo ($eventData['tipo_evento'] ?? '') === 'vacuna' ? 'selected' : ''; ?>>Vacuna</option>
                                    <option value="veterinario" <?php echo ($eventData['tipo_evento'] ?? '') === 'veterinario' ? 'selected' : ''; ?>>Visita al Veterinario</option>
                                    <option value="baño" <?php echo ($eventData['tipo_evento'] ?? '') === 'baño' ? 'selected' : ''; ?>>Baño</option>
                                    <option value="corte_uñas" <?php echo ($eventData['tipo_evento'] ?? '') === 'corte_uñas' ? 'selected' : ''; ?>>Corte de Uñas</option>
                                    <option value="desparasitacion" <?php echo ($eventData['tipo_evento'] ?? '') === 'desparasitacion' ? 'selected' : ''; ?>>Desparasitación</option>
                                    <option value="revision" <?php echo ($eventData['tipo_evento'] ?? '') === 'revision' ? 'selected' : ''; ?>>Revisión</option>
                                    <option value="otro" <?php echo ($eventData['tipo_evento'] ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="fecha_evento" class="form-label">Fecha y Hora</label>
                                <input type="datetime-local" id="fecha_evento" name="fecha_evento" class="form-control" value="<?php echo htmlspecialchars($eventData['fecha_evento'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="descripcion" class="form-label">Descripción (opcional)</label>
                                <textarea id="descripcion" name="descripcion" class="form-control form-textarea"><?php echo htmlspecialchars($eventData['descripcion'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group text-right">
                                <a href="../pets/pet_profile.php?id=<?php echo $selectedPetId ?? ''; ?>" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Evento</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
