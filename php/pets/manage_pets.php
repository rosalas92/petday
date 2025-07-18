<?php
/**
 * PetDay - Página de Gestión de Mascotas
 */

session_start();
require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado, si no, redirigir a login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);
$pets = getUserPets($userId);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Mascotas - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header de Navegación -->
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
                                <a href="manage_pets.php">Mis Mascotas</a>
                                <a href="../reports/reports.php">Reportes</a>
                                <a href="../auth/logout.php">Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="manage-pets-page">
            <div class="container">
                <div class="page-header">
                    <h2>Mis Mascotas</h2>
                    <a href="create_pet.php" class="btn btn-primary">
                        <span class="icon-plus">➕</span> Agregar Nueva Mascota
                    </a>
                </div>

                <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                    <div class="alert alert-success">Mascota guardada con éxito.</div>
                <?php endif; ?>
                
                <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                    <div class="alert alert-success">Mascota eliminada con éxito.</div>
                <?php endif; ?>

                <?php if (empty($pets)): ?>
                    <!-- Estado vacío si no hay mascotas -->
                    <div class="empty-state">
                        <div class="empty-content">
                            <span class="empty-icon">🐾</span>
                            <h3>Aún no tienes mascotas registradas</h3>
                            <p>¡Es hora de añadir a tu primer compañero! Haz clic en el botón de arriba para empezar.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Grid de mascotas -->
                    <div class="pets-grid">
                        <?php foreach ($pets as $pet): ?>
                            <div class="pet-manage-card">
                                <div class="pet-card-photo-container">
                                    <?php if ($pet['foto']): ?>
                                        <img src="../../uploads/pet_photos/<?php echo htmlspecialchars($pet['foto']); ?>" alt="<?php echo htmlspecialchars($pet['nombre']); ?>" class="pet-card-photo">
                                    <?php else: ?>
                                        <div class="pet-card-avatar">
                                            <?php echo getPetEmoji($pet['especie']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="pet-card-info">
                                    <h3><?php echo htmlspecialchars($pet['nombre']); ?></h3>
                                    <p class="pet-card-details">
                                        <?php echo htmlspecialchars(ucfirst($pet['especie'])); ?>
                                        <?php if ($pet['raza']): ?>
                                            • <?php echo htmlspecialchars($pet['raza']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="pet-card-actions">
                                    <a href="pet_profile.php?id=<?php echo $pet['id_mascota']; ?>" class="btn btn-sm btn-outline">Ver Perfil</a>
                                    <a href="edit_pet.php?id=<?php echo $pet['id_mascota']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                    <a href="delete_pet.php?id=<?php echo $pet['id_mascota']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar a esta mascota? Esta acción no se puede deshacer.');">Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>