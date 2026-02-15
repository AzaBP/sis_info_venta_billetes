<?php

require_once(__DIR__ . '/../VO/Abono.php');
require_once(__DIR__ . '/../Conexion.php');

class AbonoDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Abono $abono) {
        try {
            $sql = "INSERT INTO abono (id_pasajero, tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes) 
                    VALUES (:id_pasajero, :tipo, :fecha_inicio, :fecha_fin, :viajes_totales, :viajes_restantes)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id_pasajero', $abono->getIdPasajero());
            $stmt->bindValue(':tipo', $abono->getTipo());
            $stmt->bindValue(':fecha_inicio', $abono->getFechaInicio());
            $stmt->bindValue(':fecha_fin', $abono->getFechaFin());
            $stmt->bindValue(':viajes_totales', $abono->getViajesTotales());
            $stmt->bindValue(':viajes_restantes', $abono->getViajesRestantes());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM abono WHERE id_abono = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Abono(
                    $resultado['id_abono'],
                    $resultado['id_pasajero'],
                    $resultado['tipo'],
                    $resultado['fecha_inicio'],
                    $resultado['fecha_fin'],
                    $resultado['viajes_totales'],
                    $resultado['viajes_restantes']
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
            $sql = "SELECT * FROM abono";
            $stmt = $this->pdo->query($sql);
            $abonos = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $abonos[] = new Abono(
                    $resultado['id_abono'],
                    $resultado['id_pasajero'],
                    $resultado['tipo'],
                    $resultado['fecha_inicio'],
                    $resultado['fecha_fin'],
                    $resultado['viajes_totales'],
                    $resultado['viajes_restantes']
                );
            }
            return $abonos;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // OBTENER POR PASAJERO
    public function obtenerPorPasajero($id_pasajero) {
        try {
            $sql = "SELECT * FROM abono WHERE id_pasajero = :id_pasajero";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id_pasajero', $id_pasajero);
            $stmt->execute();
            
            $abonos = [];
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $abonos[] = new Abono(
                    $resultado['id_abono'],
                    $resultado['id_pasajero'],
                    $resultado['tipo'],
                    $resultado['fecha_inicio'],
                    $resultado['fecha_fin'],
                    $resultado['viajes_totales'],
                    $resultado['viajes_restantes']
                );
            }
            return $abonos;
        } catch (PDOException $e) {
            echo "Error al obtener por pasajero: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Abono $abono) {
        try {
            $sql = "UPDATE abono SET id_pasajero = :id_pasajero, tipo = :tipo, 
                    fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, 
                    viajes_totales = :viajes_totales, viajes_restantes = :viajes_restantes 
                    WHERE id_abono = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id', $abono->getIdAbono());
            $stmt->bindValue(':id_pasajero', $abono->getIdPasajero());
            $stmt->bindValue(':tipo', $abono->getTipo());
            $stmt->bindValue(':fecha_inicio', $abono->getFechaInicio());
            $stmt->bindValue(':fecha_fin', $abono->getFechaFin());
            $stmt->bindValue(':viajes_totales', $abono->getViajesTotales());
            $stmt->bindValue(':viajes_restantes', $abono->getViajesRestantes());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM abono WHERE id_abono = :id";
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
