<?php
/**
 * PetDay - Página de Gestión de Contactos Veterinarios
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
$vetContacts = getAllVetContacts($userId);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Contactos Veterinarios - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.5">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <a href="../../index.php" class="logo">
                    <span class="logo-icon">🐾</span>
                    <h1>PetDay</h1>
                </a>
                <nav class="main-nav">
                    <div class="user-menu">
                        <span class="welcome-text">Hola, <?php echo htmlspecialchars($user['nombre_completo']); ?></span>
                        <div class="user-dropdown">
                            <button class="user-btn">👤</button>
                            <div class="dropdown-content">
                                <a href="../pets/manage_pets.php">Mis Mascotas</a>
                                <a href="../reports/reports.php">Reportes</a>
                                <a href="manage_vet_contacts.php">Veterinarios</a>
                                <a href="../auth/logout.php">Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="manage-vet-contacts-page">
            <div class="container">
                <div class="page-header">
                    <h2>Mis Contactos Veterinarios</h2>
                    <a href="create_vet_contact.php" class="btn btn-primary">
                        <span class="icon-plus">➕</span> Añadir Contacto
                    </a>
                </div>

                <?php if (isset($_GET['status'])): ?>
                    <?php if ($_GET['status'] == 'success'): ?>
                        <div class="alert alert-success">Contacto guardado con éxito.</div>
                    <?php elseif ($_GET['status'] == 'deleted'): ?>
                        <div class="alert alert-success">Contacto eliminado con éxito.</div>
                    <?php elseif ($_GET['status'] == 'error'): ?>
                        <div class="alert alert-danger">Hubo un error en la operación.</div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (empty($vetContacts)): ?>
                    <div class="empty-state">
                        <div class="empty-content">
                            <span class="empty-icon">🏥</span>
                            <h3>Aún no tienes contactos veterinarios registrados</h3>
                            <p>Añade a tus veterinarios de confianza para tener su información siempre a mano.</p>
                            <a href="create_vet_contact.php" class="btn btn-primary">Añadir Contacto Ahora</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="vet-contacts-list">
                        <?php foreach ($vetContacts as $contact): ?>
                            <div class="card vet-contact-card">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo htmlspecialchars($contact['nombre']); ?>
                                        <?php if ($contact['es_principal']): ?>
                                            <span class="badge badge-primary">Principal</span>
                                        <?php endif; ?>
                                    </h3>
                                    <?php if ($contact['clinica']): ?><p><strong>Clínica:</strong> <?php echo htmlspecialchars($contact['clinica']); ?></p><?php endif; ?>
                                    <?php if ($contact['telefono']): ?><p><strong>Teléfono:</strong> <?php echo htmlspecialchars($contact['telefono']); ?></p><?php endif; ?>
                                    <?php if ($contact['email']): ?><p><strong>Email:</strong> <?php echo htmlspecialchars($contact['email']); ?></p><?php endif; ?>
                                    <?php if ($contact['especialidad']): ?><p><strong>Especialidad:</strong> <?php echo htmlspecialchars($contact['especialidad']); ?></p><?php endif; ?>
                                    <?php if ($contact['notas']): ?><p><strong>Notas:</strong> <?php echo nl2br(htmlspecialchars($contact['notas'])); ?></p><?php endif; ?>
                                </div>
                                <div class="card-footer text-right">
                                    <a href="edit_vet_contact.php?id=<?php echo $contact['id_contacto']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                    <a href="delete_vet_contact.php?id=<?php echo $contact['id_contacto']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este contacto?');">Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
