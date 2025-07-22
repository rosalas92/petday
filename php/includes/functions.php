<?php
/**
 * PetDay - Funciones Principales
 * Funciones auxiliares para toda la aplicación
 */

require_once __DIR__ . '/../../config/database_config.php';

/**
 * Función para hash de contraseñas
 * @param string $password Contraseña en texto plano
 * @return string Hash de la contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Función para verificar contraseñas
 * @param string $password Contraseña en texto plano
 * @param string $hash Hash almacenado
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Función para sanitizar entrada de usuario
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Función para generar token aleatorio
 * @param int $length Longitud del token
 * @return string Token generado
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Función para validar email
 * @param string $email Email a validar
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Función para obtener usuario por ID
 * @param int $userId ID del usuario
 * @return array|false Datos del usuario
 */
function getUserById($userId) {
    $sql = "SELECT id_usuario, nombre_completo, email, telefono, rol, fecha_registro 
            FROM usuarios 
            WHERE id_usuario = ? AND activo = 1";
    return fetchOne($sql, [$userId]);
}

/**
 * Función para obtener usuario por email
 * @param string $email Email del usuario
 * @return array|false Datos del usuario
 */
function getUserByEmail($email) {
    $sql = "SELECT id_usuario, nombre_completo, email, password_hash, telefono, rol 
            FROM usuarios 
            WHERE email = ? AND activo = 1";
    return fetchOne($sql, [$email]);
}

/**
 * Función para crear nuevo usuario
 * @param array $userData Datos del usuario
 * @return int ID del usuario creado
 */
function createUser($userData) {
    $sql = "INSERT INTO usuarios (nombre_completo, email, password_hash, telefono, rol) 
            VALUES (?, ?, ?, ?, ?)";
    
    $hashedPassword = hashPassword($userData['password']);
    
    return insertAndGetId($sql, [
        $userData['nombre_completo'],
        $userData['email'],
        $hashedPassword,
        $userData['telefono'] ?? null,
        $userData['rol'] ?? 'usuario'
    ]);
}

/**
 * Función para obtener mascotas de un usuario
 * @param int $userId ID del usuario
 * @return array Lista de mascotas
 */
function getUserPets($userId) {
    $sql = "SELECT id_mascota, nombre, especie, raza, edad, peso, genero, foto, fecha_creacion
            FROM mascotas 
            WHERE id_usuario = ?
            ORDER BY fecha_creacion DESC";
    return fetchAll($sql, [$userId]);
}

/**
 * Función para obtener mascota por ID
 * @param int $petId ID de la mascota
 * @param int $userId ID del usuario (para verificar propiedad)
 * @return array|false Datos de la mascota
 */
function getPetById($petId, $userId = null) {
    $sql = "SELECT * FROM mascotas WHERE id_mascota = ?";
    $params = [$petId];
    
    if ($userId !== null) {
        $sql .= " AND id_usuario = ?";
        $params[] = $userId;
    }
    
    return fetchOne($sql, $params);
}

/**
 * Función para crear nueva mascota
 * @param array $petData Datos de la mascota
 * @return int ID de la mascota creada
 */
function createPet($petData) {
    $sql = "INSERT INTO mascotas (id_usuario, nombre, especie, raza, edad, peso, genero, foto, historial_medico) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    return insertAndGetId($sql, [
        $petData['id_usuario'],
        $petData['nombre'],
        $petData['especie'],
        $petData['raza'] ?? null,
        $petData['edad'] ?? null,
        $petData['peso'] ?? null,
        $petData['genero'],
        $petData['foto'] ?? null,
        $petData['historial_medico'] ?? null
    ]);
}

/**
 * Función para obtener rutinas de hoy para una mascota
 * @param int $petId ID de la mascota
 * @return array Rutinas de hoy
 */
function getTodayRoutines($petId) {
    $dayOfWeek = strtolower(date('l')); // Día de la semana en inglés
    $dayInSpanish = [
        'monday' => 'lunes',
        'tuesday' => 'martes', 
        'wednesday' => 'miercoles',
        'thursday' => 'jueves',
        'friday' => 'viernes',
        'saturday' => 'sabado',
        'sunday' => 'domingo'
    ][$dayOfWeek];
    
    $sql = "SELECT * FROM rutinas 
            WHERE id_mascota = ? 
            AND activa = 1 
            AND FIND_IN_SET(?, dias_semana)
            ORDER BY hora_programada ASC";
    
    return fetchAll($sql, [$petId, $dayInSpanish]);
}

/**
 * Función para verificar si una rutina está completada hoy
 * @param int $routineId ID de la rutina
 * @return bool
 */
