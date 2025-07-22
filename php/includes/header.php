<?php
// Asegúrate de que config.php y functions.php estén incluidos antes de este archivo
// en la página principal que lo llama.

// Estas variables deben estar definidas en la página que incluye este header
// $isLoggedIn = isset($_SESSION['user_id']);
// $user = getUserById($_SESSION['user_id']);

?>

<header class="main-header">
    <div class="container">
        <div class="header-content">
            <a href="<?php echo URL_ADMIN; ?>/index.php" class="logo">
                <img src="<?php echo URL_ADMIN; ?>/images/logotipo-lateral.png" alt="PetDay Logo" style="height: 100px;">
            </a>
            
            <nav class="main-nav" style="transform: scale(1.2); transform-origin: right center;">
                <?php if ($isLoggedIn): ?>
                    <div class="user-menu">
                        <span class="welcome-text">Hola, <?php echo htmlspecialchars($user['nombre_completo']); ?></span>
                        <div class="user-dropdown">
                            <button class="user-btn">👤</button>
                            <div class="dropdown-content">
                                <a href="<?php echo URL_ADMIN; ?>/php/pets/manage_pets.php">Mis Mascotas</a>
                                <a href="<?php echo URL_ADMIN; ?>/php/reports/reports.php">Reportes</a>
                                <a href="<?php echo URL_ADMIN; ?>/php/calendar/calendar.php">Calendario</a>
                                <a href="<?php echo URL_ADMIN; ?>/php/vet_contacts/manage_vet_contacts.php">Veterinarios</a>
                                <a href="<?php echo URL_ADMIN; ?>/php/auth/logout.php">Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="<?php echo URL_ADMIN; ?>/php/auth/login.php" class="btn btn-outline">Iniciar Sesión</a>
                        <a href="<?php echo URL_ADMIN; ?>/php/auth/register.php" class="btn btn-primary">Registrarse</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>
