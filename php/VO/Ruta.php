<?php

class Ruta {
    private $id_ruta;
    private $origen;
    private $destino;
    private $duracion;
    private $id_vendedor;

    public function __construct($id_ruta = null, $origen = '', $destino = '', $duracion = '', $id_vendedor = null) {
        $this->id_ruta = $id_ruta;
        $this->origen = $origen;
        $this->destino = $destino;
        $this->duracion = $duracion;
        $this->id_vendedor = $id_vendedor;
    }

    // Getters
    public function getIdRuta() {
        return $this->id_ruta;
    }

    public function getOrigen() {
        return $this->origen;
    }

    public function getDestino() {
        return $this->destino;
    }

    public function getDuracion() {
        return $this->duracion;
    }

    public function getIdVendedor() {
        return $this->id_vendedor;
    }

    // Setters
    public function setIdRuta($id_ruta) {
        $this->id_ruta = $id_ruta;
    }

    public function setOrigen($origen) {
        $this->origen = $origen;
    }

    public function setDestino($destino) {
        $this->destino = $destino;
    }

    public function setDuracion($duracion) {
        $this->duracion = $duracion;
    }

    public function setIdVendedor($id_vendedor) {
        $this->id_vendedor = $id_vendedor;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_ruta' => $this->id_ruta,
            'origen' => $this->origen,
            'destino' => $this->destino,
            'duracion' => $this->duracion,
            'id_vendedor' => $this->id_vendedor
        ];
    }
}

?>
