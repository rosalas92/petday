<?php
/**
 * PetDay - Página Principal
 * Gestión de rutinas diarias para mascotas
 */

session_start();
require_once 'config/config.php'; // Cargar primero para definir constantes
require_once 'config/database_config.php';
require_once 'php/includes/functions.php';

// Verificar si el usuario está logueado
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;
$pets = [];

if ($isLoggedIn) {
    $user = getUserById($_SESSION['user_id']);
    $pets = getUserPets($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetDay - Gestiona la rutina de tus mascotas</title>
    <link rel="stylesheet" href="css/style.css?v=1.1">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php if (isset($_GET['contact_status'])): ?>
        <div class="alert <?php echo ($_GET['contact_status'] == 'success') ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo htmlspecialchars($_GET['message'] ?? (($_GET['contact_status'] == 'success') ? '¡Gracias! Tu mensaje ha sido enviado con éxito.' : 'Hubo un problema al enviar tu mensaje.')); ?>
        </div>
    <?php endif; ?>
    <?php include_once 'php/includes/header.php'; ?>

    <main class="main-content">
        <?php if (!$isLoggedIn): ?>
            <!-- Landing Page para usuarios no logueados -->
            <section class="hero">
                <div class="container">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h2>Organiza la rutina perfecta para tus mascotas</h2>
                            <p>Gestiona horarios de comida, paseos, medicación y citas veterinarias. Todo en un solo lugar.</p>
                            <div class="hero-buttons">
                                <a href="php/auth/register.php" class="btn btn-primary btn-large">Comienza</a>
                                <a href="#features" class="btn btn-outline btn-large">Ver Características</a>
                            </div>
                        </div>
                        <div class="hero-image">
                            <div class="pet-cards-demo">
                                <div class="demo-card">
                                    <span class="pet-emoji">🐕</span>
                                    <h4>Rex</h4>
                                    <p>Próximo paseo: 14:00</p>
                                </div>
                                <div class="demo-card">
                                    <span class="pet-emoji">🐱</span>
                                    <h4>Luna</h4>
                                    <p>Comida: 12:00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Características -->
            <section id="features" class="features">
                <div class="container">
                    <h3>¿Por qué elegir PetDay?</h3>
                    <div class="features-grid">
                        <div class="feature-card">
                            <span class="feature-icon">📅</span>
                            <h4>Rutinas Personalizadas</h4>
                            <p>Crea horarios específicos para cada mascota según su especie, edad y necesidades.</p>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">🔔</span>
                            <h4>Recordatorios Inteligentes</h4>
                            <p>Nunca olvides comidas, medicación o citas veterinarias con nuestras notificaciones.</p>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">📊</span>
                            <h4>Seguimiento y Estadísticas</h4>
                            <p>Monitorea el progreso y salud de tus mascotas con reportes detallados.</p>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">🏥</span>
                            <h4>Historial Médico</h4>
                            <p>Mantén registro de vacunas, tratamientos y visitas al veterinario.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Testimonios -->
            <section id="testimonials" class="testimonials">
                <div class="container">
                    <h3>Lo que dicen nuestros usuarios</h3>
                    <div class="testimonials-grid">
                        <div class="testimonial-card">
                            <p>"PetDay ha cambiado la forma en que organizo la vida de mis dos perros. Ahora nunca olvido sus comidas o medicamentos. ¡Imprescindible!"</p>
                            <div class="testimonial-author">
                                <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Usuario 1">
                                <span>— Ana García, dueña de Max y Rocky</span>
                            </div>
                        </div>
                        <div class="testimonial-card">
                            <p>"Como dueño primerizo de un gato, estaba abrumado. PetDay me ayudó a establecer una rutina y a seguir el historial de vacunas de Luna."</p>
                            <div class="testimonial-author">
                                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Usuario 2">
                                <span>— Carlos Rodríguez, dueño de Luna</span>
                            </div>
                        </div>
                        <div class="testimonial-card">
                            <p>"Me encanta la función de seguimiento. Puedo ver estadísticas sobre los paseos y la alimentación, lo que me ayuda a mantener a mi mascota sana y activa."</p>
                            <div class="testimonial-author">
                                <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Usuario 3">
                                <span>— Laura Fernández, dueña de Pipa</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        <?php else: ?>
            <!-- Dashboard para usuarios logueados -->
            <section class="dashboard">
                <div class="container">
                    <div class="dashboard-header">
                        <h2>Tu Dashboard</h2>
                        <div class="current-date">
                            <span class="date-icon">📅</span>
                            <span id="current-date"></span>
                        </div>
                    </div>

                    <?php if (empty($pets)): ?>
                        <!-- Estado vacío - Sin mascotas -->
                        <div class="empty-state">
                            <div class="empty-content">
                                <span class="empty-icon">🐾</span>
                                <h3>¡Bienvenido a PetDay!</h3>
                                <p>Parece que aún no has agregado ninguna mascota. Comienza creando el perfil de tu compañero peludo.</p>
                                <a href="php/pets/create_pet.php" class="btn btn-primary">Agregar Mi Primera Mascota</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Dashboard con mascotas -->
                        <div class="pets-dashboard">
                            <?php foreach ($pets as $pet): ?>
                                <?php $todayRoutines = getTodayRoutines($pet['id_mascota']); ?>
                                <div class="pet-card">
                                    <div class="pet-header">
                                        <div class="pet-info">
                                            <?php if ($pet['foto']): ?>
                                                <img src="uploads/pet_photos/<?php echo htmlspecialchars($pet['foto']); ?>" 
                                                     alt="<?php echo htmlspecialchars($pet['nombre']); ?>" 
                                                     class="pet-photo">
                                            <?php else: ?>
                                                <div class="pet-avatar">
                                                    <?php echo getPetEmoji($pet['especie']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="pet-details">
                                                <h3><?php echo htmlspecialchars($pet['nombre']); ?></h3>
                                                <p class="pet-breed"><?php echo htmlspecialchars($pet['raza']); ?> • <?php echo $pet['edad']; ?> años</p>
                                            </div>
                                        </div>
                                        <div class="pet-actions">
                                            <a href="php/pets/pet_profile.php?id=<?php echo $pet['id_mascota']; ?>" class="btn btn-sm btn-outline">Ver Perfil</a>
                                        </div>
                                    </div>

                                    <div class="pet-routines">
                                        <h4>Actividades de Hoy</h4>
                                        <?php if (empty($todayRoutines)): ?>
                                            <p class="no-routines">No hay actividades programadas para hoy</p>
                                            <a href="php/routines/create_routine.php?pet_id=<?php echo $pet['id_mascota']; ?>" class="btn btn-sm btn-primary">Agregar Rutina</a>
                                        <?php else: ?>
                                            <div class="routines-list">
                                                <?php foreach ($todayRoutines as $routine): ?>
                                                    <?php 
                                                    $isCompleted = isRoutineCompletedToday($routine['id_rutina']);
                                                    $isPending = strtotime($routine['hora_programada']) > time() && !$isCompleted;
                                                    $routineColorClass = 'routine-bg-' . (($routine['id_rutina'] % 5) + 1); // Assign a color based on routine ID
                                                    ?>
                                                    <div class="routine-item <?php echo $isCompleted ? 'completed' : ($isPending ? 'pending' : 'overdue'); ?> <?php echo $routineColorClass; ?>" data-routine-id="<?php echo $routine['id_rutina']; ?>">
                                                        <div class="routine-info">
                                                            <span class="routine-icon"><?php echo getActivityIcon($routine['tipo_actividad']); ?></span>
                                                            <div class="routine-details">
                                                                <span class="routine-name"><?php echo htmlspecialchars($routine['nombre_actividad']); ?></span>
                                                                <span class="routine-time"><?php echo date('H:i', strtotime($routine['hora_programada'])); ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="routine-status">
                                                            <?php if ($isCompleted): ?>
                                                                <span class="status-badge completed">✅</span>
                                                            <?php else: ?>
                                                                <button class="btn btn-xs btn-success mark-complete-btn" data-routine-id="<?php echo $routine['id_rutina']; ?>">Marcar como Completada</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Botón flotante para agregar nueva mascota -->
                        <div class="floating-actions">
                            <button class="fab" id="fabMain">
                                <span>+</span>
                            </button>
                            <div class="fab-menu" id="fabMenu">
                                <a href="php/pets/create_pet.php" class="fab-item">
                                    <span class="fab-icon">🐾</span>
                                    <span class="fab-label">Nueva Mascota</span>
                                </a>
                                <a href="php/routines/create_routine.php" class="fab-item">
                                    <span class="fab-icon">📅</span>
                                    <span class="fab-label">Nueva Rutina</span>
                                </a>
                                <a href="php/events/create_event.php" class="fab-item">
                                    <span class="fab-icon">🏥</span>
                                    <span class="fab-label">Nuevo Evento</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Contacto -->
            <section id="contact" class="contact-section">
                <div class="container">
                    <h2 class="text-center">Contáctanos</h2>
                    <p class="text-center text-muted">¿Tienes alguna pregunta o sugerencia? ¡Envíanos un mensaje!</p>
                    <div class="contact-form-container">
                        <form action="php/contact/send_contact.php" method="POST" class="contact-form">
                            <div class="form-group">
                                <label for="contact_name" class="form-label">Tu Nombre</label>
                                <input type="text" id="contact_name" name="contact_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_email" class="form-label">Tu Correo Electrónico</label>
                                <input type="email" id="contact_email" name="contact_email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_subject" class="form-label">Asunto</label>
                                <input type="text" id="contact_subject" name="contact_subject" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_message" class="form-label">Mensaje</label>
                                <textarea id="contact_message" name="contact_message" class="form-control form-textarea" rows="5" required></textarea>
                            </div>
                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-primary btn-large">Enviar Mensaje</button>
                            </div>
                            
                        </form>
                    </div>
                </div>
            </section>

    <?php include 'php/includes/footer.php'; ?>