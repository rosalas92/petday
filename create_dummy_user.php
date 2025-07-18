<?php
require_once 'config/config.php'; // Cargar primero para definir constantes
require_once 'config/database_config.php';
require_once 'php/includes/functions.php';

$userData = [
    'nombre_completo' => 'Usuario Ficticio',
    'email' => 'ficticio@example.com',
    'password' => 'password123',
    'rol' => 'usuario'
];

try {
    $userId = createUser($userData);
    if ($userId) {
        echo "Usuario ficticio creado con éxito. ID: " . $userId . "\n";
        echo "Email: ficticio@example.com\n";
        echo "Contraseña: password123\n";
    } else {
        echo "Error al crear el usuario ficticio.\n";
    }
} catch (Exception $e) {
    echo "Excepción al crear usuario: " . $e->getMessage() . "\n";
}
?>