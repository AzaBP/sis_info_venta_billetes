<?php

require_once 'Conexion.php';
require_once __DIR__ . '/../VO/Incidencia.php';

class IncidenciaDAO {
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    public function insertar($incidencia) {
        $sql = "INSERT INTO INCIDENCIA (id_viaje, id_mantenimiento, id_maquinista, descripcion, fecha_reporte, estado)
                VALUES (:id_viaje, :id_mantenimiento, :id_maquinista, :descripcion, :fecha_reporte, :estado)";
        
        $stmt = $this->conexion->prepare($sql);
        
        $stmt->bindValue(':id_viaje', $incidencia->getIdViaje());
        $stmt->bindValue(':id_mantenimiento', $incidencia->getIdMantenimiento());
        $stmt->bindValue(':id_maquinista', $incidencia->getIdMaquinista());
        $stmt->bindValue(':descripcion', $incidencia->getDescripcion());
        $stmt->bindValue(':fecha_reporte', $incidencia->getFechaReporte());
        $stmt->bindValue(':estado', $incidencia->getEstado());

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        } else {
            return false;
        }
    }

    public function obtenerPorId($id_incidencia) {
        $sql = "SELECT * FROM INCIDENCIA WHERE id_incidencia = :id_incidencia";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_incidencia', $id_incidencia);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            return new Incidencia(
                $resultado['id_incidencia'],
                $resultado['id_viaje'],
                $resultado['id_mantenimiento'],
                $resultado['id_maquinista'],
                $resultado['descripcion'],
                $resultado['fecha_reporte'],
                $resultado['estado']
            );
        }
        return null;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM INCIDENCIA";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        $incidencias = array();
        while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $incidencias[] = new Incidencia(
                $resultado['id_incidencia'],
                $resultado['id_viaje'],
                $resultado['id_mantenimiento'],
                $resultado['id_maquinista'],
                $resultado['descripcion'],
                $resultado['fecha_reporte'],
                $resultado['estado']
            );
        }
        return $incidencias;
    }

    public function obtenerPorViaje($id_viaje) {
        $sql = "SELECT * FROM INCIDENCIA WHERE id_viaje = :id_viaje";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_viaje', $id_viaje);
        $stmt->execute();

        $incidencias = array();
        while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $incidencias[] = new Incidencia(
                $resultado['id_incidencia'],
                $resultado['id_viaje'],
                $resultado['id_mantenimiento'],
                $resultado['id_maquinista'],
                $resultado['descripcion'],
                $resultado['fecha_reporte'],
                $resultado['estado']
            );
        }
        return $incidencias;
    }

    public function actualizar($incidencia) {
        $sql = "UPDATE INCIDENCIA SET id_viaje = :id_viaje, id_mantenimiento = :id_mantenimiento, id_maquinista = :id_maquinista,
                descripcion = :descripcion, fecha_reporte = :fecha_reporte, estado = :estado WHERE id_incidencia = :id_incidencia";
        
        $stmt = $this->conexion->prepare($sql);
        
        $stmt->bindValue(':id_incidencia', $incidencia->getIdIncidencia());
        $stmt->bindValue(':id_viaje', $incidencia->getIdViaje());
        $stmt->bindValue(':id_mantenimiento', $incidencia->getIdMantenimiento());
        $stmt->bindValue(':id_maquinista', $incidencia->getIdMaquinista());
        $stmt->bindValue(':descripcion', $incidencia->getDescripcion());
        $stmt->bindValue(':fecha_reporte', $incidencia->getFechaReporte());
        $stmt->bindValue(':estado', $incidencia->getEstado());

        return $stmt->execute();
    }

    public function eliminar($id_incidencia) {
        $sql = "DELETE FROM INCIDENCIA WHERE id_incidencia = :id_incidencia";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_incidencia', $id_incidencia);

        return $stmt->execute();
    }
}
?>