<?php
// conexion/instructorDAO.php — VERSION CORREGIDA
require_once __DIR__ . "/conexion.php";

class InstructorDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    public function obtenerInstructores()
    {
        $sql = "SELECT INSTRUCTOR_ID, NOMBRES, APELLIDOS, EMAIL, ESPECIALIDAD
                FROM instructor ORDER BY APELLIDOS, NOMBRES";
        $this->Consulta_ID = $this->ejecutarSQL($sql);
        return $this->Consulta_ID ? $this->cargarTodo() : [];
    }

    public function buscarPorColumna($columna, $valor)
    {
        $columnasPermitidas = ['NOMBRES','APELLIDOS','EMAIL','ESPECIALIDAD'];
        if (!in_array($columna, $columnasPermitidas)) return [];
        $sql  = "SELECT INSTRUCTOR_ID, NOMBRES, APELLIDOS, EMAIL, ESPECIALIDAD
                 FROM instructor WHERE $columna LIKE :valor ORDER BY APELLIDOS, NOMBRES";
        $stmt = $this->ejecutarPreparado($sql, [':valor' => "%$valor%"]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function obtenerPorId($id)
    {
        $sql  = "SELECT INSTRUCTOR_ID, NOMBRES, APELLIDOS, EMAIL, ESPECIALIDAD
                 FROM instructor WHERE INSTRUCTOR_ID = :id";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        if (!$stmt) return null;
        $instructor = $stmt->fetch();
        if (!$instructor) return null;

        // Total fichas
        $r = $this->ejecutarPreparado(
            "SELECT COUNT(*) as total FROM instructor_ficha WHERE INSTRUCTOR_ID = :id",
            [':id' => $id]);
        $instructor['total_fichas'] = $r ? (int)$r->fetch()['total'] : 0;

        // Fichas activas
        $r = $this->ejecutarPreparado(
            "SELECT COUNT(*) as total
             FROM instructor_ficha ifi
             INNER JOIN ficha f ON ifi.FICHA_ID = f.FICHA_ID
             WHERE ifi.INSTRUCTOR_ID = :id AND f.ESTADO = 'Activa'",
            [':id' => $id]);
        $instructor['fichas_activas'] = $r ? (int)$r->fetch()['total'] : 0;

        // Total aprendices
        $r = $this->ejecutarPreparado(
            "SELECT COUNT(DISTINCT a.APRENDIZ_ID) as total
             FROM instructor_ficha ifi
             INNER JOIN ficha f ON ifi.FICHA_ID = f.FICHA_ID
             INNER JOIN aprendiz a ON a.FICHA_ID = f.FICHA_ID
             WHERE ifi.INSTRUCTOR_ID = :id",
            [':id' => $id]);
        $instructor['total_aprendices'] = $r ? (int)$r->fetch()['total'] : 0;

        return $instructor;
    }

    public function obtenerFichas($instructorId)
    {
        // ⚠️ Usa instructor_ficha — NO ficha.INSTRUCTOR_ID (no existe)
        $sql  = "SELECT
                    f.FICHA_ID,
                    f.CODIGO_FICHA,
                    f.FECHA_INICIO,
                    f.FECHA_FIN,
                    f.ESTADO,
                    p.NOMBRE  AS PROGRAMA_NOMBRE,
                    p.NIVEL_FORMACION,
                    (SELECT COUNT(*) FROM aprendiz a WHERE a.FICHA_ID = f.FICHA_ID) AS total_aprendices
                 FROM instructor_ficha ifi
                 INNER JOIN ficha     f ON ifi.FICHA_ID    = f.FICHA_ID
                 INNER JOIN programa  p ON f.PROGRAMA_ID   = p.PROGRAMA_ID
                 WHERE ifi.INSTRUCTOR_ID = :instructorId
                 ORDER BY f.ESTADO, f.FECHA_INICIO DESC";
        $stmt = $this->ejecutarPreparado($sql, [':instructorId' => $instructorId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    // obtenerProximasClases: la tabla horario no tiene FECHA, usa FECHA_DESDE/HASTA
    public function obtenerProximasClases($instructorId, $limite = 5)
    {
        $sql  = "SELECT h.*, f.CODIGO_FICHA, p.NOMBRE AS PROGRAMA_NOMBRE
                 FROM horario h
                 INNER JOIN ficha    f ON h.FICHA_ID     = f.FICHA_ID
                 INNER JOIN programa p ON f.PROGRAMA_ID  = p.PROGRAMA_ID
                 INNER JOIN instructor_ficha ifi ON ifi.FICHA_ID = f.FICHA_ID
                 WHERE ifi.INSTRUCTOR_ID = :instructorId
                   AND h.FECHA_HASTA >= CURDATE()
                 ORDER BY h.FECHA_DESDE, h.HORA_INICIO
                 LIMIT :limite";
        $stmt = $this->ejecutarPreparado($sql, [
            ':instructorId' => $instructorId,
            ':limite'       => (int)$limite,
        ]);
        return $stmt ? $stmt->fetchAll() : [];
    }
}