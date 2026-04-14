<?php
require_once __DIR__ . "/conexion.php";

class AsistenciaDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    public function obtenerAsistenciasSemana($fichaId, $fechaInicio)
    {
        $fechaFin = date('Y-m-d', strtotime($fechaInicio . ' +5 days'));
        
        $sql = "SELECT 
                    a.APRENDIZ_ID,
                    a.FECHA,
                    a.ESTADO,
                    a.HORAS_FALTA
                FROM asistencia a
                INNER JOIN aprendiz ap ON a.APRENDIZ_ID = ap.APRENDIZ_ID
                WHERE ap.FICHA_ID = :fichaId 
                AND a.FECHA BETWEEN :fechaInicio AND :fechaFin
                ORDER BY a.FECHA";
        
        $stmt = $this->ejecutarPreparado($sql, [
            ':fichaId' => $fichaId,
            ':fechaInicio' => $fechaInicio,
            ':fechaFin' => $fechaFin
        ]);
        
        $resultado = [];
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                $aprendizId = $row['APRENDIZ_ID'];
                if (!isset($resultado[$aprendizId])) {
                    $resultado[$aprendizId] = [];
                }
                $resultado[$aprendizId][] = [
                    'fecha' => $row['FECHA'],
                    'estado' => $row['ESTADO'],
                    'horas_falta' => $row['HORAS_FALTA']
                ];
            }
        }
        
        return $resultado;
    }

    public function obtenerAsistenciaAprendiz($aprendizId, $limite = 20)
    {
        $sql = "SELECT 
                    ASISTENCIA_ID,
                    FECHA,
                    ESTADO,
                    HORAS_FALTA
                FROM asistencia
                WHERE APRENDIZ_ID = :id
                ORDER BY FECHA DESC
                LIMIT :limite";

        $stmt = $this->ejecutarPreparado($sql, [
            ':id' => $aprendizId,
            ':limite' => $limite
        ]);
        
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    public function obtenerResumenAprendiz($aprendizId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_dias,
                    SUM(CASE WHEN ESTADO = 'asistio' THEN 1 ELSE 0 END) as dias_asistidos,
                    SUM(CASE WHEN ESTADO = 'retardo' THEN 1 ELSE 0 END) as dias_retardo,
                    SUM(CASE WHEN ESTADO = 'falta' THEN 1 ELSE 0 END) as dias_falta,
                    SUM(CASE WHEN ESTADO = 'retardo' THEN HORAS_FALTA ELSE 0 END) as horas_retardo,
                    SUM(HORAS_FALTA) as total_horas_falta
                FROM asistencia 
                WHERE APRENDIZ_ID = :aprendizId";
        
        $stmt = $this->ejecutarPreparado($sql, [':aprendizId' => $aprendizId]);
        
        if ($stmt && $row = $stmt->fetch()) {
            return $row;
        }
        
        return [
            'total_dias' => 0,
            'dias_asistidos' => 0,
            'dias_retardo' => 0,
            'dias_falta' => 0,
            'horas_retardo' => 0,
            'total_horas_falta' => 0
        ];
    }

    public function guardarAsistenciasSemana($fichaId, $fechaInicio, $asistenciasMarcadas, $retardosMarcados = [])
    {
        try {
            $this->Conexion_ID->beginTransaction();
            
            $fechaFin = date('Y-m-d', strtotime($fechaInicio . ' +5 days'));
            
            $sqlAprendices = "SELECT APRENDIZ_ID FROM aprendiz WHERE FICHA_ID = :fichaId";
            $stmtAprendices = $this->ejecutarPreparado($sqlAprendices, [':fichaId' => $fichaId]);
            
            if (!$stmtAprendices) {
                throw new Exception("Error al obtener aprendices");
            }
            
            $aprendices = $stmtAprendices->fetchAll();
            
            $fechas = [];
            for ($i = 0; $i < 6; $i++) {
                $fechas[] = date('Y-m-d', strtotime($fechaInicio . " +$i days"));
            }
            
            foreach ($aprendices as $aprendiz) {
                $aprendizId = $aprendiz['APRENDIZ_ID'];
                
                foreach ($fechas as $fecha) {
                    $asistio = isset($asistenciasMarcadas[$aprendizId][$fecha]);
                    $horasRetardo = isset($retardosMarcados[$aprendizId][$fecha]) ? (int)$retardosMarcados[$aprendizId][$fecha] : 0;
                    $horasRetardo = max(0, min(4, $horasRetardo));
                    
                    $estado = 'falta';
                    $horasFalta = 4;
                    
                    if ($asistio && $horasRetardo == 0) {
                        $estado = 'asistio';
                        $horasFalta = 0;
                    } elseif ($horasRetardo > 0) {
                        $estado = 'retardo';
                        $horasFalta = $horasRetardo;
                    }
                    
                    $sqlCheck = "SELECT ASISTENCIA_ID FROM asistencia 
                                WHERE APRENDIZ_ID = :aprendizId AND FECHA = :fecha";
                    $stmtCheck = $this->ejecutarPreparado($sqlCheck, [
                        ':aprendizId' => $aprendizId,
                        ':fecha' => $fecha
                    ]);
                    
                    $existeRegistro = $stmtCheck && $stmtCheck->rowCount() > 0;
                    
                    if ($existeRegistro) {
                        $sql = "UPDATE asistencia 
                                SET ESTADO = :estado, 
                                    HORAS_FALTA = :horasFalta
                                WHERE APRENDIZ_ID = :aprendizId AND FECHA = :fecha";
                    } else {
                        $sql = "INSERT INTO asistencia 
                                (APRENDIZ_ID, FECHA, ESTADO, HORAS_FALTA) 
                                VALUES 
                                (:aprendizId, :fecha, :estado, :horasFalta)";
                    }
                    
                    $params = [
                        ':aprendizId' => $aprendizId,
                        ':fecha' => $fecha,
                        ':estado' => $estado,
                        ':horasFalta' => $horasFalta
                    ];
                    
                    $resultado = $this->ejecutarPreparado($sql, $params);
                    
                    if (!$resultado) {
                        throw new Exception("Error al guardar asistencia");
                    }
                }
            }
            
            $this->Conexion_ID->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->Conexion_ID->inTransaction()) {
                $this->Conexion_ID->rollBack();
            }
            $this->ErrTxt = $e->getMessage();
            error_log("Error guardando asistencias: " . $e->getMessage());
            return false;
        }
    }

    public function imprimirError()
    {
        return $this->ErrTxt;
    }
}
?>