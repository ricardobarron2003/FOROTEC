<?php
require_once '../config/configuracion.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

// Establecer la conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Habilitar depuración para diagnosticar el problema
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el ID de la notificación está en $_POST
if (isset($_POST['id'])) {
    $id_notificacion = $_POST['id'];
    echo "ID recibido: " . htmlspecialchars($id_notificacion); // Mostrar el ID recibido

    // Actualizar el estado de la notificación
    $sql = "UPDATE notificaciones SET leido = 1 WHERE id = ?";
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $db->error);
    }

    $stmt->bind_param("i", $id_notificacion);

    if ($stmt->execute()) {
        header("Location: ../notificaciones_usuario.php");
        exit();
    } else {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }
} else {
    echo "No se recibió un ID válido para la notificación.";
    var_dump($_POST); // Mostrar el contenido de $_POST para verificar qué se está enviando
    exit();
}
