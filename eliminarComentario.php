<?php
require_once 'config/configuracion.php';
require_once 'Comentar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté autenticado y que exista el ID del comentario
if (!isset($_SESSION['usuario_id']) || !isset($_POST['id_comentario'])) {
    //header("Location: index.php");
    //exit();
}

// Obtener el ID de usuario y el ID del comentario
$usuario_id = $_SESSION['usuario_id'];
$id_comentario = $_POST['id_comentario'];

// Crear la conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Instanciar la clase Comentar y llamar a la función eliminarComentario
$comentario = new Comentar($db, null, $usuario_id, '');
$comentario->eliminarComentario($id_comentario);


?>
