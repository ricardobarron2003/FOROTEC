<?php
session_start();
require_once 'config/configuracion.php';
require_once 'Usuario.php';

// Conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Obtener el ID del usuario del perfil desde la URL
$id_usuario_perfil = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : null;
$id_usuario_actual = $_SESSION['usuario_id'] ?? null;

// Instanciar la clase Usuario
$usuario = new Usuario($db);

// Obtener los datos del usuario por su ID
$perfil_datos = $usuario->obtenerUsuarioPorID($id_usuario_perfil);
?>

<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <br><br>
    <title>Perfil de Usuario</title>
</head>
<body>
    <?php include 'css/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'css/slidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <?php if ($perfil_datos): ?>
                    <div class="card">
                        <div class="card-header text-center bg-primary text-white">
                            <h3>Perfil de <?php echo htmlspecialchars($perfil_datos['username']); ?></h3>
                        </div>
                        <div class="card-body text-center">
                            <img src="imagenes/<?php echo htmlspecialchars($perfil_datos['foto_perfil']); ?>" 
                                 alt="Foto de perfil" 
                                 class="rounded-circle mb-3" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                            <h4><?php echo htmlspecialchars($perfil_datos['nombre'] . ' ' . $perfil_datos['apellidos']); ?></h4>
                            <p><strong>Correo:</strong> <?php echo htmlspecialchars($perfil_datos['email']); ?></p>
                            <p><strong>Biografía:</strong> <?php echo nl2br(htmlspecialchars($perfil_datos['bio'])); ?></p>
                            
                            <div class="mt-3">
                                <?php if ($id_usuario_actual === $id_usuario_perfil): ?>
                                    <a href="perfil.php" class="btn btn-warning">Editar perfil</a>
                                <?php else: 
                                    // Verificar si ya existe un chat entre los dos usuarios
                                    $query_chat = "SELECT id_chat FROM chats 
                                                   WHERE (usuario1_id = ? AND usuario2_id = ?) 
                                                      OR (usuario1_id = ? AND usuario2_id = ?)";
                                    $stmt_chat = $db->prepare($query_chat);
                                    $stmt_chat->bind_param("iiii", $id_usuario_actual, $id_usuario_perfil, $id_usuario_perfil, $id_usuario_actual);
                                    $stmt_chat->execute();
                                    $resultado_chat = $stmt_chat->get_result();

                                    if ($resultado_chat->num_rows > 0) {
                                        $chat = $resultado_chat->fetch_assoc();
                                        $id_chat = $chat['id_chat'];
                                    } else {
                                        $query_crear_chat = "INSERT INTO chats (usuario1_id, usuario2_id) VALUES (?, ?)";
                                        $stmt_crear_chat = $db->prepare($query_crear_chat);
                                        $stmt_crear_chat->bind_param("ii", $id_usuario_actual, $id_usuario_perfil);
                                        $stmt_crear_chat->execute();
                                        $id_chat = $stmt_crear_chat->insert_id;
                                    }
                                ?>
                                    <a href="chat_mensajes.php?id_chat=<?php echo $id_chat; ?>" class="btn btn-primary">Chatear</a>
                                    
                                    <form method="POST" action="seguir.php" style="display:inline;">
                                        <input type="hidden" name="id_usuario_seguir" value="<?php echo $id_usuario_perfil; ?>">
                                        <button type="submit" class="btn btn-success">
                                            <?php 
                                            // Cambiar texto según el estado de seguimiento
                                            $query_verificar = "SELECT * FROM seguidores WHERE id_usuario = ? AND id_seguido = ?";
                                            $stmt_verificar = $db->prepare($query_verificar);
                                            $stmt_verificar->bind_param("ii", $id_usuario_actual, $id_usuario_perfil);
                                            $stmt_verificar->execute();
                                            $seguimiento = $stmt_verificar->get_result();
                                            echo $seguimiento->num_rows > 0 ? "Dejar de seguir" : "Seguir";
                                            ?>
                                        </button>
                                    </form>

                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        Usuario no encontrado.
                    </div>
                <?php endif; ?>
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