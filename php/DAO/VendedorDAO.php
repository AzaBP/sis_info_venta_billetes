<?php

require_once(__DIR__ . '/../VO/Vendedor.php');
require_once(__DIR__ . '/../Conexion.php');

class VendedorDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Vendedor $vendedor) {
        try {
            $sql = "INSERT INTO vendedor (id_empleado, comision_porcentaje, region) 
                    VALUES (:id_empleado, :comision_porcentaje, :region)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindParam(':id_empleado', $vendedor->getIdEmpleado());
            $stmt->bindParam(':comision_porcentaje', $vendedor->getComisionPorcentaje());
            $stmt->bindParam(':region', $vendedor->getRegion());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM vendedor WHERE id_empleado = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Vendedor(
                    $resultado['id_empleado'],
                    $resultado['comision_porcentaje'],
                    $resultado['region']
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
            $sql = "SELECT * FROM vendedor";
            $stmt = $this->pdo->query($sql);
            $vendedores = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $vendedores[] = new Vendedor(
                    $resultado['id_empleado'],
                    $resultado['comision_porcentaje'],
                    $resultado['region']
                );
            }
            return $vendedores;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Vendedor $vendedor) {
        try {
            $sql = "UPDATE vendedor SET comision_porcentaje = :comision_porcentaje, 
                    region = :region WHERE id_empleado = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindParam(':id', $vendedor->getIdEmpleado());
            $stmt->bindParam(':comision_porcentaje', $vendedor->getComisionPorcentaje());
            $stmt->bindParam(':region', $vendedor->getRegion());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM vendedor WHERE id_empleado = :id";
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
