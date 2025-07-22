<?php
/**
 * PetDay - Página para Añadir Medida de Mascota
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

$petId = filter_input(INPUT_GET, 'pet_id', FILTER_VALIDATE_INT);
if (!$petId || !isUserPetOwner($userId, $petId)) {
    header('Location: manage_pets.php?status=error');
    exit;
}

$pet = getPetById($petId, $userId);
$errors = [];
$measurementData = [
    'id_mascota' => $petId,
    'fecha_medicion' => date('Y-m-d')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $measurementData = [
        'id_mascota' => $petId,
        'peso' => filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT),
        'altura' => filter_input(INPUT_POST, 'altura', FILTER_VALIDATE_FLOAT),
        'longitud' => filter_input(INPUT_POST, 'longitud', FILTER_VALIDATE_FLOAT),
        'circunferencia_cuello' => filter_input(INPUT_POST, 'circunferencia_cuello', FILTER_VALIDATE_FLOAT),
        'fecha_medicion' => sanitizeInput($_POST['fecha_medicion'] ?? ''),
        'notas' => sanitizeInput($_POST['notas'] ?? '')
    ];

    // Validaciones básicas
    if (empty($measurementData['fecha_medicion'])) {
        $errors[] = "La fecha de medición es obligatoria.";
    }
    if (empty($measurementData['peso']) && empty($measurementData['altura']) && empty($measurementData['longitud']) && empty($measurementData['circunferencia_cuello'])) {
        $errors[] = "Debes introducir al menos un valor de medida (peso, altura, etc.).";
    }

    if (empty($errors)) {
        try {
            $measurementId = createPetMeasurement($measurementData);
            if ($measurementId) {
                header('Location: pet_profile.php?id=' . $petId . '&status=measurement_success');
                exit;
            } else {
                $errors[] = "Error al guardar la medida.";
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
    <title>Añadir Medida para <?php echo htmlspecialchars($pet['nombre']); ?> - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.7">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="add-measurement-form">
            <div class="container">
                <div class="card" style="max-width: 700px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Añadir Medida para <?php echo htmlspecialchars($pet['nombre']); ?></h2>
                        <p class="card-text">Registra el peso, altura u otras medidas de tu mascota.</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="add_measurement.php?pet_id=<?php echo $petId; ?>" method="POST" novalidate>
                            <div class="form-group">
                                <label for="fecha_medicion" class="form-label">Fecha de Medición</label>
                                <input type="date" id="fecha_medicion" name="fecha_medicion" class="form-control" value="<?php echo htmlspecialchars($measurementData['fecha_medicion']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="peso" class="form-label">Peso (kg)</label>
                                <input type="number" step="0.01" id="peso" name="peso" class="form-control" value="<?php echo htmlspecialchars($measurementData['peso'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="altura" class="form-label">Altura (cm)</label>
                                <input type="number" step="0.1" id="altura" name="altura" class="form-control" value="<?php echo htmlspecialchars($measurementData['altura'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="longitud" class="form-label">Longitud (cm)</label>
                                <input type="number" step="0.1" id="longitud" name="longitud" class="form-control" value="<?php echo htmlspecialchars($measurementData['longitud'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="circunferencia_cuello" class="form-label">Circunferencia del Cuello (cm)</label>
                                <input type="number" step="0.1" id="circunferencia_cuello" name="circunferencia_cuello" class="form-control" value="<?php echo htmlspecialchars($measurementData['circunferencia_cuello'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="notas" class="form-label">Notas (opcional)</label>
                                <textarea id="notas" name="notas" class="form-control form-textarea"><?php echo htmlspecialchars($measurementData['notas'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group text-right">
                                <a href="pet_profile.php?id=<?php echo $petId; ?>" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Medida</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> PetDay. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>
