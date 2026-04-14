<?php
// conexion/RegionalDAO.php
require_once __DIR__ . "/conexion.php";

class RegionalDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    public function obtenerTodas()
    {
        $sql = "SELECT * FROM regional WHERE ESTADO = 'Activa' ORDER BY NOMBRE";
        $this->Consulta_ID = $this->ejecutarSQL($sql);
        if ($this->Consulta_ID) {
            return $this->cargarTodo();
        }
        return [];
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM regional WHERE REGIONAL_ID = :id";
        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        if ($stmt) {
            return $stmt->fetch();
        }
        return null;
    }

    public function obtenerCentros($regionalId)
    {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(DISTINCT f.FICHA_ID) FROM ficha f
                        LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                        WHERE f.CENTRO_ID = c.CENTRO_ID OR p.CENTRO_ID = c.CENTRO_ID) as total_fichas,
                       (SELECT COUNT(DISTINCT a.APRENDIZ_ID) FROM aprendiz a
                        INNER JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
                        LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
                        WHERE f.CENTRO_ID = c.CENTRO_ID OR p.CENTRO_ID = c.CENTRO_ID) as total_aprendices
                FROM centro c
                WHERE c.REGIONAL_ID = :regionalId AND c.ESTADO = 'Activo'
                ORDER BY c.NOMBRE";
        $stmt = $this->ejecutarPreparado($sql, [':regionalId' => $regionalId]);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    public function crearRegional(array $datos)
    {
        $sql = "INSERT INTO regional (NOMBRE, CODIGO, CIUDAD, DIRECCION, TELEFONO, ESTADO)
                VALUES (:nombre, :codigo, :ciudad, :direccion, :telefono, :estado)";
        $stmt = $this->ejecutarPreparado($sql, [
            ':nombre' => $datos['nombre'],
            ':codigo' => !empty($datos['codigo']) ? $datos['codigo'] : null,
            ':ciudad' => !empty($datos['ciudad']) ? $datos['ciudad'] : null,
            ':direccion' => !empty($datos['direccion']) ? $datos['direccion'] : null,
            ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
            ':estado' => $datos['estado'] ?? 'Activa',
        ]);
        if ($stmt) {
            return (int)$this->Conexion_ID->lastInsertId();
        }
        return false;
    }

    public function actualizarRegional($id, array $datos)
    {
        $sql = "UPDATE regional SET NOMBRE = :nombre, CODIGO = :codigo, CIUDAD = :ciudad,
                DIRECCION = :direccion, TELEFONO = :telefono, ESTADO = :estado
                WHERE REGIONAL_ID = :id";
        $stmt = $this->ejecutarPreparado($sql, [
            ':id' => $id,
            ':nombre' => $datos['nombre'],
            ':codigo' => !empty($datos['codigo']) ? $datos['codigo'] : null,
            ':ciudad' => !empty($datos['ciudad']) ? $datos['ciudad'] : null,
            ':direccion' => !empty($datos['direccion']) ? $datos['direccion'] : null,
            ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
            ':estado' => $datos['estado'] ?? 'Activa',
        ]);
        return (bool)$stmt;
    }
}
?>