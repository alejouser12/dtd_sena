<?php
// conexion/AlertaDAO.php
require_once __DIR__ . "/conexion.php";

class AlertaDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    // ==================== MÉTODOS EXISTENTES ====================

    public function crearAlertaDesdeObservacion($observacion, $observacion_id = null)
    {
        require_once __DIR__ . '/SeguimientoDAO.php';
        $seguimientoDAO = new SeguimientoDAO();
        $resultado = $seguimientoDAO->registrarDesdeObservacion($observacion);
        return $resultado !== false;
    }

    public function crearAlertaDirecta($datos)
    {
        require_once __DIR__ . '/SeguimientoDAO.php';
        $seguimientoDAO = new SeguimientoDAO();
        $resultado = $seguimientoDAO->registrarDesdeAlerta($datos);
        return $resultado !== false;
    }

    private function obtenerReglaId()
    {
        $sql = "SELECT REGLA_ID FROM parametro_regla WHERE ESTADO = 'Activa' LIMIT 1";
        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if ($this->Consulta_ID) {
            $regla = $this->cargarRegistro();
            if ($regla && isset($regla['REGLA_ID'])) {
                return $regla['REGLA_ID'];
            }
        }
        $sql_insert = "INSERT INTO parametro_regla (NOMBRE_REGLA, TIPO_REGLA, DESCRIPCION, ESTADO) 
                       VALUES ('Regla por defecto', 'GENERAL', 'Regla automática para alertas', 'Activa')";
        $this->ejecutarSQL($sql_insert);
        $this->Consulta_ID = $this->ejecutarSQL("SELECT REGLA_ID FROM parametro_regla WHERE NOMBRE_REGLA = 'Regla por defecto' LIMIT 1");
        $nueva_regla = $this->cargarRegistro();
        return $nueva_regla ? $nueva_regla['REGLA_ID'] : 1;
    }

    // Método original (sin filtros) – se mantiene por compatibilidad, pero puedes dejarlo
    public function obtenerAlertasActivas()
    {
        $sql = "SELECT ... (tu SQL original) ...";
        // ... (no lo modificamos aquí)
    }

    public function obtenerAlertasNuevasDesde($timestamp)
    {
        // ... (tu código existente)
    }

    /**
     * Conteo de alertas activas (solo estado 'Activa')
     */
    public function obtenerConteoAlertas()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN NIVEL = 'Alto' THEN 1 ELSE 0 END) as altas,
                    SUM(CASE WHEN NIVEL = 'Medio' THEN 1 ELSE 0 END) as medias,
                    SUM(CASE WHEN NIVEL = 'Bajo' THEN 1 ELSE 0 END) as bajas
                FROM alerta
                WHERE ESTADO = 'Activa'";
        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if ($this->Consulta_ID) {
            $resultado = $this->cargarRegistro();
            return [
                'total' => (int)($resultado['total'] ?? 0),
                'altas' => (int)($resultado['altas'] ?? 0),
                'medias' => (int)($resultado['medias'] ?? 0),
                'bajas' => (int)($resultado['bajas'] ?? 0)
            ];
        }
        return ['total' => 0, 'altas' => 0, 'medias' => 0, 'bajas' => 0];
    }

    /**
     * Conteo de alertas por estado (Activa o Inactiva)
     */
    public function obtenerConteoAlertasPorEstado($estado = 'Activa')
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN NIVEL = 'Alto' THEN 1 ELSE 0 END) as altas,
                    SUM(CASE WHEN NIVEL = 'Medio' THEN 1 ELSE 0 END) as medias,
                    SUM(CASE WHEN NIVEL = 'Bajo' THEN 1 ELSE 0 END) as bajas
                FROM alerta
                WHERE ESTADO = :estado";
        $stmt = $this->ejecutarPreparado($sql, [':estado' => $estado]);
        if ($stmt) {
            $row = $stmt->fetch();
            return [
                'total' => (int)($row['total'] ?? 0),
                'altas' => (int)($row['altas'] ?? 0),
                'medias' => (int)($row['medias'] ?? 0),
                'bajas' => (int)($row['bajas'] ?? 0)
            ];
        }
        return ['total' => 0, 'altas' => 0, 'medias' => 0, 'bajas' => 0];
    }

    public function marcarComoLeida($alertaId)
    {
        $sqlEst = 'SELECT ESTUDIANTE_ID FROM alerta WHERE ALERTA_ID = :id LIMIT 1';
        $st = $this->ejecutarPreparado($sqlEst, [':id' => $alertaId]);
        $estudianteId = 0;
        if ($st && ($row = $st->fetch())) {
            $estudianteId = (int)($row['ESTUDIANTE_ID'] ?? 0);
        }
        $sql = "UPDATE alerta SET ESTADO = 'Inactiva' WHERE ALERTA_ID = :id";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $alertaId]);
        if ($stmt && $estudianteId > 0) {
            require_once __DIR__ . '/AprendizDAO.php';
            (new AprendizDAO())->refrescarNivelRiesgoProgreso($estudianteId);
        }
        return $stmt ? true : false;
    }

    public function marcarTodasComoLeidas()
    {
        $sql = "UPDATE alerta SET ESTADO = 'Inactiva' WHERE ESTADO = 'Activa'";
        $stmt = $this->ejecutarSQL($sql);
        return $stmt ? true : false;
    }

    // ==================== NUEVOS MÉTODOS PARA FILTROS E HISTORIAL ====================

    /**
     * Obtiene alertas (activas o inactivas) según rol y filtros opcionales
     */
    public function obtenerAlertasConFiltros($rol = 'admin', $usuarioId = null, $regionalId = null, $centroId = null, $estado = 'Activa')
    {
        $sql = "SELECT 
                    a.ALERTA_ID,
                    a.NIVEL,
                    a.DESCRIPCION,
                    a.FECHA_GENERACION,
                    a.ESTADO,
                    ap.NOMBRES as aprendiz_nombres,
                    ap.APELLIDOS as aprendiz_apellidos,
                    ap.APRENDIZ_ID,
                    r.NOMBRE_REGLA,
                    r.TIPO_REGLA,
                    o.TIPO as TIPO_OBSERVACION,
                    i.NOMBRES as instructor_nombres,
                    i.APELLIDOS as instructor_apellidos,
                    f.CODIGO_FICHA,
                    f.CENTRO_ID,
                    c.NOMBRE as centro_nombre,
                    c.REGIONAL_ID,
                    reg.NOMBRE as regional_nombre
                FROM alerta a
                INNER JOIN aprendiz ap ON a.ESTUDIANTE_ID = ap.APRENDIZ_ID
                LEFT JOIN parametro_regla r ON a.REGLA_ID = r.REGLA_ID
                LEFT JOIN ficha f ON ap.FICHA_ID = f.FICHA_ID
                LEFT JOIN centro c ON f.CENTRO_ID = c.CENTRO_ID
                LEFT JOIN regional reg ON c.REGIONAL_ID = reg.REGIONAL_ID
                LEFT JOIN observacion_academica o ON o.OBSERVACION_ID = (
                    SELECT o2.OBSERVACION_ID
                    FROM observacion_academica o2
                    WHERE o2.ESTUDIANTE_ID = a.ESTUDIANTE_ID
                      AND (
                            (o2.DESCRIPCION = a.DESCRIPCION AND o2.NIVEL_RIESGO = a.NIVEL
                             AND ABS(TIMESTAMPDIFF(SECOND, o2.FECHA, a.FECHA_GENERACION)) <= 5)
                            OR DATE(o2.FECHA) = DATE(a.FECHA_GENERACION)
                      )
                    ORDER BY
                        CASE
                            WHEN o2.DESCRIPCION = a.DESCRIPCION
                                 AND o2.NIVEL_RIESGO = a.NIVEL
                                 AND ABS(TIMESTAMPDIFF(SECOND, o2.FECHA, a.FECHA_GENERACION)) <= 5
                            THEN 0
                            ELSE 1
                        END,
                        ABS(TIMESTAMPDIFF(SECOND, o2.FECHA, a.FECHA_GENERACION)),
                        o2.OBSERVACION_ID DESC
                    LIMIT 1
                )
                LEFT JOIN instructor i ON o.INSTRUCTOR_ID = i.INSTRUCTOR_ID
                WHERE a.ESTADO = :estado ";
        $params = [':estado' => $estado];

        if ($rol === 'instructor' && $usuarioId) {
            $sql .= " AND f.FICHA_ID IN (SELECT FICHA_ID FROM instructor_ficha WHERE INSTRUCTOR_ID = :instId)";
            $params[':instId'] = $usuarioId;
        }
        if ($regionalId) {
            $sql .= " AND c.REGIONAL_ID = :regId";
            $params[':regId'] = $regionalId;
        }
        if ($centroId) {
            $sql .= " AND f.CENTRO_ID = :centId";
            $params[':centId'] = $centroId;
        }

        $sql .= " ORDER BY a.FECHA_GENERACION DESC LIMIT 100";
        $stmt = $this->ejecutarPreparado($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene el historial de alertas (inactivas) con los mismos filtros
     */
    public function obtenerHistorialAlertas($rol = 'admin', $usuarioId = null, $regionalId = null, $centroId = null)
    {
        return $this->obtenerAlertasConFiltros($rol, $usuarioId, $regionalId, $centroId, 'Inactiva');
    }
}
?>