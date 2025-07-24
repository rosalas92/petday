<?php
require_once __DIR__ . '/config.php';
/**
 * PetDay - Configuración de Base de Datos
 * Archivo para conectar con MySQL/MariaDB
 */

// Configuración de la base de datos
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'petday');
// define('URL_ADMIN','http://localhost/petday');
// define('DB_USER', 'root');
// define('DB_PASS', '');





/**
 * Función para obtener conexión PDO a la base de datos
 * @return PDO Objeto de conexión
 * @throws PDOException Si hay error en la conexión
 */
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new PDOException("Error de conexión a la base de datos");
        }
    }
    
    return $pdo;
}

/**
 * Función para ejecutar consultas preparadas
 * @param string $sql Consulta SQL
 * @param array $params Parámetros para la consulta
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    $pdo = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Función para obtener un solo registro
 * @param string $sql Consulta SQL
 * @param array $params Parámetros
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Función para obtener múltiples registros
 * @param string $sql Consulta SQL
 * @param array $params Parámetros
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Función para insertar registro y obtener ID
 * @param string $sql Consulta SQL INSERT
 * @param array $params Parámetros
 * @return int ID del registro insertado
 */
function insertAndGetId($sql, $params = []) {
    $pdo = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

/**
 * Función para iniciar transacción
 */
function beginTransaction() {
    $pdo = getConnection();
    $pdo->beginTransaction();
}

/**
 * Función para confirmar transacción
 */
function commit() {
    $pdo = getConnection();
    $pdo->commit();
}

/**
 * Función para deshacer transacción
 */
function rollback() {
    $pdo = getConnection();
    $pdo->rollback();
}

/**
 * Función para verificar si la conexión está activa
 * @return bool
 */
function testConnection() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
    } catch (Exception $e) {
        return false;
    }
}
?>