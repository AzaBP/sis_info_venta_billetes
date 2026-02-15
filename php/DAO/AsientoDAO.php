<?php

require_once(__DIR__ . '/../VO/Asiento.php');
require_once(__DIR__ . '/../Conexion.php');

class AsientoDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Asiento $asiento) {
        try {
            $sql = "INSERT INTO asiento (numero_asiento, id_tren, clase, estado) 
                    VALUES (:numero_asiento, :id_tren, :clase, :estado)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':numero_asiento', $asiento->getNumeroAsiento());
            $stmt->bindValue(':id_tren', $asiento->getIdTren());
            $stmt->bindValue(':clase', $asiento->getClase());
            $stmt->bindValue(':estado', $asiento->getEstado());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM asiento WHERE numero_asiento = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Asiento(
                    $resultado['numero_asiento'],
                    $resultado['id_tren'],
                    $resultado['clase'],
                    $resultado['estado']
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
            $sql = "SELECT * FROM asiento";
            $stmt = $this->pdo->query($sql);
            $asientos = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $asientos[] = new Asiento(
                    $resultado['numero_asiento'],
                    $resultado['id_tren'],
                    $resultado['clase'],
                    $resultado['estado']
                );
            }
            return $asientos;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // OBTENER POR TREN
    public function obtenerPorTren($id_tren) {
        try {
            $sql = "SELECT * FROM asiento WHERE id_tren = :id_tren";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id_tren', $id_tren);
            $stmt->execute();
            
            $asientos = [];
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $asientos[] = new Asiento(
                    $resultado['numero_asiento'],
                    $resultado['id_tren'],
                    $resultado['clase'],
                    $resultado['estado']
                );
            }
            return $asientos;
        } catch (PDOException $e) {
            echo "Error al obtener por tren: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Asiento $asiento) {
        try {
            $sql = "UPDATE asiento SET id_tren = :id_tren, clase = :clase, 
                    estado = :estado WHERE numero_asiento = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id', $asiento->getNumeroAsiento());
            $stmt->bindValue(':id_tren', $asiento->getIdTren());
            $stmt->bindValue(':clase', $asiento->getClase());
            $stmt->bindValue(':estado', $asiento->getEstado());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM asiento WHERE numero_asiento = :id";
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
