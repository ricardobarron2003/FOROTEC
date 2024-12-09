<?php
class Reaccion {
    private $db;
    private $id_usuario;
    private $id_publicacion;

    public function __construct($db, $id_usuario, $id_publicacion) {
        $this->db = $db;
        $this->id_usuario = $id_usuario;
        $this->id_publicacion = $id_publicacion;
    }

    // Método para obtener la reacción actual del usuario en una publicación
    public function obtenerReaccion() {
        $stmt = $this->db->prepare("SELECT tipo FROM reacciones WHERE id_usuario = ? AND id_publicacion = ?");
        $stmt->bind_param("ii", $this->id_usuario, $this->id_publicacion);
        $stmt->execute();
        $tipo = null; // Inicializar antes de bind_result
        $stmt->bind_result($tipo);
        $stmt->fetch();
        $stmt->close();

        return $tipo; // Puede ser "like", "dislike" o NULL si no hay reacciónS
    }

    // Método para agregar o cambiar la reacción del usuario
    public function reaccionar($tipo) {
        $reaccionActual = $this->obtenerReaccion();

        if ($reaccionActual === $tipo) {
            // Si la reacción ya existe, eliminarla (el usuario está desmarcando la reacción)
            $stmt = $this->db->prepare("DELETE FROM reacciones WHERE id_usuario = ? AND id_publicacion = ?");
            $stmt->bind_param("ii", $this->id_usuario, $this->id_publicacion);
            $stmt->execute();
            $stmt->close();
        } else {
            if ($reaccionActual) {
                // Actualizar la reacción existente
                $stmt = $this->db->prepare("UPDATE reacciones SET tipo = ?, fecha = CURRENT_TIMESTAMP WHERE id_usuario = ? AND id_publicacion = ?");
                $stmt->bind_param("sii", $tipo, $this->id_usuario, $this->id_publicacion);
            } else {
                // Insertar una nueva reacción
                $stmt = $this->db->prepare("INSERT INTO reacciones (id_publicacion, id_usuario, tipo) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $this->id_publicacion, $this->id_usuario, $tipo);
            }
            $stmt->execute();
            $stmt->close();
        }
    }

    // Método para contar las reacciones de un tipo específico (like/dislike) en una publicación
    public static function contarReacciones($db, $id_publicacion, $tipo) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM reacciones WHERE id_publicacion = ? AND tipo = ?");
        $stmt->bind_param("is", $id_publicacion, $tipo);
        $stmt->execute();
        $conteo = 0; // Inicializar antes de bind_result
        $stmt->bind_result($conteo);
        $stmt->fetch();
        $stmt->close();

        return $conteo;
    }
}
