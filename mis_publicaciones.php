<?php
session_start();

// Evitar acceso sin sesión iniciada
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}


// Incluir archivos de configuración y clases necesarios
require_once 'config/configuracion.php';  
require_once 'Usuario.php';
require_once 'Publicacion.php';  
require_once 'Reaccion.php';
require_once 'Comentar.php';


// Crear la conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Actualizar publicación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_publicacion'])) {
    $id_publicacion = $_POST['id_publicacion'];
    $titulo = $_POST['titulo'];
    $contenido = $_POST['contenido'];
    $archivos = $_FILES['archivos'] ?? null;

    // Instanciar la clase Publicacion
    $publicacion = new Publicacion($db, $_SESSION['id_usuario'], '', '', $archivos);
    $resultado = $publicacion->editarPublicacion($id_publicacion, $titulo, $contenido, $archivos);

    if ($resultado) {
        header("Location: mis_publicaciones.php");
        exit;
    } else {
        echo "Error al actualizar la publicación.";
    }
}


// Consultar publicaciones del usuario en sesión
$usuario_id = $_SESSION['usuario_id'];
$consulta = "SELECT p.*, u.username, u.foto_perfil 
             FROM publicaciones p 
             JOIN usuarios u ON p.id_usuario = u.id_usuario 
             WHERE p.id_usuario = ? 
             ORDER BY p.fecha DESC";
$stmt = $db->prepare($consulta);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();


// Verificar si se han seleccionado archivos para eliminar
if (isset($_POST['eliminar_archivos']) && !empty($_POST['eliminar_archivos'])) {
    // Obtener los archivos a eliminar
    $archivos_a_eliminar = $_POST['eliminar_archivos'];
    foreach ($archivos_a_eliminar as $archivo) {
        // Eliminar el archivo físicamente del servidor
        if (file_exists($archivo)) {
            unlink($archivo);  // Eliminar el archivo
        }

        // Eliminar la ruta del archivo de la base de datos
        $id_publicacion = $_POST['id_publicacion'];  // ID de la publicación
        $consulta = "SELECT ruta_archivo FROM publicaciones WHERE id_publicacion = ?";
        $stmt = $db->prepare($consulta);
        $stmt->bind_param("i", $id_publicacion);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $publicacion = $resultado->fetch_assoc();
        $archivos_guardados = explode(",", $publicacion['ruta_archivo']);

        // Filtrar la lista de archivos y eliminar la ruta del archivo marcado
        $archivos_actualizados = array_filter($archivos_guardados, function($archivo_guardado) use ($archivo) {
            return $archivo_guardado !== $archivo;
        });

        // Actualizar la base de datos con los archivos restantes
        $nuevas_rutas = implode(",", $archivos_actualizados);
        $update_query = "UPDATE publicaciones SET ruta_archivo = ? WHERE id_publicacion = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("si", $nuevas_rutas, $id_publicacion);
        $stmt->execute();
    }
}

// Procesar la actualización de la publicación con nuevos archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_publicacion'])) {
    // Obtener la publicación actualizada
    $id_publicacion = $_POST['id_publicacion'];
    $titulo = $_POST['titulo'];
    $contenido = $_POST['contenido'];
    $archivos = $_FILES['archivos'] ?? null;

    // Instanciar la clase Publicacion
    $publicacion = new Publicacion($db, $_SESSION['usuario_id'], '', '', $archivos);
    $resultado = $publicacion->editarPublicacion($id_publicacion, $titulo, $contenido, $archivos);

    if ($resultado) {
        echo "Publicación actualizada con éxito.";
    } else {
        echo "Error al actualizar la publicación.";
    }

    // Verificar la consulta SQL
$consulta = "SELECT ruta_archivo FROM publicaciones WHERE id_publicacion = ?";
$stmt = $db->prepare($consulta);

// Verificar si la preparación fue exitosa
if ($stmt === false) {
    die('Error al preparar la consulta: ' . $db->error);
}

$stmt->bind_param("i", $id_publicacion);
$stmt->execute();

// Verificar si la ejecución fue exitosa
$resultado = $stmt->get_result();
if ($resultado === false) {
    die('Error al ejecutar la consulta: ' . $stmt->error);
}

// Si no se encuentra ningún resultado
if ($resultado->num_rows === 0) {
    die('No se encontraron archivos asociados a esta publicación.');
}

$publicacion = $resultado->fetch_assoc();

}

