<?php
require_once __DIR__ . '/conexion.php';

class JustificacionDAO extends BaseDatos
{
    public function __construct()
    {
        parent::__construct();
        $this->asegurarTablaJustificacion();
    }

    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    private function asegurarTablaJustificacion()
    {
        $sql = "CREATE TABLE IF NOT EXISTS asistencia_justificacion (
                    JUSTIFICACION_ID int(11) NOT NULL AUTO_INCREMENT,
                    ASISTENCIA_ID int(11) NOT NULL,
                    APRENDIZ_ID int(11) NOT NULL,
                    FECHA_PRESENTACION datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    TEXTO text,
                    ARCHIVO varchar(255) DEFAULT NULL,
                    ESTADO enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
                    INSTRUCTOR_ID int(11) DEFAULT NULL,
                    COMENTARIO_INSTRUCTOR text DEFAULT NULL,
                    FECHA_RESPUESTA datetime DEFAULT NULL,
                    PRIMARY KEY (JUSTIFICACION_ID),
                    KEY idx_asistencia_id (ASISTENCIA_ID),
                    KEY idx_aprendiz_id (APRENDIZ_ID),
                    KEY idx_instructor_id (INSTRUCTOR_ID),
                    CONSTRAINT fk_justificacion_asistencia FOREIGN KEY (ASISTENCIA_ID) REFERENCES asistencia (ASISTENCIA_ID) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_justificacion_aprendiz FOREIGN KEY (APRENDIZ_ID) REFERENCES aprendiz (APRENDIZ_ID) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_justificacion_instructor FOREIGN KEY (INSTRUCTOR_ID) REFERENCES instructor (INSTRUCTOR_ID) ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        return $this->ejecutarSQL($sql) !== false;
    }

