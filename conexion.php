<?php
session_start();

$host = 'localhost';
$dbname = 'eduadmin_portal';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para verificar autenticación
function verificarAuth() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Función para solo administradores
function soloAdmin() {
    if ($_SESSION['usuario_rol'] !== 'admin') {
        header('Location: admin_dashboard.php');
        exit();
    }
}

// Función para registrar actividad
function registrarActividad($usuario_id, $accion, $tipo = 'create', $descripcion = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO actividad (usuario_id, accion, tipo, descripcion) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $accion, $tipo, $descripcion]);
}
?>