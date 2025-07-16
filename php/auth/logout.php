<?php
/**
 * PetDay - Cerrar Sesión
 */

session_start();

// Destruir todas las variables de sesión
$_SESSION = [];

// Si se está usando una cookie de sesión, borrarla
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir a la página de login con un mensaje
header("Location: login.php?logout=1");
exit();
?>
