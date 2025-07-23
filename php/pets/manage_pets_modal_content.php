<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo '<p>Usuario no autenticado.</p>';
    exit;
}

$userId = $_SESSION['user_id'];
$pets = getUserPets($userId);

?>

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
    <div class="empty-state">
        <div class="empty-content">
            <span class="empty-icon">🐾</span>
            <h3>Aún no tienes mascotas registradas</h3>
            <p>¡Es hora de añadir a tu primer compañero! Haz clic en el botón de arriba para empezar.</p>
        </div>
    </div>
<?php else: ?>
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