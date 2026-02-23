<?php

class Viaje {
    private $id_viaje;
    private $id_vendedor;
    private $id_ruta;
    private $id_tren;
    private $id_maquinista;
    private $fecha;
    private $hora_salida;
    private $hora_llegada;
    private $precio;
    private $estado;

    public function __construct($id_viaje = null, $id_vendedor = null, $id_ruta = null, $id_tren = null, 
                                $id_maquinista = null, $fecha = null, $hora_salida = null, $hora_llegada = null,
                                $precio = null, $estado = null) {
        $this->id_viaje = $id_viaje;
        $this->id_vendedor = $id_vendedor;
        $this->id_ruta = $id_ruta;
        $this->id_tren = $id_tren;
        $this->id_maquinista = $id_maquinista;
        $this->fecha = $fecha;
        $this->hora_salida = $hora_salida;
        $this->hora_llegada = $hora_llegada;
        $this->precio = $precio;
        $this->estado = $estado;
    }

    public function getIdViaje() {
        return $this->id_viaje;
    }

    public function setIdViaje($id_viaje) {
        $this->id_viaje = $id_viaje;
    }

    public function getIdVendedor() {
        return $this->id_vendedor;
    }

    public function setIdVendedor($id_vendedor) {
        $this->id_vendedor = $id_vendedor;
    }

    public function getIdRuta() {
        return $this->id_ruta;
    }

    public function setIdRuta($id_ruta) {
        $this->id_ruta = $id_ruta;
    }

    public function getIdTren() {
        return $this->id_tren;
    }

    public function setIdTren($id_tren) {
        $this->id_tren = $id_tren;
    }

    public function getIdMaquinista() {
        return $this->id_maquinista;
    }

    public function setIdMaquinista($id_maquinista) {
        $this->id_maquinista = $id_maquinista;
    }

    public function getFechaSalida() {
        return $this->fecha;
    }

    public function setFechaSalida($fecha_salida) {
        $this->fecha = $fecha_salida;
    }

    public function getFechaLlegada() {
        return $this->hora_llegada;
    }

    public function setFechaLlegada($fecha_llegada) {
        $this->hora_llegada = $fecha_llegada;
    }

    public function getPrecio() {
        return $this->precio;
    }

    public function setPrecio($precio) {
        $this->precio = $precio;
    }

    public function getEstado() {
        return $this->estado;
    }

    public function setEstado($estado) {
        $this->estado = $estado;
    }

    public function toArray() {
        return array(
            'id_viaje' => $this->id_viaje,
            'id_vendedor' => $this->id_vendedor,
            'id_ruta' => $this->id_ruta,
            'id_tren' => $this->id_tren,
            'id_maquinista' => $this->id_maquinista,
            'fecha' => $this->fecha,
            'hora_salida' => $this->hora_salida,
            'hora_llegada' => $this->hora_llegada,
            'precio' => $this->precio,
            'estado' => $this->estado
        );
    }
}
?>
