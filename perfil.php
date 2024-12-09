<?php
session_start();
require_once 'config/configuracion.php'; // Incluye la configuración de la base de datos
require_once 'Usuario.php'; // Asegúrate de tener una clase Usuario con métodos de actualización

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Conexión fallida: " . $db->connect_error);
}



if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario = new Usuario($db); // Pasa la conexión $db al constructor de Usuario
$datos_usuario = $usuario->obtenerUsuarioPorID($usuario_id);


$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener la contraseña ingresada por el usuario para confirmación
    $password_actual = $_POST['password_actual'];
    
    // Verificar si la contraseña ingresada corresponde a la almacenada en la base de datos
    if (password_verify($password_actual, $datos_usuario['password'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $nombre = $_POST['nombre'];
        $apellidos = $_POST['apellidos'];
        $bio = $_POST['bio'];
        $password = !empty($_POST['password_nueva']) ? password_hash($_POST['password_nueva'], PASSWORD_BCRYPT) : $datos_usuario['password'];

        // Procesamiento de la foto de perfil
        if (!empty($_FILES['foto']['name'])) {
            $foto = $_FILES['foto'];
            $foto_nombre = $usuario_id . "_" . basename($foto['name']);
            $foto_ruta = "imagenes/" . $foto_nombre;

            if (move_uploaded_file($foto['tmp_name'], $foto_ruta)) {
                $_POST['foto'] = $foto_nombre;
            }
        } else {
            $foto_nombre = $datos_usuario['foto_perfil'];
        }

        // Actualizar el perfil del usuario
        if ($usuario->actualizarPerfil($usuario_id, $username, $password, $email, $nombre, $apellidos, $bio, $foto_nombre)) {
            $mensaje = "Perfil actualizado correctamente.";
            $_SESSION['foto_perfil'] = $foto_nombre;
            $mensaje = "Perfil actualizado correctamente, refresque la página para ver los resultados";
        } else {
            $mensaje = "Error al actualizar el perfil. Verifique su información.";
            
        }
    } else {
        $mensaje = "La contraseña actual es incorrecta.";
        
    }

    

    

}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 50px; /* Margen superior adicional */
            min-height: 80vh; /* Permitir scroll si el contenido es alto */
        }
        .form-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 20px;
        }
        .profile-pic {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'css/navbar.php'; ?>
    <?php include 'css/slidebar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-center">
            <div class="form-container col-12 col-md-8 col-lg-6">
            <div class="alert alert-info">
                <?php echo $mensaje; ?>
            </div>
                <h3 class="form-title text-center">Editar Perfil</h3>
                <form id="perfilForm" method="POST" enctype="multipart/form-data" onsubmit="return confirmarEnvio()">
                    <div class="text-center">
                    <img src="imagenes/<?php echo htmlspecialchars($datos_usuario['foto_perfil']) . '?t=' . time(); ?>" alt="Foto de perfil" class="profile-pic">

                    </div>
                    <div class="mb-2 row">
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                value="<?php echo htmlspecialchars($datos_usuario['username']); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                value="<?php echo htmlspecialchars($datos_usuario['email']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-2 row">
                        <div class="col-12 col-md-6">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                value="<?php echo htmlspecialchars($datos_usuario['nombre']); ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="apellidos" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                value="<?php echo htmlspecialchars($datos_usuario['apellidos']); ?>">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="bio" class="form-label">Biografía</label>
                        <textarea class="form-control" id="bio" name="bio" rows="2"><?php echo htmlspecialchars($datos_usuario['bio']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto de Perfil</label>
                        <input type="file" class="form-control" id="foto" name="foto">
                    </div>
                    <div class="mb-3 row">
                        <div class="col-12 col-md-6">
                            <label for="password_actual" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="password_nueva" class="form-label">Nueva Contraseña (opcional)</label>
                            <input type="password" class="form-control" id="password_nueva" name="password_nueva">
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                    </div>

                </form>
            
            </div>
            
        </div>
    </div>
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
function confirmarEnvio() {
    
    return confirm("¿Estás seguro de que deseas enviar este formulario?");
    


    
}

</script>
