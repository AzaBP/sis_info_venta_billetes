<?php

require_once(__DIR__ . '/../VO/Tren.php');
require_once(__DIR__ . '/../Conexion.php');

class TrenDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Tren $tren) {
        try {
            $sql = "INSERT INTO tren (modelo, capacidad) 
                    VALUES (:modelo, :capacidad)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':modelo', $tren->getModelo());
            $stmt->bindValue(':capacidad', $tren->getCapacidad());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM tren WHERE id_tren = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Tren(
                    $resultado['id_tren'],
                    $resultado['modelo'],
                    $resultado['capacidad']
                );
            }
            return null;
        } catch (PDOException $e) {
            echo "Error al obtener: " . $e->getMessage();
            return null;
        }
    }

    // OBTENER TODOS
    public function obtenerTodos() {
        try {
            $sql = "SELECT * FROM tren";
            $stmt = $this->pdo->query($sql);
            $trenes = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $trenes[] = new Tren(
                    $resultado['id_tren'],
                    $resultado['modelo'],
                    $resultado['capacidad']
                );
            }
            return $trenes;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Tren $tren) {
        try {
            $sql = "UPDATE tren SET modelo = :modelo, capacidad = :capacidad 
                    WHERE id_tren = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id', $tren->getIdTren());
            $stmt->bindValue(':modelo', $tren->getModelo());
            $stmt->bindValue(':capacidad', $tren->getCapacidad());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM tren WHERE id_tren = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al eliminar: " . $e->getMessage();
            return false;
        }
    }
}

?>
