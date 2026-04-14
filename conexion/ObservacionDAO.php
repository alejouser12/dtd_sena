<?php
// conexion/ObservacionDAO.php
require_once __DIR__ . "/conexion.php";

class ObservacionDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    //Guarda una nueva observación academica
    public function guardarObservacion($datos)
    {
        $sql = "INSERT INTO observacion_academica (
                    ESTUDIANTE_ID,
                    INSTRUCTOR_ID,
                    TIPO,
                    DESCRIPCION,
                    NIVEL_RIESGO,
                    FECHA
                ) VALUES (
                    :estudiante_id,
                    :instructor_id,
                    :tipo,
                    :descripcion,
                    :nivel_riesgo,
                    NOW()
                )";

        $stmt = $this->ejecutarPreparado($sql, [
            ':estudiante_id' => $datos['estudiante_id'],
            ':instructor_id' => $datos['instructor_id'],
            ':tipo' => $datos['tipo'],
            ':descripcion' => $datos['descripcion'],
            ':nivel_riesgo' => $datos['nivel_riesgo']
        ]);

        if ($stmt) {
            return $this->Conexion_ID->lastInsertId();
        }
        
        error_log("Error en guardarObservacion: " . $this->imprimirError());
        return false;
    }

    //Optiene los tipos de observación disponibles
    public function obtenerTipos()
    {
        return [
            'Académica',
            'Disciplinaria',
            'Actitudinal',
            'Familiar',
            'Salud',
            'Otro'
        ];
    }

    //Optiene todos los niveles de riesgos disponibles
    public function obtenerNivelesRiesgo()
    {
        return [
            'Bajo',
            'Medio',
            'Alto'
        ];
    }
}
?>