<?php
// conexion/AlertaDAO.php
require_once __DIR__ . "/conexion.php";

class AlertaDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}


    public function crearAlertaDesdeObservacion($observacion, $observacion_id = null)
    {
        
        $regla_id = $this->obtenerReglaId();

     
        $sql = "INSERT INTO alerta (
                    ESTUDIANTE_ID,
                    REGLA_ID,
                    NIVEL,
                    DESCRIPCION,
                    ESTADO,
                    FECHA_GENERACION
                ) VALUES (
                    :estudiante_id,
                    :regla_id,
                    :nivel,
                    :descripcion,
                    'Activa',
                    NOW()
                )";

        $stmt = $this->ejecutarPreparado($sql, [
            ':estudiante_id' => $observacion['estudiante_id'],
            ':regla_id' => $regla_id,
            ':nivel' => $observacion['nivel_riesgo'],
            ':descripcion' => 'Observación: ' . $observacion['descripcion']
        ]);

        return $stmt ? true : false;
    }


    public function crearAlertaDirecta($datos)
    {

        $regla_id = $this->obtenerReglaId();

        $sql = "INSERT INTO alerta (
                    ESTUDIANTE_ID,
                    REGLA_ID,
                    NIVEL,
                    DESCRIPCION,
                    ESTADO,
                    FECHA_GENERACION
                ) VALUES (
                    :estudiante_id,
                    :regla_id,
                    :nivel,
                    :descripcion,
                    'Activa',
                    NOW()
                )";

        $tipo = isset($datos['tipo']) ? trim($datos['tipo']) : '';
        $desc = $tipo !== '' ? ('[' . $tipo . '] ' . $datos['descripcion']) : $datos['descripcion'];

        $stmt = $this->ejecutarPreparado($sql, [
            ':estudiante_id' => $datos['estudiante_id'],
            ':regla_id' => $regla_id,
            ':nivel' => $datos['nivel_riesgo'],
            ':descripcion' => $desc
        ]);

        if (!$stmt) {
            error_log("Error en crearAlertaDirecta: " . $this->imprimirError());
            return false;
        }

        require_once __DIR__ . '/AprendizDAO.php';
        $apDao = new AprendizDAO();
        $apDao->refrescarNivelRiesgoProgreso((int)$datos['estudiante_id']);

        return true;
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


    public function obtenerAlertasActivas()
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
                    i.NOMBRES as instructor_nombres,
                    i.APELLIDOS as instructor_apellidos
                FROM alerta a
                INNER JOIN aprendiz ap ON a.ESTUDIANTE_ID = ap.APRENDIZ_ID
                LEFT JOIN parametro_regla r ON a.REGLA_ID = r.REGLA_ID
                LEFT JOIN observacion_academica o ON o.ESTUDIANTE_ID = a.ESTUDIANTE_ID 
                    AND DATE(o.FECHA) = DATE(a.FECHA_GENERACION)
                LEFT JOIN instructor i ON o.INSTRUCTOR_ID = i.INSTRUCTOR_ID
                WHERE a.ESTADO = 'Activa'
                GROUP BY a.ALERTA_ID
                ORDER BY a.FECHA_GENERACION DESC
                LIMIT 50";

        $this->Consulta_ID = $this->ejecutarSQL($sql);

        if ($this->Consulta_ID) {
            return $this->cargarTodo();
        }
        return [];
    }

  
    public function obtenerAlertasNuevasDesde($timestamp)
    {
      
        if ($timestamp == 0) {
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
                        r.TIPO_REGLA
                    FROM alerta a
                    INNER JOIN aprendiz ap ON a.ESTUDIANTE_ID = ap.APRENDIZ_ID
                    LEFT JOIN parametro_regla r ON a.REGLA_ID = r.REGLA_ID
                    WHERE a.ESTADO = 'Activa'
                    ORDER BY a.FECHA_GENERACION DESC";
        } else {
            
            $fecha = date('Y-m-d H:i:s', $timestamp);
            
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
                        r.TIPO_REGLA
                    FROM alerta a
                    INNER JOIN aprendiz ap ON a.ESTUDIANTE_ID = ap.APRENDIZ_ID
                    LEFT JOIN parametro_regla r ON a.REGLA_ID = r.REGLA_ID
                    WHERE a.ESTADO = 'Activa' 
                    AND a.FECHA_GENERACION > '$fecha'
                    ORDER BY a.FECHA_GENERACION DESC";
        }
        
        $this->Consulta_ID = $this->ejecutarSQL($sql);
        
        if ($this->Consulta_ID) {
            return $this->cargarTodo();
        }
        return [];
    }


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
                'total' => $resultado['total'] ?? 0,
                'altas' => $resultado['altas'] ?? 0,
                'medias' => $resultado['medias'] ?? 0,
                'bajas' => $resultado['bajas'] ?? 0
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
}
?>