<?php
/**
 * PetDay - Página de Calendario
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
$pets = getUserPets($userId);

// Variables para el header.php
$isLoggedIn = true;

// Lógica para el calendario
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

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
        'routines' => [], // Se llenará más tarde
        'events' => []    // Se llenará más tarde
    ];
    $dayCounter++;
}

// Obtener todas las rutinas y eventos para el mes actual
$allRoutines = [];
$allEvents = [];
foreach ($pets as $pet) {
    $petRoutines = getPetRoutines($pet['id_mascota']);
    foreach ($petRoutines as $routine) {
        $allRoutines[] = array_merge($routine, ['pet_name' => $pet['nombre'], 'pet_id' => $pet['id_mascota']]);
    }
    $petEvents = getUpcomingEvents($pet['id_mascota'], 31); // Obtener eventos para el mes
    foreach ($petEvents as $event) {
        // Filtrar eventos que caen dentro del mes actual
        $eventMonth = date('n', strtotime($event['fecha_evento']));
        $eventYear = date('Y', strtotime($event['fecha_evento']));
        if ($eventMonth == $currentMonth && $eventYear == $currentYear) {
            $allEvents[] = array_merge($event, ['pet_name' => $pet['nombre'], 'pet_id' => $pet['id_mascota']]);
        }
    }
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

    foreach ($allRoutines as $routine) {
        $diasSemana = explode(',', $routine['dias_semana']);
        if (in_array($dayInSpanish, $diasSemana)) {
            $dayData['routines'][] = $routine;
        }
    }

    foreach ($allEvents as $event) {
        if (date('Y-m-d', strtotime($event['fecha_evento'])) == $dateStr) {
            $dayData['events'][] = $event;
        }
    }
}
unset($dayData); // Romper la referencia del último elemento

// Navegación del calendario
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

$monthNames = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - PetDay</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.12">
    <link rel="icon" href="../../images/favicon.png" type="image/png">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <main class="main-content">
        <section class="calendar-page">
            <div class="container">
                <div class="page-header">
                    <h2>Calendario de Actividades</h2>
                    <div class="calendar-nav">
                        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline">Anterior</a>
                        <h3><?php echo $monthNames[$currentMonth] . ' ' . $currentYear; ?></h3>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline">Siguiente</a>
                    </div>
                </div>

                <div class="calendar-grid">
                    <div class="calendar-day-header"><span class="day-full">Lunes</span><span class="day-abbr">L</span></div>
                    <div class="calendar-day-header"><span class="day-full">Martes</span><span class="day-abbr">M</span></div>
                    <div class="calendar-day-header"><span class="day-full">Miércoles</span><span class="day-abbr">X</span></div>
                    <div class="calendar-day-header"><span class="day-full">Jueves</span><span class="day-abbr">J</span></div>
                    <div class="calendar-day-header"><span class="day-full">Viernes</span><span class="day-abbr">V</span></div>
                    <div class="calendar-day-header"><span class="day-full">Sábado</span><span class="day-abbr">S</span></div>
                    <div class="calendar-day-header"><span class="day-full">Domingo</span><span class="day-abbr">D</span></div>

                    <?php foreach ($calendar as $dayData): ?>
                        <?php if ($dayData === null): ?>
                            <div class="calendar-day empty"></div>
                        <?php else: ?>
                            <div class="calendar-day <?php echo (date('Y-m-d') == $dayData['date']) ? 'today' : ''; ?>" 
                                 data-date="<?php echo $dayData['date']; ?>"
                                 data-events='<?php echo json_encode(array_merge($dayData['routines'], $dayData['events'])); ?>'>
                                <span class="day-number"><?php echo $dayData['day']; ?></span>
                                <div class="day-events">
                                    <?php $colorIndex = 1; // Inicializar el índice de color ?>
                                    <?php foreach ($dayData['routines'] as $routine): ?>
                                        <div class="event-item routine-event routine-bg-<?php echo $colorIndex; ?>">
                                            <span class="event-icon"><?php echo getActivityIcon($routine['tipo_actividad']); ?></span>
                                            <span class="event-time"><?php echo date('H:i', strtotime($routine['hora_programada'])); ?></span>
                                            <span class="event-title"><?php echo htmlspecialchars($routine['nombre_actividad']); ?> (<?php echo htmlspecialchars($routine['pet_name']); ?>)</span>
                                        </div>
                                        <?php 
                                            $colorIndex++;
                                            if ($colorIndex > 5) $colorIndex = 1; // Reiniciar el índice si excede 5
                                        ?>
                                    <?php endforeach; ?>
                                    <?php foreach ($dayData['events'] as $event): ?>
                                        <div class="event-item calendar-event">
                                            <span class="event-icon">🏥</span>
                                            <span class="event-time"><?php echo date('H:i', strtotime($event['fecha_evento'])); ?></span>
                                            <span class="event-title"><?php echo htmlspecialchars($event['titulo']); ?> (<?php echo htmlspecialchars($event['pet_name']); ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                
            </div>
        </section>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="../../js/app.js?v=1.5"></script>

    <!-- Modal para detalles del día -->
    <div id="dayDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalDayTitle"></h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="modalEventsList">
                <!-- Los eventos se cargarán aquí con JS -->
            </div>
        </div>
    </div>
</body>
</html>