function isRoutineCompletedToday($routineId) {
    $today = date('Y-m-d');
    $sql = "SELECT COUNT(*) as count FROM seguimiento_actividades 
            WHERE id_rutina = ? AND fecha_realizada = ? AND completada = 1";
    
    $result = fetchOne($sql, [$routineId, $today]);
    return $result['count'] > 0;
}

/**
 * Función para marcar rutina como completada
 * @param int $routineId ID de la rutina
 * @return bool Éxito de la operación
 */
function markRoutineComplete($routineId) {
    $today = date('Y-m-d');
    $now = date('H:i:s');
    
    // Verificar si ya está marcada como completada hoy
    if (isRoutineCompletedToday($routineId)) {
        return false;
    }
    
    $sql = "INSERT INTO seguimiento_actividades (id_rutina, fecha_realizada, hora_realizada, completada) 
            VALUES (?, ?, ?, 1)";
    
    try {
        executeQuery($sql, [$routineId, $today, $now]);
        return true;
    } catch (Exception $e) {
        error_log("Error al marcar rutina completada: " . $e->getMessage());
        return false;
    }
}

/**
 * Función para crear nueva rutina
 * @param array $routineData Datos de la rutina
 * @return int ID de la rutina creada
 */
function createRoutine($routineData) {
    $sql = "INSERT INTO rutinas (id_mascota, tipo_actividad, nombre_actividad, descripcion, hora_programada, dias_semana) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    return insertAndGetId($sql, [
        $routineData['id_mascota'],
        $routineData['tipo_actividad'],
        $routineData['nombre_actividad'],
        $routineData['descripcion'] ?? null,
        $routineData['hora_programada'],
        implode(',', $routineData['dias_semana'])
    ]);
}

/**
 * Función para obtener icono según tipo de actividad
 * @param string $activityType Tipo de actividad
 * @return string Emoji del icono
 */
function getActivityIcon($activityType) {
    $icons = [
        'comida' => '🍽️',
        'paseo' => '🚶',
        'juego' => '🎾',
        'medicacion' => '💊',
        'higiene' => '🛁',
        'entrenamiento' => '🎯'
    ];
    
    return $icons[$activityType] ?? '📋';
}

/**
 * Función para obtener emoji según especie
 * @param string $species Especie de la mascota
 * @return string Emoji de la especie
 */
function getPetEmoji($species) {
    $emojis = [
        'perro' => '🐕',
        'gato' => '🐱',
        'pajaro' => '🦜',
        'otro' => '🐾'
    ];
    
    return $emojis[$species] ?? '🐾';
}

/**
 * Función para subir archivo de imagen
 * @param array $file Archivo $_FILES
 * @param string $targetDir Directorio destino
 * @return string|false Nombre del archivo o false si hay error
 */
function uploadImage($file, $targetDir = 'uploads/pet_photos/') {
    // Verificar que se subió correctamente
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }
    
    // Verificar tamaño (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $fileName;
    
    // Crear directorio si no existe
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    
    return false;
}

/**
 * Función para subir archivos (imágenes y PDFs).
 * @param array $file Archivo $_FILES.
 * @param string $targetDir Directorio destino.
 * @return string|false Nombre del archivo o false si hay error.
 */
function uploadDocument($file, $targetDir = 'uploads/medical_records/') {
    // Verificar que se subió correctamente
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }
    
    // Verificar tamaño (máximo 10MB para documentos)
    if ($file['size'] > 10 * 1024 * 1024) {
        return false;
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $fileName;
    
    // Crear directorio si no existe
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    
    return false;
}

/**
 * Función para crear un nuevo registro médico.
 * @param array $recordData Datos del registro médico.
 * @return int ID del registro creado.
 */
function createMedicalRecord($recordData) {
    $sql = "INSERT INTO historial_medico (id_mascota, tipo_registro, fecha_registro, titulo, descripcion, veterinario, clinica, medicamentos, dosis, observaciones, archivo_adjunto) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    return insertAndGetId($sql, [
        $recordData['id_mascota'],
        $recordData['tipo_registro'],
        $recordData['fecha_registro'],
        $recordData['titulo'],
        $recordData['descripcion'] ?? null,
        $recordData['veterinario'] ?? null,
        $recordData['clinica'] ?? null,
        $recordData['medicamentos'] ?? null,
        $recordData['dosis'] ?? null,
        $recordData['observaciones'] ?? null,
        $recordData['archivo_adjunto'] ?? null
    ]);
}

/**
 * Función para obtener registros médicos de una mascota.
 * @param int $petId ID de la mascota.
 * @return array Lista de registros médicos.
 */
function getMedicalRecords($petId) {
    $sql = "SELECT * FROM historial_medico WHERE id_mascota = ? ORDER BY fecha_registro DESC";
    return fetchAll($sql, [$petId]);
}

/**
 * Función para obtener eventos próximos de una mascota
 * @param int $petId ID de la mascota
 * @param int $days Número de días a futuro
 * @return array Lista de eventos
 */
