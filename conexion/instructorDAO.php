<?php
// conexion/instructorDAO.php — VERSION COMPLETA CON SOPORTE PARA FICHAS
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
        $sql  = "SELECT INSTRUCTOR_ID, NOMBRES, APELLIDOS, EMAIL, ESPECIALIDAD, GESTOR_FICHA_ID
                 FROM instructor WHERE INSTRUCTOR_ID = :id";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        if (!$stmt) return null;
        $instructor = $stmt->fetch();
        if (!$instructor) return null;

        $r = $this->ejecutarPreparado(
            "SELECT COUNT(*) as total
             FROM (
                 SELECT FICHA_ID FROM instructor_ficha WHERE INSTRUCTOR_ID = :id
                 UNION
                 SELECT GESTOR_FICHA_ID FROM instructor WHERE INSTRUCTOR_ID = :id AND GESTOR_FICHA_ID IS NOT NULL
             ) AS fichas",
            [':id' => $id]);
        $instructor['total_fichas'] = $r ? (int)$r->fetch()['total'] : 0;

        $r = $this->ejecutarPreparado(
            "SELECT COUNT(*) as total
             FROM (
                 SELECT FICHA_ID FROM instructor_ficha WHERE INSTRUCTOR_ID = :id
                 UNION
                 SELECT GESTOR_FICHA_ID FROM instructor WHERE INSTRUCTOR_ID = :id AND GESTOR_FICHA_ID IS NOT NULL
             ) AS fichas
             INNER JOIN ficha f ON fichas.FICHA_ID = f.FICHA_ID
             WHERE f.ESTADO = 'Activa'",
            [':id' => $id]);
        $instructor['fichas_activas'] = $r ? (int)$r->fetch()['total'] : 0;

        $r = $this->ejecutarPreparado(
            "SELECT COUNT(DISTINCT a.APRENDIZ_ID) as total
             FROM (
                 SELECT FICHA_ID FROM instructor_ficha WHERE INSTRUCTOR_ID = :id
                 UNION
                 SELECT GESTOR_FICHA_ID FROM instructor WHERE INSTRUCTOR_ID = :id AND GESTOR_FICHA_ID IS NOT NULL
             ) AS fichas
             INNER JOIN ficha f ON fichas.FICHA_ID = f.FICHA_ID
             INNER JOIN aprendiz a ON a.FICHA_ID = f.FICHA_ID",
            [':id' => $id]);
        $instructor['total_aprendices'] = $r ? (int)$r->fetch()['total'] : 0;

        return $instructor;
    }

    public function obtenerFichas($instructorId)
    {
        $sql  = "SELECT
                    f.FICHA_ID,
                    f.CODIGO_FICHA,
                    f.FECHA_INICIO,
                    f.FECHA_FIN,
                    f.ESTADO,
                    p.NOMBRE  AS PROGRAMA_NOMBRE,
                    p.NIVEL_FORMACION,
                    (SELECT COUNT(*) FROM aprendiz a WHERE a.FICHA_ID = f.FICHA_ID) AS total_aprendices
                 FROM ficha f
                 INNER JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                 WHERE f.FICHA_ID IN (
                     SELECT FICHA_ID FROM instructor_ficha WHERE INSTRUCTOR_ID = :instructorId
                     UNION
                     SELECT GESTOR_FICHA_ID FROM instructor WHERE INSTRUCTOR_ID = :instructorId AND GESTOR_FICHA_ID IS NOT NULL
                 )
                 ORDER BY f.ESTADO, f.FECHA_INICIO DESC";
        $stmt = $this->ejecutarPreparado($sql, [':instructorId' => $instructorId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

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

    // ========== NUEVOS MÉTODOS PARA ADMINISTRACIÓN DE FICHAS ==========

    /**
     * Obtiene los IDs de las fichas asignadas a un instructor.
     * @param int $instructorId
     * @return array
     */
    public function obtenerFichasIds($instructorId)
    {
        $sql = "SELECT FICHA_ID FROM (
                    SELECT FICHA_ID FROM instructor_ficha WHERE INSTRUCTOR_ID = :id
                    UNION
                    SELECT GESTOR_FICHA_ID AS FICHA_ID FROM instructor WHERE INSTRUCTOR_ID = :id AND GESTOR_FICHA_ID IS NOT NULL
                ) AS t";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $instructorId]);
        if (!$stmt) return [];
        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int)$row['FICHA_ID'];
        }
        return $ids;
    }

    /**
     * Reemplaza las fichas asignadas a un instructor por las que se pasan en el array.
     * @param int $instructorId
     * @param array $fichasIds Array de IDs de fichas (puede estar vacío)
     * @return bool
     */
    public function actualizarFichas($instructorId, array $fichasIds)
    {
        try {
            $this->Conexion_ID->beginTransaction();
            // Eliminar todas las asignaciones actuales
            $sqlDel = "DELETE FROM instructor_ficha WHERE INSTRUCTOR_ID = :id";
            $this->ejecutarPreparado($sqlDel, [':id' => $instructorId]);
            // Insertar las nuevas
            if (!empty($fichasIds)) {
                $sqlIns = "INSERT INTO instructor_ficha (INSTRUCTOR_ID, FICHA_ID) VALUES (:instId, :fichaId)";
                $stmt = $this->Conexion_ID->prepare($sqlIns);
                foreach ($fichasIds as $fichaId) {
                    $stmt->execute([':instId' => $instructorId, ':fichaId' => $fichaId]);
                }
            }
            $this->Conexion_ID->commit();
            return true;
        } catch (Exception $e) {
            $this->Conexion_ID->rollBack();
            error_log("Error actualizando fichas del instructor {$instructorId}: " . $e->getMessage());
            return false;
        }
    }
}
?>