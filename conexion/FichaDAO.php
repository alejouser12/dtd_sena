<?php
require_once __DIR__ . "/conexion.php";

class FichaDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    public function obtenerTodas()
    {
        $sql = "SELECT 
                    f.FICHA_ID,
                    f.CODIGO_FICHA,
                    f.FECHA_INICIO,
                    f.FECHA_FIN,
                    f.ESTADO,
                    f.PROGRAMA_ID,
                    f.CENTRO_ID,
                    p.NOMBRE as programa_nombre,
                    p.NIVEL_FORMACION,
                    c.NOMBRE as centro_nombre,
                    r.NOMBRE as regional_nombre,
                    (SELECT COUNT(*) FROM aprendiz a WHERE a.FICHA_ID = f.FICHA_ID) as total_aprendices
                FROM ficha f
                JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                LEFT JOIN centro c ON f.CENTRO_ID = c.CENTRO_ID
                LEFT JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                ORDER BY f.CODIGO_FICHA";

        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if ($this->Consulta_ID) {
            return $this->cargarTodo();
        }
        return [];
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT 
                    f.FICHA_ID,
                    f.CODIGO_FICHA,
                    f.FECHA_INICIO,
                    f.FECHA_FIN,
                    f.ESTADO,
                    f.PROGRAMA_ID,
                    f.CENTRO_ID,
                    p.NOMBRE as programa_nombre,
                    p.NIVEL_FORMACION,
                    c.NOMBRE as centro_nombre,
                    c.CODIGO as centro_codigo,
                    c.DIRECCION as centro_direccion,
                    c.TELEFONO as centro_telefono,
                    r.NOMBRE as regional_nombre,
                    r.CIUDAD as regional_ciudad
                FROM ficha f
                JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                LEFT JOIN centro c ON f.CENTRO_ID = c.CENTRO_ID
                LEFT JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                WHERE f.FICHA_ID = :id";

        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        
        if ($stmt) {
            $ficha = $stmt->fetch();
            
            if ($ficha) {
                $sqlAprendices = "SELECT COUNT(*) as total FROM aprendiz WHERE FICHA_ID = :id";
                $stmtAprendices = $this->ejecutarPreparado($sqlAprendices, [':id' => $id]);
                if ($stmtAprendices) {
                    $aprendices = $stmtAprendices->fetch();
                    $ficha['total_aprendices'] = $aprendices['total'];
                } else {
                    $ficha['total_aprendices'] = 0;
                }
                
                $ficha['instructor_nombres'] = null;
                $ficha['instructor_apellidos'] = null;
                $ficha['instructor_email'] = null;
                $ficha['instructor_especialidad'] = null;
                $ficha['HORARIO'] = null;
                $ficha['AULA'] = null;
            }
            return $ficha;
        }
        return null;
    }
    
    public function obtenerAprendices($fichaId)
    {
        $sql = "SELECT 
                    a.APRENDIZ_ID,
                    a.TIPO_DOCUMENTO,
                    a.NUMERO_DOCUMENTO,
                    a.NOMBRES,
                    a.APELLIDOS,
                    a.EMAIL,
                    a.TELEFONO,
                    a.ESTADO_ACADEMICO,
                    a.FECHA_NACIMIENTO,
                    a.GENERO,
                    pe.PROMEDIO_GENERAL,
                    pe.PORCENTAJE_ASISTENCIA,
                    pe.NIVEL_RIESGO_GLOBAL
                FROM aprendiz a
                LEFT JOIN progreso_estudiante pe ON a.APRENDIZ_ID = pe.ESTUDIANTE_ID
                WHERE a.FICHA_ID = :fichaId
                ORDER BY a.APELLIDOS, a.NOMBRES";

        $stmt = $this->ejecutarPreparado($sql, [':fichaId' => $fichaId]);
        
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Obtiene las asistencias de una semana específica para los aprendices de una ficha
     * @param int $fichaId
     * @param string $inicioSemana Fecha de inicio de la semana (lunes) en formato Y-m-d
     * @return array Array asociativo con APRENDIZ_ID como clave y array de fechas como valor
     */
    public function obtenerAsistenciasSemana($fichaId, $inicioSemana)
    {
        $finSemana = date('Y-m-d', strtotime($inicioSemana . ' +5 days')); // sábado
        $sql = "SELECT a.ESTUDIANTE_ID, a.FECHA 
                FROM asistencia a
                INNER JOIN aprendiz ap ON a.ESTUDIANTE_ID = ap.APRENDIZ_ID
                WHERE ap.FICHA_ID = :fichaId
                AND a.FECHA BETWEEN :inicio AND :fin
                AND a.ESTADO = 'Presente'";
        
        $stmt = $this->ejecutarPreparado($sql, [
            ':fichaId' => $fichaId,
            ':inicio' => $inicioSemana,
            ':fin' => $finSemana
        ]);
        
        $asistencias = [];
        if ($stmt) {
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $estudianteId = $row['ESTUDIANTE_ID'];
                $fecha = $row['FECHA'];
                if (!isset($asistencias[$estudianteId])) {
                    $asistencias[$estudianteId] = [];
                }
                $asistencias[$estudianteId][] = $fecha;
            }
        }
        return $asistencias;
    }

    /**
     * Guarda las asistencias de una semana para una ficha
     * @param int $fichaId
     * @param string $inicioSemana Fecha de inicio de la semana (lunes)
     * @param array $asistenciasMarcadas Array con estructura [aprendiz_id][fecha] = true
     * @return bool
     */
    public function guardarAsistenciasSemana($fichaId, $inicioSemana, $asistenciasMarcadas)
    {
        $aprendices = $this->obtenerAprendices($fichaId);
        if (empty($aprendices)) return false;
        
        $aprendicesIds = array_column($aprendices, 'APRENDIZ_ID');
        
        $fechasSemana = [];
        for ($i = 0; $i < 6; $i++) {
            $fechasSemana[] = date('Y-m-d', strtotime($inicioSemana . " +$i days"));
        }
        
        try {
            $this->Conexion_ID->beginTransaction();
            
            // Eliminar asistencias existentes de la semana para estos aprendices
            $placeholders = implode(',', array_fill(0, count($aprendicesIds), '?'));
            $sqlDelete = "DELETE FROM asistencia 
                          WHERE ESTUDIANTE_ID IN ($placeholders)
                          AND FECHA BETWEEN ? AND ?";
            $paramsDelete = array_merge($aprendicesIds, [$fechasSemana[0], $fechasSemana[5]]);
            
            $stmtDelete = $this->ejecutarPreparado($sqlDelete, $paramsDelete);
            if (!$stmtDelete) {
                $this->Conexion_ID->rollBack();
                return false;
            }
            
            // Insertar nuevas asistencias (solo las marcadas)
            $sqlInsert = "INSERT INTO asistencia (ESTUDIANTE_ID, FECHA, ESTADO) VALUES (?, ?, 'Presente')";
            $stmtInsert = $this->Conexion_ID->prepare($sqlInsert);
            
            foreach ($asistenciasMarcadas as $aprendizId => $fechas) {
                foreach ($fechas as $fecha => $marcado) {
                    if ($marcado && in_array($fecha, $fechasSemana)) {
                        $stmtInsert->execute([$aprendizId, $fecha]);
                    }
                }
            }
            
            $this->Conexion_ID->commit();
            return true;
        } catch (Exception $e) {
            $this->Conexion_ID->rollBack();
            $this->ErrTxt = $e->getMessage();
            error_log("Error guardando asistencias: " . $e->getMessage());
            return false;
        }
    }
}
?>