<?php

require_once(__DIR__ . '/../VO/Maquinista.php');
require_once(__DIR__ . '/../Conexion.php');

class MaquinistaDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Maquinista $maquinista) {
        try {
            $sql = "INSERT INTO maquinista (id_empleado, licencia, experiencia_años, horario_preferido) 
                    VALUES (:id_empleado, :licencia, :experiencia_años, :horario_preferido)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id_empleado', $maquinista->getIdEmpleado());
            $stmt->bindValue(':licencia', $maquinista->getLicencia());
            $stmt->bindValue(':experiencia_años', $maquinista->getExperienciaAños());
            $stmt->bindValue(':horario_preferido', $maquinista->getHorarioPreferido());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM maquinista WHERE id_empleado = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Maquinista(
                    $resultado['id_empleado'],
                    $resultado['licencia'],
                    $resultado['experiencia_años'],
                    $resultado['horario_preferido']
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
            $sql = "SELECT * FROM maquinista";
            $stmt = $this->pdo->query($sql);
            $maquinistas = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $maquinistas[] = new Maquinista(
                    $resultado['id_empleado'],
                    $resultado['licencia'],
                    $resultado['experiencia_años'],
                    $resultado['horario_preferido']
                );
            }
            return $maquinistas;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Maquinista $maquinista) {
        try {
            $sql = "UPDATE maquinista SET licencia = :licencia, experiencia_años = :experiencia_años, 
                    horario_preferido = :horario_preferido WHERE id_empleado = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id', $maquinista->getIdEmpleado());
            $stmt->bindValue(':licencia', $maquinista->getLicencia());
            $stmt->bindValue(':experiencia_años', $maquinista->getExperienciaAños());
            $stmt->bindValue(':horario_preferido', $maquinista->getHorarioPreferido());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM maquinista WHERE id_empleado = :id";
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
