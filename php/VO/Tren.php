<?php

class Tren {
    private $id_tren;
    private $modelo;
    private $capacidad;

    public function __construct($id_tren = null, $modelo = '', $capacidad = 0) {
        $this->id_tren = $id_tren;
        $this->modelo = $modelo;
        $this->capacidad = $capacidad;
    }

    // Getters
    public function getIdTren() {
        return $this->id_tren;
    }

    public function getModelo() {
        return $this->modelo;
    }

    public function getCapacidad() {
        return $this->capacidad;
    }

    // Setters
    public function setIdTren($id_tren) {
        $this->id_tren = $id_tren;
    }

    public function setModelo($modelo) {
        $this->modelo = $modelo;
    }

    public function setCapacidad($capacidad) {
        $this->capacidad = $capacidad;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_tren' => $this->id_tren,
            'modelo' => $this->modelo,
            'capacidad' => $this->capacidad
        ];
    }
}

?>
