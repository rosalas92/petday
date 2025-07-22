<?php
/**
 * PetDay - Página de Cartilla Sanitaria
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
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

$petId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$petId || !isUserPetOwner($userId, $petId)) {
    header('Location: ../pets/manage_pets.php');
    exit;
}

$pet = getPetById($petId);
$cartillaRecords = getCartillaRecordsByPetId($petId);

$errors = [];
$success = '';

// Límite de cartillas
$maxCartillas = 5;
$currentCartillasCount = count($cartillaRecords);
$canUpload = ($currentCartillasCount < $maxCartillas);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_document'])) {
    if (!$canUpload) {
        $errors[] = "Ya has alcanzado el límite de " . $maxCartillas . " cartillas sanitarias para esta mascota.";
    } else {
        $nombreDocumento = sanitizeInput($_POST['nombre_documento'] ?? '');
        $fechaDocumento = sanitizeInput($_POST['fecha_documento'] ?? '');

        if (empty($nombreDocumento)) {
            $errors[] = "El nombre del documento es obligatorio.";
        }
        if (empty($fechaDocumento)) {
            $errors[] = "La fecha del documento es obligatoria.";
        }

        if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/medical_records/'; // Directorio para guardar los archivos
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            $maxFileSize = 10 * 1024 * 1024; // 10MB

            $fileType = mime_content_type($_FILES['archivo_adjunto']['tmp_name']);
            $fileSize = $_FILES['archivo_adjunto']['size'];

            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Tipo de archivo no permitido. Solo se aceptan JPG, PNG y PDF.";
            }
            if ($fileSize > $maxFileSize) {
                $errors[] = "El archivo es demasiado grande. El tamaño máximo permitido es 10MB.";
            }

            if (empty($errors)) {
                $fileName = uploadDocument($_FILES['archivo_adjunto'], $uploadDir);
                if ($fileName) {
                    $filePath = $uploadDir . $fileName;
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $tipoArchivo = '';
                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                        $tipoArchivo = 'imagen';
                    } elseif ($fileExtension === 'pdf') {
                        $tipoArchivo = 'pdf';
                    }

                    try {
                        $recordId = createCartillaRecord($petId, $nombreDocumento, $fechaDocumento, $filePath, $tipoArchivo);
                        if ($recordId) {
                            $success = "Documento subido exitosamente.";
                            $cartillaRecords = getCartillaRecordsByPetId($petId); // Recargar registros
                            $currentCartillasCount = count($cartillaRecords);
                            $canUpload = ($currentCartillasCount < $maxCartillas);
                        } else {
                            $errors[] = "Error al guardar el registro de la cartilla.";
                        }
                    } catch (Exception $e) {
                        $errors[] = "Error en la base de datos: " . $e->getMessage();
                        logError($e->getMessage(), __FILE__, __LINE__);
                    }
                } else {
                    $errors[] = "Error al subir el archivo adjunto.";
                }
            }
        } else if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "Error al subir el archivo: " . $_FILES['archivo_adjunto']['error'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartilla Sanitaria de <?php echo htmlspecialchars($pet['nombre']); ?> - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.8">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="cartilla-sanitaria-section">
            <div class="container">
                <div class="card" style="max-width: 900px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Cartilla Sanitaria de <?php echo htmlspecialchars($pet['nombre']); ?></h2>
                        <p class="card-text">Gestiona los documentos importantes de la salud de tu mascota.</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <h3>Subir Nuevo Documento (<?php echo $currentCartillasCount; ?>/<?php echo $maxCartillas; ?>)</h3>
                        <?php if ($canUpload): ?>
                            <form action="cartilla_sanitaria.php?id=<?php echo $petId; ?>" method="POST" enctype="multipart/form-data" novalidate>
                                <div class="form-group">
                                    <label for="nombre_documento" class="form-label">Nombre del Documento</label>
                                    <input type="text" id="nombre_documento" name="nombre_documento" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="fecha_documento" class="form-label">Fecha del Documento</label>
                                    <input type="date" id="fecha_documento" name="fecha_documento" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="archivo_adjunto" class="form-label">Archivo (JPG, PNG, PDF)</label>
                                    <input type="file" id="archivo_adjunto" name="archivo_adjunto" class="form-control" accept="image/jpeg, image/png, application/pdf" required>
                                    <small class="text-muted">Tamaño máximo: 10MB.</small>
                                </div>
                                <div class="form-group text-right">
                                    <button type="submit" name="submit_document" class="btn btn-primary">Subir Documento</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Has alcanzado el límite de <?php echo $maxCartillas; ?> cartillas sanitarias para esta mascota. Para subir una nueva, debes eliminar una existente.
                            </div>
                        <?php endif; ?>

                        <hr>

                        <h3>Documentos Existentes</h3>
                        <?php if (empty($cartillaRecords)): ?>
                            <p>No hay documentos en la cartilla sanitaria de <?php echo htmlspecialchars($pet['nombre']); ?>.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Subido</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartillaRecords as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['nombre_documento']); ?></td>
                                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($record['fecha_documento']))); ?></td>
                                                <td><?php echo htmlspecialchars($record['tipo_archivo']); ?></td>
                                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($record['fecha_subida']))); ?></td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($record['archivo_path']); ?>" target="_blank" class="btn btn-sm btn-info">Ver</a>
                                                    <a href="delete_cartilla.php?id=<?php echo $record['id_cartilla']; ?>&pet_id=<?php echo $petId; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este documento?');">Eliminar</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <div class="form-group text-left mt-4">
                            <a href="../pets/pet_profile.php?id=<?php echo $petId; ?>" class="btn btn-outline">Volver al Perfil de Mascota</a>
                            <a href="../pets/manage_pets.php" class="btn btn-secondary">Añadir Otra Mascota</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>