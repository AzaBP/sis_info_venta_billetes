<?php

require_once 'Conexion.php';
require_once __DIR__ . '/../VO/Incidencia.php';

class IncidenciaDAO {
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    public function insertar($incidencia) {
        $sql = "INSERT INTO INCIDENCIA (id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero, resolucion, fecha_resolucion)
                VALUES (:id_viaje, :id_mantenimiento, :id_maquinista, :tipo_incidencia, :origen, :descripcion, :fecha_reporte, :estado, :afecta_pasajero, :resolucion, :fecha_resolucion)";
        
        $stmt = $this->conexion->prepare($sql);
        
        $stmt->bindValue(':id_viaje', $incidencia->getIdViaje());
        $stmt->bindValue(':id_mantenimiento', $incidencia->getIdMantenimiento());
        $stmt->bindValue(':id_maquinista', $incidencia->getIdMaquinista());
        $stmt->bindValue(':tipo_incidencia', $incidencia->getTipoIncidencia());
        $stmt->bindValue(':origen', $incidencia->getOrigen());
        $stmt->bindValue(':descripcion', $incidencia->getDescripcion());
        $stmt->bindValue(':fecha_reporte', $incidencia->getFechaReporte());
        $stmt->bindValue(':estado', $incidencia->getEstado());
        $stmt->bindValue(':afecta_pasajero', $incidencia->getAfectaPasajero(), PDO::PARAM_BOOL);
        $stmt->bindValue(':resolucion', $incidencia->getResolucion());
        $stmt->bindValue(':fecha_resolucion', $incidencia->getFechaResolucion());

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
                $resultado['tipo_incidencia'] ?? null,
                $resultado['origen'] ?? null,
                $resultado['descripcion'],
                $resultado['fecha_reporte'],
                $resultado['estado'],
                isset($resultado['afecta_pasajero']) ? (bool)$resultado['afecta_pasajero'] : null,
                $resultado['resolucion'] ?? null,
                $resultado['fecha_resolucion'] ?? null
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
                $resultado['tipo_incidencia'] ?? null,
                $resultado['origen'] ?? null,
                $resultado['descripcion'],
                $resultado['fecha_reporte'],
                $resultado['estado'],
                isset($resultado['afecta_pasajero']) ? (bool)$resultado['afecta_pasajero'] : null,
                $resultado['resolucion'] ?? null,
                $resultado['fecha_resolucion'] ?? null
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
                $resultado['tipo_incidencia'] ?? null,
                $resultado['origen'] ?? null,
                $resultado['descripcion'],
                $resultado['fecha_reporte'],
                $resultado['estado'],
                isset($resultado['afecta_pasajero']) ? (bool)$resultado['afecta_pasajero'] : null,
                $resultado['resolucion'] ?? null,
                $resultado['fecha_resolucion'] ?? null
            );
        }
        return $incidencias;
    }

    public function actualizar($incidencia) {
        $sql = "UPDATE INCIDENCIA SET id_viaje = :id_viaje, id_mantenimiento = :id_mantenimiento, id_maquinista = :id_maquinista,
                tipo_incidencia = :tipo_incidencia, origen = :origen, descripcion = :descripcion, fecha_reporte = :fecha_reporte, 
                estado = :estado, afecta_pasajero = :afecta_pasajero, resolucion = :resolucion, fecha_resolucion = :fecha_resolucion
                WHERE id_incidencia = :id_incidencia";
        
        $stmt = $this->conexion->prepare($sql);
        
        $stmt->bindValue(':id_incidencia', $incidencia->getIdIncidencia());
        $stmt->bindValue(':id_viaje', $incidencia->getIdViaje());
        $stmt->bindValue(':id_mantenimiento', $incidencia->getIdMantenimiento());
        $stmt->bindValue(':id_maquinista', $incidencia->getIdMaquinista());
        $stmt->bindValue(':tipo_incidencia', $incidencia->getTipoIncidencia());
        $stmt->bindValue(':origen', $incidencia->getOrigen());
        $stmt->bindValue(':descripcion', $incidencia->getDescripcion());
        $stmt->bindValue(':fecha_reporte', $incidencia->getFechaReporte());
        $stmt->bindValue(':estado', $incidencia->getEstado());
        $stmt->bindValue(':afecta_pasajero', $incidencia->getAfectaPasajero(), PDO::PARAM_BOOL);
        $stmt->bindValue(':resolucion', $incidencia->getResolucion());
        $stmt->bindValue(':fecha_resolucion', $incidencia->getFechaResolucion());

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
