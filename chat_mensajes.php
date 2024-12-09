<?php 
session_start();
require_once 'config/configuracion.php';
require_once 'Usuario.php'; // Para obtener datos del usuario

// Conexión a la base de datos
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

// Obtener el id_chat de la URL
$id_chat = $_GET['id_chat'] ?? null;
$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$id_chat || !$id_usuario) {
    die("Chat no encontrado o no tienes acceso.");
}

// Verificar que el chat exista y que el usuario sea parte de él
$query_validar = "SELECT * FROM chats WHERE id_chat = ? AND (usuario1_id = ? OR usuario2_id = ?)";
$stmt_validar = $db->prepare($query_validar);
$stmt_validar->bind_param("iii", $id_chat, $id_usuario, $id_usuario);
$stmt_validar->execute();
$resultado_validar = $stmt_validar->get_result();

if ($resultado_validar->num_rows === 0) {
    die("Chat no encontrado o no tienes acceso.");
}

// Consulta para obtener los mensajes del chat
$query_mensajes = "SELECT m.*, u.username FROM mensajes m 
                   INNER JOIN usuarios u ON m.usuario_id = u.id_usuario 
                   WHERE m.id_chat = ? ORDER BY m.fecha ASC";
$stmt_mensajes = $db->prepare($query_mensajes);
$stmt_mensajes->bind_param("i", $id_chat);
$stmt_mensajes->execute();
$mensajes = $stmt_mensajes->get_result();

// Enviar nuevo mensaje
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = $_POST['mensaje'];
    $nombre_archivo = null;

    // Verificar si hay un archivo adjunto
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $ruta_directorio = 'documentos_chats/';
        if (!is_dir($ruta_directorio)) {
            mkdir($ruta_directorio, 0777, true); // Crear el directorio si no existe
        }

        $nombre_archivo = basename($_FILES['archivo']['name']);
        $ruta_archivo = $ruta_directorio . $nombre_archivo;

        // Mover el archivo a la carpeta de destino
        if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_archivo)) {
            echo "<p>Error al subir el archivo.</p>";
            $nombre_archivo = null;
        }
    }

    // Insertar el mensaje en la base de datos
    $query_insertar = "INSERT INTO mensajes (id_chat, usuario_id, mensaje, archivo) VALUES (?, ?, ?, ?)";
    $stmt_insertar = $db->prepare($query_insertar);
    $stmt_insertar->bind_param("iiss", $id_chat, $id_usuario, $mensaje, $nombre_archivo);

    if ($stmt_insertar->execute()) {
        // Recargar la página para ver el nuevo mensaje
        header("Location: chat_mensajes.php?id_chat=$id_chat");
        exit;
    } else {
        echo "<p>Error al enviar el mensaje.</p>";
    }
}
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <title>Chat</title>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
        }
        .chat-container {
            width: 90%;
            max-width: 600px;
            height: 80%;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            padding: 20px;
            overflow: hidden;
        }
        .messages {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 10px;
            padding-right: 10px;
        }
        .messages p {
            margin: 5px 0;
        }
        .messages img {
            max-width: 100%;
            border-radius: 5px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    </style>
    <?php include "css/navbar.php" ?>
    <?php include 'css/slidebar.php'; ?>
    
</head>
<body>

    <div class="chat-container">
        <div class="messages">
            <?php
            echo "<h2>Chat</h2>";
            while ($mensaje = $mensajes->fetch_assoc()) {
                $username = htmlspecialchars($mensaje['username']);
                $contenido = htmlspecialchars($mensaje['mensaje']);
                $fecha = htmlspecialchars($mensaje['fecha']);
                echo "<p><strong>{$username}:</strong> {$contenido} <em>({$fecha})</em></p>";
                
                if ($mensaje['archivo']) {
                    $archivo = htmlspecialchars($mensaje['archivo']);
                    $ruta_archivo = 'documentos_chats/' . $archivo;
                    $ext = pathinfo($archivo, PATHINFO_EXTENSION);
                    
                    // Si el archivo es una imagen, mostrar la imagen
                    if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                        echo "<p><img src='$ruta_archivo' alt='Imagen adjunta'></p>";
                    } else {
                        // Si no es una imagen, mostrar enlace para descargar
                        echo "<p><a href='$ruta_archivo' download>Descargar archivo adjunto</a></p>";
                    }
                }
            }
            ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="mensaje" placeholder="Escribe un mensaje" class="form-control" required>
            <input type="file" name="archivo" id="archivo" accept="image/*" class="form-control">
            <button type="submit" class="btn btn-primary">Enviar</button>
        </form>
    </div>
</body>
</html>
