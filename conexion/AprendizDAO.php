<?php
require_once __DIR__ . "/conexion.php";

class AprendizDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    /**
     * Obtiene un aprendiz por su ID con toda la información detallada
     */
    public function obtenerPorId($id)
    {
        $sql = "SELECT 
                    a.APRENDIZ_ID,
                    a.TIPO_DOCUMENTO,
                    a.NUMERO_DOCUMENTO,
                    a.NOMBRES,
                    a.APELLIDOS,
                    a.FECHA_NACIMIENTO,
                    a.GENERO,
                    a.TELEFONO,
                    a.EMAIL,
                    a.ESTADO_ACADEMICO,
                    a.FECHA_REGISTRO,
                    a.FICHA_ID,
                    f.CODIGO_FICHA,
                    p.NOMBRE AS programa_nombre,
                    p.NIVEL_FORMACION,
                    p.PROGRAMA_ID,
                    pe.NIVEL_RIESGO_GLOBAL,
                    pe.PROMEDIO_GENERAL,
                    pe.PORCENTAJE_ASISTENCIA,
                    pe.COMPETENCIAS_APROBADAS,
                    pe.COMPETENCIAS_PENDIENTES
                FROM aprendiz a
                LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                LEFT JOIN progreso_estudiante pe ON a.APRENDIZ_ID = pe.ESTUDIANTE_ID
                WHERE a.APRENDIZ_ID = :id
                ORDER BY pe.FECHA_ACTUALIZACION DESC
                LIMIT 1";

        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        if (!$stmt) return null;
        $aprendiz = $stmt->fetch();
        if (!$aprendiz) return null;

        // Si no hay progreso, calcular nivel de riesgo por defecto
        if (!isset($aprendiz['NIVEL_RIESGO_GLOBAL'])) {
            $aprendiz['NIVEL_RIESGO_GLOBAL'] = $this->calcularNivelRiesgo($aprendiz);
        }
        
        return $aprendiz;
    }

    /**
     * Obtiene aprendices paginados con su ficha y programa
     */
    public function obtenerAprendicesPaginados($inicio = 0, $cantidad = 10, $filtroFichas = false)
    {
        // Asegurar que sean enteros para evitar inyección
        $inicio = (int)$inicio;
        $cantidad = (int)$cantidad;

        if ($filtroFichas) {
            $sql = "SELECT 
                        a.APRENDIZ_ID,
                        a.TIPO_DOCUMENTO,
                        a.NUMERO_DOCUMENTO,
                        a.NOMBRES,
                        a.APELLIDOS,
                        a.FECHA_NACIMIENTO,
                        a.GENERO,
                        a.TELEFONO,
                        a.EMAIL,
                        a.ESTADO_ACADEMICO,
                        a.FECHA_REGISTRO,
                        f.CODIGO_FICHA,
                        f.FICHA_ID,
                        p.NOMBRE AS PROGRAMA_NOMBRE,
                        pe.NIVEL_RIESGO_GLOBAL,
                        pe.PROMEDIO_GENERAL,
                        pe.PORCENTAJE_ASISTENCIA,
                        pe.COMPETENCIAS_APROBADAS,
                        pe.COMPETENCIAS_PENDIENTES
                    FROM aprendiz a
                    LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                    LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                    LEFT JOIN progreso_estudiante pe ON a.APRENDIZ_ID = pe.ESTUDIANTE_ID
                    WHERE a.FICHA_ID IS NOT NULL
                    ORDER BY a.APELLIDOS, a.NOMBRES
                    LIMIT $inicio, $cantidad";
        } else {
            $sql = "SELECT 
                        a.APRENDIZ_ID,
                        a.TIPO_DOCUMENTO,
                        a.NUMERO_DOCUMENTO,
                        a.NOMBRES,
                        a.APELLIDOS,
                        a.FECHA_NACIMIENTO,
                        a.GENERO,
                        a.TELEFONO,
                        a.EMAIL,
                        a.ESTADO_ACADEMICO,
                        a.FECHA_REGISTRO,
                        f.CODIGO_FICHA,
                        f.FICHA_ID,
                        p.NOMBRE AS PROGRAMA_NOMBRE,
                        pe.NIVEL_RIESGO_GLOBAL,
                        pe.PROMEDIO_GENERAL,
                        pe.PORCENTAJE_ASISTENCIA,
                        pe.COMPETENCIAS_APROBADAS,
                        pe.COMPETENCIAS_PENDIENTES
                    FROM aprendiz a
                    LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                    LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                    LEFT JOIN progreso_estudiante pe ON a.APRENDIZ_ID = pe.ESTUDIANTE_ID
                    ORDER BY a.APELLIDOS, a.NOMBRES
                    LIMIT $inicio, $cantidad";
        }

        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if (!$this->Consulta_ID) {
            error_log("Error en obtenerAprendicesPaginados: " . $this->imprimirError());
            return [];
        }
        return $this->cargarTodo();
    }

    /**
     * Cuenta el total de aprendices
     */
    public function contarAprendices($filtroFichas = false)
    {
        if ($filtroFichas) {
            $sql = "SELECT COUNT(*) as total FROM aprendiz WHERE FICHA_ID IS NOT NULL";
        } else {
            $sql = "SELECT COUNT(*) as total FROM aprendiz";
        }
        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if (!$this->Consulta_ID) {
            error_log("Error en contarAprendices: " . $this->imprimirError());
            return 0;
        }
        $resultado = $this->cargarRegistro();
        return $resultado['total'] ?? 0;
    }

    /**
     * Busca aprendices por columna con paginación
     */
    public function buscarPorColumnaPaginado($columna, $valor, $inicio = 0, $cantidad = 10)
    {
        $columnasPermitidas = [
            'a.NOMBRES', 
            'a.APELLIDOS', 
            'a.NUMERO_DOCUMENTO', 
            'a.EMAIL', 
            'a.ESTADO_ACADEMICO',
            'a.TELEFONO',
            'f.CODIGO_FICHA'
        ];
        
        if (!in_array($columna, $columnasPermitidas)) {
            return [];
        }

        $inicio = (int)$inicio;
        $cantidad = (int)$cantidad;
        $valor = addslashes($valor);

        $sql = "SELECT 
                    a.APRENDIZ_ID,
                    a.TIPO_DOCUMENTO,
                    a.NUMERO_DOCUMENTO,
                    a.NOMBRES,
                    a.APELLIDOS,
                    a.FECHA_NACIMIENTO,
                    a.GENERO,
                    a.TELEFONO,
                    a.EMAIL,
                    a.ESTADO_ACADEMICO,
                    a.FECHA_REGISTRO,
                    f.CODIGO_FICHA,
                    f.FICHA_ID,
                    p.NOMBRE AS PROGRAMA_NOMBRE,
                    pe.NIVEL_RIESGO_GLOBAL,
                    pe.PROMEDIO_GENERAL,
                    pe.PORCENTAJE_ASISTENCIA,
                    pe.COMPETENCIAS_APROBADAS,
                    pe.COMPETENCIAS_PENDIENTES
                FROM aprendiz a
                LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                LEFT JOIN progreso_estudiante pe ON a.APRENDIZ_ID = pe.ESTUDIANTE_ID
                WHERE $columna LIKE '%$valor%'
                ORDER BY a.APELLIDOS, a.NOMBRES
                LIMIT $inicio, $cantidad";

        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if (!$this->Consulta_ID) {
            error_log("Error en buscarPorColumnaPaginado: " . $this->imprimirError());
            return [];
        }
        return $this->cargarTodo();
    }

    /**
     * Cuenta resultados de búsqueda
     */
    public function contarBusqueda($columna, $valor)
    {
        $columnasPermitidas = [
            'a.NOMBRES', 
            'a.APELLIDOS', 
            'a.NUMERO_DOCUMENTO', 
            'a.EMAIL', 
            'a.ESTADO_ACADEMICO',
            'a.TELEFONO',
            'f.CODIGO_FICHA'
        ];
        
        if (!in_array($columna, $columnasPermitidas)) {
            return 0;
        }

        $valor = addslashes($valor);
        $sql = "SELECT COUNT(*) as total 
                FROM aprendiz a
                LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                WHERE $columna LIKE '%$valor%'";

        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if (!$this->Consulta_ID) {
            error_log("Error en contarBusqueda: " . $this->imprimirError());
            return 0;
        }
        $resultado = $this->cargarRegistro();
        return $resultado['total'] ?? 0;
    }

    /**
     * Calcula el nivel de riesgo de un aprendiz basado en su progreso y observaciones
     */
    public function calcularNivelRiesgo($aprendiz)
    {
        $aid = (int)($aprendiz['APRENDIZ_ID'] ?? 0);
        if ($aid > 0) {
            $sqlA = "SELECT NIVEL FROM alerta WHERE ESTUDIANTE_ID = :id AND ESTADO = 'Activa'";
            $stmtA = $this->ejecutarPreparado($sqlA, [':id' => $aid]);
            if ($stmtA) {
                $ord = ['Bajo' => 1, 'Medio' => 2, 'Alto' => 3];
                $max = 0;
                $label = null;
                foreach ($stmtA->fetchAll() as $row) {
                    $n = ucfirst(strtolower(trim($row['NIVEL'] ?? '')));
                    $v = $ord[$n] ?? 0;
                    if ($v > $max) {
                        $max = $v;
                        $label = $n;
                    }
                }
                if ($label) {
                    return $label;
                }
            }
        }

        // Buscar en progreso_estudiante
        $sql = "SELECT * FROM progreso_estudiante WHERE ESTUDIANTE_ID = :id ORDER BY FECHA_ACTUALIZACION DESC LIMIT 1";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendiz['APRENDIZ_ID']]);
        $progreso = $stmt ? $stmt->fetch() : null;
        
        if ($progreso && !empty($progreso['NIVEL_RIESGO_GLOBAL'])) {
            return $progreso['NIVEL_RIESGO_GLOBAL'];
        }
        
        // Si no hay progreso, buscar en observaciones
        $sqlObs = "SELECT NIVEL_RIESGO FROM observacion_academica 
                   WHERE ESTUDIANTE_ID = :id AND NIVEL_RIESGO IS NOT NULL 
                   ORDER BY FECHA DESC LIMIT 1";
        $stmtObs = $this->ejecutarPreparado($sqlObs, [':id' => $aprendiz['APRENDIZ_ID']]);
        $obs = $stmtObs ? $stmtObs->fetch() : null;
        
        if ($obs && !empty($obs['NIVEL_RIESGO'])) {
            return $obs['NIVEL_RIESGO'];
        }
        
        return 'Bajo';
    }

    /**
     * Persiste en progreso_estudiante el nivel de riesgo según alertas activas (si existe fila de progreso).
     */
    public function refrescarNivelRiesgoProgreso($aprendizId)
    {
        $aprendizId = (int)$aprendizId;
        if ($aprendizId <= 0) {
            return;
        }
        $nivel = $this->calcularNivelRiesgo(['APRENDIZ_ID' => $aprendizId]);
        $sql = 'UPDATE progreso_estudiante SET NIVEL_RIESGO_GLOBAL = :n, FECHA_ACTUALIZACION = NOW() WHERE ESTUDIANTE_ID = :id LIMIT 1';
        $this->ejecutarPreparado($sql, [':n' => $nivel, ':id' => $aprendizId]);
    }

    public function actualizarPromedioDesdeCalificaciones($aprendizId)
    {
        $aprendizId = (int)$aprendizId;
        if ($aprendizId <= 0) {
            return;
        }
        $sql = 'SELECT AVG(calificacion) AS p FROM calificaciones_evidencias WHERE aprendiz_id = :id AND calificacion IS NOT NULL';
        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendizId]);
        $row = $stmt ? $stmt->fetch() : null;
        $prom = isset($row['p']) && $row['p'] !== null ? round((float)$row['p'], 2) : null;
        if ($prom === null) {
            return;
        }
        $this->ejecutarPreparado(
            'UPDATE progreso_estudiante SET PROMEDIO_GENERAL = :p, FECHA_ACTUALIZACION = NOW() WHERE ESTUDIANTE_ID = :id LIMIT 1',
            [':p' => $prom, ':id' => $aprendizId]
        );
    }

    /**
     * Obtiene las observaciones académicas de un aprendiz
     */
    public function obtenerObservaciones($aprendizId)
    {
        $sql = "SELECT 
                    o.*,
                    i.NOMBRES as instructor_nombres,
                    i.APELLIDOS as instructor_apellidos
                FROM observacion_academica o
                LEFT JOIN instructor i ON o.INSTRUCTOR_ID = i.INSTRUCTOR_ID
                WHERE o.ESTUDIANTE_ID = :id
                ORDER BY o.FECHA DESC
                LIMIT 20";

        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendizId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Obtiene el historial de asistencia de un aprendiz
     */
    public function obtenerAsistencia($aprendizId, $limite = 10)
    {
        $sql = "SELECT 
                    ASISTENCIA_ID,
                    FECHA,
                    ESTADO
                FROM asistencia
                WHERE ESTUDIANTE_ID = :id
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

    // ==================== MÉTODOS PARA EVIDENCIAS ====================

    /**
     * Obtiene el promedio de calificaciones de las evidencias del aprendiz
     */
    public function obtenerPromedioEvidencias($aprendizId)
    {
        $sql = "SELECT AVG(calificacion) as promedio 
                FROM calificaciones_evidencias 
                WHERE aprendiz_id = :id AND calificacion IS NOT NULL";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendizId]);
        if ($stmt) {
            $result = $stmt->fetch();
            return $result['promedio'] ? round($result['promedio'], 1) : 0;
        }
        return 0;
    }

    /**
     * Obtiene el número de evidencias aprobadas por el aprendiz
     */
    public function obtenerEvidenciasAprobadas($aprendizId)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM calificaciones_evidencias 
                WHERE aprendiz_id = :id AND estado_aprobacion = 'aprobado'";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendizId]);
        if ($stmt) {
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        }
        return 0;
    }

    /**
     * Obtiene el número de evidencias pendientes del aprendiz
     */
    public function obtenerEvidenciasPendientes($aprendizId)
    {
        // Primero obtener la ficha del aprendiz
        $sqlFicha = "SELECT FICHA_ID FROM aprendiz WHERE APRENDIZ_ID = :id";
        $stmtFicha = $this->ejecutarPreparado($sqlFicha, [':id' => $aprendizId]);
        if (!$stmtFicha) return 0;
        $fila = $stmtFicha->fetch();
        if (!$fila || !$fila['FICHA_ID']) return 0;
        $fichaId = $fila['FICHA_ID'];

        // Contar evidencias de la ficha que no tienen calificación para este aprendiz
        $sql = "SELECT COUNT(*) as total 
                FROM evidencias e
                LEFT JOIN calificaciones_evidencias c 
                    ON e.evidencias_id = c.evidencia_id AND c.aprendiz_id = :aprendizId
                WHERE e.ficha_id = :fichaId AND c.calificacion_id IS NULL";
        $stmt = $this->ejecutarPreparado($sql, [
            ':aprendizId' => $aprendizId,
            ':fichaId' => $fichaId
        ]);
        if ($stmt) {
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        }
        return 0;
    }

    // ==================== MÉTODO DE ELIMINACIÓN FORZADA ====================

    /**
     * Elimina un aprendiz y todos sus registros relacionados
     */
    public function eliminarAprendizForzado($id)
    {
        try {
            // Iniciar transacción
            $this->Conexion_ID->beginTransaction();
            
            // 1. Eliminar alertas
            $sql1 = "DELETE FROM alerta WHERE ESTUDIANTE_ID = :id";
            $this->ejecutarPreparado($sql1, [':id' => $id]);
            
            // 2. Eliminar asistencias
            $sql2 = "DELETE FROM asistencia WHERE APRENDIZ_ID = :id";
            $this->ejecutarPreparado($sql2, [':id' => $id]);
            
            // 3. Eliminar observaciones académicas
            $sql3 = "DELETE FROM observacion_academica WHERE ESTUDIANTE_ID = :id";
            $this->ejecutarPreparado($sql3, [':id' => $id]);
            
            // 4. Eliminar progreso del estudiante
            $sql4 = "DELETE FROM progreso_estudiante WHERE ESTUDIANTE_ID = :id";
            $this->ejecutarPreparado($sql4, [':id' => $id]);
            
            // 5. Eliminar calificaciones de evidencias
            $sql5 = "DELETE FROM calificaciones_evidencias WHERE aprendiz_id = :id";
            $this->ejecutarPreparado($sql5, [':id' => $id]);
            
            // 6. Finalmente eliminar el aprendiz
            $sql6 = "DELETE FROM aprendiz WHERE APRENDIZ_ID = :id";
            $stmt = $this->ejecutarPreparado($sql6, [':id' => $id]);
            
            // Confirmar transacción
            $this->Conexion_ID->commit();
            
            return $stmt ? true : false;
            
        } catch (Exception $e) {
            // Revertir cambios en caso de error
            $this->Conexion_ID->rollBack();
            error_log("Error eliminando aprendiz $id: " . $e->getMessage());
            $this->ErrTxt = $e->getMessage();
            return false;
        }
    }
}
?>