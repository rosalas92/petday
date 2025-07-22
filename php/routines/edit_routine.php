<?php
/**
 * PetDay - Página para Editar Rutina
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

// Variables para el header.php
$isLoggedIn = true;

$errors = [];

$routineId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$routineId) {
    header('Location: ../pets/manage_pets.php');
    exit;
}

$routineData = getRoutineById($routineId);
if (!$routineData || !isUserPetOwner($userId, $routineData['id_mascota'])) {
    header('Location: ../pets/manage_pets.php?status=error');
    exit;
}

// Convertir los días de la semana a un array para el formulario
$routineData['dias_semana'] = explode(',', $routineData['dias_semana']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedRoutineData = [
        'tipo_actividad' => sanitizeInput($_POST['tipo_actividad'] ?? ''),
        'nombre_actividad' => sanitizeInput($_POST['nombre_actividad'] ?? ''),
        'descripcion' => sanitizeInput($_POST['descripcion'] ?? ''),
        'hora_programada' => sanitizeInput($_POST['hora_programada'] ?? ''),
        'dias_semana' => $_POST['dias_semana'] ?? []
    ];

    $errors = validateRoutineData($updatedRoutineData);

    if (empty($errors)) {
        try {
            if (updateRoutine($routineId, $updatedRoutineData)) {
                header('Location: ../pets/pet_profile.php?id=' . $routineData['id_mascota'] . '&status=routine_updated');
                exit;
            } else {
                header('Location: ../pets/pet_profile.php?id=' . $routineData['id_mascota'] . '&status=nochange');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Error en la base de datos: " . $e->getMessage();
            logError($e->getMessage(), __FILE__, __LINE__);
        }
    }
    $routineData = array_merge($routineData, $updatedRoutineData);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Rutina - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.3">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    <main class="main-content">
        <section class="edit-routine-form">
            <div class="container">
                <div class="card" style="max-width: 700px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Editar Rutina</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="edit_routine.php?id=<?php echo $routineId; ?>" method="POST" novalidate>
                            <div class="form-group">
                                <label for="nombre_actividad" class="form-label">Nombre de la Actividad</label>
                                <input type="text" id="nombre_actividad" name="nombre_actividad" class="form-control" value="<?php echo htmlspecialchars($routineData['nombre_actividad']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="tipo_actividad" class="form-label">Tipo de Actividad</label>
                                <select id="tipo_actividad" name="tipo_actividad" class="form-control form-select" required>
                                    <option value="comida" <?php echo ($routineData['tipo_actividad'] === 'comida') ? 'selected' : ''; ?>>Comida</option>
                                    <option value="paseo" <?php echo ($routineData['tipo_actividad'] === 'paseo') ? 'selected' : ''; ?>>Paseo</option>
                                    <option value="juego" <?php echo ($routineData['tipo_actividad'] === 'juego') ? 'selected' : ''; ?>>Juego</option>
                                    <option value="medicacion" <?php echo ($routineData['tipo_actividad'] === 'medicacion') ? 'selected' : ''; ?>>Medicación</option>
                                    <option value="higiene" <?php echo ($routineData['tipo_actividad'] === 'higiene') ? 'selected' : ''; ?>>Higiene</option>
                                    <option value="entrenamiento" <?php echo ($routineData['tipo_actividad'] === 'entrenamiento') ? 'selected' : ''; ?>>Entrenamiento</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="hora_programada" class="form-label">Hora Programada</label>
                                <input type="time" id="hora_programada" name="hora_programada" class="form-control" value="<?php echo htmlspecialchars($routineData['hora_programada']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Días de la Semana</label>
                                <button type="button" id="selectAllDaysBtn" class="btn btn-sm btn-outline" style="margin-left: 10px;">Toda la semana</button>
                                <div class="checkbox-group" id="daysOfWeekCheckboxes">
                                    <?php 
                                    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                                    foreach ($dias as $dia): 
                                        $checked = in_array($dia, $routineData['dias_semana']) ? 'checked' : '';
                                    ?>
                                        <label class="checkbox-item">
                                            <input type="checkbox" name="dias_semana[]" value="<?php echo $dia; ?>" <?php echo $checked; ?>> <?php echo ucfirst($dia); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="descripcion" class="form-label">Descripción (opcional)</label>
                                <textarea id="descripcion" name="descripcion" class="form-control form-textarea"><?php echo htmlspecialchars($routineData['descripcion']); ?></textarea>
                            </div>

                            <div class="form-group text-right">
                                <a href="../pets/pet_profile.php?id=<?php echo $routineData['id_mascota']; ?>" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
