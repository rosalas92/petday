<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database_config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);
$isLoggedIn = true;

$measurementId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$measurementId) {
    header('Location: pet_profile.php?status=error&message=ID de medida no proporcionado.');
    exit;
}

$measurement = getMeasurementById($measurementId);

if (!$measurement) {
    header('Location: pet_profile.php?status=error&message=Medida no encontrada.');
    exit;
}

// Verificar que el usuario es propietario de la mascota asociada a la medida
if (!isUserPetOwner($userId, $measurement['id_mascota'])) {
    header('Location: pet_profile.php?id=' . $measurement['id_mascota'] . '&status=error&message=Acceso denegado.');
    exit;
}

$petId = $measurement['id_mascota'];
$pet = getPetById($petId, $userId);

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fechaMedicion = filter_input(INPUT_POST, 'fecha_medicion', FILTER_SANITIZE_STRING);
    $peso = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $altura = filter_input(INPUT_POST, 'altura', FILTER_VALIDATE_FLOAT);
    $longitud = filter_input(INPUT_POST, 'longitud', FILTER_VALIDATE_FLOAT);
    $circunferenciaCuello = filter_input(INPUT_POST, 'circunferencia_cuello', FILTER_VALIDATE_FLOAT);
    $notas = filter_input(INPUT_POST, 'notas', FILTER_SANITIZE_STRING);

    if (empty($fechaMedicion)) {
        $errors[] = 'La fecha de medición es obligatoria.';
    }

    if (empty($errors)) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE medidas SET fecha_medicion = :fecha_medicion, peso = :peso, altura = :altura, longitud = :longitud, circunferencia_cuello = :circunferencia_cuello, notas = :notas WHERE id_medida = :id_medida");
            $stmt->execute([
                'fecha_medicion' => $fechaMedicion,
                'peso' => $peso,
                'altura' => $altura,
                'longitud' => $longitud,
                'circunferencia_cuello' => $circunferenciaCuello,
                'notas' => $notas,
                'id_medida' => $measurementId
            ]);

            $successMessage = 'Medida actualizada correctamente.';
            // Recargar la medida para mostrar los datos actualizados
            $measurement = getMeasurementById($measurementId);

        } catch (PDOException $e) {
            error_log("Error al actualizar medida: " . $e->getMessage());
            $errors[] = 'Error al actualizar la medida en la base de datos.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Medida - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.12">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="container">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Editar Medida para <?php echo htmlspecialchars($pet['nombre']); ?></h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <p><?php echo htmlspecialchars($successMessage); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="edit_measurement.php?id=<?php echo $measurementId; ?>" method="POST">
                        <div class="form-group">
                            <label for="fecha_medicion" class="form-label">Fecha de Medición:</label>
                            <input type="date" id="fecha_medicion" name="fecha_medicion" class="form-control" value="<?php echo htmlspecialchars($measurement['fecha_medicion']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="peso" class="form-label">Peso (kg):</label>
                            <input type="number" step="0.01" id="peso" name="peso" class="form-control" value="<?php echo htmlspecialchars($measurement['peso'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="altura" class="form-label">Altura (cm):</label>
                            <input type="number" step="0.1" id="altura" name="altura" class="form-control" value="<?php echo htmlspecialchars($measurement['altura'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="longitud" class="form-label">Longitud (cm):</label>
                            <input type="number" step="0.1" id="longitud" name="longitud" class="form-control" value="<?php echo htmlspecialchars($measurement['longitud'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="circunferencia_cuello" class="form-label">Circunferencia del Cuello (cm):</label>
                            <input type="number" step="0.1" id="circunferencia_cuello" name="circunferencia_cuello" class="form-control" value="<?php echo htmlspecialchars($measurement['circunferencia_cuello'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="notas" class="form-label">Notas:</label>
                            <textarea id="notas" name="notas" class="form-control form-textarea"><?php echo htmlspecialchars($measurement['notas'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            <a href="pet_profile.php?id=<?php echo $petId; ?>" class="btn btn-outline">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>