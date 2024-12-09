<?php
session_start();
require_once 'config/configuracion.php';
require_once 'Usuario.php'; // Para obtener los datos del usuario

// Conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Verificar que el usuario está autenticado
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    die("No estás autenticado.");
}

// Obtener los chats del usuario
$query_chats = "SELECT DISTINCT c.id_chat, 
                      CASE 
                          WHEN c.usuario1_id = ? THEN c.usuario2_id 
                          ELSE c.usuario1_id 
                      END AS id_contacto 
               FROM chats c
               WHERE c.usuario1_id = ? OR c.usuario2_id = ?";

$stmt_chats = $db->prepare($query_chats);
$stmt_chats->bind_param("iii", $id_usuario, $id_usuario, $id_usuario);
$stmt_chats->execute();
$chats_resultado = $stmt_chats->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Chats</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .chat-container {
            max-width: 600px;
            margin: 50px auto;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .chat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .chat-item:last-child {
            border-bottom: none;
        }
        .chat-item img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .chat-item strong {
            flex-grow: 1;
            font-size: 16px;
            color: #333;
        }
        .chat-actions {
            display: flex;
            gap: 10px;
        }
        .last-message {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php require_once "css/navbar.php"; ?>
    <?php require_once 'css/slidebar.php'; ?>
    <div class="container">
    <br>
        <div class="chat-container">
            
            <h2 class="text-center">Mis Conversaciones</h2>
            <br>
            <?php while ($chat = $chats_resultado->fetch_assoc()): ?>
                <?php
                $id_contacto = $chat['id_contacto'];

                // Obtener los detalles del contacto
                $query_contacto = "SELECT username, foto_perfil FROM usuarios WHERE id_usuario = ?";
                $stmt_contacto = $db->prepare($query_contacto);
                $stmt_contacto->bind_param("i", $id_contacto);
                $stmt_contacto->execute();
                $contacto = $stmt_contacto->get_result()->fetch_assoc();

                $username_contacto = htmlspecialchars($contacto['username']);
                $foto_perfil = $contacto['foto_perfil'] ?? 'default.png';

                // Obtener el último mensaje del chat
                $query_last_message = "SELECT mensaje, fecha FROM mensajes WHERE id_chat = ? ORDER BY fecha DESC LIMIT 1";
                $stmt_last_message = $db->prepare($query_last_message);
                $stmt_last_message->bind_param("i", $chat['id_chat']);
                $stmt_last_message->execute();
                $last_message = $stmt_last_message->get_result()->fetch_assoc();

                $mensaje = $last_message['mensaje'] ?? "Sin mensajes.";
                $fecha_mensaje = $last_message['fecha'] ?? "";
                ?>
                <div class="chat-item">
                    <img src="imagenes/<?= $foto_perfil ?>" alt="Foto de perfil">
                    <div>
                        <strong><?= $username_contacto ?></strong>
                        <p class="last-message"><?= htmlspecialchars($mensaje) ?></p>
                    </div>
                    <a href="chat_mensajes.php?id_chat=<?= htmlspecialchars($chat['id_chat']) ?>" class="btn btn-outline-primary btn-sm">Chatear</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php require_once "css/footer.php"; ?>
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
