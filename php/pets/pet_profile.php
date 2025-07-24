<?php
/**
 * PetDay - Perfil de Mascota
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
    <link rel="stylesheet" href="../../css/style.css?v=1.10">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

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
                        <a href="../medical_records/cartilla_sanitaria.php?id=<?php echo $petId; ?>" class="btn btn-outline">Cartilla Sanitaria</a>
                    </div>
                </div>

                <!-- Contenido del Perfil -->
                <div class="profile-content">
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
                                        <div class="routine-item-profile clickable-event" data-event='<?php echo json_encode($routine); ?>'>
                                            <span class="routine-icon"><?php echo getActivityIcon($routine['tipo_actividad']); ?></span>
                                            <div class="routine-item-details">
                                                <strong><?php echo htmlspecialchars($routine['nombre_actividad']); ?></strong>
                                                <span><?php echo date('H:i', strtotime($routine['hora_programada'])); ?></span>
                                                <small class="text-muted"><?php echo str_replace(',', ', ', $routine['dias_semana']); ?></small>
                                            </div>
                                            <div class="routine-item-actions">
                                                <!-- Placeholder para icono de estado -->
                                                <span class="routine-status-icon" data-routine-id="<?php echo $routine['id_rutina']; ?>"></span>
                                                <a href="../routines/edit_routine.php?id=<?php echo $routine['id_rutina']; ?>" class="btn btn-xs btn-outline">Editar</a>
                                                <a href="../routines/delete_routine.php?id=<?php echo $routine['id_rutina']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta rutina? Esta acción no se puede deshacer.');">Eliminar</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="events-section card full-width-card">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="card-title">Calendario de Eventos y Rutinas</h3>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <select id="calendarViewSelector" class="form-control form-select" style="width: auto;">
                                    <option value="month">Mes</option>
                                    <option value="week">Semana</option>
                                    <option value="day">Día</option>
                                </select>
                                <a href="../events/create_event.php?pet_id=<?php echo $petId; ?>" class="btn btn-sm btn-primary">+ Nuevo Evento</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            $currentMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
                            $currentYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
                            $currentDay = isset($_GET['day']) ? intval($_GET['day']) : date('j');
                            $currentView = isset($_GET['view']) ? $_GET['view'] : 'month';

                            $monthNames = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];

                            $petRoutines = getPetRoutines($petId);
                            $petEvents = getUpcomingEvents($petId, 365); // Obtener eventos para el año para filtrar por mes, semana o día

                            // Función auxiliar para generar enlaces de navegación
                            function generateCalendarNavUrl($petId, $month, $year, $view, $day = null) {
                                $url = "?id=$petId&month=$month&year=$year&view=$view";
                                if ($day !== null) {
                                    $url .= "&day=$day";
                                }
                                return $url;
                            }

                            echo '<div class="calendar-nav">';
                            if ($currentView === 'month') {
                                $prevMonth = $currentMonth - 1;
                                $prevYear = $currentYear;
                                if ($prevMonth < 1) {
                                    $prevMonth = 12;
                                    $prevYear--;
                                }
                                $nextMonth = $currentMonth + 1;
                                $nextYear = $currentYear;
                                if ($nextMonth > 12) {
                                    $nextMonth = 1;
                                    $nextYear++;
                                }
                                echo '<a href="' . generateCalendarNavUrl($petId, $prevMonth, $prevYear, $currentView) . '" class="btn btn-sm btn-outline">&lt; Anterior</a>';
                                echo '<h3>' . $monthNames[$currentMonth] . ' ' . $currentYear . '</h3>';
                                echo '<a href="' . generateCalendarNavUrl($petId, $nextMonth, $nextYear, $currentView) . '" class="btn btn-sm btn-outline">Siguiente &gt;</a>';
                            } elseif ($currentView === 'week') {
                                $date = new DateTime("$currentYear-$currentMonth-$currentDay");
                                $date->modify('monday this week');
                                $prevWeek = clone $date;
                                $prevWeek->modify('-1 week');
                                $nextWeek = clone $date;
                                $nextWeek->modify('+1 week');

                                echo '<a href="' . generateCalendarNavUrl($petId, $prevWeek->format('n'), $prevWeek->format('Y'), $currentView, $prevWeek->format('j')) . '" class="btn btn-sm btn-outline">&lt; Semana Anterior</a>';
                                echo '<h3>Semana del ' . $date->format('d M Y') . '</h3>';
                                echo '<a href="' . generateCalendarNavUrl($petId, $nextWeek->format('n'), $nextWeek->format('Y'), $currentView, $nextWeek->format('j')) . '" class="btn btn-sm btn-outline">Semana Siguiente &gt;</a>';
                            }
                            echo '</div>';

                            if ($currentView === 'month') {
                                $date = new DateTime("$currentYear-$currentMonth-01");
                                $daysInMonth = $date->format('t');
                                $firstDayOfWeek = $date->format('N'); // 1 (for Monday) through 7 (for Sunday)

                                $calendar = [];
                                $dayCounter = 1;

                                // Rellenar días vacíos al principio del mes
                                for ($i = 1; $i < $firstDayOfWeek; $i++) {
                                    $calendar[] = null;
                                }

                                // Rellenar días del mes
                                while ($dayCounter <= $daysInMonth) {
                                    $currentDate = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $dayCounter);
                                    $calendar[] = [
                                        'date' => $currentDate,
                                        'day' => $dayCounter,
                                        'routines' => [],
                                        'events' => []
                                    ];
                                    $dayCounter++;
                                }

                                // Asignar rutinas y eventos a los días del calendario
                                foreach ($calendar as &$dayData) {
                                    if ($dayData === null) continue;

                                    $dateStr = $dayData['date'];
                                    $dayOfWeek = strtolower(date('l', strtotime($dateStr)));
                                    $dayInSpanish = [
                                        'monday' => 'lunes',
                                        'tuesday' => 'martes',
                                        'wednesday' => 'miercoles',
                                        'thursday' => 'jueves',
                                        'friday' => 'viernes',
                                        'saturday' => 'sabado',
                                        'sunday' => 'domingo'
                                    ][$dayOfWeek];

                                    foreach ($petRoutines as $routine) {
                                        $diasSemana = explode(',', $routine['dias_semana']);
                                        if (in_array($dayInSpanish, $diasSemana)) {
                                            $dayData['routines'][] = $routine;
                                        }
                                    }

                                    foreach ($petEvents as $event) {
                                        if (date('Y-m-d', strtotime($event['fecha_evento'])) == $dateStr) {
                                            $dayData['events'][] = $event;
                                        }
                                    }
                                }
                                unset($dayData); // Romper la referencia del último elemento

                                ?>

                                <div class="calendar-grid">
                                    <div class="calendar-day-header">Lun</div>
                                    <div class="calendar-day-header">Mar</div>
                                    <div class="calendar-day-header">Mié</div>
                                    <div class="calendar-day-header">Jue</div>
                                    <div class="calendar-day-header">Vie</div>
                                    <div class="calendar-day-header">Sáb</div>
                                    <div class="calendar-day-header">Dom</div>

                                    <?php foreach ($calendar as $dayData): ?>
                                        <?php if ($dayData === null): ?>
                                            <div class="calendar-day empty"></div>
                                        <?php else: ?>
                                            <div class="calendar-day <?php echo (date('Y-m-d') == $dayData['date']) ? 'today' : ''; ?>" data-date="<?php echo $dayData['date']; ?>" data-events='<?php echo json_encode(array_merge($dayData['routines'], $dayData['events'])); ?>'>
                                                <span class="day-number"><?php echo $dayData['day']; ?></span>
                                                <div class="day-events">
                                                    <?php foreach ($dayData['routines'] as $routine): ?>
                                                        <div class="event-item routine-event clickable-event" data-event='<?php echo json_encode($routine); ?>'>
                                                            <span class="event-icon"><?php echo getActivityIcon($routine['tipo_actividad']); ?></span>
                                                            <span class="event-time"><?php echo date('H:i', strtotime($routine['hora_programada'])); ?></span>
                                                            <span class="event-title"><?php echo htmlspecialchars($routine['nombre_actividad']); ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php foreach ($dayData['events'] as $event): ?>
                                                        <div class="event-item calendar-event clickable-event" data-event='<?php echo json_encode($event); ?>'>
                                                            <span class="event-icon">🏥</span>
                                                            <span class="event-time"><?php echo date('H:i', strtotime($event['fecha_evento'])); ?></span>
                                                            <span class="event-title"><?php echo htmlspecialchars($event['titulo']); ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php } elseif ($currentView === 'week') {
                                // Lógica para la vista semanal
                                $date = new DateTime("$currentYear-$currentMonth-$currentDay");
                                $startOfWeek = clone $date;
                                $startOfWeek->modify('monday this week');
                                $endOfWeek = clone $startOfWeek;
                                $endOfWeek->modify('+6 days');

                                $weekDays = [];
                                $interval = new DateInterval('P1D');
                                $period = new DatePeriod($startOfWeek, $interval, $endOfWeek->modify('+1 day'));

                                foreach ($period as $day) {
                                    $dayData = [
                                        'date' => $day->format('Y-m-d'),
                                        'day_name' => $monthNames[$day->format('n')] . ' ' . $day->format('j'),
                                        'routines' => [],
                                        'events' => []
                                    ];

                                    $dayOfWeek = strtolower($day->format('l'));
                                    $dayInSpanish = [
                                        'monday' => 'lunes',
                                        'tuesday' => 'martes',
                                        'wednesday' => 'miercoles',
                                        'thursday' => 'jueves',
                                        'friday' => 'viernes',
                                        'saturday' => 'sabado',
                                        'sunday' => 'domingo'
                                    ][$dayOfWeek];

                                    foreach ($petRoutines as $routine) {
                                        $diasSemana = explode(',', $routine['dias_semana']);
                                        if (in_array($dayInSpanish, $diasSemana)) {
                                            $dayData['routines'][] = $routine;
                                        }
                                    }

                                    foreach ($petEvents as $event) {
                                        if (date('Y-m-d', strtotime($event['fecha_evento'])) == $day->format('Y-m-d')) {
                                            $dayData['events'][] = $event;
                                        }
                                    }
                                    $weekDays[] = $dayData;
                                }
                                ?>
                                <div class="calendar-week-grid">
                                    <?php foreach ($weekDays as $dayData): ?>
                                        <div class="calendar-day-week-view <?php echo (date('Y-m-d') == $dayData['date']) ? 'today' : ''; ?>" data-date="<?php echo $dayData['date']; ?>" data-events='<?php echo json_encode(array_merge($dayData['routines'], $dayData['events'])); ?>'>
                                            <span class="day-number"><?php echo $dayData['day_name']; ?></span>
                                            <div class="day-events">
                                                <?php foreach ($dayData['routines'] as $routine): ?>
                                                    <div class="event-item routine-event clickable-event" data-event='<?php echo json_encode($routine); ?>'>
                                                        <span class="event-icon"><?php echo getActivityIcon($routine['tipo_actividad']); ?></span>
                                                        <span class="event-time"><?php echo date('H:i', strtotime($routine['hora_programada'])); ?></span>
                                                        <span class="event-title"><?php echo htmlspecialchars($routine['nombre_actividad']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php foreach ($dayData['events'] as $event): ?>
                                                    <div class="event-item calendar-event clickable-event" data-event='<?php echo json_encode($event); ?>'>
                                                        <span class="event-icon">🏥</span>
                                                        <span class="event-time"><?php echo date('H:i', strtotime($event['fecha_evento'])); ?></span>
                                                        <span class="event-title"><?php echo htmlspecialchars($event['titulo']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php } elseif ($currentView === 'day') {
                                // Lógica para la vista diaria
                                $date = new DateTime("$currentYear-$currentMonth-$currentDay");
                                $dayData = [
                                    'date' => $date->format('Y-m-d'),
                                    'day_name' => $monthNames[$date->format('n')] . ' ' . $date->format('j') . ', ' . $date->format('Y'),
                                    'routines' => [],
                                    'events' => []
                                ];

                                $dayOfWeek = strtolower($date->format('l'));
                                $dayInSpanish = [
                                    'monday' => 'lunes',
                                    'tuesday' => 'martes',
                                    'wednesday' => 'miercoles',
                                    'thursday' => 'jueves',
                                    'friday' => 'viernes',
                                    'saturday' => 'sabado',
                                    'sunday' => 'domingo'
                                ][$dayOfWeek];

                                foreach ($petRoutines as $routine) {
                                    $diasSemana = explode(',', $routine['dias_semana']);
                                    if (in_array($dayInSpanish, $diasSemana)) {
                                        $dayData['routines'][] = $routine;
                                    }
                                }

                                foreach ($petEvents as $event) {
                                    if (date('Y-m-d', strtotime($event['fecha_evento'])) == $date->format('Y-m-d')) {
                                        $dayData['events'][] = $event;
                                    }
                                }
                                ?>
                                <div class="calendar-day-view">
                                    <div class="calendar-day-single-view <?php echo (date('Y-m-d') == $dayData['date']) ? 'today' : ''; ?>" data-date="<?php echo $dayData['date']; ?>" data-events='<?php echo json_encode(array_merge($dayData['routines'], $dayData['events'])); ?>'>
                                        <span class="day-number">Eventos para <?php echo $dayData['day_name']; ?></span>
                                        <div class="day-events">
                                            <?php if (empty($dayData['routines']) && empty($dayData['events'])): ?>
                                                <p class="text-muted">No hay eventos ni rutinas para este día.</p>
                                            <?php else: ?>
                                                <?php foreach ($dayData['routines'] as $routine): ?>
                                                    <div class="event-item routine-event clickable-event" data-event='<?php echo json_encode($routine); ?>'>
                                                        <span class="event-icon"><?php echo getActivityIcon($routine['tipo_actividad']); ?></span>
                                                        <span class="event-time"><?php echo date('H:i', strtotime($routine['hora_programada'])); ?></span>
                                                        <span class="event-title"><?php echo htmlspecialchars($routine['nombre_actividad']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php foreach ($dayData['events'] as $event): ?>
                                                    <div class="event-item calendar-event clickable-event" data-event='<?php echo json_encode($event); ?>'>
                                                        <span class="event-icon">🏥</span>
                                                        <span class="event-time"><?php echo date('H:i', strtotime($event['fecha_evento'])); ?></span>
                                                        <span class="event-title"><?php echo htmlspecialchars($event['titulo']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
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
                                                <a href="../medical_records/download_pdf.php?id=<?php echo $record['id_historial']; ?>" class="btn btn-xs btn-info">Descargar PDF</a>
                                                <a href="../medical_records/edit_medical_record.php?id=<?php echo $record['id_historial']; ?>" class="btn btn-xs btn-primary">Editar</a>
                                                <a href="../medical_records/delete_medical_record.php?id=<?php echo $record['id_historial']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este registro médico? Esta acción no se puede deshacer.');');">Eliminar</a>
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
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($measurements as $measurement): ?>
                                                <tr>
                                                    <td><?php echo formatDateSpanish($measurement['fecha_medicion']); ?></td>
                                                    <td><?php echo htmlspecialchars($measurement['peso'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($measurement['altura'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($measurement['longitud'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($measurement['circunferencia_cuello'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($measurement['notas'] ?? '-'); ?></td>
                                                    <td>
                                                        <a href="edit_measurement.php?id=<?php echo $measurement['id_medida']; ?>" class="btn btn-xs btn-outline">Editar</a>
                                                        <a href="delete_measurement.php?id=<?php echo $measurement['id_medida']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta medida? Esta acción no se puede deshacer.');">Eliminar</a>
                                                    </td>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // Pasar los datos de las medidas a JavaScript para que charts.js los use
        const petMeasurements = <?php echo json_encode($measurements); ?>;
    </script>
    <script src="../../js/app.js?v=1.7"></script>
    <script src="../../js/charts.js?v=1.1"></script>