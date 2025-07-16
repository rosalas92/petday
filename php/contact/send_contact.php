<?php
/**
 * PetDay - Script para Enviar Mensajes de Contacto
 */

require_once '../../config/database_config.php'; // Para funciones como sanitizeInput
require_once '../includes/functions.php'; // Para funciones como logError

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['contact_name'] ?? '');
    $email = sanitizeInput($_POST['contact_email'] ?? '');
    $subject = sanitizeInput($_POST['contact_subject'] ?? '');
    $message = sanitizeInput($_POST['contact_message'] ?? '');

    // Validaciones básicas
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $response['message'] = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'El correo electrónico no es válido.';
    } else {
        // Dirección de correo a la que se enviará el mensaje
        $to = 'soporte@petday.com'; // ¡CAMBIA ESTO A TU CORREO REAL!
        $email_subject = "Mensaje de Contacto PetDay: " . $subject;
        $email_body = "Has recibido un nuevo mensaje de contacto de PetDay.\n\n"
                      . "Nombre: " . $name . "\n"
                      . "Email: " . $email . "\n"
                      . "Asunto: " . $subject . "\n"
                      . "Mensaje:\n" . $message;
        
        $headers = "From: no-reply@petday.com\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Intentar enviar el correo
        if (@mail($to, $email_subject, $email_body, $headers)) {
            $response['success'] = true;
            $response['message'] = '¡Gracias! Tu mensaje ha sido enviado con éxito.';
        } else {
            $response['message'] = 'Hubo un problema al enviar tu mensaje. Por favor, inténtalo de nuevo más tarde.';
            logError("Error al enviar correo de contacto desde: " . $email . " - " . error_get_last()['message'], __FILE__, __LINE__);
        }
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

// Redirigir o mostrar mensaje
if ($response['success']) {
    header('Location: ../../index.php?contact_status=success');
} else {
    header('Location: ../../index.php?contact_status=error&message=' . urlencode($response['message']));
}
exit;
?>