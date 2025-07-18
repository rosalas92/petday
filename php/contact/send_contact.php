<?php
/**
 * PetDay - Script para Enviar Mensajes de Contacto
 */

require_once __DIR__ . '/../../config/config.php'; // Cargar primero para definir constantes
require_once '../../config/database_config.php'; // Para funciones como sanitizeInput
require_once '../includes/functions.php'; // Para funciones como logError
require_once __DIR__ . '/../../vendor/autoload.php';

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
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Configuración del servidor SMTP desde config.php
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USER;
            $mail->Password = MAIL_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Configuración para depuración (opcional)
            if (defined('DEBUG_MAIL') && DEBUG_MAIL) {
                $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            }

            // Destinatarios
            $mail->setFrom(MAIL_USER, 'PetDay Contacto');
            $mail->addAddress('soporte@petday.com', 'Soporte PetDay'); // Cambia esto a tu correo real
            $mail->addReplyTo($email, $name);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = "Mensaje de Contacto PetDay: " . $subject;
            $mail->Body = "<p>Has recibido un nuevo mensaje de contacto de PetDay.</p>";
            $mail->Body .= "<ul>";
            $mail->Body .= "<li><strong>Nombre:</strong> " . htmlspecialchars($name) . "</li>";
            $mail->Body .= "<li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>";
            $mail->Body .= "<li><strong>Asunto:</strong> " . htmlspecialchars($subject) . "</li>";
            $mail->Body .= "</ul>";
            $mail->Body .= "<p><strong>Mensaje:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";
            $mail->AltBody = "Has recibido un nuevo mensaje de contacto de PetDay.\n\n" .
                             "Nombre: " . $name . "\n" .
                             "Email: " . $email . "\n" .
                             "Asunto: " . $subject . "\n" .
                             "Mensaje:\n" . $message;

            $mail->send();
            $response['success'] = true;
            $response['message'] = '¡Gracias! Tu mensaje ha sido enviado con éxito.';
        } catch (Exception $e) {
            $response['message'] = "Hubo un problema al enviar tu mensaje: {$mail->ErrorInfo}";
            logError("Error al enviar correo de contacto: " . $e->getMessage(), __FILE__, __LINE__);
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