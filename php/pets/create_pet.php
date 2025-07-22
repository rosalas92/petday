<?php
/**
 * PetDay - Página para Crear Nueva Mascota
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
$user = getUserById($userId);
$errors = [];
$petData = [];

// Variables para el header.php
$isLoggedIn = true;

// Procesar el formulario al recibir un POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y recoger datos
    $petData = [
        'nombre' => sanitizeInput($_POST['nombre'] ?? ''),
        'especie' => sanitizeInput($_POST['especie'] ?? ''),
        'raza' => sanitizeInput($_POST['raza'] ?? ''),
        'edad' => filter_input(INPUT_POST, 'edad', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 50]]),
        'peso' => filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.1, 'max_range' => 200]]),
        'genero' => sanitizeInput($_POST['genero'] ?? ''),
        'id_usuario' => $userId
    ];

    // Validar datos
    $errors = validatePetData($petData);

    // Gestionar subida de foto
    $fotoNombre = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/pet_photos/';
        $fotoNombre = uploadImage($_FILES['foto'], $uploadDir);
        if ($fotoNombre === false) {
            $errors[] = "Error al subir la imagen. Asegúrate de que es un archivo JPG, PNG o GIF y no pesa más de 5MB.";
        }
    } 
    $petData['foto'] = $fotoNombre;

    // Si no hay errores, crear mascota
    if (empty($errors)) {
        try {
            $petId = createPet($petData);
            if ($petId) {
                // Redirigir a la página de gestión con mensaje de éxito
                header('Location: manage_pets.php?status=success');
                exit;
            } else {
                $errors[] = "Hubo un error al guardar la mascota en la base de datos.";
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
    <title>Agregar Nueva Mascota - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.2">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="create-pet-form">
            <div class="container">
                <div class="card" style="max-width: 700px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Agregar Nueva Mascota</h2>
                        <p class="card-text">Completa el perfil de tu nuevo compañero.</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <strong>¡Ups! Hubo algunos errores:</strong>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="create_pet.php" method="POST" enctype="multipart/form-data" novalidate>
                            <div class="form-group">
                                <label for="nombre" class="form-label">Nombre de la mascota</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($petData['nombre'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="especie" class="form-label">Especie</label>
                                <select id="especie" name="especie" class="form-control form-select" required>
                                    <option value="" disabled <?php echo empty($petData['especie']) ? 'selected' : ''; ?>>Selecciona una especie</option>
                                    <option value="perro" <?php echo ($petData['especie'] ?? '') === 'perro' ? 'selected' : ''; ?>>Perro</option>
                                    <option value="gato" <?php echo ($petData['especie'] ?? '') === 'gato' ? 'selected' : ''; ?>>Gato</option>
                                    <option value="pajaro" <?php echo ($petData['especie'] ?? '') === 'pajaro' ? 'selected' : ''; ?>>Pájaro</option>
                                    <option value="otro" <?php echo ($petData['especie'] ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="raza" class="form-label">Raza (opcional)</label>
                                <input type="text" id="raza" name="raza" class="form-control" value="<?php echo htmlspecialchars($petData['raza'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Género</label>
                                <div class="radio-group">
                                    <label class="radio-item"><input type="radio" name="genero" value="macho" required <?php echo ($petData['genero'] ?? '') === 'macho' ? 'checked' : ''; ?>> Macho</label>
                                    <label class="radio-item"><input type="radio" name="genero" value="hembra" required <?php echo ($petData['genero'] ?? '') === 'hembra' ? 'checked' : ''; ?>> Hembra</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edad" class="form-label">Edad (años, opcional)</label>
                                <input type="number" id="edad" name="edad" class="form-control" min="0" max="50" value="<?php echo htmlspecialchars($petData['edad'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="peso" class="form-label">Peso (kg, opcional)</label>
                                <input type="number" step="0.1" id="peso" name="peso" class="form-control" min="0.1" max="200" value="<?php echo htmlspecialchars($petData['peso'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="foto" class="form-label">Foto de la mascota (opcional)</label>
                                <input type="file" id="foto" name="foto" class="form-control" accept="image/png, image/jpeg, image/gif">
                                <small class="text-muted">La imagen debe ser JPG, PNG o GIF y no pesar más de 5MB.</small>
                            </div>

                            <div class="form-group text-right">
                                <a href="manage_pets.php" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Mascota</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>