<?php
session_start();

// Redirigir al usuario autenticado a index.php
if (isset($_SESSION['usuario_id']) && isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Incluir configuración y clases
require_once 'config/configuracion.php';
require_once 'Usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($db->connect_error) {
        die("Error en la conexión: " . $db->connect_error);
    }

    $usuario = new Usuario($db);
    $datos_usuario = $usuario->iniciarSesion($username, $password);

    if ($datos_usuario) {
        $_SESSION['usuario_id'] = $datos_usuario['id_usuario'];
        $_SESSION['username'] = $datos_usuario['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Nombre de usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background: url('css/Mural.jpg') no-repeat center center fixed;
        background-size: cover;
        position: relative;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        overflow: hidden;
        animation: moveBackground 100s infinite alternate ease-in-out;
    }

    @keyframes moveBackground {
        0% {
            background-position: center center;
        }
        50% {
            background-position: center left;
        }
        100% {
            background-position: center right;
        }
    }

    body::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        backdrop-filter: blur(3px);
        z-index: -1;
    }

    .header-image-container {
        width: 100%;
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .header-image {
        width: 100%;
        max-width: 1200px;
        height: auto;
        object-fit: contain;
        padding-left: 5%;
        padding-right: 5%;
    }

    .container {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 30px 25px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(15px);
        color: #ffffff;
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    h1 {
        font-size: 4rem;
        color: #003e61;
        font-weight: 700;
        text-shadow: 0 0 20px rgba(255, 255, 255, 0.5), 0 0 40px rgba(255, 255, 255, 0.3);
        animation: fadeIn 3s ease-in-out, glow 2s infinite alternate;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes glow {
        0% {
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5), 0 0 40px rgba(255, 255, 255, 0.3);
        }
        100% {
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.8), 0 0 60px rgba(255, 255, 255, 0.6);
        }
    }

    h2 {
        font-weight: 600;
        margin-bottom: 20px;
        color: #ffffff;
    }

    .form-label {
        color: #ffffff;
        font-weight: 500;
    }

    .form-control {
        background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: #ffffff;
        border-radius: 30px;
        transition: all 0.3s ease-in-out;
    }

    .form-control:focus {
        background-color: rgba(255, 255, 255, 0.4);
        border-color: #ffffff;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
    }

    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        background-color: transparent;
        color: #ffffff;
        font-weight: 600;
        padding: 10px;
        text-align: center;
    }

    .btn:hover {
        color: #d1eaff;
    }

    .text-center a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .text-center a:hover {
        color: #d1eaff;
    }
    </style>

</head>
<body>
    <!-- Contenedor de la imagen de cabecera -->
    <div class="header-image-container">
        <img src="css/cabecera.png" alt="Cabecera" class="header-image">
    </div>
    <div class="header-image-container">
        <h1>ForoTEC</h1>
    </div>

    <!-- Contenedor del formulario de inicio de sesión -->
    <div class="container text-center">
        <h2>Iniciar Sesión</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Nombre de Usuario</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn w-100">
                <i class="bi bi-box-arrow-in-right"></i> Ingresar
            </button>
            <div class="text-center mt-3">
                <a href="registro.php"><i class="bi bi-person-plus"></i> Regístrate</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>