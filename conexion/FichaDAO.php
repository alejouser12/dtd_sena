<?php
// conexion/FichaDAO.php
require_once __DIR__ . "/conexion.php";

class FichaDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    /**
     * Obtiene todas las fichas con información relacionada (programa, centro, regional, total aprendices)
     * @return array
     */
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

    /**
     * Obtiene las fichas de un centro específico (útil para selects dependientes)
     * @param int $centroId
     * @return array
     */
    public function obtenerPorCentro($centroId)
    {
        $sql = "SELECT f.FICHA_ID, f.CODIGO_FICHA, p.NOMBRE as programa_nombre
                FROM ficha f
                JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE f.CENTRO_ID = :centroId AND f.ESTADO = 'Activa'
                ORDER BY f.CODIGO_FICHA";
        $stmt = $this->ejecutarPreparado($sql, [':centroId' => $centroId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene una ficha por ID con toda la información relacionada.
     * Versión robusta: consultas separadas para evitar fallos por JOIN.
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId($id)
    {
        // 1. Obtener datos básicos de la ficha
        $sqlFicha = "SELECT FICHA_ID, CODIGO_FICHA, FECHA_INICIO, FECHA_FIN, ESTADO, PROGRAMA_ID, CENTRO_ID
                     FROM ficha WHERE FICHA_ID = :id";
        $stmt = $this->ejecutarPreparado($sqlFicha, [':id' => $id]);
        if (!$stmt) {
            error_log("FichaDAO::obtenerPorId($id) - falló consulta básica");
            return null;
        }
        $ficha = $stmt->fetch();
        if (!$ficha) {
            error_log("FichaDAO::obtenerPorId($id) - ficha no existe");
            return null;
        }

        // 2. Cargar información del programa si existe
        if (!empty($ficha['PROGRAMA_ID'])) {
            $sqlProg = "SELECT NOMBRE as programa_nombre, NIVEL_FORMACION 
                        FROM programa WHERE PROGRAMA_ID = :pid";
            $stmtProg = $this->ejecutarPreparado($sqlProg, [':pid' => $ficha['PROGRAMA_ID']]);
            if ($stmtProg && ($prog = $stmtProg->fetch())) {
                $ficha['programa_nombre'] = $prog['programa_nombre'];
                $ficha['NIVEL_FORMACION'] = $prog['NIVEL_FORMACION'];
            } else {
                $ficha['programa_nombre'] = null;
                $ficha['NIVEL_FORMACION'] = null;
            }
        } else {
            $ficha['programa_nombre'] = null;
            $ficha['NIVEL_FORMACION'] = null;
        }

        // 3. Cargar información del centro si existe
        if (!empty($ficha['CENTRO_ID'])) {
            $sqlCentro = "SELECT NOMBRE as centro_nombre, CODIGO as centro_codigo, 
                                 DIRECCION as centro_direccion, TELEFONO as centro_telefono
                          FROM centro WHERE CENTRO_ID = :cid";
            $stmtCentro = $this->ejecutarPreparado($sqlCentro, [':cid' => $ficha['CENTRO_ID']]);
            if ($stmtCentro && ($centro = $stmtCentro->fetch())) {
                $ficha['centro_nombre'] = $centro['centro_nombre'];
                $ficha['centro_codigo'] = $centro['centro_codigo'];
                $ficha['centro_direccion'] = $centro['centro_direccion'];
                $ficha['centro_telefono'] = $centro['centro_telefono'];

                // 4. Cargar regional a través del centro
                $sqlReg = "SELECT r.NOMBRE as regional_nombre, r.CIUDAD as regional_ciudad
                           FROM regional r
                           INNER JOIN centro c ON c.REGIONAL_ID = r.REGIONAL_ID
                           WHERE c.CENTRO_ID = :cid";
                $stmtReg = $this->ejecutarPreparado($sqlReg, [':cid' => $ficha['CENTRO_ID']]);
                if ($stmtReg && ($reg = $stmtReg->fetch())) {
                    $ficha['regional_nombre'] = $reg['regional_nombre'];
                    $ficha['regional_ciudad'] = $reg['regional_ciudad'];
                } else {
                    $ficha['regional_nombre'] = null;
                    $ficha['regional_ciudad'] = null;
                }
            } else {
                $ficha['centro_nombre'] = null;
                $ficha['centro_codigo'] = null;
                $ficha['centro_direccion'] = null;
                $ficha['centro_telefono'] = null;
                $ficha['regional_nombre'] = null;
                $ficha['regional_ciudad'] = null;
            }
        } else {
            $ficha['centro_nombre'] = null;
            $ficha['centro_codigo'] = null;
            $ficha['centro_direccion'] = null;
            $ficha['centro_telefono'] = null;
            $ficha['regional_nombre'] = null;
            $ficha['regional_ciudad'] = null;
        }

        // 5. Contar aprendices
        $sqlAprendices = "SELECT COUNT(*) as total FROM aprendiz WHERE FICHA_ID = :id";
        $stmtAprendices = $this->ejecutarPreparado($sqlAprendices, [':id' => $id]);
        $ficha['total_aprendices'] = ($stmtAprendices && ($row = $stmtAprendices->fetch())) ? (int)$row['total'] : 0;

        // 6. Valores por defecto para campos que no están en BD
        $ficha['instructor_nombres'] = null;
        $ficha['instructor_apellidos'] = null;
        $ficha['instructor_email'] = null;
        $ficha['instructor_especialidad'] = null;
        $ficha['HORARIO'] = null;
        $ficha['AULA'] = null;

        return $ficha;
    }

    /**
     * Obtiene los aprendices de una ficha (con su progreso académico)
     * @param int $fichaId
     * @return array
     */
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
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene las asistencias de una semana para los aprendices de una ficha (formato antiguo, se recomienda usar AsistenciaDAO)
     * @deprecated Usar AsistenciaDAO->obtenerAsistenciasSemana()
     */
    public function obtenerAsistenciasSemana($fichaId, $inicioSemana)
    {
        $finSemana = date('Y-m-d', strtotime($inicioSemana . ' +5 days'));
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
            foreach ($stmt->fetchAll() as $row) {
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
     * Guarda asistencias de una semana (método obsoleto, usar AsistenciaDAO)
     * @deprecated
     */
    public function guardarAsistenciasSemana($fichaId, $inicioSemana, $asistenciasMarcadas)
    {
        // Este método ya no se usa, se recomienda usar AsistenciaDAO
        // Se mantiene por compatibilidad pero no se recomienda su uso.
        return false;
    }

    /**
     * Crea una nueva ficha
     * @param string $codigoFicha
     * @param int $programaId
     * @param int $centroId
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param string $estado
     * @return int|false ID de la ficha o false en caso de error
     */
    public function crearFicha($codigoFicha, $programaId, $centroId, $fechaInicio, $fechaFin, $estado = 'Activa')
    {
        $sql = "INSERT INTO ficha (CODIGO_FICHA, PROGRAMA_ID, CENTRO_ID, FECHA_INICIO, FECHA_FIN, ESTADO)
                VALUES (:codigo, :programaId, :centroId, :inicio, :fin, :estado)";
        $stmt = $this->ejecutarPreparado($sql, [
            ':codigo' => $codigoFicha,
            ':programaId' => $programaId,
            ':centroId' => $centroId,
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin,
            ':estado' => $estado
        ]);
        return $stmt ? $this->Conexion_ID->lastInsertId() : false;
    }

    /**
     * Actualiza una ficha existente
     * @param int $id
     * @param string $codigoFicha
     * @param int $programaId
     * @param int $centroId
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param string $estado
     * @return bool
     */
    public function actualizarFicha($id, $codigoFicha, $programaId, $centroId, $fechaInicio, $fechaFin, $estado = 'Activa')
    {
        $sql = "UPDATE ficha SET 
                    CODIGO_FICHA = :codigo,
                    PROGRAMA_ID = :programaId,
                    CENTRO_ID = :centroId,
                    FECHA_INICIO = :inicio,
                    FECHA_FIN = :fin,
                    ESTADO = :estado
                WHERE FICHA_ID = :id";
        $stmt = $this->ejecutarPreparado($sql, [
            ':id' => $id,
            ':codigo' => $codigoFicha,
            ':programaId' => $programaId,
            ':centroId' => $centroId,
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin,
            ':estado' => $estado
        ]);
        return $stmt ? true : false;
    }

    /**
     * Elimina una ficha (solo si no tiene aprendices asociados)
     * @param int $id
     * @return bool
     */
    public function eliminarFicha($id)
    {
        // Verificar si tiene aprendices
        $sqlCheck = "SELECT COUNT(*) as total FROM aprendiz WHERE FICHA_ID = :id";
        $stmt = $this->ejecutarPreparado($sqlCheck, [':id' => $id]);
        if ($stmt && ($row = $stmt->fetch()) && $row['total'] > 0) {
            return false;
        }
        $sql = "DELETE FROM ficha WHERE FICHA_ID = :id";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        return $stmt ? true : false;
    }

        /**
     * Obtiene fichas por una lista de IDs
     * @param array $ids
     * @return array
     */
    public function obtenerPorIds($ids)
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT 
                    f.*,
                    p.NOMBRE as programa_nombre,
                    (SELECT COUNT(*) FROM aprendiz WHERE FICHA_ID = f.FICHA_ID) as total_aprendices
                FROM ficha f
                LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE f.FICHA_ID IN ($placeholders)
                ORDER BY f.CODIGO_FICHA";
        $stmt = $this->ejecutarPreparado($sql, $ids);
        return $stmt ? $stmt->fetchAll() : [];
    }
}
?>