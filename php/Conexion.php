<?php

class Conexion {
    private $host = 'localhost';
    private $puerto = 5432;
    private $bd = 'trenesDB';
    private $usuario = 'postgres';
    private $password = 'password';
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
            echo "Error de conexión: " . $e->getMessage();
            return null;
        }
    }

    public function getConexion() {
        return $this->pdo;
    }
}

?>