// Eliminar publicación, comentarios, reacciones y archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_publicacion'])) {
    $id_publicacion = $_POST['id_publicacion'];

    // Eliminar archivos adjuntos
    $consulta = "SELECT ruta_archivo FROM publicaciones WHERE id_publicacion = ?";
    $stmt = $db->prepare($consulta);
    $stmt->bind_param("i", $id_publicacion);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $publicacion = $resultado->fetch_assoc();

    if ($publicacion['ruta_archivo']) {
        $archivos = explode(",", $publicacion['ruta_archivo']);
        foreach ($archivos as $archivo) {
            if (file_exists($archivo)) {
                unlink($archivo); // Eliminar archivo físicamente
            }
        }
    }


    // Eliminar comentarios asociados a la publicación
    $consulta_comentarios = "DELETE FROM comentarios WHERE id_publicacion = ?";
    $stmt_comentarios = $db->prepare($consulta_comentarios);
    $stmt_comentarios->bind_param("i", $id_publicacion);
    $stmt_comentarios->execute();

    // Eliminar reacciones asociadas a la publicación
    $consulta_reacciones = "DELETE FROM reacciones WHERE id_publicacion = ?";
    $stmt_reacciones = $db->prepare($consulta_reacciones);
    $stmt_reacciones->bind_param("i", $id_publicacion);
    $stmt_reacciones->execute();

    // Finalmente, eliminar la publicación
    $consulta_publicacion = "DELETE FROM publicaciones WHERE id_publicacion = ?";
    $stmt_publicacion = $db->prepare($consulta_publicacion);
    $stmt_publicacion->bind_param("i", $id_publicacion);
    if ($stmt_publicacion->execute()) {
        echo "Publicación eliminada con éxito.";
    } else {
        echo "Error al eliminar la publicación.";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Publicaciones - ForoTEC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css"> <!-- Enlace al CSS externo -->
</head>
<body>
    <!-- Navbar -->
    <?php include "css/navbar.php" ?>

    <!-- Sidebar Izquierda -->
    <?php include "css/slidebar.php" ?>

    <div class="container-fluid main-content">
        <h2 class="mt-4">Mis Publicaciones</h2>
        <div class="feed-container">
            <div class="contenedor-publicaciones">
                <?php while ($publicacion = $resultado->fetch_assoc()): ?>
                    <div class="publicacion-card">
                        <div class="publicacion-header d-flex justify-content-between align-items-center mb-3">
                            <div class="autor-info d-flex align-items-center">
                                <img src="imagenes/<?php echo $publicacion['foto_perfil']; ?>" alt="Foto de perfil"
                                     class="rounded-circle" width="40" height="40">
                                <span class="ms-3"><?php echo htmlspecialchars($publicacion['username']); ?></span>
                            </div>
                            <span><?php echo date('d M Y H:i', strtotime($publicacion['fecha'])); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($publicacion['titulo']); ?></h3>
                        <p><?php echo htmlspecialchars($publicacion['contenido']); ?></p>

                        <form action="mis_publicaciones.php" method="POST">
                            <input type="hidden" name="id_publicacion" value="<?php echo $publicacion['id_publicacion']; ?>">
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editarModal-<?php echo $publicacion['id_publicacion']; ?>">
                                Editar
                            </button>
                        </form>

                        <!-- Modal para editar publicación -->
                        <div class="modal fade" id="editarModal-<?php echo $publicacion['id_publicacion']; ?>" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editarModalLabel">Editar Publicación</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="mis_publicaciones.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="id_publicacion" value="<?php echo $publicacion['id_publicacion']; ?>">
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="nuevo_titulo" class="form-label">Título</label>
                                                <input type="text" class="form-control" id="nuevo_titulo" name="titulo" value="<?php echo htmlspecialchars($publicacion['titulo']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="nuevo_contenido" class="form-label">Contenido</label>
                                                <textarea class="form-control" id="nuevo_contenido" name="contenido" rows="3" required><?php echo htmlspecialchars($publicacion['contenido']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="archivos" class="form-label">Archivos adjuntos</label>
                                                <ul>
                                                    <?php 
                                                    $archivos = explode(",", $publicacion['ruta_archivo']);
                                                    foreach ($archivos as $archivo):
                                                        if (!empty($archivo)):
                                                    ?>
                                                        <li>
                                                            <a href="<?php echo htmlspecialchars($archivo); ?>" target="_blank">Ver archivo</a>
                                                            <input type="checkbox" name="eliminar_archivos[]" value="<?php echo htmlspecialchars($archivo); ?>"> Eliminar
                                                        </li>
                                                    <?php 
                                                        endif; 
                                                    endforeach;
                                                    ?>
                                                </ul>
                                            </div>
                                            <div class="mb-3">
                                                <label for="archivos" class="form-label">Subir nuevos archivos</label>
                                                <input type="file" class="form-control" id="archivos" name="archivos[]" multiple>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary" name="editar_publicacion">Guardar cambios</button>
                                        </div>
                                    </form>
                                    <!-- Formulario para eliminar publicación -->
                                    <form action="mis_publicaciones.php" method="POST">
                                            <input type="hidden" name="id_publicacion" value="<?php echo $publicacion['id_publicacion']; ?>">
                                            <button type="submit" class="btn btn-danger" name="eliminar_publicacion">Eliminar</button>
                                    </form>

                                </div>
                            </div>
                        </div>
                        <!-- Modal para confirmar la eliminación -->
                        <div class="modal fade" id="eliminarModal-<?php echo $publicacion['id_publicacion']; ?>" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="eliminarModalLabel">Confirmar Eliminación</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ¿Estás seguro de que deseas eliminar esta publicación y todos sus comentarios, reacciones y archivos adjuntos?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        
                                        
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Aquí se incluye el código para las reacciones y comentarios -->
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php include 'css/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<script>
// Añadir espacio extra al final de la página
function extenderScrollFinal() {
    document.body.style.height = (window.innerHeight + 1000) + "px"; // Añadir 1000px extra al final
}

// Añadir espacio extra al principio de la página
function extenderScrollPrincipio() {
    document.body.style.marginTop = "100px"; // Añadir 1000px extra al principio
}

window.onload = function() {
    extenderScrollFinal(); // Ejecutar al cargar la página para añadir espacio al final
    extenderScrollPrincipio(); // Ejecutar al cargar la página para añadir espacio al principio
};

// También puedes hacerlo dinámicamente si el contenido cambia
window.onscroll = function() {
    // Si se llega al final de la página, agregar más espacio al final
    if (window.scrollY + window.innerHeight >= document.body.scrollHeight) {
        extenderScrollFinal();
    }

    // Si se llega al principio de la página, agregar más espacio al principio
    if (window.scrollY <= 0) {
        extenderScrollPrincipio();
    }
};
</script>