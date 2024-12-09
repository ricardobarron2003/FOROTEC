<?php
session_start();
require_once 'config/configuracion.php';
require_once 'Usuario.php';

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Verificar si hay error de conexión
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado
$usuario = new Usuario($db); // Objeto Usuario con la conexión a la base de datos

// Consulta para obtener todas las notificaciones
$query = "SELECT * FROM notificaciones WHERE id_usuario = ? ORDER BY fecha DESC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

// Almacenamos las notificaciones en un array
$notificaciones = [];
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        /* Ajusta el contenedor de notificaciones */
        .notificaciones-container {
            max-width: 800px; /* Limita el ancho de las notificaciones */
            margin: 0 auto; /* Centra el contenedor */
        }

        .notificacion {
            margin-bottom: 10px;
        }

        .alert-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .img-fluid {
            margin-right: 10px; /* Separación de la imagen del texto */
        }
    </style>
</head>
<body>
    <?php include 'css/navbar.php'; ?>
    <?php include 'css/slidebar.php'; ?>

    <div class="container mt-4 notificaciones-container">
        <!-- Formulario para marcar todas las notificaciones como leídas -->
        <form action="marcar_todas_leidas.php" method="POST">
            <button type="submit" class="btn btn-primary mb-3 w-100">Marcar todas como leídas</button>
        </form>

        <h2 class="text-center">Notificaciones</h2>

        <?php if (count($notificaciones) > 0): ?>
            <?php foreach ($notificaciones as $notificacion): ?>
                <div class="alert alert-info notificacion">
                    <div class="d-flex">
                        <img src="imagenes/<?php echo !empty($notificacion['foto_perfil']) ? $notificacion['foto_perfil'] : 'default.png'; ?>" alt="Foto de perfil" class="img-fluid rounded-circle" width="30" height="30">
                        <div>
                            <?php echo htmlspecialchars($notificacion['mensaje']); ?>
                            <br>
                            <small><?php echo date("Y-m-d H:i:s", strtotime($notificacion['fecha'])); ?></small>
                            <?php if ($notificacion['leido'] == 0): ?>
                                <span class="badge bg-warning">Nuevo</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Botón para marcar una notificación individual como leída -->
                    <form action="css/marcar_leida.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $notificacion['id']; ?>"> <!-- Asegúrate que el name sea 'id' -->
                        <button type="submit" class="btn btn-sm btn-success">Marcar como leída</button>
                    </form>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No tienes notificaciones.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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