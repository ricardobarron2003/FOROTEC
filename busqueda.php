<?php
session_start();

// Evitar acceso sin sesión iniciada
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Incluir archivos de configuración y clases necesarios
require_once 'config/configuracion.php';  
require_once 'Publicacion.php';  
require_once 'Reaccion.php';
require_once 'Comentar.php';
require_once 'Usuario.php';

// Crear la conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Manejar publicaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenido']) && isset($_POST['titulo']) && !empty($_POST['titulo'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $titulo = $_POST['titulo'];
    $contenido = $_POST['contenido'];
    $archivos = $_FILES['archivos'] ?? null;

    $publicacion = new Publicacion($db, $usuario_id, $contenido, $titulo, $archivos);
    $publicacion->crearPublicacion();

    header("Location: busqueda.php");
    exit();
}

// Manejar reacciones
if (isset($_POST['reaccion'], $_POST['publicacion_id'])) {
    $tipo = $_POST['reaccion'];
    $publicacion_id = (int) $_POST['publicacion_id'];
    $usuario_id = $_SESSION['usuario_id'];

    $reaccion = new Reaccion($db, $usuario_id, $publicacion_id);
    $reaccion->reaccionar($tipo);

    header("Location: busqueda.php");
    exit();
}

// Manejar comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'], $_POST['id_publicacion'])) {
    $id_publicacion = intval($_POST['id_publicacion']);
    $contenido_comentario = trim($_POST['comentario']);

    if (!empty($contenido_comentario)) {
        $comentarioHandler = new Comentar($db, $id_publicacion, $_SESSION['usuario_id']);
        if ($comentarioHandler->agregarComentario($contenido_comentario)) {
            header("Location: busqueda.php");
            exit();
        } else {
            echo "Hubo un problema al agregar el comentario.";
        }
    } else {
        echo "El comentario no puede estar vacío.";
    }
}

// Manejar edición y eliminación de comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['id_comentario'], $_POST['id_publicacion'])) {
    $accion = $_POST['accion'];
    $id_comentario = intval($_POST['id_comentario']);
    $id_publicacion = intval($_POST['id_publicacion']);
    $usuario_id = $_SESSION['usuario_id'];

    $comentarioHandler = new Comentar($db, $id_publicacion, $usuario_id);

    if ($accion === 'eliminar') {
        $comentarioHandler->eliminarComentario($id_comentario);
    } elseif ($accion === 'editar' && isset($_POST['nuevo_comentario'])) {
        $nuevo_comentario = trim($_POST['nuevo_comentario']);
        if (!empty($nuevo_comentario)) {
            $comentarioHandler->editarComentario($id_comentario, $nuevo_comentario);
        } else {
            echo "El comentario no puede estar vacío.";
        }
    }
}

// Búsqueda de publicaciones
$consulta = "";
if (isset($_GET['termino']) && !empty($_GET['termino'])) {
    $termino = $db->real_escape_string($_GET['termino']);
    $consulta = "SELECT p.*, u.username, u.foto_perfil 
                 FROM publicaciones p
                 JOIN usuarios u ON p.id_usuario = u.id_usuario
                 WHERE p.titulo LIKE '%$termino%' OR p.contenido LIKE '%$termino%'
                 ORDER BY p.fecha DESC";
} else {
    $consulta = "SELECT p.*, u.username, u.foto_perfil 
                 FROM publicaciones p
                 JOIN usuarios u ON p.id_usuario = u.id_usuario
                 ORDER BY p.fecha DESC LIMIT 10";
}

$resultado = $db->query($consulta);
if (!$resultado) {
    die("Error en la consulta: " . $db->error);
}

