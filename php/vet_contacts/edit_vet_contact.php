<?php
/**
 * PetDay - Página para Editar Contacto Veterinario
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

$contactId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$contactId) {
    header('Location: manage_vet_contacts.php');
    exit;
}

$contactData = getVetContactById($contactId, $userId);
if (!$contactData) {
    header('Location: manage_vet_contacts.php?status=error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedContactData = [
        'nombre' => sanitizeInput($_POST['nombre'] ?? ''),
        'clinica' => sanitizeInput($_POST['clinica'] ?? ''),
        'telefono' => sanitizeInput($_POST['telefono'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'direccion' => sanitizeInput($_POST['direccion'] ?? ''),
        'especialidad' => sanitizeInput($_POST['especialidad'] ?? ''),
        'notas' => sanitizeInput($_POST['notas'] ?? ''),
        'es_principal' => isset($_POST['es_principal']) ? 1 : 0
    ];

    $errors = validateVetContactData($updatedContactData);

    if (empty($errors)) {
        try {
            if (updateVetContact($contactId, $updatedContactData)) {
                header('Location: manage_vet_contacts.php?status=success');
                exit;
            } else {
                header('Location: manage_vet_contacts.php?status=nochange');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Error en la base de datos: " . $e->getMessage();
            logError($e->getMessage(), __FILE__, __LINE__);
        }
    }
    $contactData = array_merge($contactData, $updatedContactData);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Contacto Veterinario - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.5">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="edit-vet-contact-form">
            <div class="container">
                <div class="card" style="max-width: 700px; margin: auto;">
                    <div class="card-header">
                        <h2 class="card-title">Editar Contacto Veterinario</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="edit_vet_contact.php?id=<?php echo $contactId; ?>" method="POST" novalidate>
                            <div class="form-group">
                                <label for="nombre" class="form-label">Nombre del Contacto</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($contactData['nombre']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="clinica" class="form-label">Clínica (opcional)</label>
                                <input type="text" id="clinica" name="clinica" class="form-control" value="<?php echo htmlspecialchars($contactData['clinica']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="telefono" class="form-label">Teléfono (opcional)</label>
                                <input type="tel" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($contactData['telefono']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email (opcional)</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($contactData['email']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="direccion" class="form-label">Dirección (opcional)</label>
                                <textarea id="direccion" name="direccion" class="form-control form-textarea"><?php echo htmlspecialchars($contactData['direccion']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="especialidad" class="form-label">Especialidad (opcional)</label>
                                <input type="text" id="especialidad" name="especialidad" class="form-control" value="<?php echo htmlspecialchars($contactData['especialidad']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="notas" class="form-label">Notas (opcional)</label>
                                <textarea id="notas" name="notas" class="form-control form-textarea"><?php echo htmlspecialchars($contactData['notas']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="es_principal" value="1" <?php echo ($contactData['es_principal']) ? 'checked' : ''; ?>> Marcar como contacto principal
                                </label>
                            </div>

                            <div class="form-group text-right">
                                <a href="manage_vet_contacts.php" class="btn btn-outline">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
