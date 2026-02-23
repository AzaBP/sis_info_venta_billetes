<?php

require_once(__DIR__ . '/../VO/Empleado.php');
require_once(__DIR__ . '/../Conexion.php');

class EmpleadoDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Empleado $empleado) {
        try {
            $sql = "INSERT INTO empleado (id_usuario, tipo_empleado) 
                    VALUES (:id_usuario, :tipo_empleado)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id_usuario', $empleado->getIdUsuario());
            $stmt->bindValue(':tipo_empleado', $empleado->getTipoEmpleado());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM empleado WHERE id_empleado = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Empleado(
                    $resultado['id_empleado'],
                    $resultado['id_usuario'],
                    $resultado['email']
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
            $sql = "SELECT * FROM empleado";
            $stmt = $this->pdo->query($sql);
            $empleados = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $empleados[] = new Empleado(
                    $resultado['id_empleado'],
                    $resultado['id_usuario'],
                    $resultado['email']
                );
            }
            return $empleados;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Empleado $empleado) {
        try {
            $sql = "UPDATE empleado SET id_usuario = :id_usuario, tipo_empleado = :tipo_empleado 
                    WHERE id_empleado = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id', $empleado->getIdEmpleado());
            $stmt->bindValue(':id_usuario', $empleado->getIdUsuario());
            $stmt->bindValue(':tipo_empleado', $empleado->getTipoEmpleado());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM empleado WHERE id_empleado = :id";
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