function getUpcomingEvents($petId, $days = 30) {
    $endDate = date('Y-m-d', strtotime("+{$days} days"));
    
    $sql = "SELECT * FROM eventos 
            WHERE id_mascota = ? 
            AND fecha_evento >= CURDATE() 
            AND fecha_evento <= ?
            AND completado = 0
            ORDER BY fecha_evento ASC";
    
    return fetchAll($sql, [$petId, $endDate]);
}

/**
 * Función para crear nuevo evento
 * @param array $eventData Datos del evento
 * @return int ID del evento creado
 */
function createEvent($eventData) {
    $sql = "INSERT INTO eventos (id_mascota, tipo_evento, titulo, descripcion, fecha_evento) 
            VALUES (?, ?, ?, ?, ?)";
    
    return insertAndGetId($sql, [
        $eventData['id_mascota'],
        $eventData['tipo_evento'],
        $eventData['titulo'],
        $eventData['descripcion'] ?? null,
        $eventData['fecha_evento']
    ]);
}

/**
 * Función para obtener estadísticas de una mascota
 * @param int $petId ID de la mascota
 * @param int $days Número de días atrás
 * @return array Estadísticas
 */
function getPetStats($petId, $days = 30) {
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    
    // Total de rutinas completadas
    $sql = "SELECT COUNT(*) as total_completed
            FROM seguimiento_actividades sa
            JOIN rutinas r ON sa.id_rutina = r.id_rutina
            WHERE r.id_mascota = ? 
            AND sa.fecha_realizada >= ?
            AND sa.completada = 1";
    
    $completed = fetchOne($sql, [$petId, $startDate]);
    
    // Total de rutinas programadas
    $sql = "SELECT COUNT(*) as total_scheduled
            FROM rutinas 
            WHERE id_mascota = ? 
            AND activa = 1";
    
    $scheduled = fetchOne($sql, [$petId]);
    
    // Actividades por tipo
    $sql = "SELECT r.tipo_actividad, COUNT(*) as count
            FROM seguimiento_actividades sa
            JOIN rutinas r ON sa.id_rutina = r.id_rutina
            WHERE r.id_mascota = ? 
            AND sa.fecha_realizada >= ?
            AND sa.completada = 1
            GROUP BY r.tipo_actividad";
    
    $byType = fetchAll($sql, [$petId, $startDate]);
    
    return [
        'total_completed' => $completed['total_completed'],
        'total_scheduled' => $scheduled['total_scheduled'],
        'completion_rate' => $scheduled['total_scheduled'] > 0 ? 
            round(($completed['total_completed'] / ($scheduled['total_scheduled'] * $days)) * 100, 2) : 0,
        'by_type' => $byType
    ];
}

/**
 * Función para enviar notificación (placeholder para futuras implementaciones)
 * @param int $userId ID del usuario
 * @param string $title Título de la notificación
 * @param string $message Mensaje
 * @param string $type Tipo de notificación
 * @return bool
 */
