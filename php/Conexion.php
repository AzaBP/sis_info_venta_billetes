<?php

class Conexion {
    //private $host = 'localhost';
    //en docker
    private $host = 'trenes_postgres';
    private $puerto = 5432;
    private $bd = 'sis_info_venta_billetes_bd';
    private $usuario = 'admin';
    private $password = 'password123';
    private $pdo;

    public function conectar() {
        try {
            $this->pdo = new PDO(
                'pgsql:host=' . $this->host . ';port=' . $this->puerto . ';dbname=' . $this->bd,
                $this->usuario,
                $this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            return $this->pdo;
        } catch (PDOException $e) {
            error_log("Error de conexion: " . $e->getMessage());
            return null;
        }
    }

    public function getConexion() {
        return $this->pdo;
    }
}

?>
