<?php

require_once(__DIR__ . '/../VO/Ruta.php');
require_once(__DIR__ . '/../Conexion.php');

class RutaDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Ruta $ruta) {
        try {
            $sql = "INSERT INTO ruta (origen, destino, duracion, id_vendedor) 
                    VALUES (:origen, :destino, :duracion, :id_vendedor)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindParam(':origen', $ruta->getOrigen());
            $stmt->bindParam(':destino', $ruta->getDestino());
            $stmt->bindParam(':duracion', $ruta->getDuracion());
            $stmt->bindParam(':id_vendedor', $ruta->getIdVendedor());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM ruta WHERE id_ruta = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Ruta(
                    $resultado['id_ruta'],
                    $resultado['origen'],
                    $resultado['destino'],
                    $resultado['duracion'],
                    $resultado['id_vendedor']
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
            $sql = "SELECT * FROM ruta";
            $stmt = $this->pdo->query($sql);
            $rutas = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rutas[] = new Ruta(
                    $resultado['id_ruta'],
                    $resultado['origen'],
                    $resultado['destino'],
                    $resultado['duracion'],
                    $resultado['id_vendedor']
                );
            }
            return $rutas;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Ruta $ruta) {
        try {
            $sql = "UPDATE ruta SET origen = :origen, destino = :destino, 
                    duracion = :duracion, id_vendedor = :id_vendedor WHERE id_ruta = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindParam(':id', $ruta->getIdRuta());
            $stmt->bindParam(':origen', $ruta->getOrigen());
            $stmt->bindParam(':destino', $ruta->getDestino());
            $stmt->bindParam(':duracion', $ruta->getDuracion());
            $stmt->bindParam(':id_vendedor', $ruta->getIdVendedor());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM ruta WHERE id_ruta = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al eliminar: " . $e->getMessage();
            return false;
        }
    }
}

?>
