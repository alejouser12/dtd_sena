<?php
// conexion/CentroDAO.php
require_once __DIR__ . "/conexion.php";

class CentroDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    public function obtenerPorId($id)
    {
        $sql = "SELECT c.*, r.NOMBRE as regional_nombre, r.CODIGO as regional_codigo
                FROM centro c
                JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                WHERE c.CENTRO_ID = :id";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        if ($stmt) {
            return $stmt->fetch();
        }
        return null;
    }

    public function obtenerTodos()
    {
        $sql = 'SELECT c.*, r.NOMBRE AS regional_nombre
                FROM centro c
                INNER JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                WHERE c.ESTADO = \'Activo\'
                ORDER BY r.NOMBRE, c.NOMBRE';
        $stmt = $this->ejecutarPreparado($sql, []);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtiene los centros de una regional específica (usado para filtros)
     * @param int $regionalId
     * @return array
     */
    public function obtenerPorRegional($regionalId)
    {
        $sql = "SELECT c.*, r.NOMBRE as regional_nombre
                FROM centro c
                INNER JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                WHERE c.REGIONAL_ID = :regionalId AND c.ESTADO = 'Activo'
                ORDER BY c.NOMBRE";
        $stmt = $this->ejecutarPreparado($sql, [':regionalId' => $regionalId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function obtenerProgramas($centroId)
    {
        $sql = "SELECT 
                    p.PROGRAMA_ID,
                    p.NOMBRE,
                    p.NIVEL_FORMACION,
                    p.DURACION_MESES,
                    p.ESTADO,
                    COUNT(DISTINCT f.FICHA_ID) as total_fichas,
                    COUNT(DISTINCT a.APRENDIZ_ID) as total_aprendices
                FROM programa p
                LEFT JOIN ficha f ON p.PROGRAMA_ID = f.PROGRAMA_ID
                LEFT JOIN aprendiz a ON f.FICHA_ID = a.FICHA_ID
                WHERE p.CENTRO_ID = :centroId
                GROUP BY p.PROGRAMA_ID
                ORDER BY p.NOMBRE";

        $stmt = $this->ejecutarPreparado($sql, [':centroId' => $centroId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
}
?>