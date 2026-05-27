<?php
// conexion/AsistenciaDAO.php
require_once __DIR__ . "/conexion.php";

class AsistenciaDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    const DIAS_HABILES_EXCUSA = 3;

    public function calcularFechaLimiteExcusa(string $fechaFalta): string
    {
        $dt    = new DateTime($fechaFalta);
        $count = 0;
        while ($count < self::DIAS_HABILES_EXCUSA) {
            $dt->modify('+1 day');
            if ((int)$dt->format('N') < 6) $count++;
        }
        return $dt->format('Y-m-d');
    }

    public function puedeExcusar(string $fechaFalta): bool
    {
        return date('Y-m-d') <= $this->calcularFechaLimiteExcusa($fechaFalta);
    }

    public function obtenerAsistenciasSemana($fichaId, $fechaInicio): array
    {
        $fechaFin = date('Y-m-d', strtotime($fechaInicio . ' +5 days'));

        $sql = "SELECT
                    a.ESTUDIANTE_ID,
                    a.FECHA,
                    a.ESTADO,
                    a.HORAS_FALTA,
                    a.EXCUSA_PRESENTADA,
                    a.FECHA_LIMITE_EXCUSA
                FROM asistencia a
                INNER JOIN aprendiz ap ON a.ESTUDIANTE_ID = ap.APRENDIZ_ID
                WHERE ap.FICHA_ID = :fichaId
                  AND a.FECHA BETWEEN :fi AND :ff
                ORDER BY a.FECHA";

        $stmt = $this->ejecutarPreparado($sql, [
            ':fichaId' => $fichaId,
            ':fi'      => $fechaInicio,
            ':ff'      => $fechaFin,
        ]);

        $out = [];
        if ($stmt) {
            while ($r = $stmt->fetch()) {
                $out[$r['ESTUDIANTE_ID']][] = [
                    'fecha'               => $r['FECHA'],
                    'estado'              => $r['ESTADO'],
                    'horas_falta'         => (int)$r['HORAS_FALTA'],
                    'excusa_presentada'   => (bool)$r['EXCUSA_PRESENTADA'],
                    'fecha_limite_excusa' => $r['FECHA_LIMITE_EXCUSA'],
                ];
            }
        }
        return $out;
    }

    public function obtenerAsistenciaAprendiz($aprendizId, $limite = 30): array
    {
        $sql = "SELECT
                    ASISTENCIA_ID,
                    FECHA,
                    ESTADO,
                    HORAS_FALTA,
                    EXCUSA_PRESENTADA,
                    FECHA_LIMITE_EXCUSA
                FROM asistencia
                WHERE ESTUDIANTE_ID = :id
                ORDER BY FECHA DESC
                LIMIT :lim";

        $stmt = $this->ejecutarPreparado($sql, [
            ':id'  => $aprendizId,
            ':lim' => (int)$limite,
        ]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function obtenerResumenAprendiz($aprendizId): array
    {
        $sql = "SELECT
                    COUNT(*) as total_dias,
                    SUM(CASE WHEN ESTADO='asistio' THEN 1 ELSE 0 END) as dias_asistidos,
                    SUM(CASE WHEN ESTADO='retardo' THEN 1 ELSE 0 END) as dias_retardo,
                    SUM(CASE WHEN ESTADO='falta'   THEN 1 ELSE 0 END) as dias_falta,
                    SUM(CASE WHEN ESTADO='excusa'  THEN 1 ELSE 0 END) as dias_excusa,
                    SUM(CASE WHEN ESTADO='retardo' THEN HORAS_FALTA ELSE 0 END) as horas_retardo,
                    SUM(CASE WHEN ESTADO IN('falta','retardo') THEN HORAS_FALTA ELSE 0 END) as total_horas_falta
                FROM asistencia
                WHERE ESTUDIANTE_ID = :id";

        $stmt = $this->ejecutarPreparado($sql, [':id' => $aprendizId]);
        if ($stmt && $r = $stmt->fetch()) return $r;

        return [
            'total_dias'        => 0, 'dias_asistidos'    => 0,
            'dias_retardo'      => 0, 'dias_falta'        => 0,
            'dias_excusa'       => 0, 'horas_retardo'     => 0,
            'total_horas_falta' => 0,
        ];
    }

    public function obtenerPorcentajeAsistencia($aprendizId): float
    {
        $r     = $this->obtenerResumenAprendiz($aprendizId);
        $total = (int)$r['total_dias'];
        if ($total === 0) return 100.0;
        $pres  = (int)$r['dias_asistidos'] + (int)$r['dias_retardo'] + (int)$r['dias_excusa'];
        return round($pres / $total * 100, 1);
    }

    // $celdas[aprendizId][fecha] = ['estado'=>'asistio|retardo|falta|excusa', 'horas'=>int]
    public function guardarAsistenciasSemana($fichaId, $fechaInicio, array $celdas): bool
    {
        try {
            $this->Conexion_ID->beginTransaction();

            $stmtAp = $this->ejecutarPreparado(
                "SELECT APRENDIZ_ID FROM aprendiz WHERE FICHA_ID = :f",
                [':f' => $fichaId]
            );
            if (!$stmtAp) throw new Exception("Error al obtener aprendices");
            $aprendices = array_column($stmtAp->fetchAll(), 'APRENDIZ_ID');

            $fechas = [];
            for ($i = 0; $i < 6; $i++)
                $fechas[] = date('Y-m-d', strtotime("$fechaInicio +$i days"));

            foreach ($aprendices as $apId) {
                foreach ($fechas as $fecha) {
                    $celda  = $celdas[$apId][$fecha] ?? null;
                    $estado = $celda['estado'] ?? 'asistio';
                    $horas  = (int)($celda['horas'] ?? 0);

                    // Normalizar
                    if ($estado === 'asistio') $horas = 0;
                    if ($estado === 'falta')   $horas = 4;
                    if ($estado === 'excusa')  $horas = 0;
                    if ($estado === 'retardo' && $horas <= 0) $horas = 1;

                    $fechaLimite = ($estado === 'falta')
                        ? $this->calcularFechaLimiteExcusa($fecha)
                        : null;

                    // ¿Existe?
                    $chk = $this->ejecutarPreparado(
                        "SELECT ASISTENCIA_ID, ESTADO
                         FROM asistencia
                         WHERE ESTUDIANTE_ID = :a AND FECHA = :f LIMIT 1",
                        [':a' => $apId, ':f' => $fecha]
                    );
                    $existe = $chk ? $chk->fetch() : null;

                    if ($existe) {
                        // Respetar excusa ya registrada
                        if ($existe['ESTADO'] === 'excusa' && $estado === 'falta') continue;

                        $this->ejecutarPreparado(
                            "UPDATE asistencia
                             SET ESTADO = :e,
                                 HORAS_FALTA = :h,
                                 FECHA_LIMITE_EXCUSA = :fl
                             WHERE ESTUDIANTE_ID = :a AND FECHA = :f",
                            [':e'=>$estado, ':h'=>$horas, ':fl'=>$fechaLimite,
                             ':a'=>$apId,   ':f'=>$fecha]
                        );
                    } else {
                        $this->ejecutarPreparado(
                            "INSERT INTO asistencia
                             (ESTUDIANTE_ID, FECHA, ESTADO, HORAS_FALTA, FECHA_LIMITE_EXCUSA)
                             VALUES (:a, :f, :e, :h, :fl)",
                            [':a'=>$apId, ':f'=>$fecha, ':e'=>$estado,
                             ':h'=>$horas, ':fl'=>$fechaLimite]
                        );
                    }
                }
            }

            $this->Conexion_ID->commit();
            return true;

        } catch (Exception $ex) {
            if ($this->Conexion_ID->inTransaction()) $this->Conexion_ID->rollBack();
            $this->ErrTxt = $ex->getMessage();
            error_log("AsistenciaDAO::guardarAsistenciasSemana — " . $ex->getMessage());
            return false;
        }
    }

    public function registrarExcusa(int $aprendizId, string $fecha): bool
    {
        $stmt = $this->ejecutarPreparado(
            "SELECT ASISTENCIA_ID, ESTADO, FECHA_LIMITE_EXCUSA
             FROM asistencia
             WHERE ESTUDIANTE_ID = :a AND FECHA = :f LIMIT 1",
            [':a' => $aprendizId, ':f' => $fecha]
        );
        if (!$stmt) return false;
        $r = $stmt->fetch();
        if (!$r || $r['ESTADO'] !== 'falta') return false;
        if ($r['FECHA_LIMITE_EXCUSA'] && date('Y-m-d') > $r['FECHA_LIMITE_EXCUSA']) return false;

        return (bool)$this->ejecutarPreparado(
            "UPDATE asistencia
             SET ESTADO = 'excusa', HORAS_FALTA = 0, EXCUSA_PRESENTADA = 1
             WHERE ASISTENCIA_ID = :id",
            [':id' => $r['ASISTENCIA_ID']]
        );
    }

    public function revocarExcusa(int $aprendizId, string $fecha): bool
    {
        $stmt = $this->ejecutarPreparado(
            "SELECT ASISTENCIA_ID FROM asistencia
             WHERE ESTUDIANTE_ID = :a AND FECHA = :f AND ESTADO = 'excusa' LIMIT 1",
            [':a' => $aprendizId, ':f' => $fecha]
        );
        if (!$stmt) return false;
        $r = $stmt->fetch();
        if (!$r) return false;

        return (bool)$this->ejecutarPreparado(
            "UPDATE asistencia
             SET ESTADO = 'falta', HORAS_FALTA = 4,
                 EXCUSA_PRESENTADA = 0,
                 FECHA_LIMITE_EXCUSA = :fl
             WHERE ASISTENCIA_ID = :id",
            [':fl' => $this->calcularFechaLimiteExcusa($fecha), ':id' => $r['ASISTENCIA_ID']]
        );
    }

    public function imprimirError(): string { return $this->ErrTxt ?? ''; }
}