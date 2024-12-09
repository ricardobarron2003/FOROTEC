<?php
require_once 'config/configuracion.php';
// Conexión a la base de datos (asegúrate de que esta parte esté en el archivo de configuración o aquí)
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Verificar si la conexión fue exitosa
if ($db->connect_error) {
    die("Error de conexión: " . $db->connect_error);
}
class Comentar {
    private $db;
    private $id_publicacion;
    private $usuario_id;
    private $comentario;

    // Constructor para inicializar la base de datos y los valores de la publicación y el usuario
    public function __construct($db, $id_publicacion, $usuario_id, $comentario = null) {
        $this->db = $db;
        $this->id_publicacion = $id_publicacion;
        $this->usuario_id = $usuario_id;
        $this->comentario = $comentario;
    }

    // Método para obtener los comentarios de una publicación
    public function obtenerComentarios() {
        $sql = "SELECT c.id_comentario, c.id_publicacion, c.id_usuario, c.comentario, c.fecha, u.username, u.foto_perfil 
                FROM comentarios c
                JOIN usuarios u ON c.id_usuario = u.id_usuario
                WHERE c.id_publicacion = ? 
                ORDER BY c.fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $this->id_publicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $comentarios = [];

        // Almacenar los resultados de la consulta en el array $comentarios
        while ($row = $result->fetch_assoc()) {
            $comentarios[] = $row;
        }

        return $comentarios;
    }

    // Método para agregar un comentario a una publicación
    public function agregarComentario($contenido_comentario) {
        // Insertar el comentario
        $sql = "INSERT INTO comentarios (id_publicacion, id_usuario, comentario, fecha) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iis", $this->id_publicacion, $this->usuario_id, $contenido_comentario);

        if (empty($titulo_publicacion)) {
            $titulo_publicacion = 'publicación desconocida';
        }
        $mensaje = 'Comentó en tu publicación "' . $titulo_publicacion . '"';

        if (empty($id_usuario_publicacion)) {
            $titulo_publicacion = 'usuario desconocida';
        }
        $mensaje = 'Comentó en tu publicación "' . $id_usuario_publicacion . '"';
        
    
        if ($stmt->execute()) {
            // Obtener el ID del usuario que creó la publicación y el título de la publicación
            $sql_publicacion = "SELECT id_usuario, titulo FROM publicaciones WHERE id_publicacion = ?";
            $stmt_publicacion = $this->db->prepare($sql_publicacion);
            $stmt_publicacion->bind_param("i", $this->id_publicacion);
            $stmt_publicacion->execute();
            $stmt_publicacion->bind_result($id_usuario_publicacion, $titulo_publicacion);
            $stmt_publicacion->fetch();
            $stmt_publicacion->close();
    
            // Preparar el mensaje de la notificación con el título de la publicación
            $mensaje = 'Comentó en tu publicación "' . $titulo_publicacion . '"';
    
            // Insertar la notificación
            $sql_notificacion = "INSERT INTO notificaciones (id_usuario, id_emisor, tipo_accion, leido, fecha, mensaje) 
                                 VALUES (?, ?, ?, ?, NOW(), ?)";
            $stmt_notificacion = $this->db->prepare($sql_notificacion);
    
            // Datos de la notificación
            $id_emisor = $this->usuario_id; // El emisor es el usuario que comenta
            $tipo_accion = 'Comentario';   // Tipo de acción, que en este caso será un comentario
            $leido = 0;                    // La notificación está "no leída" por defecto

            if (empty($id_usuario_publicacion) || empty($mensaje)) {
                echo "Error: Datos insuficientes para la notificación.";
                return false;
            }
    
            $stmt_notificacion->bind_param("iiiss", $id_usuario_publicacion, $id_emisor, $tipo_accion, $leido, $mensaje);
    
            if ($stmt_notificacion->execute()) {
                return true;
            } else {
                echo "Error al agregar la notificación: " . $stmt_notificacion->error;
                return false;
            }
        } else {
            echo "Error al agregar el comentario: " . $stmt->error;
            return false;
        }
    }

    // Método para editar un comentario
    public function editarComentario($id_comentario, $nuevo_comentario) {
        $sql = "UPDATE comentarios SET comentario = ? WHERE id_comentario = ? AND id_usuario = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $nuevo_comentario, $id_comentario, $this->usuario_id);
        $stmt->execute();
    }

    // Método para eliminar un comentario
    public function eliminarComentario($id_comentario) {
        $sql = "DELETE FROM comentarios WHERE id_comentario = ? AND id_usuario = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $id_comentario, $this->usuario_id);
    
        if ($stmt->execute()) {
            return true;
        } else {
            echo "Error al eliminar el comentario: " . $stmt->error;
            return false;
        }
    }
    
}
?>
