<?php
// Incluir la configuración de la base de datos
require_once 'config/configuracion.php';

class Usuario {
    private $db;

    // Constructor que recibe la conexión a la base de datos
    public function __construct($db) {
        $this->db = $db;
    }

    // Método para registrar un nuevo usuario
    public function registrar($username, $password, $email) {
        // Preparar la consulta para insertar el nuevo usuario
        $query = "INSERT INTO usuarios (username, password, email) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sss", $username, $password, $email);

        // Ejecutar la consulta y verificar si fue exitosa
        return $stmt->execute();
    }

    // Método para iniciar sesión
    public function iniciarSesion($username, $password) {
        // Preparar la consulta para obtener los datos del usuario
        $query = "SELECT * FROM usuarios WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);

        // Ejecutar la consulta
        $stmt->execute();
        $result = $stmt->get_result();

        // Verificar si el usuario existe
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            // Verificar si la contraseña es correcta
            if (password_verify($password, $usuario['password'])) {
                return $usuario; // Devuelve los datos del usuario si la contraseña es correcta
            }
        }
        return false; // Retorna false si las credenciales no son válidas
    }
    
    // Método para actualizar el perfil del usuario
    public function actualizarPerfil($id, $username, $password, $email, $nombre, $apellidos, $bio, $foto) {
        $query = "UPDATE usuarios SET username = ?, password = ?, email = ?, nombre = ?, apellidos = ?, bio = ?, foto_perfil = ? WHERE id_usuario = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sssssssi", $username, $password, $email, $nombre, $apellidos, $bio, $foto, $id);

        return $stmt->execute();
    }

    // Método para obtener información del usuario por su ID
    public function obtenerUsuarioPorID($id) {
        $query = "SELECT * FROM usuarios WHERE id_usuario = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
