<?php
require_once 'config/configuracion.php';
require_once 'Usuario.php';

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id']; // ID del usuario autenticado
$usuario = new Usuario($db); // Objeto Usuario con la conexión a la base de datos

// Consulta para obtener las primeras 5 notificaciones
$sql = "SELECT n.id, n.tipo_accion, n.fecha, n.mensaje, u.foto_perfil, n.leido
        FROM notificaciones n
        JOIN usuarios u ON n.id_emisor = u.id_usuario
        WHERE n.id_usuario = ?
        ORDER BY n.fecha DESC
        LIMIT 5";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

// Almacenamos las notificaciones en un array
$notificaciones = [];
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = $row;
}
?>

<!-- navbar.php -->
<HTML DOCTYPE>
    <!-- Scripts de Bootstrap -->
<header>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</header>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>


</HTML>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <!-- Navbar con animación en el logo -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="css/logoitl.png" alt="Logo Institucional" width="50" height="50" class="me-2">
            ForoTEC
        </a>

        <div class="d-flex ms-auto gap-5">
            <!-- Icono de notificaciones con el número de notificaciones no leídas -->
            <a class="nav-link text-white" href="#" id="notificacionesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php
                // Obtener el número de notificaciones no leídas
                $sql = "SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leido = 0";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $_SESSION['usuario_id']);
                $stmt->execute();
                $stmt->bind_result($notificaciones_no_leidas);
                $stmt->fetch();
                $stmt->close();
                ?>
                <img src="css/campana.png" alt="Notificaciones" class="rounded-circle" width="30" height="30">
                <?php if ($notificaciones_no_leidas > 0): ?>
                    <span class="badge bg-danger"><?php echo $notificaciones_no_leidas; ?></span>
                <?php endif; ?>
            </a>

            <!-- Dropdown de notificaciones alineado a la derecha -->
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificacionesDropdown">
                <li>
                    <h6 class="dropdown-header">Notificaciones</h6>
                </li>
                <?php if (count($notificaciones) > 0): ?>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <li>
                            <a class="dropdown-item" href="#">
                                <img src="imagenes/<?php echo $notificacion['foto_perfil']; ?>" alt="Foto de perfil" class="img-fluid rounded-circle" width="30" height="30">
                                <?php echo htmlspecialchars($notificacion['mensaje']); ?>
                                <br>
                                <small><?php echo date("Y-m-d H:i:s", strtotime($notificacion['fecha'])); ?></small>
                                <?php if ($notificacion['leido'] == 0): ?>
                                    <span class="badge bg-warning">Nuevo</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>
                        <a class="dropdown-item" href="#">No tienes notificaciones</a>
                    </li>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <li>
                    <a class="dropdown-item text-center" href="notificaciones_usuario.php">Mostrar más</a>
                </li>
            </ul>

            <!-- Foto de perfil -->
            <a class="nav-link" href="perfil.php">
                <?php
                $sql = "SELECT foto_perfil FROM usuarios WHERE id_usuario = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $_SESSION['usuario_id']);
                $stmt->execute();
                $stmt->bind_result($foto_perfil);
                $stmt->fetch();
                $stmt->close();

                $ruta_imagen = 'imagenes/' . (!empty($foto_perfil) && file_exists("imagenes/$foto_perfil") ? $foto_perfil : 'default.png');
                ?>
                <img src="<?php echo htmlspecialchars($ruta_imagen); ?>" alt="Perfil" class="rounded-circle" width="30" height="30">
            </a>

            <!-- Cerrar sesión -->
            <a class="nav-link text-white" href="logout.php">
                <img src="css/cerrar-sesion.png" alt="Cerrar sesión" class="rounded-circle" width="30" height="30">
            </a>
        </div>
    </div>
</nav>

<style>
    /* Animación para el logo de la navbar */
    .navbar-brand {
        position: relative;
        font-size: 36px;
        transition: transform 0.3s ease, color 0.3s ease;
    }

    .navbar-brand:hover {
        transform: scale(1.1);
        color: #ff9900; /* Cambia el color a un tono llamativo */
    }

    /* Efecto de subrayado animado */
    .navbar-brand::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #ff9900; /* Color de la línea */
        transform: scaleX(0);
        transform-origin: bottom right;
        transition: transform 0.25s ease-out;
    }

    .navbar-brand:hover::after {
        transform: scaleX(1);
        transform-origin: bottom left;
    }
</style>
