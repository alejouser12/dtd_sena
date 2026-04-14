<?php
require_once __DIR__ . "/conexion.php";

class ProgramaDAO extends BaseDatos
{
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}

    public function obtenerProgramas()
    {
        // Consulta simple sin joins complejos
        $sql = "SELECT 
                    p.PROGRAMA_ID,
                    p.NOMBRE,
                    p.NIVEL_FORMACION,
                    p.DURACION_MESES,
                    p.ESTADO
                FROM programa p
                ORDER BY p.NOMBRE";

        $this->Consulta_ID = $this->ejecutarSQL($sql);

        if (!$this->Consulta_ID) {
            return [];
        }
        
        $programas = $this->cargarTodo();
        
        // Para cada programa, calcular fichas, aprendices y ubicación
        foreach ($programas as &$programa) {
            // Contar fichas del programa
            $sqlFichas = "SELECT COUNT(*) as total FROM ficha WHERE PROGRAMA_ID = " . $programa['PROGRAMA_ID'];
            $this->Consulta_ID = $this->ejecutarSQL($sqlFichas);
            if ($this->Consulta_ID) {
                $fichas = $this->cargarRegistro();
                $programa['total_fichas'] = $fichas['total'];
            } else {
                $programa['total_fichas'] = 0;
            }
            
            // Contar aprendices del programa (a través de fichas)
            $sqlAprendices = "SELECT COUNT(DISTINCT a.APRENDIZ_ID) as total
                             FROM aprendiz a
                             WHERE a.FICHA_ID IN (SELECT FICHA_ID FROM ficha WHERE PROGRAMA_ID = " . $programa['PROGRAMA_ID'] . ")";
            $this->Consulta_ID = $this->ejecutarSQL($sqlAprendices);
            if ($this->Consulta_ID) {
                $aprendices = $this->cargarRegistro();
                $programa['total_aprendices'] = $aprendices['total'];
            } else {
                $programa['total_aprendices'] = 0;
            }
            
            // Obtener centro y regional (si existen)
            $sqlCentro = "SELECT c.NOMBRE as centro_nombre, c.CENTRO_ID, 
                                 r.NOMBRE as regional_nombre, r.REGIONAL_ID
                          FROM centro c
                          JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                          WHERE c.CENTRO_ID = (
                              SELECT CENTRO_ID FROM ficha 
                              WHERE PROGRAMA_ID = " . $programa['PROGRAMA_ID'] . " 
                              AND CENTRO_ID IS NOT NULL 
                              LIMIT 1
                          )";
            
            $this->Consulta_ID = $this->ejecutarSQL($sqlCentro);
            if ($this->Consulta_ID) {
                $ubicacion = $this->cargarRegistro();
                if ($ubicacion) {
                    $programa['centro_nombre'] = $ubicacion['centro_nombre'];
                    $programa['centro_id'] = $ubicacion['CENTRO_ID'];
                    $programa['regional_nombre'] = $ubicacion['regional_nombre'];
                    $programa['regional_id'] = $ubicacion['REGIONAL_ID'];
                } else {
                    $programa['centro_nombre'] = null;
                    $programa['centro_id'] = null;
                    $programa['regional_nombre'] = null;
                    $programa['regional_id'] = null;
                }
            } else {
                $programa['centro_nombre'] = null;
                $programa['centro_id'] = null;
                $programa['regional_nombre'] = null;
                $programa['regional_id'] = null;
            }
        }
        
        return $programas;
    }

    public function buscarProgramas($termino)
    {
        $sql = "SELECT 
                    PROGRAMA_ID,
                    NOMBRE,
                    NIVEL_FORMACION,
                    DURACION_MESES,
                    ESTADO
                FROM programa
                WHERE NOMBRE LIKE :termino OR NIVEL_FORMACION LIKE :termino
                ORDER BY NOMBRE";

        $stmt = $this->ejecutarPreparado($sql, [':termino' => "%$termino%"]);
        
        if (!$stmt) {
            return [];
        }
        
        $programas = $stmt->fetchAll();
        
        foreach ($programas as &$programa) {
            // Contar fichas
            $sqlFichas = "SELECT COUNT(*) as total FROM ficha WHERE PROGRAMA_ID = " . $programa['PROGRAMA_ID'];
            $this->Consulta_ID = $this->ejecutarSQL($sqlFichas);
            if ($this->Consulta_ID) {
                $fichas = $this->cargarRegistro();
                $programa['total_fichas'] = $fichas['total'];
            } else {
                $programa['total_fichas'] = 0;
            }
            
            // Contar aprendices
            $sqlAprendices = "SELECT COUNT(DISTINCT a.APRENDIZ_ID) as total
                             FROM aprendiz a
                             WHERE a.FICHA_ID IN (SELECT FICHA_ID FROM ficha WHERE PROGRAMA_ID = " . $programa['PROGRAMA_ID'] . ")";
            $this->Consulta_ID = $this->ejecutarSQL($sqlAprendices);
            if ($this->Consulta_ID) {
                $aprendices = $this->cargarRegistro();
                $programa['total_aprendices'] = $aprendices['total'];
            } else {
                $programa['total_aprendices'] = 0;
            }
            
            // Obtener centro y regional
            $sqlCentro = "SELECT c.NOMBRE as centro_nombre, c.CENTRO_ID, 
                                 r.NOMBRE as regional_nombre, r.REGIONAL_ID
                          FROM centro c
                          JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                          WHERE c.CENTRO_ID = (
                              SELECT CENTRO_ID FROM ficha 
                              WHERE PROGRAMA_ID = " . $programa['PROGRAMA_ID'] . " 
                              AND CENTRO_ID IS NOT NULL 
                              LIMIT 1
                          )";
            
            $this->Consulta_ID = $this->ejecutarSQL($sqlCentro);
            if ($this->Consulta_ID) {
                $ubicacion = $this->cargarRegistro();
                if ($ubicacion) {
                    $programa['centro_nombre'] = $ubicacion['centro_nombre'];
                    $programa['centro_id'] = $ubicacion['CENTRO_ID'];
                    $programa['regional_nombre'] = $ubicacion['regional_nombre'];
                    $programa['regional_id'] = $ubicacion['REGIONAL_ID'];
                } else {
                    $programa['centro_nombre'] = null;
                    $programa['centro_id'] = null;
                    $programa['regional_nombre'] = null;
                    $programa['regional_id'] = null;
                }
            } else {
                $programa['centro_nombre'] = null;
                $programa['centro_id'] = null;
                $programa['regional_nombre'] = null;
                $programa['regional_id'] = null;
            }
        }
        
        return $programas;
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT 
                    PROGRAMA_ID,
                    CENTRO_ID,
                    NOMBRE,
                    NIVEL_FORMACION,
                    DURACION_MESES,
                    ESTADO
                FROM programa
                WHERE PROGRAMA_ID = :id";

        $stmt = $this->ejecutarPreparado($sql, [':id' => $id]);
        
        if (!$stmt) {
            return null;
        }
        
        $programa = $stmt->fetch();
        
        if (!$programa) {
            return null;
        }
        
        // Contar fichas
        $sqlFichas = "SELECT COUNT(*) as total FROM ficha WHERE PROGRAMA_ID = $id";
        $this->Consulta_ID = $this->ejecutarSQL($sqlFichas);
        if ($this->Consulta_ID) {
            $fichas = $this->cargarRegistro();
            $programa['total_fichas'] = $fichas['total'];
        } else {
            $programa['total_fichas'] = 0;
        }
        
        // Contar aprendices
        $sqlAprendices = "SELECT COUNT(DISTINCT a.APRENDIZ_ID) as total
                         FROM aprendiz a
                         WHERE a.FICHA_ID IN (SELECT FICHA_ID FROM ficha WHERE PROGRAMA_ID = $id)";
        $this->Consulta_ID = $this->ejecutarSQL($sqlAprendices);
        if ($this->Consulta_ID) {
            $aprendices = $this->cargarRegistro();
            $programa['total_aprendices'] = $aprendices['total'];
        } else {
            $programa['total_aprendices'] = 0;
        }
        
        // Obtener centro y regional
        $sqlCentro = "SELECT c.NOMBRE as centro_nombre, c.CENTRO_ID, c.DIRECCION as centro_direccion,
                             r.NOMBRE as regional_nombre, r.CIUDAD as regional_ciudad, r.REGIONAL_ID
                      FROM centro c
                      JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                      WHERE c.CENTRO_ID = (
                          SELECT CENTRO_ID FROM ficha 
                          WHERE PROGRAMA_ID = $id 
                          AND CENTRO_ID IS NOT NULL 
                          LIMIT 1
                      )";
        
        $this->Consulta_ID = $this->ejecutarSQL($sqlCentro);
        if ($this->Consulta_ID) {
            $ubicacion = $this->cargarRegistro();
            if ($ubicacion) {
                $programa['centro_nombre'] = $ubicacion['centro_nombre'];
                $programa['centro_id'] = $ubicacion['CENTRO_ID'];
                $programa['centro_direccion'] = $ubicacion['centro_direccion'];
                $programa['regional_nombre'] = $ubicacion['regional_nombre'];
                $programa['regional_ciudad'] = $ubicacion['regional_ciudad'];
                $programa['regional_id'] = $ubicacion['REGIONAL_ID'];
            } else {
                $programa['centro_nombre'] = null;
                $programa['centro_id'] = null;
                $programa['centro_direccion'] = null;
                $programa['regional_nombre'] = null;
                $programa['regional_ciudad'] = null;
                $programa['regional_id'] = null;
            }
        } else {
            $programa['centro_nombre'] = null;
            $programa['centro_id'] = null;
            $programa['centro_direccion'] = null;
            $programa['regional_nombre'] = null;
            $programa['regional_ciudad'] = null;
            $programa['regional_id'] = null;
        }
        
        return $programa;
    }

    public function obtenerFichas($programaId)
    {
        $sql = "SELECT 
                    f.FICHA_ID,
                    f.CODIGO_FICHA,
                    f.FECHA_INICIO,
                    f.FECHA_FIN,
                    f.ESTADO,
                    f.CENTRO_ID,
                    (SELECT COUNT(*) FROM aprendiz WHERE FICHA_ID = f.FICHA_ID) as total_aprendices,
                    c.NOMBRE as centro_nombre,
                    r.NOMBRE as regional_nombre
                FROM ficha f
                LEFT JOIN centro c ON f.CENTRO_ID = c.CENTRO_ID
                LEFT JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
                WHERE f.PROGRAMA_ID = :programaId
                ORDER BY f.ESTADO, f.FECHA_INICIO DESC";

        $stmt = $this->ejecutarPreparado($sql, [':programaId' => $programaId]);
        
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
}
?>