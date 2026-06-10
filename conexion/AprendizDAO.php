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
                    f.FECHA_INICIO,
                    f.FECHA_FIN,
                    f.ESTADO AS ficha_estado,
                    f.CENTRO_ID,
                    c.NOMBRE AS centro_nombre,
                    r.NOMBRE AS regional_nombre,
                    r.CIUDAD AS regional_ciudad,
                    p.NOMBRE AS programa_nombre,
                    p.NIVEL_FORMACION,
                    p.PROGRAMA_ID,
                    pe.NIVEL_RIESGO_GLOBAL,
                    pe.PROMEDIO_GENERAL,
                    pe.PORCENTAJE_ASISTENCIA,
                    pe.COMPETENCIAS_APROBADAS,
                    pe.COMPETENCIAS_PENDIENTES,
                    CONCAT(gestor.NOMBRES, ' ', gestor.APELLIDOS) AS gestor_nombre,
                    gestor.EMAIL AS gestor_email
                FROM aprendiz a
                LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                LEFT JOIN centro c ON f.CENTRO_ID = c.CENTRO_ID
                LEFT JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                LEFT JOIN instructor gestor ON f.FICHA_ID = gestor.GESTOR_FICHA_ID
                LEFT JOIN progreso_estudiante pe ON a.APRENDIZ_ID = pe.ESTUDIANTE_ID
                WHERE a.APRENDIZ_ID = :id
                ORDER BY pe.FECHA_ACTUALIZACION DESC
                LIMIT 1";

        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        if (!$stmt) return null;
        $aprendiz = $stmt->fetch();
        if (!$aprendiz) return null;

        if (!isset($aprendiz['NIVEL_RIESGO_GLOBAL'])) {
            $aprendiz['NIVEL_RIESGO_GLOBAL'] = $this->calcularNivelRiesgo($aprendiz);
        }
        
        return $aprendiz;
    }

    /**
     * Busca un aprendiz usando primero la referencia, luego usuario_id y finalmente email.
     */
    public function obtenerPorSesion($referenciaId, $usuarioId, $email = null)
    {
        if ($referenciaId > 0) {
            $aprendiz = $this->obtenerPorId($referenciaId);
            if ($aprendiz) {
                return $aprendiz;
            }
        }

        if ($usuarioId > 0 && !empty($email)) {
            $aprendiz = $this->obtenerPorUsuarioId($usuarioId, $email, true);
            if ($aprendiz) {
                return $aprendiz;
            }
        }

        if ($usuarioId > 0) {
            $aprendiz = $this->obtenerPorUsuarioId($usuarioId, $email);
            if ($aprendiz) {
                return $aprendiz;
            }
        }

        if (!empty($email)) {
            $aprendiz = $this->obtenerPorUsuarioId(0, $email);
            if ($aprendiz) {
                return $aprendiz;
            }
        }

        return null;
    }

    /**
     * Obtiene aprendices paginados con su ficha y programa
     */
    public function obtenerAprendicesPaginados($inicio = 0, $cantidad = 10, $filtroFichas = false, $fichasIds = [])
    {
        $inicio = (int)$inicio;
        $cantidad = (int)$cantidad;
        // Si se pasan $fichasIds usamos filtro por IN
        if (!empty($fichasIds)) {
            $placeholders = implode(',', array_fill(0, count($fichasIds), '?'));
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
                    WHERE a.FICHA_ID IN ($placeholders)
                    ORDER BY a.APELLIDOS, a.NOMBRES
                    LIMIT $inicio, $cantidad";
            $stmt = $this->ejecutarPreparado($sql, $fichasIds);
            return $stmt ? $stmt->fetchAll() : [];
        }

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
    public function contarAprendices($filtroFichas = false, $fichasIds = [])
    {
        if (!empty($fichasIds)) {
            $placeholders = implode(',', array_fill(0, count($fichasIds), '?'));
            $sql = "SELECT COUNT(*) as total FROM aprendiz WHERE FICHA_ID IN ($placeholders)";
            $stmt = $this->ejecutarPreparado($sql, $fichasIds);
            if (!$stmt) {
                error_log("Error en contarAprendices (fichas): " . $this->imprimirError());
                return 0;
            }
            $row = $stmt->fetch();
            return $row['total'] ?? 0;
        }

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
    public function buscarPorColumnaPaginado($columna, $valor, $inicio = 0, $cantidad = 10, $fichasIds = [])
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

        $select = "SELECT 
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
                WHERE $columna LIKE '%$valor%'";

        if (!empty($fichasIds)) {
            $placeholders = implode(',', array_fill(0, count($fichasIds), '?'));
            $sql = $select . " AND a.FICHA_ID IN ($placeholders) ORDER BY a.APELLIDOS, a.NOMBRES LIMIT $inicio, $cantidad";
            $stmt = $this->ejecutarPreparado($sql, $fichasIds);
            return $stmt ? $stmt->fetchAll() : [];
        }

        $sql = $select . " ORDER BY a.APELLIDOS, a.NOMBRES LIMIT $inicio, $cantidad";
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
    public function contarBusqueda($columna, $valor, $fichasIds = [])
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
        $baseSql = "SELECT COUNT(*) as total 
                FROM aprendiz a
                LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                WHERE $columna LIKE '%$valor%'";

        if (!empty($fichasIds)) {
            $placeholders = implode(',', array_fill(0, count($fichasIds), '?'));
            $sql = $baseSql . " AND a.FICHA_ID IN ($placeholders)";
            $stmt = $this->ejecutarPreparado($sql, $fichasIds);
            if (!$stmt) {
                error_log("Error en contarBusqueda (fichas): " . $this->imprimirError());
                return 0;
            }
            $row = $stmt->fetch();
            return $row['total'] ?? 0;
        }

        $this->Consulta_ID = $this->ejecutarSQL($baseSql);
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

        $sql = "SELECT * FROM progreso_estudiante WHERE ESTUDIANTE_ID = :id ORDER BY FECHA_ACTUALIZACION DESC LIMIT 1";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendiz['APRENDIZ_ID']]);
        $progreso = $stmt ? $stmt->fetch() : null;
        
        if ($progreso && !empty($progreso['NIVEL_RIESGO_GLOBAL'])) {
            return $progreso['NIVEL_RIESGO_GLOBAL'];
        }
        
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
     * Persiste en progreso_estudiante el nivel de riesgo según alertas activas
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
        $sql = "SELECT *
                FROM (
                    SELECT
                        CONCAT('OBS-', o.OBSERVACION_ID) AS REGISTRO_ID,
                        'observacion' AS ORIGEN,
                        o.OBSERVACION_ID,
                        o.TIPO,
                        o.DESCRIPCION,
                        o.NIVEL_RIESGO,
                        o.FECHA,
                        i.NOMBRES AS instructor_nombres,
                        i.APELLIDOS AS instructor_apellidos
                    FROM observacion_academica o
                    LEFT JOIN instructor i ON o.INSTRUCTOR_ID = i.INSTRUCTOR_ID
                    WHERE o.ESTUDIANTE_ID = :id_observaciones

                    UNION ALL

                    SELECT
                        CONCAT('ALT-', a.ALERTA_ID) AS REGISTRO_ID,
                        'alerta' AS ORIGEN,
                        NULL AS OBSERVACION_ID,
                        COALESCE(o.TIPO, 'Alerta') AS TIPO,
                        a.DESCRIPCION,
                        a.NIVEL AS NIVEL_RIESGO,
                        a.FECHA_GENERACION AS FECHA,
                        COALESCE(i.NOMBRES, 'Sistema') AS instructor_nombres,
                        i.APELLIDOS AS instructor_apellidos
                    FROM alerta a
                    LEFT JOIN observacion_academica o ON o.OBSERVACION_ID = (
                        SELECT o2.OBSERVACION_ID
                        FROM observacion_academica o2
                        WHERE o2.ESTUDIANTE_ID = a.ESTUDIANTE_ID
                          AND (
                                (
                                    o2.DESCRIPCION = a.DESCRIPCION
                                    AND o2.NIVEL_RIESGO = a.NIVEL
                                    AND ABS(TIMESTAMPDIFF(SECOND, o2.FECHA, a.FECHA_GENERACION)) <= 5
                                )
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
                    WHERE a.ESTUDIANTE_ID = :id_alertas
                      AND a.ESTADO = 'Activa'
                      AND o.OBSERVACION_ID IS NULL
                ) AS historial
                ORDER BY FECHA DESC
                LIMIT 20";

        $stmt = $this->ejecutarPreparado($sql, [
            ':id_observaciones' => $aprendizId,
            ':id_alertas' => $aprendizId
        ]);
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
        $sqlFicha = "SELECT FICHA_ID FROM aprendiz WHERE APRENDIZ_ID = :id";
        $stmtFicha = $this->ejecutarPreparado($sqlFicha, [':id' => $aprendizId]);
        if (!$stmtFicha) return 0;
        $fila = $stmtFicha->fetch();
        if (!$fila || !$fila['FICHA_ID']) return 0;
        $fichaId = $fila['FICHA_ID'];

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
            $this->Conexion_ID->beginTransaction();
            
            $sql1 = "DELETE FROM alerta WHERE ESTUDIANTE_ID = :id";
            $this->ejecutarPreparado($sql1, [':id' => $id]);
            
            $sql2 = "DELETE FROM asistencia WHERE APRENDIZ_ID = :id";
            $this->ejecutarPreparado($sql2, [':id' => $id]);
            
            $sql3 = "DELETE FROM observacion_academica WHERE ESTUDIANTE_ID = :id";
            $this->ejecutarPreparado($sql3, [':id' => $id]);
            
            $sql4 = "DELETE FROM progreso_estudiante WHERE ESTUDIANTE_ID = :id";
            $this->ejecutarPreparado($sql4, [':id' => $id]);
            
            $sql5 = "DELETE FROM calificaciones_evidencias WHERE aprendiz_id = :id";
            $this->ejecutarPreparado($sql5, [':id' => $id]);
            
            $sql6 = "DELETE FROM aprendiz WHERE APRENDIZ_ID = :id";
            $stmt = $this->ejecutarPreparado($sql6, [':id' => $id]);
            
            $this->Conexion_ID->commit();
            return $stmt ? true : false;
            
        } catch (Exception $e) {
            $this->Conexion_ID->rollBack();
            error_log("Error eliminando aprendiz $id: " . $e->getMessage());
            $this->ErrTxt = $e->getMessage();
            return false;
        }
    }

    // ==================== MÉTODOS PARA EL PERFIL Y ESTADÍSTICAS ====================

    /**
     * Obtiene un aprendiz por su ID de usuario o por email si no hay relación directa.
     */
    public function obtenerPorUsuarioId($usuarioId, $email = null, $emailRequireMatch = false)
    {
        $baseSql = "SELECT a.*, f.CODIGO_FICHA, f.FECHA_INICIO, f.FECHA_FIN, f.ESTADO AS ficha_estado,
                       c.NOMBRE as centro_nombre, r.NOMBRE as regional_nombre,
                       r.CIUDAD as regional_ciudad, p.NOMBRE as programa_nombre, p.NIVEL_FORMACION,
                       CONCAT(gestor.NOMBRES, ' ', gestor.APELLIDOS) AS gestor_nombre,
                       gestor.EMAIL AS gestor_email
                FROM aprendiz a
                LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                LEFT JOIN centro c ON f.CENTRO_ID = c.CENTRO_ID
                LEFT JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                LEFT JOIN instructor gestor ON f.FICHA_ID = gestor.GESTOR_FICHA_ID";

        if ($usuarioId > 0 && !empty($email)) {
            $sql = $baseSql . " WHERE a.usuario_id = :usuarioId AND a.EMAIL = :email LIMIT 1";
            $stmt = $this->ejecutarPreparado($sql, [':usuarioId' => $usuarioId, ':email' => $email]);
            if ($stmt) {
                $aprendiz = $stmt->fetch();
                if ($aprendiz) {
                    return $aprendiz;
                }
            }
            if ($emailRequireMatch) {
                return null;
            }
        }

        if ($usuarioId > 0) {
            $sql = $baseSql . " WHERE a.usuario_id = :usuarioId LIMIT 1";
            $stmt = $this->ejecutarPreparado($sql, [':usuarioId' => $usuarioId]);
            if ($stmt) {
                $aprendiz = $stmt->fetch();
                if ($aprendiz) {
                    return $aprendiz;
                }
            }
        }

        if (!empty($email)) {
            $sql = $baseSql . " WHERE a.EMAIL = :email LIMIT 1";
            $stmt = $this->ejecutarPreparado($sql, [':email' => $email]);
            return $stmt ? $stmt->fetch() : null;
        }

        return null;
    }

    /**
     * Resumen de asistencia para un aprendiz (delega en AsistenciaDAO)
     */
    public function obtenerResumenAsistencia($aprendizId)
    {
        require_once __DIR__ . '/AsistenciaDAO.php';
        $asistenciaDAO = new AsistenciaDAO();
        return $asistenciaDAO->obtenerResumenAprendiz($aprendizId);
    }

    /**
     * Evidencias con calificación del aprendiz
     */
    public function obtenerEvidenciasConCalificacion($aprendizId)
    {
        $sqlFicha = "SELECT FICHA_ID FROM aprendiz WHERE APRENDIZ_ID = :id";
        $stmtFicha = $this->ejecutarPreparado($sqlFicha, [':id' => $aprendizId]);
        if (!$stmtFicha) return [];
        $fila = $stmtFicha->fetch();
        if (!$fila || !$fila['FICHA_ID']) return [];
        $fichaId = $fila['FICHA_ID'];

        $sql = "SELECT e.*, c.calificacion, c.estado_aprobacion
                FROM evidencias e
                WHERE e.ficha_id = :fichaId
                ORDER BY e.fecha_evidencia DESC";
        $stmt = $this->ejecutarPreparado($sql, [':fichaId' => $fichaId]);
        $evidencias = $stmt ? $stmt->fetchAll() : [];

        foreach ($evidencias as &$ev) {
            $sqlCal = "SELECT calificacion, estado_aprobacion 
                       FROM calificaciones_evidencias 
                       WHERE evidencia_id = :evId AND aprendiz_id = :apId";
            $stmtCal = $this->ejecutarPreparado($sqlCal, [
                ':evId' => $ev['evidencias_id'],
                ':apId' => $aprendizId
            ]);
            if ($stmtCal && ($cal = $stmtCal->fetch())) {
                $ev['calificacion'] = $cal['calificacion'];
                $ev['estado_aprobacion'] = $cal['estado_aprobacion'];
            } else {
                $ev['calificacion'] = null;
                $ev['estado_aprobacion'] = null;
            }
        }
        return $evidencias;
    }

    /**
     * Obtiene la asistencia mensual de un aprendiz
     */
    public function obtenerAsistenciaMensual($aprendizId, $year = null, $month = null)
    {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        $sql = "SELECT 
                    DAY(FECHA) as dia,
                    ESTADO,
                    HORAS_FALTA
                FROM asistencia
                WHERE ESTUDIANTE_ID = :id
                AND YEAR(FECHA) = :year
                AND MONTH(FECHA) = :month
                ORDER BY FECHA ASC";
        $stmt = $this->ejecutarPreparado($sql, [
            ':id' => $aprendizId,
            ':year' => $year,
            ':month' => $month
        ]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene la evolución de notas del aprendiz
     */
    public function obtenerEvolucionNotas($aprendizId)
    {
        $sql = "SELECT 
                    e.fecha_evidencia as fecha,
                    c.calificacion,
                    e.nombre as evidencia_nombre
                FROM calificaciones_evidencias c
                JOIN evidencias e ON c.evidencia_id = e.evidencias_id
                WHERE c.aprendiz_id = :id
                AND c.calificacion IS NOT NULL
                ORDER BY e.fecha_evidencia ASC";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendizId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene las faltas pendientes de justificación
     */
    public function obtenerFaltasConJustificacionPendiente($aprendizId)
    {
        $sql = "SELECT 
                    ASISTENCIA_ID,
                    FECHA,
                    ESTADO,
                    HORAS_FALTA,
                    FECHA_LIMITE_EXCUSA
                FROM asistencia
                WHERE ESTUDIANTE_ID = :id
                AND ESTADO = 'falta'
                AND EXCUSA_PRESENTADA = 0
                AND (FECHA_LIMITE_EXCUSA IS NULL OR FECHA_LIMITE_EXCUSA >= CURDATE())
                ORDER BY FECHA ASC";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendizId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Verifica si una falta puede ser justificada
     */
    public function obtenerJustificacionPendiente($asistenciaId, $aprendizId)
    {
        $sql = "SELECT * FROM asistencia 
                WHERE ASISTENCIA_ID = :asistencia_id 
                AND ESTUDIANTE_ID = :aprendiz_id 
                AND ESTADO = 'falta' 
                AND EXCUSA_PRESENTADA = 0
                AND (FECHA_LIMITE_EXCUSA IS NULL OR FECHA_LIMITE_EXCUSA >= CURDATE())";
        $stmt = $this->ejecutarPreparado($sql, [
            ':asistencia_id' => $asistenciaId,
            ':aprendiz_id' => $aprendizId
        ]);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Guarda la justificación de una falta
     */
    public function guardarJustificacion($asistenciaId, $aprendizId, $textoJustificacion, $archivoRuta = null)
    {
        require_once __DIR__ . '/JustificacionDAO.php';
        $justDao = new JustificacionDAO();
        return $justDao->guardarJustificacion($asistenciaId, $aprendizId, $textoJustificacion, $archivoRuta);
    }

    /**
     * Obtiene las evidencias próximas a vencer
     */
    public function obtenerEvidenciasProximas($aprendizId, $dias = 30)
    {
        $sqlFicha = "SELECT FICHA_ID FROM aprendiz WHERE APRENDIZ_ID = :id";
        $stmtFicha = $this->ejecutarPreparado($sqlFicha, [':id' => $aprendizId]);
        if (!$stmtFicha) return [];
        $fila = $stmtFicha->fetch();
        if (!$fila || !$fila['FICHA_ID']) return [];
        $fichaId = $fila['FICHA_ID'];

        $sql = "SELECT 
                    e.*,
                    CASE 
                        WHEN c.calificacion IS NOT NULL THEN 'calificado'
                        ELSE 'pendiente'
                    END as estado_calificacion
                FROM evidencias e
                LEFT JOIN calificaciones_evidencias c 
                    ON e.evidencias_id = c.evidencia_id AND c.aprendiz_id = :aprendizId
                WHERE e.ficha_id = :fichaId
                AND e.fecha_evidencia >= CURDATE()
                AND e.fecha_evidencia <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                ORDER BY e.fecha_evidencia ASC";
        $stmt = $this->ejecutarPreparado($sql, [
            ':aprendizId' => $aprendizId,
            ':fichaId' => $fichaId,
            ':dias' => $dias
        ]);
        return $stmt ? $stmt->fetchAll() : [];
    }

        /**
     * Obtiene aprendices cuyas fichas están en la lista de IDs
     * @param array $fichasIds
     * @param int $inicio
     * @param int $cantidad
     * @param bool $filtroFichas (si es true, solo los que tienen ficha asignada)
     * @return array
     */
    public function obtenerAprendicesPorFichas($fichasIds, $inicio = 0, $cantidad = 10, $filtroFichas = false)
    {
        if (empty($fichasIds)) return [];
        $placeholders = implode(',', array_fill(0, count($fichasIds), '?'));
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
                WHERE a.FICHA_ID IN ($placeholders)";
        if ($filtroFichas) {
            $sql .= " AND a.FICHA_ID IS NOT NULL";
        }
        $sql .= " ORDER BY a.APELLIDOS, a.NOMBRES LIMIT $inicio, $cantidad";
        $stmt = $this->ejecutarPreparado($sql, $fichasIds);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Cuenta aprendices cuyas fichas están en la lista de IDs
     * @param array $fichasIds
     * @param bool $filtroFichas
     * @return int
     */
    public function contarAprendicesPorFichas($fichasIds, $filtroFichas = false)
    {
        if (empty($fichasIds)) return 0;
        $placeholders = implode(',', array_fill(0, count($fichasIds), '?'));
        $sql = "SELECT COUNT(*) as total FROM aprendiz WHERE FICHA_ID IN ($placeholders)";
        if ($filtroFichas) {
            $sql .= " AND FICHA_ID IS NOT NULL";
        }
        $stmt = $this->ejecutarPreparado($sql, $fichasIds);
        if (!$stmt) return 0;
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }
}
?>