function sendNotification($userId, $title, $message, $type = 'recordatorio') {
    $sql = "INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, fecha_envio) 
            VALUES (?, ?, ?, ?, NOW())";
    
    try {
        executeQuery($sql, [$userId, $type, $title, $message]);
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar notificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Función para obtener notificaciones no leídas
 * @param int $userId ID del usuario
 * @return array Lista de notificaciones
 */
function getUnreadNotifications($userId) {
    $sql = "SELECT * FROM notificaciones 
            WHERE id_usuario = ? 
            AND leida = 0 
            ORDER BY fecha_envio DESC 
            LIMIT 10";
    
    return fetchAll($sql, [$userId]);
}

/**
 * Función para marcar notificación como leída
 * @param int $notificationId ID de la notificación
 * @return bool
 */
function markNotificationRead($notificationId) {
    $sql = "UPDATE notificaciones SET leida = 1 WHERE id_notificacion = ?";
    
    try {
        executeQuery($sql, [$notificationId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Función para verificar si el usuario es propietario de la mascota
 * @param int $userId ID del usuario
 * @param int $petId ID de la mascota
 * @return bool
 */
function isUserPetOwner($userId, $petId) {
    $sql = "SELECT COUNT(*) as count FROM mascotas WHERE id_mascota = ? AND id_usuario = ?";
    $result = fetchOne($sql, [$petId, $userId]);
    return $result['count'] > 0;
}

/**
 * Función para log de errores personalizado
 * @param string $message Mensaje de error
 * @param string $file Archivo donde ocurrió
 * @param int $line Línea donde ocurrió
 */
function logError($message, $file = '', $line = '') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message";
    
    if ($file) {
        $logMessage .= " in $file";
    }
    
    if ($line) {
        $logMessage .= " on line $line";
    }
    
    error_log($logMessage . PHP_EOL, 3, 'logs/app_errors.log');
}

/**
 * Función para validar datos de mascota
 * @param array $data Datos a validar
 * @return array Errores encontrados
 */
function validatePetData($data) {
    $errors = [];
    
    if (empty($data['nombre']) || strlen($data['nombre']) < 2) {
        $errors[] = "El nombre debe tener al menos 2 caracteres";
    }
    
    $validSpecies = ['perro', 'gato', 'pajaro', 'otro'];
    if (empty($data['especie']) || !in_array($data['especie'], $validSpecies)) {
        $errors[] = "Debe seleccionar una especie válida";
    }
    
    $validGenders = ['macho', 'hembra'];
    if (empty($data['genero']) || !in_array($data['genero'], $validGenders)) {
        $errors[] = "Debe seleccionar un género válido";
    }
    
    if (!empty($data['edad']) && (!is_numeric($data['edad']) || $data['edad'] < 0 || $data['edad'] > 50)) {
        $errors[] = "La edad debe ser un número entre 0 y 50";
    }
    
    if (!empty($data['peso']) && (!is_numeric($data['peso']) || $data['peso'] <= 0 || $data['peso'] > 200)) {
        $errors[] = "El peso debe ser un número entre 0.1 y 200 kg";
    }
    
    return $errors;
}

/**
 * Función para actualizar los datos de una mascota
 * @param int $petId ID de la mascota a actualizar
 * @param array $petData Nuevos datos de la mascota
 * @return bool Éxito de la operación
 */
function updatePet($petId, $petData) {
    $sql = "UPDATE mascotas SET 
                nombre = ?,
                especie = ?,
                raza = ?,
                edad = ?,
                peso = ?,
                genero = ?";
    
    $params = [
        $petData['nombre'],
        $petData['especie'],
        $petData['raza'] ?? null,
        $petData['edad'] ?? null,
        $petData['peso'] ?? null,
        $petData['genero']
    ];

    // Solo actualizar la foto si se proporciona una nueva
    if (!empty($petData['foto'])) {
        $sql .= ", foto = ?";
        $params[] = $petData['foto'];
    }

    $sql .= " WHERE id_mascota = ?";
    $params[] = $petId;

    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para eliminar una mascota
 * @param int $petId ID de la mascota
 * @param int $userId ID del usuario para verificación
 * @return bool
 */
function deletePet($petId, $userId) {
    // Primero, obtener datos de la mascota para poder borrar la foto
    $pet = getPetById($petId, $userId);
    if (!$pet) {
        return false; // La mascota no existe o no pertenece al usuario
    }

    $sql = "DELETE FROM mascotas WHERE id_mascota = ? AND id_usuario = ?";
    try {
        $stmt = executeQuery($sql, [$petId, $userId]);
        
        // Si la eliminación fue exitosa, borrar la foto del servidor
        if ($stmt->rowCount() > 0 && !empty($pet['foto'])) {
            $photoPath = __DIR__ . '/../../uploads/pet_photos/' . $pet['foto'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para validar datos de rutina
 * @param array $data Datos a validar
 * @return array Errores encontrados
 */
function validateRoutineData($data) {
    $errors = [];
    
    if (empty($data['nombre_actividad']) || strlen($data['nombre_actividad']) < 3) {
        $errors[] = "El nombre de la actividad debe tener al menos 3 caracteres";
    }
    
    $validTypes = ['comida', 'paseo', 'juego', 'medicacion', 'higiene', 'entrenamiento'];
    if (empty($data['tipo_actividad']) || !in_array($data['tipo_actividad'], $validTypes)) {
        $errors[] = "Debe seleccionar un tipo de actividad válido";
    }
    
    if (empty($data['hora_programada']) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['hora_programada'])) {
        $errors[] = "Debe especificar una hora válida (HH:MM)";
    }
    
    if (empty($data['dias_semana']) || !is_array($data['dias_semana'])) {
        $errors[] = "Debe seleccionar al menos un día de la semana";
    }
    
    return $errors;
}

/**
 * Función para obtener todas las rutinas de una mascota
 * @param int $petId ID de la mascota
 * @return array
 */
function getPetRoutines($petId) {
    $sql = "SELECT * FROM rutinas WHERE id_mascota = ? ORDER BY hora_programada ASC";
    return fetchAll($sql, [$petId]);
}

/**
 * Función para obtener una rutina por su ID
 * @param int $routineId ID de la rutina
 * @return array|false
 */
function getRoutineById($routineId) {
    $sql = "SELECT * FROM rutinas WHERE id_rutina = ?";
    return fetchOne($sql, [$routineId]);
}

/**
 * Función para actualizar una rutina
 * @param int $routineId ID de la rutina
 * @param array $routineData Datos de la rutina
 * @return bool
 */
function updateRoutine($routineId, $routineData) {
    $sql = "UPDATE rutinas SET 
                tipo_actividad = ?,
                nombre_actividad = ?,
                descripcion = ?,
                hora_programada = ?,
                dias_semana = ?
            WHERE id_rutina = ?";
    
    $params = [
        $routineData['tipo_actividad'],
        $routineData['nombre_actividad'],
        $routineData['descripcion'] ?? null,
        $routineData['hora_programada'],
        implode(',', $routineData['dias_semana']),
        $routineId
    ];

    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para eliminar una rutina
 * @param int $routineId ID de la rutina
 * @return bool
 */
function deleteRoutine($routineId) {
    $sql = "DELETE FROM rutinas WHERE id_rutina = ?";
    try {
        $stmt = executeQuery($sql, [$routineId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para validar datos de un evento
 * @param array $data Datos a validar
 * @return array Errores encontrados
 */
function validateEventData($data) {
    $errors = [];

    if (empty($data['titulo']) || strlen($data['titulo']) < 3) {
        $errors[] = "El título debe tener al menos 3 caracteres.";
    }

    $validTypes = ['vacuna', 'veterinario', 'baño', 'corte_uñas', 'desparasitacion', 'revision', 'otro'];
    if (empty($data['tipo_evento']) || !in_array($data['tipo_evento'], $validTypes)) {
        $errors[] = "Debe seleccionar un tipo de evento válido.";
    }

    if (empty($data['fecha_evento'])) {
        $errors[] = "Debe especificar una fecha y hora para el evento.";
    } else {
        // Opcional: validar que la fecha no sea en el pasado
        $eventDate = new DateTime($data['fecha_evento']);
        $now = new DateTime();
        if ($eventDate < $now) {
            $errors[] = "La fecha del evento no puede ser en el pasado.";
        }
    }

    return $errors;
}

/**
 * Función para obtener un evento por su ID
 * @param int $eventId ID del evento
 * @return array|false
 */
function getEventById($eventId) {
    $sql = "SELECT * FROM eventos WHERE id_evento = ?";
    return fetchOne($sql, [$eventId]);
}

/**
 * Función para actualizar un evento
 * @param int $eventId ID del evento
 * @param array $eventData Datos del evento
 * @return bool
 */
function updateEvent($eventId, $eventData) {
    $sql = "UPDATE eventos SET 
                titulo = ?,
                tipo_evento = ?,
                fecha_evento = ?,
                descripcion = ?
            WHERE id_evento = ?";
    
    $params = [
        $eventData['titulo'],
        $eventData['tipo_evento'],
        $eventData['fecha_evento'],
        $eventData['descripcion'] ?? null,
        $eventId
    ];

    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para eliminar un evento
 * @param int $eventId ID del evento
 * @return bool
 */
function deleteEvent($eventId) {
    $sql = "DELETE FROM eventos WHERE id_evento = ?";
    try {
        $stmt = executeQuery($sql, [$eventId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para validar datos de un contacto veterinario
 * @param array $data Datos a validar
 * @return array Errores encontrados
 */
function validateVetContactData($data) {
    $errors = [];

    if (empty($data['nombre']) || strlen($data['nombre']) < 3) {
        $errors[] = "El nombre del contacto debe tener al menos 3 caracteres.";
    }

    if (!empty($data['email']) && !isValidEmail($data['email'])) {
        $errors[] = "El formato del email no es válido.";
    }

    // Puedes añadir más validaciones para teléfono, etc.

    return $errors;
}

/**
 * Función para crear un nuevo contacto veterinario
 * @param array $contactData Datos del contacto
 * @return int ID del contacto creado
 */
function createVetContact($contactData) {
    $sql = "INSERT INTO contactos_veterinarios (id_usuario, nombre, clinica, telefono, email, direccion, especialidad, notas, es_principal) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    return insertAndGetId($sql, [
        $contactData['id_usuario'],
        $contactData['nombre'],
        $contactData['clinica'] ?? null,
        $contactData['telefono'] ?? null,
        $contactData['email'] ?? null,
        $contactData['direccion'] ?? null,
        $contactData['especialidad'] ?? null,
        $contactData['notas'] ?? null,
        $contactData['es_principal'] ?? 0
    ]);
}

/**
 * Función para obtener todos los contactos veterinarios de un usuario
 * @param int $userId ID del usuario
 * @return array Lista de contactos
 */
function getAllVetContacts($userId) {
    $sql = "SELECT * FROM contactos_veterinarios WHERE id_usuario = ? AND activo = 1 ORDER BY nombre ASC";
    return fetchAll($sql, [$userId]);
}

/**
 * Función para obtener un contacto veterinario por ID
 * @param int $contactId ID del contacto
 * @param int $userId ID del usuario (para verificar propiedad)
 * @return array|false Datos del contacto
 */
function getVetContactById($contactId, $userId = null) {
    $sql = "SELECT * FROM contactos_veterinarios WHERE id_contacto = ?";
    $params = [$contactId];
    
    if ($userId !== null) {
        $sql .= " AND id_usuario = ?";
        $params[] = $userId;
    }
    
    return fetchOne($sql, $params);
}

/**
 * Función para actualizar un contacto veterinario
 * @param int $contactId ID del contacto
 * @param array $contactData Nuevos datos del contacto
 * @return bool Éxito de la operación
 */
function updateVetContact($contactId, $contactData) {
    $sql = "UPDATE contactos_veterinarios SET 
                nombre = ?,
                clinica = ?,
                telefono = ?,
                email = ?,
                direccion = ?,
                especialidad = ?,
                notas = ?,
                es_principal = ?
            WHERE id_contacto = ?";
    
    $params = [
        $contactData['nombre'],
        $contactData['clinica'] ?? null,
        $contactData['telefono'] ?? null,
        $contactData['email'] ?? null,
        $contactData['direccion'] ?? null,
        $contactData['especialidad'] ?? null,
        $contactData['notas'] ?? null,
        $contactData['es_principal'] ?? 0,
        $contactId
    ];

    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para eliminar un contacto veterinario
 * @param int $contactId ID del contacto
 * @param int $userId ID del usuario para verificación
 * @return bool
 */
function deleteVetContact($contactId, $userId) {
    $sql = "DELETE FROM contactos_veterinarios WHERE id_contacto = ? AND id_usuario = ?";
    try {
        $stmt = executeQuery($sql, [$contactId, $userId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para enviar un correo electrónico de registro.
 * @param string $to Email del destinatario.
 * @param string $subject Asunto del correo.
 * @param string $message Cuerpo del correo.
 * @return bool True si el correo se envió con éxito, false en caso contrario.
 */
function sendRegistrationEmail($to, $username) {
    $subject = "¡Bienvenido a PetDay, $username!";
    $message = "
        <html>
        <head>
          <title>¡Bienvenido a PetDay!</title>
        </head>
        <body>
          <p>Hola <strong>$username</strong>,</p>
          <p>¡Gracias por registrarte en PetDay! Estamos emocionados de ayudarte a organizar la rutina de tus mascotas.</p>
          <p>Puedes iniciar sesión en tu cuenta aquí: <a href=\"http://localhost/petday/php/auth/login.php\">Iniciar Sesión en PetDay</a></p>
          <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
          <p>Saludos cordiales,</p>
          <p>El equipo de PetDay</p>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: PetDay <no-reply@petday.com>' . "\r\n";

    // Usar @ para suprimir errores si la configuración de mail() no es correcta
    return @mail($to, $subject, $message, $headers);
}

/**
 * Función para crear una nueva medida de mascota.
 * @param array $measurementData Datos de la medida.
 * @return int ID de la medida creada.
 */
function createPetMeasurement($measurementData) {
    $sql = "INSERT INTO medidas_mascota (id_mascota, peso, altura, longitud, circunferencia_cuello, fecha_medicion, notas) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    return insertAndGetId($sql, [
        $measurementData['id_mascota'],
        $measurementData['peso'] ?? null,
        $measurementData['altura'] ?? null,
        $measurementData['longitud'] ?? null,
        $measurementData['circunferencia_cuello'] ?? null,
        $measurementData['fecha_medicion'],
        $measurementData['notas'] ?? null
    ]);
}

/**
 * Función para obtener las medidas de una mascota.
 * @param int $petId ID de la mascota.
 * @param int $limit Límite de resultados.
 * @return array Lista de medidas.
 */
function getPetMeasurements($petId, $limit = 10) {
    $sql = "SELECT * FROM medidas_mascota WHERE id_mascota = ? ORDER BY fecha_medicion DESC LIMIT ?";
    return fetchAll($sql, [$petId, $limit]);
}

/**
 * Función para obtener estadísticas de entrenamiento de una mascota.
 * @param int $petId ID de la mascota.
 * @param int $days Número de días atrás para el cálculo.
 * @return array Estadísticas de entrenamiento.
 */
function getTrainingStats($petId, $days = 30) {
    $startDate = date('Y-m-d', strtotime("-{$days} days"));

    // Total de sesiones de entrenamiento completadas
    $sqlTotal = "SELECT COUNT(*) as total_training_sessions
                 FROM seguimiento_actividades sa
                 JOIN rutinas r ON sa.id_rutina = r.id_rutina
                 WHERE r.id_mascota = ? 
                 AND sa.fecha_realizada >= ?
                 AND sa.completada = 1
                 AND r.tipo_actividad = 'entrenamiento'";
    $totalTraining = fetchOne($sqlTotal, [$petId, $startDate]);

    // Paseos de entrenamiento
    $sqlWalks = "SELECT COUNT(*) as training_walks
                 FROM seguimiento_actividades sa
                 JOIN rutinas r ON sa.id_rutina = r.id_rutina
                 WHERE r.id_mascota = ? 
                 AND sa.fecha_realizada >= ?
                 AND sa.completada = 1
                 AND r.tipo_actividad = 'entrenamiento'
                 AND r.nombre_actividad LIKE '%paseo%'"; // Asumiendo que el nombre de la actividad contiene 'paseo'
    $trainingWalks = fetchOne($sqlWalks, [$petId, $startDate]);

    // Juegos de entrenamiento
    $sqlGames = "SELECT COUNT(*) as training_games
                 FROM seguimiento_actividades sa
                 JOIN rutinas r ON sa.id_rutina = r.id_rutina
                 WHERE r.id_mascota = ? 
                 AND sa.fecha_realizada >= ?
                 AND sa.completada = 1
                 AND r.tipo_actividad = 'entrenamiento'
                 AND r.nombre_actividad LIKE '%juego%'"; // Asumiendo que el nombre de la actividad contiene 'juego'
    $trainingGames = fetchOne($sqlGames, [$petId, $startDate]);

    return [
        'total_training_sessions' => $totalTraining['total_training_sessions'],
        'training_walks' => $trainingWalks['training_walks'],
        'training_games' => $trainingGames['training_games']
    ];
}

/**
 * Función para eliminar un registro médico.
 * @param int $recordId ID del registro médico.
 * @param int $userId ID del usuario para verificación.
 * @return bool Éxito de la operación.
 */
function deleteMedicalRecord($recordId, $userId) {
    // Obtener el registro médico para poder borrar el archivo adjunto
    $record = getMedicalRecordById($recordId);
    if (!$record || !isUserPetOwner($userId, $record['id_mascota'])) {
        return false; // El registro no existe o no pertenece al usuario
    }

    $sql = "DELETE FROM historial_medico WHERE id_historial = ?";
    try {
        $stmt = executeQuery($sql, [$recordId]);
        
        // Si la eliminación fue exitosa, borrar el archivo adjunto del servidor
        if ($stmt->rowCount() > 0 && !empty($record['archivo_adjunto'])) {
            $filePath = __DIR__ . '/../../uploads/medical_records/' . $record['archivo_adjunto'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError("Error al eliminar registro médico: " . $e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para validar datos de un registro médico.
 * @param array $data Datos a validar.
 * @return array Errores encontrados.
 */
function validateMedicalRecordData($data) {
    $errors = [];

    if (empty($data['titulo']) || strlen($data['titulo']) < 3) {
        $errors[] = "El título del registro médico debe tener al menos 3 caracteres.";
    }

    $validTypes = ['vacuna', 'enfermedad', 'tratamiento', 'cirugia', 'revision', 'medicacion', 'alergia', 'otro'];
    if (empty($data['tipo_registro']) || !in_array($data['tipo_registro'], $validTypes)) {
        $errors[] = "Debe seleccionar un tipo de registro válido.";
    }

    if (empty($data['fecha_registro'])) {
        $errors[] = "La fecha del registro es obligatoria.";
    }

    return $errors;
}

/**
 * Función para obtener un registro médico por su ID.
 * @param int $recordId ID del registro médico.
 * @return array|false
 */
function getMedicalRecordById($recordId) {
    $sql = "SELECT * FROM historial_medico WHERE id_historial = ?";
    return fetchOne($sql, [$recordId]);
}

/**
 * Función para actualizar un registro médico.
 * @param int $recordId ID del registro médico.
 * @param array $recordData Nuevos datos del registro médico.
 * @return bool Éxito de la operación.
 */
function updateMedicalRecord($recordId, $recordData) {
    $sql = "UPDATE historial_medico SET 
                tipo_registro = ?,
                fecha_registro = ?,
                titulo = ?,
                descripcion = ?,
                veterinario = ?,
                clinica = ?,
                medicamentos = ?,
                dosis = ?,
                observaciones = ?,
                archivo_adjunto = ?
            WHERE id_historial = ?";
    
    $params = [
        $recordData['tipo_registro'],
        $recordData['fecha_registro'],
        $recordData['titulo'],
        $recordData['descripcion'] ?? null,
        $recordData['veterinario'] ?? null,
        $recordData['clinica'] ?? null,
        $recordData['medicamentos'] ?? null,
        $recordData['dosis'] ?? null,
        $recordData['observaciones'] ?? null,
        $recordData['archivo_adjunto'] ?? null,
        $recordId
    ];

    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError("Error al actualizar registro médico: " . $e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Función para obtener el último registro médico de un tipo específico para una mascota.
 * @param int $petId ID de la mascota.
 * @param string $type Tipo de registro (ej. 'vacuna', 'alergia').
 * @return array|false El último registro o false si no se encuentra.
 */
function getLatestMedicalRecordByType($petId, $type) {
    $sql = "SELECT * FROM historial_medico 
            WHERE id_mascota = ? AND tipo_registro = ? 
            ORDER BY fecha_registro DESC, id_historial DESC LIMIT 1";
    return fetchOne($sql, [$petId, $type]);
}

/**
 * Función para obtener todas las alergias registradas para una mascota.
 * @param int $petId ID de la mascota.
 * @return array Lista de alergias.
 */
function getAllergies($petId) {
    $sql = "SELECT * FROM historial_medico 
            WHERE id_mascota = ? AND tipo_registro = 'alergia' 
            ORDER BY fecha_registro DESC";
    return fetchAll($sql, [$petId]);
}

/**
 * Función para obtener las medicaciones activas para una mascota.
 * @param int $petId ID de la mascota.
 * @return array Lista de medicaciones activas.
 */
function getActiveMedications($petId) {
    $sql = "SELECT * FROM medicaciones 
            WHERE id_mascota = ? AND activa = 1 
            ORDER BY fecha_inicio DESC";
    return fetchAll($sql, [$petId]);
}

/**
 * Función para formatear fecha en español
 * @param string $date Fecha en formato Y-m-d
 * @return string Fecha formateada
 */
function formatDateSpanish($date) {
    $months = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day de $month de $year";
}
/**
 * Genera y guarda un token de restablecimiento de contraseña para un usuario.
 * @param int $userId ID del usuario.
 * @return string El token generado.
 */


/**
 * Valida un token de restablecimiento de contraseña.
 * @param string $token El token a validar.
 * @return array|false Los datos del usuario si el token es válido y no ha expirado, o false en caso contrario.
 */
function validateResetToken($token) {
    $user = fetchOne('SELECT id_usuario, reset_token_expires_at FROM usuarios WHERE reset_token = ?', [$token]);

    if ($user && strtotime($user['reset_token_expires_at']) > time()) {
        return $user;
    }
    return false;
}

/**
 * Actualiza la contraseña de un usuario y limpia el token de restablecimiento.
 * @param int $userId ID del usuario.
 * @param string $newPassword La nueva contraseña en texto plano.
 * @return bool True si la contraseña se actualizó con éxito, false en caso contrario.
 */
function updatePasswordAndClearToken($userId, $newPassword) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    try {
        executeQuery('UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id_usuario = ?', [$hashedPassword, $userId]);
        return true;
    } catch (Exception $e) {
        logError("Error al actualizar contraseña y limpiar token: " . $e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

/**
 * Crea un nuevo registro en la cartilla sanitaria.
 * @param int $petId ID de la mascota.
 * @param string $nombreDocumento Nombre del documento.
 * @param string $fechaDocumento Fecha del documento.
 * @param string $filePath Ruta del archivo.
 * @param string $tipoArchivo Tipo de archivo.
 * @return int ID del registro creado.
 */
function createCartillaRecord($petId, $nombreDocumento, $fechaDocumento, $filePath, $tipoArchivo) {
    $sql = "INSERT INTO cartilla_sanitaria (id_mascota, nombre_documento, fecha_documento, archivo_path, tipo_archivo) 
            VALUES (?, ?, ?, ?, ?)";
    
    return insertAndGetId($sql, [
        $petId,
        $nombreDocumento,
        $fechaDocumento,
        $filePath,
        $tipoArchivo
    ]);
}

/**
 * Obtiene todos los registros de la cartilla sanitaria de una mascota.
 * @param int $petId ID de la mascota.
 * @return array Lista de registros de la cartilla.
 */
function getCartillaRecordsByPetId($petId) {
    $sql = "SELECT * FROM cartilla_sanitaria WHERE id_mascota = ? ORDER BY fecha_documento DESC";
    return fetchAll($sql, [$petId]);
}

/**
 * Obtiene un registro de la cartilla sanitaria por su ID.
 * @param int $recordId ID del registro.
 * @return array|false Datos del registro o false si no se encuentra.
 */
function getCartillaRecordById($recordId) {
    $sql = "SELECT * FROM cartilla_sanitaria WHERE id_cartilla = ?";
    return fetchOne($sql, [$recordId]);
}

/**
 * Elimina un registro de la cartilla sanitaria.
 * @param int $recordId ID del registro.
 * @return bool Éxito de la operación.
 */
function deleteCartillaRecord($recordId) {
    $sql = "DELETE FROM cartilla_sanitaria WHERE id_cartilla = ?";
    try {
        $stmt = executeQuery($sql, [$recordId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError($e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

?>