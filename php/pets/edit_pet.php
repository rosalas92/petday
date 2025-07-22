<?php
/**
 * PetDay - Página para Editar Mascota
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

// Variables para el header.php
$isLoggedIn = true;

$errors = [];

// Obtener ID de la mascota de la URL
$petId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$petId) {
    header('Location: manage_pets.php');
    exit;
}

// Verificar que la mascota pertenece al usuario
$petData = getPetById($petId, $userId);
if (!$petData) {
    // Si no se encuentra la mascota o no pertenece al usuario, redirigir
    header('Location: manage_pets.php?status=error');
    exit;
}

// Procesar el formulario al recibir un POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y recoger datos
    $updatedPetData = [
        'nombre' => sanitizeInput($_POST['nombre'] ?? ''),
        'especie' => sanitizeInput($_POST['especie'] ?? ''),
        'raza' => sanitizeInput($_POST['raza'] ?? ''),
        'edad' => filter_input(INPUT_POST, 'edad', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 50]]),
        'peso' => filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.1, 'max_range' => 200]]),
        'genero' => sanitizeInput($_POST['genero'] ?? '')
    ];

    // Validar datos
    $errors = validatePetData($updatedPetData);

    // Gestionar subida de nueva foto
    $fotoNombre = $petData['foto']; // Mantener la foto anterior por defecto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/pet_photos/';
        $newFoto = uploadImage($_FILES['foto'], $uploadDir);
        if ($newFoto) {
            // Si se sube una nueva foto, eliminar la anterior si existe
            if ($fotoNombre && file_exists($uploadDir . $fotoNombre)) {
                unlink($uploadDir . $fotoNombre);
            }
            $fotoNombre = $newFoto;
        } else {
            $errors[] = "Error al subir la nueva imagen. Asegúrate de que es un archivo JPG, PNG o GIF y no pesa más de 5MB.";
        }
    }
    $updatedPetData['foto'] = $fotoNombre;

    // Si no hay errores, actualizar mascota
    if (empty($errors)) {
        try {
            if (updatePet($petId, $updatedPetData)) {
                // Redirigir a la página de gestión con mensaje de éxito
                header('Location: manage_pets.php?status=success');
                exit;
            } else {
                // Si no se afectaron filas, puede que no hubiera cambios
                header('Location: manage_pets.php?status=nochange');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Error en la base de datos: " . $e->getMessage();
            logError($e->getMessage(), __FILE__, __LINE__);
        }
    }
    // Si hay errores, los datos del formulario se recargan con los datos POSTeados
    $petData = array_merge($petData, $updatedPetData);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Mascota - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.2">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="edit-pet-form">
            <div class="container">
                <div class="card" style="max-width: 700px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Editar Perfil de <?php echo htmlspecialchars($petData['nombre']); ?></h2>
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

                        <form action="edit_pet.php?id=<?php echo $petId; ?>" method="POST" enctype="multipart/form-data" novalidate>
                            <!-- ... (campos del formulario similares a create_pet.php, pero con values del array $petData) ... -->
                            <div class="form-group">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($petData['nombre']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="especie" class="form-label">Especie</label>
                                <select id="especie" name="especie" class="form-control form-select" required>
                                    <option value="perro" <?php echo ($petData['especie'] === 'perro') ? 'selected' : ''; ?>>Perro</option>
                                    <option value="gato" <?php echo ($petData['especie'] === 'gato') ? 'selected' : ''; ?>>Gato</option>
                                    <option value="pajaro" <?php echo ($petData['especie'] === 'pajaro') ? 'selected' : ''; ?>>Pájaro</option>
                                    <option value="otro" <?php echo ($petData['especie'] === 'otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="raza" class="form-label">Raza</label>
                                <input type="text" id="raza" name="raza" class="form-control" value="<?php echo htmlspecialchars($petData['raza']); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Género</label>
                                <div class="radio-group">
                                    <label class="radio-item"><input type="radio" name="genero" value="macho" required <?php echo ($petData['genero'] === 'macho') ? 'checked' : ''; ?>> Macho</label>
                                    <label class="radio-item"><input type="radio" name="genero" value="hembra" required <?php echo ($petData['genero'] === 'hembra') ? 'checked' : ''; ?>> Hembra</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edad" class="form-label">Edad (años)</label>
                                <input type="number" id="edad" name="edad" class="form-control" min="0" max="50" value="<?php echo htmlspecialchars($petData['edad']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="peso" class="form-label">Peso (kg)</label>
                                <input type="number" step="0.1" id="peso" name="peso" class="form-control" min="0.1" max="200" value="<?php echo htmlspecialchars($petData['peso']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="foto" class="form-label">Cambiar Foto</label>
                                <?php if ($petData['foto']): ?>
                                    <img src="../../uploads/pet_photos/<?php echo htmlspecialchars($petData['foto']); ?>" alt="Foto actual" style="max-width: 100px; display: block; margin-bottom: 10px;">
                                <?php endif; ?>
                                <input type="file" id="foto" name="foto" class="form-control" accept="image/png, image/jpeg, image/gif">
                                <small class="text-muted">Sube una nueva imagen solo si quieres reemplazar la actual.</small>
                            </div>

                            <div class="form-group text-right">
                                <a href="manage_pets.php" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>