$resultado_usuarios = null;
if (isset($_GET['termino']) && !empty($_GET['termino'])) {
    $consulta_usuarios = "SELECT id_usuario, username, nombre, apellidos, foto_perfil 
                          FROM usuarios 
                          WHERE username LIKE '%$termino%' 
                             OR CONCAT(nombre, ' ', apellidos) LIKE '%$termino%' 
                          LIMIT 10";
    $resultado_usuarios = $db->query($consulta_usuarios);
    if (!$resultado_usuarios) {
        die("Error en la consulta de usuarios: " . $db->error);
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>ForoTEC - Inicio</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css"> <!-- Enlace al CSS externo -->
</head>
<script>
    function toggleEditMenu(commentId) {
        var menu = document.getElementById("edit-menu-" + commentId);
        if (menu.style.display === "none" || menu.style.display === "") {
            menu.style.display = "block";
        } else {
            menu.style.display = "none";
        }
    }
    // Ocultar el menú de edición al hacer clic fuera de él
    document.addEventListener('click', function (event) {
        var menus = document.querySelectorAll('.edit-menu');
        menus.forEach(function (menu) {
            if (!menu.contains(event.target) && !event.target.classList.contains('config-icon')) {
                menu.style.display = 'none';
            }
        });
    });
</script>

<body>
    <!-- Navbar -->
    <?php require_once "css/navbar.php" ?>
	<!-- Navbar -->
    <!-- Sidebar Izquierda -->
    <?php require_once "css/slidebar.php" ?>
	<!-- Sidebar Izquierda -->
    <br>
    <!-- Contenido Principal -->
    <div class="container-fluid main-content">
        
        <div class="post-form">
            <H2>¿En qué estás pensando?</H2>
            <form action="busqueda.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titulo">Título:</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="contenido">Contenido:</label>
                    <textarea id="contenido" name="contenido" rows="2" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="archivos">Elegir archivos:</label>
                    <input type="file" id="archivos" name="archivos[]" multiple class="form-control" />
                </div>
                <button type="submit" class="btn btn-primary">Publicar</button>
            </form>
        </div>
        

        <div class="feed-container">
        <?php if ($resultado_usuarios && $resultado_usuarios->num_rows > 0): ?>
            <div class="resultados-usuarios">
                <h3>Usuarios encontrados:</h3>
                <ul class="list-group">
                    <?php while ($usuario = $resultado_usuarios->fetch_assoc()): ?>
                        <li class="list-group-item d-flex align-items-center">
                            <a href="perfil_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>">
                                <img src="imagenes/<?php echo $usuario['foto_perfil']; ?>" 
                                    alt="Foto de perfil" class="rounded-circle me-2" width="40" height="40">
                            </a>
                            <div>
                                <strong>
                                    <a href="perfil_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>">
                                        <?php echo htmlspecialchars($usuario['username']); ?>
                                    </a>
                                </strong>
                                <p class="mb-0">
                                    <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?>
                                </p>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['termino']) && !empty($_GET['termino'])): ?>
            <h2>Resultados para "<?php echo htmlspecialchars($_GET['termino']); ?>"</h2>
        <?php else: ?>
            <h2>Últimas publicaciones</h2>
        <?php endif; ?>
            
            <div class="contenedor-publicaciones">
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while ($publicacion = $resultado->fetch_assoc()): ?>
                    <div class="publicacion-card">
                        <div class="publicacion-header d-flex justify-content-between align-items-center mb-3">
                            <div class="autor-info d-flex align-items-center">

							<!-- Perfiles de usuarios -->
							<a href="perfil_usuario.php?id_usuario=<?php echo $publicacion['id_usuario']; ?>">
							<img src="imagenes/<?php echo $publicacion['foto_perfil']; ?>"
							alt="Foto de perfil" class="rounded-circle" width="40" height="40">
							</a>

                                <span class="ms-3"><?php echo htmlspecialchars($publicacion['username']); ?></span>
                            </div>
                            <span><?php echo date('d M Y H:i', strtotime($publicacion['fecha'])); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($publicacion['titulo']); ?></h3>
                        <p><?php echo htmlspecialchars($publicacion['contenido']); ?></p>

                        <?php if (!empty($publicacion['ruta_archivo'])): ?>
                            <?php foreach (explode(",", $publicacion['ruta_archivo']) as $archivo): ?>
                                <div>
                                    <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $archivo)): ?>
                                        <img src="<?php echo htmlspecialchars($archivo); ?>" class="vista-previa-imagen"
                                            alt="Imagen de la publicación"> <style>.vista-previa-imagen{display: block;margin: 0 auto;}</style>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($archivo); ?>" download>Descargar
                                            <?php echo htmlspecialchars(basename($archivo)); ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div>
                            <br>
                            <?php
                            $reaccion = new Reaccion($db, $_SESSION['usuario_id'], $publicacion['id_publicacion']);
                            $reaccionUsuario = $reaccion->obtenerReaccion();
                            $numLikes = Reaccion::contarReacciones($db, $publicacion['id_publicacion'], 'like');
                            $numDislikes = Reaccion::contarReacciones($db, $publicacion['id_publicacion'], 'dislike');
                            ?>

                            <form action="busqueda.php" method="POST">
                                <input type="hidden" name="publicacion_id"
                                    value="<?php echo $publicacion['id_publicacion']; ?>">
                                <button type="submit" name="reaccion" value="like"
                                    class="btn <?php echo ($reaccionUsuario === 'like') ? 'btn-success' : 'btn-outline-success'; ?>">
                                    <img src="imagenes/like.png" alt="Like" width="20" height="20">
                                    (<?php echo $numLikes; ?>)
                                </button>
                                <button type="submit" name="reaccion" value="dislike"
                                    class="btn <?php echo ($reaccionUsuario === 'dislike') ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                    <img src="imagenes/dislike.png" alt="Dislike" width="20" height="20">
                                    (<?php echo $numDislikes; ?>)
                                </button>
                            </form>
                        </div>
                        <br>
                        <div>
                            <form action="busqueda.php" method="POST" class="mt-2">
                                <input type="hidden" name="id_publicacion"
                                    value="<?php echo $publicacion['id_publicacion']; ?>">
                                <input type="text" name="comentario" class="form-control" placeholder="Comenta algo"
                                    required>
                                <button type="submit" class="btn btn-primary mt-1">Comentar</button>
                            </form>

                            <div class="comentarios mt-3">
                                <?php
                                $comentario = new Comentar($db, $publicacion['id_publicacion'], $_SESSION['usuario_id'], '');
                                $comentarios = $comentario->obtenerComentarios();

                                foreach ($comentarios as $coment):
                                    ?>
                                    <br><div class="comentario position-relative">
								<!-- Perfiles de usuarios en los comentarios-->
                                <a href="perfil_usuario.php?id_usuario=<?php echo $coment['id_usuario']; ?>">
                                <img src="imagenes/<?php echo $coment['foto_perfil']; ?>" alt="Perfil" width="30" height="30" class="rounded-circle">
                                </a>
                                        <strong><?php echo htmlspecialchars($coment['username']); ?>:</strong>
                                        <span><?php echo date('d M Y H:i', strtotime($coment['fecha'])); ?></span>
                                        <p><?php echo htmlspecialchars($coment['comentario']); ?></p>
                                        <br>
                                        <?php if ($coment['id_usuario'] == $_SESSION['usuario_id']): ?>
                                            <span class="config-icon"
                                                onclick="toggleEditMenu(<?php echo $coment['id_comentario']; ?>)">⚙️Editar</span>
                                            <div class="edit-menu" id="edit-menu-<?php echo $coment['id_comentario']; ?>">

											<!-- Formulario para editar comentario -->
											<form action="" method="POST">
												<input type="hidden" name="id_comentario" value="<?php echo $coment['id_comentario']; ?>">
												<input type="hidden" name="id_publicacion" value="<?php echo $coment['id_publicacion']; ?>">
												<input type="hidden" name="accion" value="editar">
												<input type="text" name="nuevo_comentario" value="<?php echo htmlspecialchars($coment['comentario']); ?>"><br>
												<button type="submit" class="btn btn-sm btn-primary">Editar</button>
											</form>
											<!-- Formulario para Eliminar comentario -->
											<form action="" method="POST">
												<input type="hidden" name="id_comentario" value="<?php echo $coment['id_comentario']; ?>">
												<input type="hidden" name="id_publicacion" value="<?= $id_publicacion ?>">
												<input type="hidden" name="accion" value="eliminar">
												<button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
											</form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div> <!-- Cierre del div publicacion-card -->
                <?php endwhile; ?>
            </div>
            <?php else: ?>
        <p>No se encontraron publicaciones con el término "<strong><?php echo htmlspecialchars($termino); ?></strong>".</p>
        <footer class="text-center">
                <?php require_once "css/footer.php" ?>
            </footer>
        <?php endif; ?>
        </div>
    </div>
    
</body>
<!-- Footer -->
<?php require_once "css/footer.php" ?>
	<!-- Footer -->
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
