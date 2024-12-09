<?php
// ProcesarPublicacion.php
require_once 'Publicacion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenido']) && isset($_POST['titulo']) && !empty($_POST['titulo'])) {
    // Recibimos los datos del formulario
    $id_usuario = $_SESSION['usuario_id'];
    $contenido = $_POST['contenido'];
    $titulo = $_POST['titulo'];
    $archivos = $_FILES['archivos'] ?? null;

    // Creamos una nueva instancia de la clase Publicacion
    $publicacion = new Publicacion($db, $id_usuario, $contenido, $titulo, $archivos);

    // Llamamos al método para crear la publicación
    $publicacion->crearPublicacion();

    // Redirigimos para evitar la resubida del formulario
    header("Location: index.php");
    exit();
}
