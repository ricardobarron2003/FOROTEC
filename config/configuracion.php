
<?php

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', ''); // Cambia la contraseña si la tienes configurada
define('DB_NAME', 'database'); // Nombre de la base de datos

class Configuracion {

    // Propiedades para la configuración de la base de datos
    private $host = "localhost";
    private $usuario = "root";
    private $contrasena = "";  // Contraseña vacía en XAMPP por defecto
    private $nombre_bd = "database";  // Cambia "redsocial" a "database"
    private $conexion;

    // Método para obtener la conexión a la base de datos
    public function conectar() {
        // Conexión utilizando mysqli
        $this->conexion = new mysqli($this->host, $this->usuario, $this->contrasena, $this->nombre_bd);

        // Verificar si hay errores en la conexión
        if ($this->conexion->connect_error) {
            die("Error de conexión: " . $this->conexion->connect_error);
        }

        return $this->conexion;
    }

    // Método para cerrar la conexión (opcional)
    public function desconectar() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}
