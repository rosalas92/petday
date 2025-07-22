<?php
/**
 * PetDay - Página de Reportes
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

$pets = getUserPets($userId);

$overallStats = [
    'total_pets' => count($pets),
    'total_routines' => 0,
    'total_events' => 0,
    'completed_routines_last_30_days' => 0,
    'upcoming_events_next_30_days' => 0
];

$petStats = [];
foreach ($pets as $pet) {
    $stats = getPetStats($pet['id_mascota'], 30);
    $petStats[$pet['id_mascota']] = $stats;
    
    $overallStats['total_routines'] += $stats['total_scheduled'];
    $overallStats['completed_routines_last_30_days'] += $stats['total_completed'];

    $events = getUpcomingEvents($pet['id_mascota'], 30);
    $overallStats['total_events'] += count($events);
    $overallStats['upcoming_events_next_30_days'] += count($events);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.4">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="reports-page">
            <div class="container">
                <div class="page-header">
                    <h2>Reportes y Estadísticas</h2>
                </div>

                <div class="overall-stats-grid stats-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $overallStats['total_pets']; ?></span>
                        <span class="stat-label">Mascotas Registradas</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $overallStats['completed_routines_last_30_days']; ?></span>
                        <span class="stat-label">Rutinas Completadas (últimos 30 días)</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $overallStats['upcoming_events_next_30_days']; ?></span>
                        <span class="stat-label">Eventos Próximos (próximos 30 días)</span>
                    </div>
                </div>

                <?php if (empty($pets)): ?>
                    <div class="empty-state">
                        <div class="empty-content">
                            <span class="empty-icon">📊</span>
                            <h3>No hay datos para mostrar</h3>
                            <p>Agrega mascotas y rutinas para ver tus estadísticas aquí.</p>
                            <a href="../pets/create_pet.php" class="btn btn-primary">Agregar Mascota</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($pets as $pet): ?>
                        <?php $stats = $petStats[$pet['id_mascota']]; ?>
                        <div class="card mb-lg">
                            <div class="card-header">
                                <h3 class="card-title">Estadísticas de <?php echo htmlspecialchars($pet['nombre']); ?></h3>
                            </div>
                            <div class="card-body">
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <span class="stat-number"><?php echo $stats['total_completed']; ?></span>
                                        <span class="stat-label">Rutinas Completadas</span>
                                    </div>
                                    <div class="stat-card">
                                        <span class="stat-number"><?php echo $stats['total_scheduled']; ?></span>
                                        <span class="stat-label">Rutinas Programadas</span>
                                    </div>
                                    <div class="stat-card">
                                        <span class="stat-number"><?php echo $stats['completion_rate']; ?>%</span>
                                        <span class="stat-label">Tasa de Completado</span>
                                        <div class="progress-bar mt-sm">
                                            <div class="progress-fill" style="width: <?php echo $stats['completion_rate']; ?>%;"></div>
                                        </div>
                                    </div>
                                </div>

                                <h4>Actividades por Tipo (últimos 30 días)</h4>
                                <?php if (empty($stats['by_type'])): ?>
                                    <p class="text-muted">No hay actividades completadas por tipo.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach ($stats['by_type'] as $activityType): ?>
                                            <li class="list-group-item">
                                                <?php echo getActivityIcon($activityType['tipo_actividad']); ?>
                                                <?php echo htmlspecialchars(ucfirst($activityType['tipo_actividad'])); ?>: 
                                                <strong><?php echo $activityType['count']; ?></strong> veces
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <h4>Historial de Peso</h4>
                                <?php 
                                    $measurements = getPetMeasurements($pet['id_mascota'], 5); // Obtener las últimas 5 medidas
                                    if (empty($measurements)): 
                                ?>
                                    <p class="text-muted">No hay registros de peso para esta mascota.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Peso (kg)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($measurements as $measurement): ?>
                                                    <tr>
                                                        <td><?php echo formatDateSpanish($measurement['fecha_medicion']); ?></td>
                                                        <td><?php echo htmlspecialchars($measurement['peso'] ?? '-'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>

                                <h4>Estadísticas de Entrenamiento (últimos 30 días)</h4>
                                <?php 
                                    $trainingStats = getTrainingStats($pet['id_mascota'], 30); 
                                    if (empty($trainingStats['total_training_sessions'])): 
                                ?>
                                    <p class="text-muted">No hay registros de entrenamiento para esta mascota.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <li class="list-group-item">
                                            <span class="routine-icon">🎯</span> Sesiones de Entrenamiento: 
                                            <strong><?php echo $trainingStats['total_training_sessions']; ?></strong>
                                        </li>
                                        <li class="list-group-item">
                                            <span class="routine-icon">🚶</span> Paseos (entrenamiento): 
                                            <strong><?php echo $trainingStats['training_walks']; ?></strong>
                                        </li>
                                        <li class="list-group-item">
                                            <span class="routine-icon">🎾</span> Juegos (entrenamiento): 
                                            <strong><?php echo $trainingStats['training_games']; ?></strong>
                                        </li>
                                    </ul>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
