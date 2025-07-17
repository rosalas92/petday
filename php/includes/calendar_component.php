<?php
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

<div class="calendar-grid">
    <div class="calendar-day-header">Lunes</div>
    <div class="calendar-day-header">Martes</div>
    <div class="calendar-day-header">Miércoles</div>
    <div class="calendar-day-header">Jueves</div>
    <div class="calendar-day-header">Viernes</div>
    <div class="calendar-day-header">Sábado</div>
    <div class="calendar-day-header">Domingo</div>

    <?php foreach ($calendar as $dayData): ?>
        <?php if ($dayData === null): ?>
            <div class="calendar-day empty"></div>
        <?php else: ?>
            <div class="calendar-day <?php echo (date('Y-m-d') == $dayData['date']) ? 'today' : ''; ?>">
                <span class="day-number"><?php echo $dayData['day']; ?></span>
                <div class="day-events">
                    <?php foreach ($dayData['routines'] as $routine): ?>
                        <div class="event-item routine-event">
                            <span class="event-icon"><?php echo getActivityIcon($routine['tipo_actividad']); ?></span>
                            <span class="event-time"><?php echo date('H:i', strtotime($routine['hora_programada'])); ?></span>
                            <span class="event-title"><?php echo htmlspecialchars($routine['nombre_actividad']); ?> (<?php echo htmlspecialchars($routine['pet_name']); ?>)</span>
                        </div>
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