<?php

require_once 'Conexion.php';
require_once __DIR__ . '/../VO/Viaje.php';

class ViajeDAO {
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    public function insertar($viaje) {
        $sql = "INSERT INTO VIAJE (id_vendedor, id_ruta, id_tren, id_maquinista, fecha, hora_salida, hora_llegada, precio, estado)
                VALUES (:id_vendedor, :id_ruta, :id_tren, :id_maquinista, :fecha, :hora_salida, :hora_llegada, :precio, :estado)";
        
        $stmt = $this->conexion->prepare($sql);
        
        $stmt->bindValue(':id_vendedor', $viaje->getIdVendedor());
        $stmt->bindValue(':id_ruta', $viaje->getIdRuta());
        $stmt->bindValue(':id_tren', $viaje->getIdTren());
        $stmt->bindValue(':id_maquinista', $viaje->getIdMaquinista());
        $stmt->bindValue(':fecha', $viaje->getFechaSalida());
        $stmt->bindValue(':hora_salida', $viaje->getHoraSalida());
        $stmt->bindValue(':hora_llegada', $viaje->getFechaLlegada());
        $stmt->bindValue(':precio', $viaje->getPrecio());
        $stmt->bindValue(':estado', $viaje->getEstado());

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        } else {
            return false;
        }
    }

    public function obtenerPorId($id_viaje) {
        $sql = "SELECT * FROM VIAJE WHERE id_viaje = :id_viaje";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_viaje', $id_viaje);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            return new Viaje(
                $resultado['id_viaje'],
                $resultado['id_vendedor'],
                $resultado['id_ruta'],
                $resultado['id_tren'],
                $resultado['id_maquinista'],
                $resultado['fecha'],
                $resultado['hora_salida'],
                $resultado['hora_llegada'],
                $resultado['precio'],
                $resultado['estado']
            );
        }
        return null;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM VIAJE";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        $viajes = array();
        while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $viajes[] = new Viaje(
                $resultado['id_viaje'],
                $resultado['id_vendedor'],
                $resultado['id_ruta'],
                $resultado['id_tren'],
                $resultado['id_maquinista'],
                $resultado['fecha'],
                $resultado['hora_salida'],
                $resultado['hora_llegada'],
                $resultado['precio'],
                $resultado['estado']
            );
        }
        return $viajes;
    }

    public function obtenerPorVendedor($id_vendedor) {
        $sql = "SELECT * FROM VIAJE WHERE id_vendedor = :id_vendedor";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_vendedor', $id_vendedor);
        $stmt->execute();

        $viajes = array();
        while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $viajes[] = new Viaje(
                $resultado['id_viaje'],
                $resultado['id_vendedor'],
                $resultado['id_ruta'],
                $resultado['id_tren'],
                $resultado['id_maquinista'],
                $resultado['fecha'],
                $resultado['hora_salida'],
                $resultado['hora_llegada'],
                $resultado['precio'],
                $resultado['estado']
            );
        }
        return $viajes;
    }

    public function actualizar($viaje) {
        $sql = "UPDATE VIAJE SET id_vendedor = :id_vendedor, id_ruta = :id_ruta, id_tren = :id_tren, 
            id_maquinista = :id_maquinista, fecha = :fecha, hora_salida = :hora_salida, hora_llegada = :hora_llegada, 
            precio = :precio, estado = :estado WHERE id_viaje = :id_viaje";
        
        $stmt = $this->conexion->prepare($sql);
        
        $stmt->bindValue(':id_viaje', $viaje->getIdViaje());
        $stmt->bindValue(':id_vendedor', $viaje->getIdVendedor());
        $stmt->bindValue(':id_ruta', $viaje->getIdRuta());
        $stmt->bindValue(':id_tren', $viaje->getIdTren());
        $stmt->bindValue(':id_maquinista', $viaje->getIdMaquinista());
        $stmt->bindValue(':fecha', $viaje->getFechaSalida());
        $stmt->bindValue(':hora_salida', $viaje->getHoraSalida());
        $stmt->bindValue(':hora_llegada', $viaje->getFechaLlegada());
        $stmt->bindValue(':precio', $viaje->getPrecio());
        $stmt->bindValue(':estado', $viaje->getEstado());

        return $stmt->execute();
    }

    public function eliminar($id_viaje) {
        $sql = "DELETE FROM VIAJE WHERE id_viaje = :id_viaje";
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id_viaje', $id_viaje);

        return $stmt->execute();
    }
}
?>
