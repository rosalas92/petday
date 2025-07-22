<?php
/**
 * PetDay - Página para Editar Registro Médico
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

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$recordId) {
    header('Location: ../pets/manage_pets.php');
    exit;
}

$recordData = getMedicalRecordById($recordId);
if (!$recordData || !isUserPetOwner($userId, $recordData['id_mascota'])) {
    header('Location: ../pets/manage_pets.php?status=error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usar el id_mascota original del registro, ya que no se puede cambiar
    $updatedRecordData = [
        'id_mascota' => $recordData['id_mascota'],
        'tipo_registro' => sanitizeInput($_POST['tipo_registro'] ?? ''),
        'fecha_registro' => sanitizeInput($_POST['fecha_registro'] ?? ''),
        'titulo' => sanitizeInput($_POST['titulo'] ?? ''),
        'descripcion' => sanitizeInput($_POST['descripcion'] ?? ''),
        'veterinario' => sanitizeInput($_POST['veterinario'] ?? ''),
        'clinica' => sanitizeInput($_POST['clinica'] ?? ''),
        'medicamentos' => sanitizeInput($_POST['medicamentos'] ?? ''),
        'dosis' => sanitizeInput($_POST['dosis'] ?? ''),
        'observaciones' => sanitizeInput($_POST['observaciones'] ?? ''),
        'archivo_adjunto' => $recordData['archivo_adjunto'] // Mantener el archivo existente por defecto
    ];

    // Validar datos
    $errors = validateMedicalRecordData($updatedRecordData);
    // La validación de propiedad de la mascota ya se hizo al cargar $recordData

    // Manejar subida de nuevo archivo
    if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/medical_records/';
        $newFileName = uploadDocument($_FILES['archivo_adjunto'], $uploadDir);
        if ($newFileName) {
            // Si se sube un nuevo archivo, eliminar el anterior si existe
            if ($recordData['archivo_adjunto'] && file_exists($uploadDir . $recordData['archivo_adjunto'])) {
                unlink($uploadDir . $recordData['archivo_adjunto']);
            }
            $updatedRecordData['archivo_adjunto'] = $newFileName;
        } else {
            $errors[] = "Error al subir el nuevo archivo adjunto. Asegúrate de que es una imagen (JPG, PNG, GIF) o PDF y no pesa más de 10MB.";
        }
    }

    if (empty($errors)) {
        try {
            if (updateMedicalRecord($recordId, $updatedRecordData)) {
                header('Location: ../pets/pet_profile.php?id=' . $updatedRecordData['id_mascota'] . '&status=medical_record_updated');
                exit;
            } else {
                header('Location: ../pets/pet_profile.php?id=' . $updatedRecordData['id_mascota'] . '&status=nochange');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Error en la base de datos: " . $e->getMessage();
            logError($e->getMessage(), __FILE__, __LINE__);
        }
    }
    // Si hay errores, recargar los datos del formulario con los datos POSTeados
    $recordData = array_merge($recordData, $updatedRecordData);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Registro Médico - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.7">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="edit-medical-record-form">
            <div class="container">
                <div class="card" style="max-width: 800px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Editar Registro Médico</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="edit_medical_record.php?id=<?php echo $recordId; ?>" method="POST" enctype="multipart/form-data" novalidate>
                            <div class="form-group">
                                <label for="id_mascota" class="form-label">Mascota</label>
                                <select id="id_mascota" name="id_mascota" class="form-control form-select" required disabled>
                                    <?php foreach ($userPets as $pet): ?>
                                        <option value="<?php echo $pet['id_mascota']; ?>" <?php echo ($recordData['id_mascota'] ?? '') == $pet['id_mascota'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pet['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="id_mascota" value="<?php echo htmlspecialchars($recordData['id_mascota']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="tipo_registro" class="form-label">Tipo de Registro</label>
                                <select id="tipo_registro" name="tipo_registro" class="form-control form-select" required>
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
                                <?php if ($recordData['archivo_adjunto']): ?>
                                    <p class="text-muted">Archivo actual: <a href="../../uploads/medical_records/<?php echo htmlspecialchars($recordData['archivo_adjunto']); ?>" target="_blank"><?php echo htmlspecialchars($recordData['archivo_adjunto']); ?></a></p>
                                <?php endif; ?>
                                <input type="file" id="archivo_adjunto" name="archivo_adjunto" class="form-control" accept="image/jpeg, image/png, image/gif, application/pdf">
                                <small class="text-muted">Sube un nuevo archivo solo si quieres reemplazar el actual. Tamaño máximo: 10MB.</small>
                            </div>

                            <div class="form-group text-right">
                                <a href="../pets/pet_profile.php?id=<?php echo $recordData['id_mascota']; ?>" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
