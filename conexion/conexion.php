<?php
abstract class BaseDatos
{
    private $Servidor = 'localhost';
    private $BaseDatos = 'dtd_sena';
    private $Usuario = 'root';
    private $Clave = '';

    protected $Conexion_ID;
    protected $Consulta_ID;
    protected $ErrNo;
    protected $ErrTxt;

    public function __construct()
    {
        $this->conectar();
    }

    abstract protected function consultar();
    abstract protected function insertar();
    abstract protected function actualizar();
    abstract protected function eliminar();

    protected function conectar()
    {
        try {
            $this->Conexion_ID = new PDO(
                "mysql:host={$this->Servidor};dbname={$this->BaseDatos};charset=utf8",
                $this->Usuario,
                $this->Clave
            );
            $this->Conexion_ID->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->Conexion_ID->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    protected function desconectar()
    {
        $this->Conexion_ID = null;
    }

    public function ejecutarSQL($sql)
    {
        try {
            $this->Consulta_ID = $this->Conexion_ID->query($sql);
            return $this->Consulta_ID;
        } catch (PDOException $e) {
            $this->ErrTxt = $e->getMessage();
            error_log("Error SQL: " . $e->getMessage() . " - SQL: " . $sql);
            return false;
        }
    }

    /**
     * Ejecuta una consulta preparada con parámetros.
     * @param string 
     * @param array 
     * @return PDOStatement|false
     */
    public function ejecutarPreparado($sql, $parametros = [])
    {
        try {
            $stmt = $this->Conexion_ID->prepare($sql);
            $stmt->execute($parametros);
            return $stmt;
        } catch (PDOException $e) {
            $this->ErrTxt = $e->getMessage();
            error_log("Error en consulta preparada: " . $e->getMessage());
            return false;
        }
    }

    protected function cargarTodo()
    {
        if ($this->Consulta_ID) {
            return $this->Consulta_ID->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    protected function cargarRegistro()
    {
        if ($this->Consulta_ID) {
            return $this->Consulta_ID->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function imprimirError()
    {
        return "Error: " . $this->ErrTxt;
    }

    public function obtenerUltimoId()
    {
        return $this->Conexion_ID->lastInsertId();
    }
}
?>