<?php
//credenciales para el envío de correos
define('MAIL_HOST', 'smtp.ionos.es');
define('MAIL_USER', 'info@aznaitin.es');
define('MAIL_PASS', 'SanFermin$7_Marisol');
define('DEBUG_MAIL', false);



$host = $_SERVER['HTTP_HOST'];
$serverName = $_SERVER['SERVER_NAME'];

if ($host === 'localhost' || 
    $host === '127.0.0.1' || 
    $serverName === 'localhost' || 
    $serverName === '127.0.0.1' ||
    strpos($host, 'localhost:') === 0 ||
    strpos($host, '127.0.0.1:') === 0) {
    
    //echo 'Entorno de desarrollo local';
    // Configuración para desarrollo
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'petday');
    define('URL_ADMIN','http://localhost/petday');
} else {
    //echo 'Entorno de producción en la nube';
    // Configuración para producción
    define('DB_HOST', 'db5018277465.hosting-data.io');
    define('DB_USER', 'dbu3600379');//nombre de usuario
    define('DB_PASS', '384549erTf345dfgh4590pjgDGDFG');//contraseña
    define('DB_NAME', 'dbs14491668');//base de datos
    define('URL_ADMIN','http://www.alumnaro.com');
}
define('DB_CHARSET', 'utf8mb4');