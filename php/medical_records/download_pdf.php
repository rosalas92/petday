<?php
session_start();
require_once __DIR__ . '/../../config/database_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Cargar el autoloader de Composer

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$recordId) {
    die('ID de registro médico no proporcionado.');
}

$record = getMedicalRecordById($recordId);

if (!$record) {
    die('Registro médico no encontrado.');
}

// Verificar que el usuario es propietario de la mascota asociada al registro
if (!isUserPetOwner($userId, $record['id_mascota'])) {
    die('Acceso denegado.');
}

$pet = getPetById($record['id_mascota'], $userId);

// Configurar opciones de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Contenido HTML para el PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registro Médico - ' . htmlspecialchars($pet['nombre']) . '</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #4CAF50; }
        .section { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .section h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; margin-bottom: 15px; }
        .data-row { margin-bottom: 10px; }
        .data-row strong { display: inline-block; width: 150px; color: #555; }
        .notes { margin-top: 15px; padding: 10px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px; }
        .attachment { margin-top: 15px; }
        .attachment a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Registro Médico de ' . htmlspecialchars($pet['nombre']) . '</h1>
        <p><strong>Fecha de Registro:</strong> ' . formatDateSpanish($record['fecha_registro']) . '</p>
    </div>

    <div class="section">
        <h2>Detalles del Registro</h2>
        <div class="data-row"><strong>Título:</strong> ' . htmlspecialchars($record['titulo']) . '</div>
        <div class="data-row"><strong>Tipo de Registro:</strong> ' . htmlspecialchars(ucfirst($record['tipo_registro'])) . '</div>
        <div class="data-row"><strong>Veterinario:</strong> ' . (empty($record['veterinario']) ? 'N/A' : htmlspecialchars($record['veterinario'])) . '</div>
        <div class="data-row"><strong>Fecha de Visita:</strong> ' . (empty($record['fecha_visita']) ? 'N/A' : formatDateSpanish($record['fecha_visita'])) . '</div>
    </div>

    <div class="section">
        <h2>Descripción</h2>
        <div class="notes">' . (empty($record['descripcion']) ? 'No hay descripción.' : nl2br(htmlspecialchars($record['descripcion']))) . '</div>
    </div>

    ' . ($record['archivo_adjunto'] ? '
    <div class="section">
        <h2>Archivo Adjunto</h2>
        <div class="attachment">
            <p>Se adjunta un archivo a este registro. No se incluye directamente en el PDF por razones de formato y tamaño.</p>
            <p><strong>Nombre del archivo:</strong> ' . htmlspecialchars($record['archivo_adjunto']) . '</p>
            <p>Puedes descargarlo desde la aplicación.</p>
        </div>
    </div>
    ' : '') . '

</body>
</html>
';

$dompdf->loadHtml($html);

// (Opcional) Configurar el tamaño y la orientación del papel
$dompdf->setPaper('A4', 'portrait');

// Renderizar el HTML a PDF
$dompdf->render();

// Enviar el PDF al navegador
$dompdf->stream('registro_medico_' . $recordId . '.pdf', ['Attachment' => true]);
?>