<?php
session_start();
require_once 'config/configuracion.php';

// Verificar que el usuario está en sesión
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    header("Location: login.php");
    exit;
}

// Obtener el usuario a seguir/dejar de seguir
$id_usuario_seguir = $_POST['id_usuario_seguir'] ?? null;
if (!$id_usuario_seguir || $id_usuario_seguir == $id_usuario) {
    die("Acción no permitida.");
}

// Conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Verificar si ya se está siguiendo
$query_verificar = "SELECT * FROM seguidores WHERE id_usuario = ? AND id_seguido = ?";
$stmt_verificar = $db->prepare($query_verificar);
$stmt_verificar->bind_param("ii", $id_usuario, $id_usuario_seguir);
$stmt_verificar->execute();
$seguimiento = $stmt_verificar->get_result();

if ($seguimiento->num_rows > 0) {
    // Dejar de seguir
    $query_borrar = "DELETE FROM seguidores WHERE id_usuario = ? AND id_seguido = ?";
    $stmt_borrar = $db->prepare($query_borrar);
    $stmt_borrar->bind_param("ii", $id_usuario, $id_usuario_seguir);
    $stmt_borrar->execute();
} else {
    // Seguir
    $query_insertar = "INSERT INTO seguidores (id_usuario, id_seguido, fecha) VALUES (?, ?, NOW())";
    $stmt_insertar = $db->prepare($query_insertar);
    $stmt_insertar->bind_param("ii", $id_usuario, $id_usuario_seguir);
    $stmt_insertar->execute();

    // Insertar la notificación de "Seguir"
    $sql = "INSERT INTO notificaciones (id_usuario, id_emisor, tipo_accion, leido, fecha, mensaje) 
            VALUES (?, ?, ?, ?, NOW(), ?)";

    // Definir los valores para la notificación
    $id_usuario_seguidor = $_SESSION['usuario_id']; // Usuario que está siguiendo
    $tipo_notificacion = 'Seguir'; // Tipo de notificación
    $id_usuario_seguido = $id_usuario_seguir; // Usuario que está siendo seguido
    $leido = 0; // Notificación no leída
    $mensaje = 'Nuevo seguidor';  // Mensaje de la notificación

    // Preparar y ejecutar la consulta de notificación
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iiiss", $id_usuario_seguido, $id_usuario_seguidor, $tipo_notificacion, $leido, $mensaje);
    $stmt->execute();
    $stmt->close();
}

// Redirigir de regreso al perfil del usuario
header("Location: perfil_usuario.php?id_usuario=" . $id_usuario_seguir);
exit;
?>
