<?php
require_once __DIR__ . '/conexion.php';

class SeguimientoDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    public function registrarDesdeAlerta(array $datos)
    {
        return $this->registrarSeguimiento($datos, 'alerta');
    }

    public function registrarDesdeObservacion(array $datos)
    {
        return $this->registrarSeguimiento($datos, 'observacion');
    }

    public function registrarSeguimiento(array $datos, $origen = 'general')
    {
        $seguimiento = $this->normalizarDatos($datos);
        $fechaRegistro = date('Y-m-d H:i:s');

        try {
            $this->Conexion_ID->beginTransaction();

            $observacionId = $this->insertarObservacion($seguimiento, $fechaRegistro);
            $alertaId = $this->insertarAlerta($seguimiento, $fechaRegistro);

            $this->Conexion_ID->commit();
            $this->refrescarNivelRiesgo((int)$seguimiento['estudiante_id']);

            return [
                'success' => true,
                'origen' => $origen,
                'fecha' => $fechaRegistro,
                'alerta_id' => $alertaId,
                'observacion_id' => $observacionId
            ];
        } catch (Throwable $e) {
            if ($this->Conexion_ID && $this->Conexion_ID->inTransaction()) {
                $this->Conexion_ID->rollBack();
            }

            $this->ErrTxt = $e->getMessage();
            error_log('Error sincronizando seguimiento (' . $origen . '): ' . $e->getMessage());
            return false;
        }
    }

    private function normalizarDatos(array $datos)
    {
        $estudianteId = (int)($datos['estudiante_id'] ?? 0);
        if ($estudianteId <= 0) {
            throw new RuntimeException('El aprendiz no es valido.');
        }

        $descripcion = trim((string)($datos['descripcion'] ?? ''));
        if ($descripcion === '') {
            throw new RuntimeException('La descripcion no puede estar vacia.');
        }

        $tipo = trim((string)($datos['tipo'] ?? ''));
        if ($tipo === '') {
            $tipo = 'General';
        }

        $nivel = ucfirst(strtolower(trim((string)($datos['nivel_riesgo'] ?? ''))));
        $nivelesPermitidos = ['Bajo', 'Medio', 'Alto'];
        if (!in_array($nivel, $nivelesPermitidos, true)) {
            throw new RuntimeException('El nivel de riesgo no es valido.');
        }

        $instructorId = $this->resolverInstructorId($datos['instructor_id'] ?? 0);
        if ($instructorId <= 0) {
            throw new RuntimeException('No se pudo determinar el instructor para registrar el seguimiento.');
        }

        return [
            'estudiante_id' => $estudianteId,
            'instructor_id' => $instructorId,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'nivel_riesgo' => $nivel
        ];
    }

    private function resolverInstructorId($instructorId)
    {
        $candidatos = [
            (int)$instructorId,
            (int)($_SESSION['usuario_ref_id'] ?? 0),
            (int)($_SESSION['instructor_id'] ?? 0)
        ];

        foreach ($candidatos as $candidato) {
            if ($candidato > 0 && $this->existeInstructor($candidato)) {
                return $candidato;
            }
        }

        $stmt = $this->ejecutarSQL('SELECT INSTRUCTOR_ID FROM instructor ORDER BY INSTRUCTOR_ID ASC LIMIT 1');
        $fila = $stmt ? $stmt->fetch() : null;
        return (int)($fila['INSTRUCTOR_ID'] ?? 0);
    }

    private function existeInstructor($instructorId)
    {
        $stmt = $this->ejecutarPreparado(
            'SELECT INSTRUCTOR_ID FROM instructor WHERE INSTRUCTOR_ID = :id LIMIT 1',
            [':id' => (int)$instructorId]
        );

        return $stmt ? (bool)$stmt->fetch() : false;
    }

    private function insertarObservacion(array $seguimiento, $fechaRegistro)
    {
        $sql = 'INSERT INTO observacion_academica (
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
                    :fecha
                )';

        $stmt = $this->ejecutarPreparado($sql, [
            ':estudiante_id' => $seguimiento['estudiante_id'],
            ':instructor_id' => $seguimiento['instructor_id'],
            ':tipo' => $seguimiento['tipo'],
            ':descripcion' => $seguimiento['descripcion'],
            ':nivel_riesgo' => $seguimiento['nivel_riesgo'],
            ':fecha' => $fechaRegistro
        ]);

        if (!$stmt) {
            throw new RuntimeException('No fue posible guardar la observacion academica.');
        }

        return (int)$this->obtenerUltimoId();
    }

    private function insertarAlerta(array $seguimiento, $fechaRegistro)
    {
        $sql = 'INSERT INTO alerta (
                    ESTUDIANTE_ID,
                    REGLA_ID,
                    NIVEL,
                    DESCRIPCION,
                    ESTADO,
                    FECHA_GENERACION
                ) VALUES (
                    :estudiante_id,
                    :regla_id,
                    :nivel,
                    :descripcion,
                    :estado,
                    :fecha
                )';

        $stmt = $this->ejecutarPreparado($sql, [
            ':estudiante_id' => $seguimiento['estudiante_id'],
            ':regla_id' => $this->obtenerReglaId(),
            ':nivel' => $seguimiento['nivel_riesgo'],
            ':descripcion' => $seguimiento['descripcion'],
            ':estado' => 'Activa',
            ':fecha' => $fechaRegistro
        ]);

        if (!$stmt) {
            throw new RuntimeException('No fue posible guardar la alerta.');
        }

        return (int)$this->obtenerUltimoId();
    }

    private function obtenerReglaId()
    {
        $stmt = $this->ejecutarSQL("SELECT REGLA_ID FROM parametro_regla WHERE ESTADO = 'Activa' ORDER BY REGLA_ID ASC LIMIT 1");
        $regla = $stmt ? $stmt->fetch() : null;

        if (!empty($regla['REGLA_ID'])) {
            return (int)$regla['REGLA_ID'];
        }

        $stmt = $this->ejecutarPreparado(
            'INSERT INTO parametro_regla (NOMBRE_REGLA, TIPO_REGLA, DESCRIPCION, ESTADO)
             VALUES (:nombre, :tipo, :descripcion, :estado)',
            [
                ':nombre' => 'Regla por defecto',
                ':tipo' => 'GENERAL',
                ':descripcion' => 'Regla automatica para alertas sincronizadas',
                ':estado' => 'Activa'
            ]
        );

        if (!$stmt) {
            throw new RuntimeException('No fue posible crear la regla por defecto.');
        }

        return (int)$this->obtenerUltimoId();
    }

    private function refrescarNivelRiesgo($aprendizId)
    {
        require_once __DIR__ . '/AprendizDAO.php';
        $aprendizDAO = new AprendizDAO();
        $aprendizDAO->refrescarNivelRiesgoProgreso((int)$aprendizId);
    }
}
