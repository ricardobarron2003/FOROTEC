<?php
session_start();
require_once 'config/configuracion.php';
require_once 'Usuario.php'; 

// Conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Verificar si el usuario está en sesión
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    header("Location: login.php");
    exit;
}

// Función para obtener o crear un chat
function obtenerOCrearChat($db, $id_usuario, $id_seguidor) {
    $query_chat = "SELECT id_chat FROM chats WHERE 
                   (usuario1_id = ? AND usuario2_id = ?) OR 
                   (usuario1_id = ? AND usuario2_id = ?)";
    $stmt_chat = $db->prepare($query_chat);
    $stmt_chat->bind_param("iiii", $id_usuario, $id_seguidor, $id_seguidor, $id_usuario);
    $stmt_chat->execute();
    $resultado_chat = $stmt_chat->get_result();

    if ($resultado_chat->num_rows > 0) {
        $chat = $resultado_chat->fetch_assoc();
        return $chat['id_chat'];
    } else {
        $query_crear_chat = "INSERT INTO chats (usuario1_id, usuario2_id) VALUES (?, ?)";
        $stmt_crear_chat = $db->prepare($query_crear_chat);
        $stmt_crear_chat->bind_param("ii", $id_usuario, $id_seguidor);
        if ($stmt_crear_chat->execute()) {
            return $stmt_crear_chat->insert_id;
        } else {
            return null;
        }
    }
}

// Consulta para obtener seguidores y seguidos
$query_usuarios = "
    SELECT u.id_usuario, u.username, u.foto_perfil,
           CASE 
               WHEN s.id_usuario IS NOT NULL THEN 'Seguidor'
               ELSE 'Seguido'
           END AS relacion
    FROM usuarios u
    LEFT JOIN seguidores s ON s.id_usuario = u.id_usuario AND s.id_seguido = ?
    LEFT JOIN seguidores t ON t.id_usuario = ? AND t.id_seguido = u.id_usuario
    WHERE s.id_usuario IS NOT NULL OR t.id_usuario IS NOT NULL
";
$stmt = $db->prepare($query_usuarios);
$stmt->bind_param("ii", $id_usuario, $id_usuario);
$stmt->execute();
$resultado_usuarios = $stmt->get_result();

?>

<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css"> <!-- Enlace al CSS externo -->
    <br><br>
    <title>Seguidores y Seguidos</title>
</head>
<body>

    <!-- Navbar -->
    <?php include 'css/navbar.php'; ?>
    <?php include 'css/slidebar.php'; ?>

    <div class="container mt-4">
        <h1 class="text-center mb-4">Seguidores</h1>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <ul class="list-group">
                    <?php if ($resultado_usuarios->num_rows > 0): ?>
                        <?php while ($usuario = $resultado_usuarios->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <img src="<?= htmlspecialchars('imagenes/' . ($usuario['foto_perfil'] ?? 'default.png')) ?>" 
                                         alt="Foto de <?= htmlspecialchars($usuario['username']) ?>" 
                                         class="rounded-circle me-2" 
                                         style="width: 40px; height: 40px;">
                                    <strong><?= htmlspecialchars($usuario['username']) ?></strong>
                                    <small class="text-muted">(<?= htmlspecialchars($usuario['relacion']) ?>)</small>
                                </div>
                                <div>
                                    <?php
                                    $id_chat = obtenerOCrearChat($db, $id_usuario, $usuario['id_usuario']);
                                    if ($id_chat):
                                    ?>
                                        <a href="chat_mensajes.php?id_chat=<?= $id_chat ?>" class="btn btn-primary btn-sm">Chatear</a>
                                    <?php else: ?>
                                        <button class="btn btn-danger btn-sm" disabled>Error</button>
                                    <?php endif; ?>

                                    <form method="POST" action="seguir.php" style="display:inline;">
                                        <input type="hidden" name="id_usuario_seguir" value="<?= $usuario['id_usuario'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <?php
                                            $query_seguimiento = "SELECT 1 FROM seguidores WHERE id_usuario = ? AND id_seguido = ?";
                                            $stmt_seguimiento = $db->prepare($query_seguimiento);
                                            $stmt_seguimiento->bind_param("ii", $id_usuario, $usuario['id_usuario']);
                                            $stmt_seguimiento->execute();
                                            $es_seguidor = $stmt_seguimiento->get_result()->num_rows > 0;
                                            echo $es_seguidor ? "Dejar de seguir" : "Seguir";
                                            ?>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center">No tienes usuarios relacionados.</p>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'css/footer.php'; ?>
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
