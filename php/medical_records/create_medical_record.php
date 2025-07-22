<?php
/**
 * PetDay - Página para Crear Nuevo Registro Médico
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

// Variables para el header.php
$isLoggedIn = true;

$errors = [];
$recordData = [];

// Pre-seleccionar mascota si se pasa por URL
$selectedPetId = filter_input(INPUT_GET, 'pet_id', FILTER_VALIDATE_INT);
if ($selectedPetId) {
    $recordData['id_mascota'] = $selectedPetId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordData = [
        'id_mascota' => filter_input(INPUT_POST, 'id_mascota', FILTER_VALIDATE_INT),
        'tipo_registro' => sanitizeInput($_POST['tipo_registro'] ?? ''),
        'fecha_registro' => sanitizeInput($_POST['fecha_registro'] ?? ''),
        'titulo' => sanitizeInput($_POST['titulo'] ?? ''),
        'descripcion' => sanitizeInput($_POST['descripcion'] ?? ''),
        'veterinario' => sanitizeInput($_POST['veterinario'] ?? ''),
        'clinica' => sanitizeInput($_POST['clinica'] ?? ''),
        'medicamentos' => sanitizeInput($_POST['medicamentos'] ?? ''),
        'dosis' => sanitizeInput($_POST['dosis'] ?? ''),
        'observaciones' => sanitizeInput($_POST['observaciones'] ?? ''),
        'archivo_adjunto' => null
    ];

    // Validar datos
    $errors = validateMedicalRecordData($recordData);
    if (empty($recordData['id_mascota']) || !isUserPetOwner($userId, $recordData['id_mascota'])) {
        $errors[] = "Debes seleccionar una de tus mascotas.";
    }

    // Manejar subida de archivo
    if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/medical_records/';
        $fileName = uploadDocument($_FILES['archivo_adjunto'], $uploadDir);
        if ($fileName) {
            $recordData['archivo_adjunto'] = $fileName;
        } else {
            $errors[] = "Error al subir el archivo adjunto. Asegúrate de que es una imagen (JPG, PNG, GIF) o PDF y no pesa más de 10MB.";
        }
    }

    if (empty($errors)) {
        try {
            $recordId = createMedicalRecord($recordData);
            if ($recordId) {
                header('Location: ../pets/pet_profile.php?id=' . $recordData['id_mascota'] . '&status=medical_record_success');
                exit;
            } else {
                $errors[] = "Error al guardar el registro médico.";
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
    <title>Nuevo Registro Médico - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.7">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="create-medical-record-form">
            <div class="container">
                <div class="card" style="max-width: 800px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Nuevo Registro Médico</h2>
                        <p class="card-text">Añade un nuevo evento o historial médico para tu mascota.</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="create_medical_record.php" method="POST" enctype="multipart/form-data" novalidate>
                            <div class="form-group">
                                <label for="id_mascota" class="form-label">Mascota</label>
                                <select id="id_mascota" name="id_mascota" class="form-control form-select" required>
                                    <option value="">Selecciona una mascota</option>
                                    <?php foreach ($userPets as $pet): ?>
                                        <option value="<?php echo $pet['id_mascota']; ?>" <?php echo ($recordData['id_mascota'] ?? '') == $pet['id_mascota'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pet['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="tipo_registro" class="form-label">Tipo de Registro</label>
                                <select id="tipo_registro" name="tipo_registro" class="form-control form-select" required>
                                    <option value="">Selecciona un tipo</option>
                                    <option value="vacuna" <?php echo ($recordData['tipo_registro'] ?? '') === 'vacuna' ? 'selected' : ''; ?>>Vacuna</option>
                                    <option value="enfermedad" <?php echo ($recordData['tipo_registro'] ?? '') === 'enfermedad' ? 'selected' : ''; ?>>Enfermedad</option>
                                    <option value="tratamiento" <?php echo ($recordData['tipo_registro'] ?? '') === 'tratamiento' ? 'selected' : ''; ?>>Tratamiento</option>
                                    <option value="cirugia" <?php echo ($recordData['tipo_registro'] ?? '') === 'cirugia' ? 'selected' : ''; ?>>Cirugía</option>
                                    <option value="revision" <?php echo ($recordData['tipo_registro'] ?? '') === 'revision' ? 'selected' : ''; ?>>Revisión</option>
                                    <option value="medicacion" <?php echo ($recordData['tipo_registro'] ?? '') === 'medicacion' ? 'selected' : ''; ?>>Medicación</option>
                                    <option value="alergia" <?php echo ($recordData['tipo_registro'] ?? '') === 'alergia' ? 'selected' : ''; ?>>Alergia</option>
                                    <option value="otro" <?php echo ($recordData['tipo_registro'] ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="fecha_registro" class="form-label">Fecha del Registro</label>
                                <input type="date" id="fecha_registro" name="fecha_registro" class="form-control" value="<?php echo htmlspecialchars($recordData['fecha_registro'] ?? date('Y-m-d')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="titulo" class="form-label">Título / Resumen</label>
                                <input type="text" id="titulo" name="titulo" class="form-control" value="<?php echo htmlspecialchars($recordData['titulo'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="descripcion" class="form-label">Descripción Detallada (opcional)</label>
                                <textarea id="descripcion" name="descripcion" class="form-control form-textarea"><?php echo htmlspecialchars($recordData['descripcion'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="veterinario" class="form-label">Veterinario (opcional)</label>
                                <input type="text" id="veterinario" name="veterinario" class="form-control" value="<?php echo htmlspecialchars($recordData['veterinario'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="clinica" class="form-label">Clínica (opcional)</label>
                                <input type="text" id="clinica" name="clinica" class="form-control" value="<?php echo htmlspecialchars($recordData['clinica'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="medicamentos" class="form-label">Medicamentos (opcional)</label>
                                <input type="text" id="medicamentos" name="medicamentos" class="form-control" value="<?php echo htmlspecialchars($recordData['medicamentos'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="dosis" class="form-label">Dosis (opcional)</label>
                                <input type="text" id="dosis" name="dosis" class="form-control" value="<?php echo htmlspecialchars($recordData['dosis'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="observaciones" class="form-label">Observaciones (opcional)</label>
                                <textarea id="observaciones" name="observaciones" class="form-control form-textarea"><?php echo htmlspecialchars($recordData['observaciones'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="archivo_adjunto" class="form-label">Adjuntar Archivo (JPG, PNG, GIF, PDF)</label>
                                <input type="file" id="archivo_adjunto" name="archivo_adjunto" class="form-control" accept="image/jpeg, image/png, image/gif, application/pdf">
                                <small class="text-muted">Tamaño máximo: 10MB.</small>
                            </div>

                            <div class="form-group text-right">
                                <a href="../pets/pet_profile.php?id=<?php echo $selectedPetId ?? ''; ?>" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Registro</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
