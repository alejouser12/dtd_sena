<?php
require_once __DIR__ . "/conexion.php";

class InstructorDAO extends BaseDatos
{
    // Implementación de métodos abstractos
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    //Optiene todos los instructores
    public function obtenerInstructores()
    {
        $sql = "SELECT 
                    INSTRUCTOR_ID,
                    NOMBRES,
                    APELLIDOS,
                    EMAIL,
                    ESPECIALIDAD
                FROM instructor
                ORDER BY APELLIDOS, NOMBRES";

        $this->Consulta_ID = $this->ejecutarSQL($sql);

        if ($this->Consulta_ID) {
            return $this->cargarTodo();
        }
        return [];
    }

    /**
     * Busca instructores con filtro por columna específica
     * @param string $columna Nombre de la columna (NOMBRES, APELLIDOS, EMAIL, ESPECIALIDAD)
     * @param string $valor Valor a buscar
     */
    public function buscarPorColumna($columna, $valor)
    {
        // Validar que la columna sea permitida
        $columnasPermitidas = [
            'NOMBRES', 
            'APELLIDOS', 
            'EMAIL', 
            'ESPECIALIDAD'
        ];
        
        if (!in_array($columna, $columnasPermitidas)) {
            return [];
        }

        $sql = "SELECT 
                    INSTRUCTOR_ID,
                    NOMBRES,
                    APELLIDOS,
                    EMAIL,
                    ESPECIALIDAD
                FROM instructor
                WHERE $columna LIKE :valor
                ORDER BY APELLIDOS, NOMBRES";

        $stmt = $this->ejecutarPreparado($sql, [':valor' => "%$valor%"]);
        
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    //Optiene un instructor por su ID y trae la informacion detallada
    public function obtenerPorId($id)
    {
        // Primero obtener datos básicos del instructor
        $sql = "SELECT 
                    INSTRUCTOR_ID,
                    NOMBRES,
                    APELLIDOS,
                    EMAIL,
                    ESPECIALIDAD
                FROM instructor 
                WHERE INSTRUCTOR_ID = :id";
        
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        
        if (!$stmt) {
            return null;
        }
        
        $instructor = $stmt->fetch();
        
        if (!$instructor) {
            return null;
        }

        // Obtener total de fichas a través de la tabla intermedia instructor_ficha
        $sqlFichas = "SELECT COUNT(*) as total FROM instructor_ficha WHERE INSTRUCTOR_ID = :id";
        $stmtFichas = $this->ejecutarPreparado($sqlFichas, [':id' => $id]);
        if ($stmtFichas) {
            $fichas = $stmtFichas->fetch();
            $instructor['total_fichas'] = $fichas['total'];
        } else {
            $instructor['total_fichas'] = 0;
        }

        // Obtener fichas activas (aquellas fichas con estado 'Activa')
        $sqlActivas = "SELECT COUNT(*) as total 
                       FROM instructor_ficha ifi
                       INNER JOIN ficha f ON ifi.FICHA_ID = f.FICHA_ID
                       WHERE ifi.INSTRUCTOR_ID = :id AND f.ESTADO = 'Activa'";
        $stmtActivas = $this->ejecutarPreparado($sqlActivas, [':id' => $id]);
        if ($stmtActivas) {
            $activas = $stmtActivas->fetch();
            $instructor['fichas_activas'] = $activas['total'];
        } else {
            $instructor['fichas_activas'] = 0;
        }

        // Obtener total de aprendices (a través de las fichas del instructor)
        $sqlAprendices = "SELECT COUNT(DISTINCT a.APRENDIZ_ID) as total
                          FROM instructor_ficha ifi
                          INNER JOIN ficha f ON ifi.FICHA_ID = f.FICHA_ID
                          INNER JOIN aprendiz a ON a.FICHA_ID = f.FICHA_ID
                          WHERE ifi.INSTRUCTOR_ID = :id";
        $stmtAprendices = $this->ejecutarPreparado($sqlAprendices, [':id' => $id]);
        if ($stmtAprendices) {
            $aprendices = $stmtAprendices->fetch();
            $instructor['total_aprendices'] = $aprendices['total'];
        } else {
            $instructor['total_aprendices'] = 0;
        }
        
        return $instructor;
    }

    // optener las fichas de un instructor
    public function obtenerFichas($instructorId)
    {
        $sql = "SELECT 
                    f.FICHA_ID,
                    f.CODIGO_FICHA,
                    f.FECHA_INICIO,
                    f.FECHA_FIN,
                    f.ESTADO,
                    'Horario no definido' AS HORARIO,
                    'N/A' AS AULA,
                    p.NOMBRE as PROGRAMA_NOMBRE,
                    p.NIVEL_FORMACION,
                    (SELECT COUNT(*) FROM aprendiz a WHERE a.FICHA_ID = f.FICHA_ID) as total_aprendices
                FROM instructor_ficha ifi
                INNER JOIN ficha f ON ifi.FICHA_ID = f.FICHA_ID
                INNER JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE ifi.INSTRUCTOR_ID = :instructorId
                ORDER BY f.ESTADO, f.FECHA_INICIO DESC";

        $stmt = $this->ejecutarPreparado($sql, [':instructorId' => $instructorId]);
        
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    //optiene las proximas clases de un instructo
    public function obtenerProximasClases($instructorId, $limite = 5)
    {
        $sql = "SELECT 
                    h.*,
                    f.CODIGO_FICHA,
                    p.NOMBRE as PROGRAMA_NOMBRE
                FROM horario h
                INNER JOIN ficha f ON h.FICHA_ID = f.FICHA_ID
                INNER JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                WHERE f.INSTRUCTOR_ID = :instructorId
                AND h.FECHA >= CURDATE()
                ORDER BY h.FECHA, h.HORA_INICIO
                LIMIT :limite";

        $stmt = $this->ejecutarPreparado($sql, [
            ':instructorId' => $instructorId,
            ':limite' => $limite
        ]);
        
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
}
?>