    /**
     * Guarda una nueva justificación (aprendiz)
     */
    public function guardarJustificacion($asistenciaId, $aprendizId, $texto, $archivoRuta = null)
    {
        try {
            if (!$this->asegurarTablaJustificacion()) {
                return false;
            }

            $this->Conexion_ID->beginTransaction();

            $stmt = $this->ejecutarPreparado(
                "SELECT ASISTENCIA_ID, ESTADO, EXCUSA_PRESENTADA
                 FROM asistencia
                 WHERE ASISTENCIA_ID = :asistenciaId
                   AND ESTUDIANTE_ID = :aprendizId
                 LIMIT 1",
                [':asistenciaId' => $asistenciaId, ':aprendizId' => $aprendizId]
            );
            $fila = $stmt ? $stmt->fetch() : null;
            if (!$fila || $fila['ESTADO'] !== 'falta' || (int)$fila['EXCUSA_PRESENTADA'] !== 0) {
                $this->Conexion_ID->rollBack();
                return false;
            }

            $sql = "INSERT INTO asistencia_justificacion
                    (ASISTENCIA_ID, APRENDIZ_ID, FECHA_PRESENTACION, TEXTO, ARCHIVO, ESTADO)
                    VALUES (:asistenciaId, :aprendizId, NOW(), :texto, :archivo, 'pendiente')";
            $stmtInsert = $this->ejecutarPreparado($sql, [
                ':asistenciaId' => $asistenciaId,
                ':aprendizId' => $aprendizId,
                ':texto' => $texto,
                ':archivo' => $archivoRuta,
            ]);
            if (!$stmtInsert) {
                $this->Conexion_ID->rollBack();
                return false;
            }

            $this->ejecutarPreparado(
                "UPDATE asistencia
                 SET EXCUSA_PRESENTADA = 1
                 WHERE ASISTENCIA_ID = :asistenciaId",
                [':asistenciaId' => $asistenciaId]
            );

            $this->Conexion_ID->commit();
            return true;
        } catch (Exception $e) {
            if ($this->Conexion_ID->inTransaction()) {
                $this->Conexion_ID->rollBack();
            }
            error_log('JustificacionDAO::guardarJustificacion => ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las justificaciones de un aprendiz específico
     */
    public function obtenerJustificacionesPorAprendiz($aprendizId)
    {
        $sql = "SELECT aj.*, a.FECHA AS fecha_falta, a.HORAS_FALTA, a.FECHA_LIMITE_EXCUSA,
                       f.CODIGO_FICHA, p.NOMBRE AS PROGRAMA_NOMBRE,
                       i.NOMBRES AS instructor_nombres, i.APELLIDOS AS instructor_apellidos
                FROM asistencia_justificacion aj
                INNER JOIN asistencia a ON aj.ASISTENCIA_ID = a.ASISTENCIA_ID
                INNER JOIN aprendiz ap ON aj.APRENDIZ_ID = ap.APRENDIZ_ID
                LEFT JOIN ficha f ON ap.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                LEFT JOIN instructor i ON aj.INSTRUCTOR_ID = i.INSTRUCTOR_ID
                WHERE aj.APRENDIZ_ID = :aprendizId
                ORDER BY aj.FECHA_PRESENTACION DESC";
        $stmt = $this->ejecutarPreparado($sql, [':aprendizId' => $aprendizId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene las justificaciones pendientes para un instructor (solo sus fichas)
     */
    public function obtenerJustificacionesPendientesParaInstructor($instructorId)
    {
        $sql = "SELECT aj.*, a.FECHA AS fecha_falta, a.HORAS_FALTA, a.FECHA_LIMITE_EXCUSA,
                       ap.APRENDIZ_ID, ap.NOMBRES AS aprendiz_nombres, ap.APELLIDOS AS aprendiz_apellidos,
                       f.CODIGO_FICHA, p.NOMBRE AS PROGRAMA_NOMBRE, f.FICHA_ID
                FROM asistencia_justificacion aj
                INNER JOIN asistencia a ON aj.ASISTENCIA_ID = a.ASISTENCIA_ID
                INNER JOIN aprendiz ap ON aj.APRENDIZ_ID = ap.APRENDIZ_ID
                LEFT JOIN ficha f ON ap.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE aj.ESTADO = 'pendiente'
                  AND ap.FICHA_ID IN (
                      SELECT FICHA_ID FROM instructor_ficha WHERE INSTRUCTOR_ID = :instructorId
                      UNION
                      SELECT GESTOR_FICHA_ID FROM instructor WHERE INSTRUCTOR_ID = :instructorId AND GESTOR_FICHA_ID IS NOT NULL
                  )
                ORDER BY aj.FECHA_PRESENTACION ASC";
        $stmt = $this->ejecutarPreparado($sql, [':instructorId' => $instructorId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene todas las justificaciones pendientes (solo para admin)
     */
    public function obtenerTodasPendientes()
    {
        $sql = "SELECT aj.*, a.FECHA AS fecha_falta, a.HORAS_FALTA, a.FECHA_LIMITE_EXCUSA,
                       ap.APRENDIZ_ID, ap.NOMBRES AS aprendiz_nombres, ap.APELLIDOS AS aprendiz_apellidos,
                       f.CODIGO_FICHA, p.NOMBRE AS PROGRAMA_NOMBRE, f.FICHA_ID
                FROM asistencia_justificacion aj
                INNER JOIN asistencia a ON aj.ASISTENCIA_ID = a.ASISTENCIA_ID
                INNER JOIN aprendiz ap ON aj.APRENDIZ_ID = ap.APRENDIZ_ID
                LEFT JOIN ficha f ON ap.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE aj.ESTADO = 'pendiente'
                ORDER BY aj.FECHA_PRESENTACION ASC";
        $stmt = $this->ejecutarPreparado($sql, []);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene justificaciones por estado (pendiente, aprobada, rechazada) - solo admin
     */
    public function obtenerPorEstado($estado)
    {
        if (!in_array($estado, ['pendiente', 'aprobada', 'rechazada'])) {
            return [];
        }
        $sql = "SELECT aj.*, a.FECHA AS fecha_falta, a.HORAS_FALTA, a.FECHA_LIMITE_EXCUSA,
                       ap.APRENDIZ_ID, ap.NOMBRES AS aprendiz_nombres, ap.APELLIDOS AS aprendiz_apellidos,
                       f.CODIGO_FICHA, p.NOMBRE AS PROGRAMA_NOMBRE, f.FICHA_ID
                FROM asistencia_justificacion aj
                INNER JOIN asistencia a ON aj.ASISTENCIA_ID = a.ASISTENCIA_ID
                INNER JOIN aprendiz ap ON aj.APRENDIZ_ID = ap.APRENDIZ_ID
                LEFT JOIN ficha f ON ap.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE aj.ESTADO = :estado
                ORDER BY aj.FECHA_RESPUESTA DESC";
        $stmt = $this->ejecutarPreparado($sql, [':estado' => $estado]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene justificaciones de un instructor por estado (aprobada/rechazada)
     */
    public function obtenerJustificacionesPorInstructorYEstado($instructorId, $estado)
    {
        if (!in_array($estado, ['aprobada', 'rechazada'])) {
            return [];
        }
        $sql = "SELECT aj.*, a.FECHA AS fecha_falta, a.HORAS_FALTA, a.FECHA_LIMITE_EXCUSA,
                       ap.APRENDIZ_ID, ap.NOMBRES AS aprendiz_nombres, ap.APELLIDOS AS aprendiz_apellidos,
                       f.CODIGO_FICHA, p.NOMBRE AS PROGRAMA_NOMBRE, f.FICHA_ID
                FROM asistencia_justificacion aj
                INNER JOIN asistencia a ON aj.ASISTENCIA_ID = a.ASISTENCIA_ID
                INNER JOIN aprendiz ap ON aj.APRENDIZ_ID = ap.APRENDIZ_ID
                LEFT JOIN ficha f ON ap.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE aj.ESTADO = :estado
                  AND aj.INSTRUCTOR_ID = :instructorId
                ORDER BY aj.FECHA_RESPUESTA DESC";
        $stmt = $this->ejecutarPreparado($sql, [':estado' => $estado, ':instructorId' => $instructorId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Actualiza el estado de una justificación (aprobar/rechazar)
     */
    public function actualizarEstado($justificacionId, $estado, $instructorId, $comentario = null)
    {
        if (!in_array($estado, ['aprobada', 'rechazada'], true)) {
            return false;
        }

        try {
            $this->Conexion_ID->beginTransaction();

            $stmt = $this->ejecutarPreparado(
                "SELECT ASISTENCIA_ID FROM asistencia_justificacion
                 WHERE JUSTIFICACION_ID = :id
                   AND ESTADO = 'pendiente'
                 LIMIT 1",
                [':id' => $justificacionId]
            );
            $fila = $stmt ? $stmt->fetch() : null;
            if (!$fila) {
                $this->Conexion_ID->rollBack();
                return false;
            }

            $sql = "UPDATE asistencia_justificacion
                    SET ESTADO = :estado,
                        INSTRUCTOR_ID = :instructorId,
                        COMENTARIO_INSTRUCTOR = :comentario,
                        FECHA_RESPUESTA = NOW()
                    WHERE JUSTIFICACION_ID = :id";
            $ok = $this->ejecutarPreparado($sql, [
                ':estado' => $estado,
                ':instructorId' => $instructorId,
                ':comentario' => $comentario,
                ':id' => $justificacionId,
            ]);
            if (!$ok) {
                $this->Conexion_ID->rollBack();
                return false;
            }

            if ($estado === 'aprobada') {
                $this->ejecutarPreparado(
                    "UPDATE asistencia
                     SET ESTADO = 'excusa', HORAS_FALTA = 0
                     WHERE ASISTENCIA_ID = :asistenciaId",
                    [':asistenciaId' => $fila['ASISTENCIA_ID']]
                );
            }

            $this->Conexion_ID->commit();
            return true;
        } catch (Exception $e) {
            if ($this->Conexion_ID->inTransaction()) {
                $this->Conexion_ID->rollBack();
            }
            error_log('JustificacionDAO::actualizarEstado => ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene una justificación por su ID
     */
    public function obtenerPorId($justificacionId)
    {
        $sql = "SELECT aj.*, a.FECHA AS fecha_falta, a.HORAS_FALTA, a.FECHA_LIMITE_EXCUSA,
                       ap.APRENDIZ_ID, ap.NOMBRES AS aprendiz_nombres, ap.APELLIDOS AS aprendiz_apellidos,
                       f.CODIGO_FICHA, p.NOMBRE AS PROGRAMA_NOMBRE, f.FICHA_ID
                FROM asistencia_justificacion aj
                INNER JOIN asistencia a ON aj.ASISTENCIA_ID = a.ASISTENCIA_ID
                INNER JOIN aprendiz ap ON aj.APRENDIZ_ID = ap.APRENDIZ_ID
                LEFT JOIN ficha f ON ap.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE aj.JUSTIFICACION_ID = :id
                LIMIT 1";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $justificacionId]);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Obtiene el número de justificaciones pendientes para un instructor (para el badge)
     */
    public function contarPendientesPorInstructor($instructorId)
    {
        $sql = "SELECT COUNT(*) as total
                FROM asistencia_justificacion aj
                INNER JOIN asistencia a ON aj.ASISTENCIA_ID = a.ASISTENCIA_ID
                INNER JOIN aprendiz ap ON aj.APRENDIZ_ID = ap.APRENDIZ_ID
                WHERE aj.ESTADO = 'pendiente'
                  AND ap.FICHA_ID IN (
                      SELECT FICHA_ID FROM instructor_ficha WHERE INSTRUCTOR_ID = :instructorId
                      UNION
                      SELECT GESTOR_FICHA_ID FROM instructor WHERE INSTRUCTOR_ID = :instructorId AND GESTOR_FICHA_ID IS NOT NULL
                  )";
        $stmt = $this->ejecutarPreparado($sql, [':instructorId' => $instructorId]);
        $row = $stmt ? $stmt->fetch() : null;
        return $row ? (int)$row['total'] : 0;
    }
}
?>