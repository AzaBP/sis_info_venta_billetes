<?php

require_once(__DIR__ . '/../VO/Mantenimiento.php');
require_once(__DIR__ . '/../Conexion.php');

class MantenimientoDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Mantenimiento $mantenimiento) {
        try {
            $sql = "INSERT INTO mantenimiento (id_empleado, especialidad, turno, certificaciones) 
                    VALUES (:id_empleado, :especialidad, :turno, :certificaciones)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id_empleado', $mantenimiento->getIdEmpleado());
            $stmt->bindValue(':especialidad', $mantenimiento->getEspecialidad());
            $stmt->bindValue(':turno', $mantenimiento->getTurno());
            $stmt->bindValue(':certificaciones', $mantenimiento->getCertificaciones());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM mantenimiento WHERE id_empleado = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Mantenimiento(
                    $resultado['id_empleado'],
                    $resultado['especialidad'],
                    $resultado['turno'],
                    $resultado['certificaciones']
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
            $sql = "SELECT * FROM mantenimiento";
            $stmt = $this->pdo->query($sql);
            $mantenimientos = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mantenimientos[] = new Mantenimiento(
                    $resultado['id_empleado'],
                    $resultado['especialidad'],
                    $resultado['turno'],
                    $resultado['certificaciones']
                );
            }
            return $mantenimientos;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Mantenimiento $mantenimiento) {
        try {
            $sql = "UPDATE mantenimiento SET especialidad = :especialidad, turno = :turno, 
                    certificaciones = :certificaciones WHERE id_empleado = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id', $mantenimiento->getIdEmpleado());
            $stmt->bindValue(':especialidad', $mantenimiento->getEspecialidad());
            $stmt->bindValue(':turno', $mantenimiento->getTurno());
            $stmt->bindValue(':certificaciones', $mantenimiento->getCertificaciones());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM mantenimiento WHERE id_empleado = :id";
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
