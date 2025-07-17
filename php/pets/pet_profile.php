<?php
/**
 * PetDay - Perfil de Mascota
 */

session_start();
require_once '../../config/database_config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

$petId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$petId || !isUserPetOwner($userId, $petId)) {
    header('Location: manage_pets.php?status=error');
    exit;
}

$pet = getPetById($petId, $userId);
$routines = getPetRoutines($petId);
$events = getUpcomingEvents($petId, 365); // Próximos eventos del año

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($pet['nombre']); ?> - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.3">
</head>
<body>
    <header class="main-header">
        <!-- ... (header similar a otras páginas de la sección) ... -->
    </header>

    <main class="main-content">
        <section class="pet-profile-page">
            <div class="container">
                <!-- Cabecera del Perfil -->
                <div class="profile-header">
                    <div class="profile-photo-container">
                        <?php if ($pet['foto']): ?>
                            <img src="../../uploads/pet_photos/<?php echo htmlspecialchars($pet['foto']); ?>" alt="<?php echo htmlspecialchars($pet['nombre']); ?>" class="profile-photo">
                        <?php else: ?>
                            <div class="profile-avatar">
                                <?php echo getPetEmoji($pet['especie']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($pet['nombre']); ?></h1>
                        <p class="profile-details">
                            <?php echo htmlspecialchars(ucfirst($pet['especie'])); ?>
                            <?php if ($pet['raza']): ?> • <?php echo htmlspecialchars($pet['raza']); ?><?php endif; ?>
                            • <?php echo $pet['edad']; ?> años • <?php echo $pet['peso']; ?> kg
                            • <?php echo htmlspecialchars(ucfirst($pet['genero'])); ?>
                        </p>
                    </div>
                    <div class="profile-actions">
                        <a href="edit_pet.php?id=<?php echo $petId; ?>" class="btn btn-primary">Editar Perfil</a>
                    </div>
                </div>

                <!-- Contenido del Perfil -->
                <div class="profile-content">
                    <div class="medical-card-section card">
                        <div class="card-header">
                            <h3 class="card-title">Cartilla Sanitaria</h3>
                        </div>
                        <div class="card-body">
                            <?php 
                                $lastVaccine = getLatestMedicalRecordByType($petId, 'vacuna');
                                $allergies = getAllergies($petId);
                                $activeMedications = getActiveMedications($petId);
                            ?>
                            <p><strong>Última Vacuna:</strong> <?php echo $lastVaccine ? formatDateSpanish($lastVaccine['fecha_registro']) . ' (' . htmlspecialchars($lastVaccine['titulo']) . ')' : 'No registrada'; ?></p>
                            <p><strong>Alergias:</strong> 
                                <?php if (!empty($allergies)): ?>
                                    <?php foreach ($allergies as $allergy): ?>
                                        <span class="badge badge-danger"><?php echo htmlspecialchars($allergy['titulo']); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    Ninguna conocida
                                <?php endif; ?>
                            </p>
                            <p><strong>Medicación Activa:</strong> 
                                <?php if (!empty($activeMedications)): ?>
                                    <?php foreach ($activeMedications as $medication): ?>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($medication['nombre_medicamento']); ?> (<?php echo htmlspecialchars($medication['dosis']); ?>)</span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    Ninguna
                                <?php endif; ?>
                            </p>
                            <!-- Puedes añadir más campos relevantes aquí -->
                        </div>
                    </div>

                    <div class="routines-section card">
                        <div class="card-header">
                            <h3 class="card-title">Rutinas Semanales</h3>
                            <a href="../routines/create_routine.php?pet_id=<?php echo $petId; ?>" class="btn btn-sm btn-primary">+ Nueva Rutina</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($routines)): ?>
                                <p class="text-muted">No hay rutinas programadas para esta mascota.</p>
                            <?php else: ?>
                                <div class="routines-list-profile">
                                    <?php foreach ($routines as $routine): ?>
                                        <div class="routine-item-profile">
                                            <span class="routine-icon"><?php echo getActivityIcon($routine['tipo_actividad']); ?></span>
                                            <div class="routine-item-details">
                                                <strong><?php echo htmlspecialchars($routine['nombre_actividad']); ?></strong>
                                                <span><?php echo date('H:i', strtotime($routine['hora_programada'])); ?></span>
                                                <small class="text-muted"><?php echo str_replace(',', ', ', $routine['dias_semana']); ?></small>
                                            </div>
                                            <div class="routine-item-actions">
                                                <a href="../routines/edit_routine.php?id=<?php echo $routine['id_rutina']; ?>" class="btn btn-xs btn-outline">Editar</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="events-section card">
                        <div class="card-header">
                            <h3 class="card-title">Próximos Eventos</h3>
                            <a href="../events/create_event.php?pet_id=<?php echo $petId; ?>" class="btn btn-sm btn-primary">+ Nuevo Evento</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($events)): ?>
                                <p class="text-muted">No hay eventos próximos.</p>
                            <?php else: ?>
                                <ul class="events-list">
                                    <?php foreach ($events as $event): ?>
                                        <li>
                                            <strong><?php echo htmlspecialchars($event['titulo']); ?></strong> - 
                                            <?php echo formatDateSpanish($event['fecha_evento']); ?> a las <?php echo date('H:i', strtotime($event['fecha_evento'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="medical-records-section card">
                        <div class="card-header">
                            <h3 class="card-title">Historial Médico Completo</h3>
                            <a href="../medical_records/create_medical_record.php?pet_id=<?php echo $petId; ?>" class="btn btn-sm btn-primary">+ Nuevo Registro</a>
                        </div>
                        <div class="card-body">
                            <?php $medicalRecords = getMedicalRecords($petId); ?>
                            <?php if (empty($medicalRecords)): ?>
                                <p class="text-muted">No hay registros médicos para esta mascota.</p>
                            <?php else: ?>
                                <div class="medical-records-list">
                                    <?php foreach ($medicalRecords as $record): ?>
                                        <div class="medical-record-item">
                                            <div class="record-info">
                                                <strong><?php echo htmlspecialchars($record['titulo']); ?></strong>
                                                <small class="text-muted"><?php echo formatDateSpanish($record['fecha_registro']); ?> - <?php echo htmlspecialchars(ucfirst($record['tipo_registro'])); ?></small>
                                                <?php if ($record['veterinario']): ?><small class="text-muted">Vet: <?php echo htmlspecialchars($record['veterinario']); ?></small><?php endif; ?>
                                            </div>
                                            <div class="record-actions">
                                                <?php if ($record['archivo_adjunto']): ?>
                                                    <a href="../../uploads/medical_records/<?php echo htmlspecialchars($record['archivo_adjunto']); ?>" target="_blank" class="btn btn-xs btn-outline">Ver Archivo</a>
                                                <?php endif; ?>
                                                <a href="../medical_records/edit_medical_record.php?id=<?php echo $record['id_historial']; ?>" class="btn btn-xs btn-primary">Editar</a>
                                                <a href="../medical_records/delete_medical_record.php?id=<?php echo $record['id_historial']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este registro médico? Esta acción no se puede deshacer.');">Eliminar</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="measurements-section card">
                        <div class="card-header">
                            <h3 class="card-title">Historial de Medidas</h3>
                            <a href="add_measurement.php?pet_id=<?php echo $petId; ?>" class="btn btn-sm btn-primary">+ Nueva Medida</a>
                        </div>
                        <div class="card-body">
                            <?php $measurements = getPetMeasurements($petId); ?>
                            <?php if (empty($measurements)): ?>
                                <p class="text-muted">No hay registros de medidas para esta mascota.</p>
                            <?php else: ?>
                                <div class="chart-container" style="position: relative; margin-bottom: 20px;">
                                    <canvas id="weightChart"></canvas>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Peso (kg)</th>
                                                <th>Altura (cm)</th>
                                                <th>Longitud (cm)</th>
                                                <th>Cuello (cm)</th>
                                                <th>Notas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($measurements as $measurement): ?>
                                                <tr>
                                                    <td><?php echo formatDateSpanish($measurement['fecha_medicion']); ?></td>
                                                    <td><?php echo htmlspecialchars($measurement['peso'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($measurement['altura'] ?? '-'); ?></td>
                                                    <td><?php htmlspecialchars($measurement['longitud'] ?? '-'); ?></td>
                                                    <td><?php htmlspecialchars($measurement['circunferencia_cuello'] ?? '-'); ?></td>
                                                    <td><?php htmlspecialchars($measurement['notas'] ?? '-'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <!-- ... (footer) ... -->
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const petMeasurements = <?php echo json_encode($measurements); ?>;
    </script>
    <script src="../../js/charts.js"></script>
</body>
</html>