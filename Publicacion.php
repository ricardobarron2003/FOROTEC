<?php
// Publicacion.php
class Publicacion {
    private $db;
    private $id_usuario;
    private $contenido;
    private $titulo;
    private $archivos = [];

    // Constructor de la clase, inicializa los parámetros
    public function __construct($db, $id_usuario, $contenido, $titulo, $archivos = []) {
        $this->db = $db;
        $this->id_usuario = $id_usuario;
        $this->contenido = $contenido;
        $this->titulo = $titulo;
        $this->archivos = $archivos;
    }

    // Método para manejar la carga de archivos
    public function manejarArchivos() {
        $archivosSubidos = [];
        $rutaCarpeta = "imagenes/"; // Carpeta donde se guardarán los archivos

        // Verificar si se recibieron archivos
        if ($this->archivos && isset($this->archivos['name'])) {
            foreach ($this->archivos['name'] as $index => $nombreArchivo) {
                $rutaArchivo = $rutaCarpeta . uniqid() . "_" . basename($nombreArchivo);
                $archivoTmp = $this->archivos['tmp_name'][$index];

                // Verificar el tipo de archivo antes de guardarlo (puedes agregar más extensiones si lo deseas)
                if (move_uploaded_file($archivoTmp, $rutaArchivo)) {
                    $archivosSubidos[] = $rutaArchivo;
                }
            }
        }

        return $archivosSubidos;
    }

    // Método para crear la publicación
    public function crearPublicacion() {
        // Subir los archivos
        $archivosSubidos = $this->manejarArchivos();

        // Convertir las rutas de los archivos a una cadena separada por comas
        // Si no hay archivos subidos, asignamos una cadena vacía
        $rutasArchivos = !empty($archivosSubidos) ? implode(",", $archivosSubidos) : "";

        // Insertar la publicación en la base de datos
        $stmt = $this->db->prepare("INSERT INTO publicaciones (id_usuario, contenido, tipo, ruta_archivo, titulo) VALUES (?, ?, ?, ?, ?)");
        $tipo = "texto"; // Tipo de publicación, puede ajustarse según el contenido
        $stmt->bind_param("issss", $this->id_usuario, $this->contenido, $tipo, $rutasArchivos, $this->titulo);

        // Ejecutar la consulta y verificar si se insertó correctamente
        if ($stmt->execute()) {
            // Publicación creada con éxito
            $stmt->close();
            return true;
        } else {
            // Error al crear la publicación
            $stmt->close();
            return false;
        }
    }

    // Método para obtener las publicaciones del usuario (o todas si no se especifica un usuario)
    public static function obtenerPublicaciones($db, $usuario_id = null) {
        $sql = "SELECT p.*, u.username, u.foto_perfil FROM publicaciones p
                JOIN usuarios u ON p.id_usuario = u.id_usuario";
        
        if ($usuario_id) {
            $sql .= " WHERE p.id_usuario = ?";
        }
        
        $sql .= " ORDER BY p.fecha DESC LIMIT 10"; // Limitar a 10 publicaciones

        $stmt = $db->prepare($sql);
        
        if ($usuario_id) {
            $stmt->bind_param("i", $usuario_id);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();
        $stmt->close();

        return $resultado;
    }

    // Método para obtener los archivos de una publicación (si tiene)
    public static function obtenerArchivos($ruta_archivo) {
        $archivos = [];
        
        if ($ruta_archivo) {
            // Si hay archivos, los separamos por coma
            $archivos = explode(",", $ruta_archivo);
        }

        return $archivos;
    }

    public function editarPublicacion($id_publicacion, $titulo, $contenido, $archivos = null, $eliminar_archivos = []) {
        // Obtener archivos actuales
        $stmt = $this->db->prepare("SELECT ruta_archivo FROM publicaciones WHERE id_publicacion = ?");
        $stmt->bind_param("i", $id_publicacion);
        $stmt->execute();
        // Linea para prevenir errores
        $rutas_actuales_serializadas = null;
        // Linea para prevenir errores
        $stmt->bind_result($rutas_actuales_serializadas);
        $stmt->fetch();
        $stmt->close();

    
        $rutas_actuales = explode(",", $rutas_actuales_serializadas);
    
        // Eliminar archivos seleccionados
        $rutas_actuales = array_diff($rutas_actuales, $eliminar_archivos);
    
        // Manejar los nuevos archivos
        if ($archivos && isset($archivos['name'])) {
            $rutas_nuevas = $this->manejarArchivos();
            $rutas_actuales = array_merge($rutas_actuales, $rutas_nuevas);
        }
    
        // Serializar las rutas actualizadas
        $rutas_actualizadas = implode(",", $rutas_actuales);
    
        // Actualizar la publicación en la base de datos
        $sql = "UPDATE publicaciones SET titulo = ?, contenido = ?, ruta_archivo = ? WHERE id_publicacion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssi", $titulo, $contenido, $rutas_actualizadas, $id_publicacion);
    
        return $stmt->execute();

    }
    
    
}
?>
