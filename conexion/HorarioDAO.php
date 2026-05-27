<?php
// conexion/HorarioDAO.php
require_once __DIR__ . "/conexion.php";

class HorarioDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    /**
     * Obtiene el horario de una ficha (opcionalmente filtrado por trimestre)
     */
    public function obtenerHorarioFicha($fichaId, $trimestre = null)
    {
        $sql = "SELECT h.*, 
                       CASE h.DIA_SEMANA
                           WHEN 1 THEN 'Lunes'
                           WHEN 2 THEN 'Martes'
                           WHEN 3 THEN 'Miércoles'
                           WHEN 4 THEN 'Jueves'
                           WHEN 5 THEN 'Viernes'
                           WHEN 6 THEN 'Sábado'
                       END as dia_nombre
                FROM horario h
                WHERE h.FICHA_ID = :fichaId";
        $params = [':fichaId' => $fichaId];
        if ($trimestre) {
            $sql .= " AND h.TRIMESTRE = :trimestre";
            $params[':trimestre'] = $trimestre;
        }
        $sql .= " ORDER BY h.DIA_SEMANA, h.HORA_INICIO";
        $stmt = $this->ejecutarPreparado($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Guarda (o actualiza) el horario completo de una ficha para un trimestre.
     * Elimina los registros anteriores del mismo trimestre y los reemplaza.
     */
    public function guardarHorario($fichaId, $datos)
    {
        // Eliminar horario existente del mismo trimestre
        $sqlDelete = "DELETE FROM horario WHERE FICHA_ID = :fichaId AND TRIMESTRE = :trimestre";
        $this->ejecutarPreparado($sqlDelete, [':fichaId' => $fichaId, ':trimestre' => $datos['trimestre']]);
        
        $sql = "INSERT INTO horario (FICHA_ID, DIA_SEMANA, HORA_INICIO, HORA_FIN, MATERIA, AULA, TRIMESTRE, FECHA_DESDE, FECHA_HASTA)
                VALUES (:fichaId, :dia, :inicio, :fin, :materia, :aula, :trimestre, :desde, :hasta)";
        $stmt = $this->Conexion_ID->prepare($sql);
        $ok = true;
        foreach ($datos['horarios'] as $h) {
            $res = $stmt->execute([
                ':fichaId' => $fichaId,
                ':dia' => $h['dia'],
                ':inicio' => $h['inicio'],
                ':fin' => $h['fin'],
                ':materia' => $h['materia'],
                ':aula' => $h['aula'],
                ':trimestre' => $datos['trimestre'],
                ':desde' => $datos['fecha_desde'],
                ':hasta' => $datos['fecha_hasta']
            ]);
            if (!$res) $ok = false;
        }
        return $ok;
    }
}
?>