<?php

class Billete {
    private $id_billete;
    private $id_pasajero;
    private $id_ruta;
    private $id_tren;
    private $id_asiento;
    private $fecha_viaje;
    private $precio_pagado;
    private $metodo_pago;
    private $codigo_billete;
    private $estado;
    private $fecha_compra;

    public function __construct($id_billete = null, $id_pasajero = null, $id_ruta = null, $id_tren = null, 
                                $id_asiento = null, $fecha_viaje = '', $precio_pagado = 0.0, $metodo_pago = '', 
                                $codigo_billete = '', $estado = '', $fecha_compra = '') {
        $this->id_billete = $id_billete;
        $this->id_pasajero = $id_pasajero;
        $this->id_ruta = $id_ruta;
        $this->id_tren = $id_tren;
        $this->id_asiento = $id_asiento;
        $this->fecha_viaje = $fecha_viaje;
        $this->precio_pagado = $precio_pagado;
        $this->metodo_pago = $metodo_pago;
        $this->codigo_billete = $codigo_billete;
        $this->estado = $estado;
        $this->fecha_compra = $fecha_compra;
    }

    // Getters
    public function getIdBillete() {
        return $this->id_billete;
    }

    public function getIdPasajero() {
        return $this->id_pasajero;
    }

    public function getIdRuta() {
        return $this->id_ruta;
    }

    public function getIdTren() {
        return $this->id_tren;
    }

    public function getIdAsiento() {
        return $this->id_asiento;
    }

    public function getFechaViaje() {
        return $this->fecha_viaje;
    }

    public function getPrecioPagado() {
        return $this->precio_pagado;
    }

    public function getMetodoPago() {
        return $this->metodo_pago;
    }

    public function getCodigoBillete() {
        return $this->codigo_billete;
    }

    public function getEstado() {
        return $this->estado;
    }

    public function getFechaCompra() {
        return $this->fecha_compra;
    }

    // Setters
    public function setIdBillete($id_billete) {
        $this->id_billete = $id_billete;
    }

    public function setIdPasajero($id_pasajero) {
        $this->id_pasajero = $id_pasajero;
    }

    public function setIdRuta($id_ruta) {
        $this->id_ruta = $id_ruta;
    }

    public function setIdTren($id_tren) {
        $this->id_tren = $id_tren;
    }

    public function setIdAsiento($id_asiento) {
        $this->id_asiento = $id_asiento;
    }

    public function setFechaViaje($fecha_viaje) {
        $this->fecha_viaje = $fecha_viaje;
    }

    public function setPrecioPagado($precio_pagado) {
        $this->precio_pagado = $precio_pagado;
    }

    public function setMetodoPago($metodo_pago) {
        $this->metodo_pago = $metodo_pago;
    }

    public function setCodigoBillete($codigo_billete) {
        $this->codigo_billete = $codigo_billete;
    }

    public function setEstado($estado) {
        $this->estado = $estado;
    }

    public function setFechaCompra($fecha_compra) {
        $this->fecha_compra = $fecha_compra;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_billete' => $this->id_billete,
            'id_pasajero' => $this->id_pasajero,
            'id_ruta' => $this->id_ruta,
            'id_tren' => $this->id_tren,
            'id_asiento' => $this->id_asiento,
            'fecha_viaje' => $this->fecha_viaje,
            'precio_pagado' => $this->precio_pagado,
            'metodo_pago' => $this->metodo_pago,
            'codigo_billete' => $this->codigo_billete,
            'estado' => $this->estado,
            'fecha_compra' => $this->fecha_compra
        ];
    }
}

?>
