<?php
// conexion/EvidenciaDAO.php
require_once __DIR__ . "/conexion.php";

class EvidenciaDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    /**
     * Obtiene todas las evidencias con información de la ficha
     */
    public function obtenerTodas()
    {
        $sql = "SELECT e.*, f.CODIGO_FICHA 
                FROM evidencias e
                JOIN ficha f ON e.ficha_id = f.FICHA_ID
                WHERE e.estado_evidencia = 'activa'
                ORDER BY e.fecha_evidencia DESC";

        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if ($this->Consulta_ID) {
            return $this->cargarTodo();
        }
        return [];
    }

    /**
     * Obtiene todas las evidencias de una ficha
     */
    public function obtenerPorFicha($fichaId)
    {
        $sql = "SELECT e.*, 
                       (SELECT COUNT(*) FROM calificaciones_evidencias WHERE evidencia_id = e.evidencias_id) as total_calificados,
                       (SELECT ROUND(AVG(calificacion), 2) FROM calificaciones_evidencias WHERE evidencia_id = e.evidencias_id) as promedio
                FROM evidencias e
                WHERE e.ficha_id = :ficha_id AND e.estado_evidencia = 'activa'
                ORDER BY e.fecha_evidencia DESC";

        $stmt = $this->ejecutarPreparado($sql, [':ficha_id' => $fichaId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Obtiene una evidencia por ID
     */
    public function obtenerPorId($id)
    {
        $sql = "SELECT e.*, f.CODIGO_FICHA 
                FROM evidencias e
                JOIN ficha f ON e.ficha_id = f.FICHA_ID
                WHERE e.evidencias_id = :id";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        if ($stmt) {
            return $stmt->fetch();
        }
        return null;
    }

    /**
     * Crea una nueva evidencia
     */
    public function crearEvidencia($nombre, $tipo, $porcentaje, $fecha, $tiempo_entrega, $ficha_id, $estado = 'activa')
    {
        $sql = "INSERT INTO evidencias (nombre, tipo_evidencia, porcentaje, fecha_evidencia, tiempo_entrega, ficha_id, estado_evidencia) 
                VALUES (:nombre, :tipo, :porcentaje, :fecha, :tiempo, :ficha_id, :estado)";

        $params = [
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':porcentaje' => $porcentaje,
            ':fecha' => $fecha,
            ':tiempo' => $tiempo_entrega,
            ':ficha_id' => $ficha_id,
            ':estado' => $estado
        ];

        $stmt = $this->ejecutarPreparado($sql, $params);
        if ($stmt) {
            return $this->Conexion_ID->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza una evidencia existente
     */
    public function actualizarEvidencia($id, $nombre, $tipo, $porcentaje, $fecha, $tiempo_entrega, $ficha_id, $estado = 'activa')
    {
        $sql = "UPDATE evidencias SET 
                    nombre = :nombre,
                    tipo_evidencia = :tipo,
                    porcentaje = :porcentaje,
                    fecha_evidencia = :fecha,
                    tiempo_entrega = :tiempo,
                    ficha_id = :ficha_id,
                    estado_evidencia = :estado
                WHERE evidencias_id = :id";

        $params = [
            ':id' => $id,
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':porcentaje' => $porcentaje,
            ':fecha' => $fecha,
            ':tiempo' => $tiempo_entrega,
            ':ficha_id' => $ficha_id,
            ':estado' => $estado
        ];

        $stmt = $this->ejecutarPreparado($sql, $params);
        return $stmt ? true : false;
    }

    /**
     * Elimina una evidencia por su ID
     */
    public function eliminarEvidencia($id)
    {
        $sql = "DELETE FROM evidencias WHERE evidencias_id = :id";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        return $stmt ? true : false;
    }

    /**
     * Obtiene las calificaciones de una evidencia
     */
    public function obtenerCalificaciones($evidenciaId)
    {
        $sql = "SELECT c.*, a.NOMBRES, a.APELLIDOS, a.APRENDIZ_ID
                FROM calificaciones_evidencias c
                JOIN aprendiz a ON c.aprendiz_id = a.APRENDIZ_ID
                WHERE c.evidencia_id = :evidencia_id
                ORDER BY a.APELLIDOS, a.NOMBRES";

        $stmt = $this->ejecutarPreparado($sql, [':evidencia_id' => $evidenciaId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Guarda o actualiza una calificación
     */
    public function guardarCalificacion($evidenciaId, $aprendizId, $calificacion, $estado)
    {
        // Verificar si ya existe
        $sql = "SELECT calificacion_id FROM calificaciones_evidencias 
                WHERE evidencia_id = :evidencia_id AND aprendiz_id = :aprendiz_id";
        $stmt = $this->ejecutarPreparado($sql, [
            ':evidencia_id' => $evidenciaId,
            ':aprendiz_id' => $aprendizId
        ]);
        
        $existente = $stmt ? $stmt->fetch() : null;

        if ($existente) {
            // Actualizar
            $sql = "UPDATE calificaciones_evidencias 
                    SET calificacion = :calificacion, 
                        estado_aprobacion = :estado,
                        fecha_calificacion = NOW()
                    WHERE evidencia_id = :evidencia_id AND aprendiz_id = :aprendiz_id";
        } else {
            // Insertar
            $sql = "INSERT INTO calificaciones_evidencias (evidencia_id, aprendiz_id, calificacion, estado_aprobacion, fecha_calificacion) 
                    VALUES (:evidencia_id, :aprendiz_id, :calificacion, :estado, NOW())";
        }

        $params = [
            ':evidencia_id' => $evidenciaId,
            ':aprendiz_id' => $aprendizId,
            ':calificacion' => $calificacion,
            ':estado' => $estado
        ];

        $stmt = $this->ejecutarPreparado($sql, $params);
        return $stmt ? true : false;
    }

    public function obtenerAprendicesPorFicha($fichaId)
    {
        require_once __DIR__ . '/FichaDAO.php';
        return (new FichaDAO())->obtenerAprendices((int)$fichaId);
    }

    public function guardarCalificaciones($evidenciaId, array $calificaciones)
    {
        require_once __DIR__ . '/AprendizDAO.php';
        $ap = new AprendizDAO();
        foreach ($calificaciones as $aprendizId => $c) {
            $aprendizId = (int)$aprendizId;
            if ($aprendizId <= 0) {
                continue;
            }
            $estado = $c['estado'] ?? '';
            if ($estado === '') {
                continue;
            }
            $cal = isset($c['calificacion']) && $c['calificacion'] !== '' ? (float)$c['calificacion'] : null;
            $this->guardarCalificacion($evidenciaId, $aprendizId, $cal, $estado);
            $ap->actualizarPromedioDesdeCalificaciones($aprendizId);
        }
        return true;
    }
}
?>