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


// Crear publicación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenido']) && isset($_POST['titulo']) && !empty($_POST['titulo'])) {
    // Obtener los datos del formulario
    $usuario_id = $_SESSION['usuario_id'];
    $titulo = $_POST['titulo'];
    $contenido = $_POST['contenido'];
    $archivos = $_FILES['archivos'] ?? null;

    // Instanciar la clase Publicacion y crear la publicación
    $publicacion = new Publicacion($db, $usuario_id, $contenido, $titulo, $archivos);
    $publicacion->crearPublicacion();

    // Redirigir a la página principal después de crear la publicación
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica que los campos necesarios estén presentes
    if (isset($_POST['accion'], $_POST['id_comentario'], $_POST['id_publicacion'])) {
        $accion = $_POST['accion'];
        $id_comentario = intval($_POST['id_comentario']); // Asegura que sea un entero
        $id_publicacion = intval($_POST['id_publicacion']); // Asegura que sea un entero
        $usuario_id = $_SESSION['usuario_id']; // Asume que el ID del usuario está almacenado en la sesión
        
        
        // Instancia de la clase Comentar
        $comentarioHandler = new Comentar($db, $id_publicacion, $usuario_id);
        
        // Lógica según la acción solicitada
        if ($accion === 'eliminar' ) {
            $comentarioHandler->eliminarComentario($id_comentario);
        } elseif ($accion === 'editar' && isset($_POST['nuevo_comentario'])) {
            $nuevo_comentario = trim($_POST['nuevo_comentario']); // Sanitizar la entrada
            if (!empty($nuevo_comentario)) {
                $comentarioHandler->editarComentario($id_comentario, $nuevo_comentario);
            } else {
                echo "El comentario no puede estar vacío.";
            }
        }
    }
}

// Manejar reacciones
if (isset($_POST['reaccion'], $_POST['publicacion_id'])) {
    $tipo = $_POST['reaccion'];
    $publicacion_id = (int) $_POST['publicacion_id'];
    $usuario_id = $_SESSION['usuario_id'];

    $reaccion = new Reaccion($db, $usuario_id, $publicacion_id);
    $reaccion->reaccionar($tipo);

    header("Location: index.php");
    exit();
}

// Manejar comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'], $_POST['id_publicacion'])) {
    $id_publicacion = intval($_POST['id_publicacion']); // Validar y convertir a entero
    $contenido_comentario = trim($_POST['comentario']); // Sanitizar
    
    if (!empty($contenido_comentario)) {
        $comentarioHandler = new Comentar($db, $id_publicacion, $_SESSION['usuario_id']);
        if ($comentarioHandler->agregarComentario($contenido_comentario)) {
            header("Location: index.php"); // Evitar reenvíos duplicados
            exit();
        } else {
            echo "Hubo un problema al agregar el comentario.";
        }
    } else {
        echo "El comentario no puede estar vacío.";
    }
}


$consulta = "SELECT p.*, u.username, u.foto_perfil FROM publicaciones p
             JOIN usuarios u ON p.id_usuario = u.id_usuario ORDER BY p.fecha DESC LIMIT 10";
$resultado = $db->query($consulta);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>ForoTEC - Inicio</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">



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
            <form action="index.php" method="POST" enctype="multipart/form-data">
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
            <div class="contenedor-publicaciones">
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

                            <form action="index.php" method="POST">
                                <input type="hidden" name="publicacion_id"
                                    value="<?php echo $publicacion['id_publicacion']; ?>">
                                    <button type="submit" name="reaccion" value="like"
                                        class="btn custom-like <?php echo ($reaccionUsuario === 'like') ? 'active' : ''; ?>">
                                        <img src="imagenes/like.png" alt="Like" width="20" height="20">
                                        (<?php echo $numLikes; ?>)
                                    </button>
                                    <button type="submit" name="reaccion" value="dislike"
                                        class="btn custom-dislike <?php echo ($reaccionUsuario === 'dislike') ? 'active' : ''; ?>">
                                        <img src="imagenes/dislike.png" alt="Dislike" width="20" height="20">
                                        (<?php echo $numDislikes; ?>)
                                    </button>
                            </form>
                        </div>
                        <br>
                        <div>
                            <form action="index.php" method="POST" class="mt-2">
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
                <footer class="text-center">
                <?php require_once "css/footer.php" ?>
            </footer>
            </div>
        </div>
        
    </div>
    <!-- Footer -->
    <script type="text/javascript">
    (function(d, t) {
        var v = d.createElement(t), s = d.getElementsByTagName(t)[0];
            v.onload = function() {
                window.voiceflow.chat.load({
                    verify: { projectID: '67512ec3cf5e9062101f40ea' },
                    url: 'https://general-runtime.voiceflow.com',
                    versionID: 'production'
                });
            }
            v.src = "https://cdn.voiceflow.com/widget/bundle.mjs"; v.type = "text/javascript"; s.parentNode.insertBefore(v, s);
        })(document, 'script');
    </script>
	<!-- Footer -->
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
