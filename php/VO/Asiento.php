<?php

class Asiento {
    private $numero_asiento;
    private $id_tren;
    private $clase;
    private $estado;

    public function __construct($numero_asiento = null, $id_tren = null, $clase = '', $estado = 'disponible') {
        $this->numero_asiento = $numero_asiento;
        $this->id_tren = $id_tren;
        $this->clase = $clase;
        $this->estado = $estado;
    }

    // Getters
    public function getNumeroAsiento() {
        return $this->numero_asiento;
    }

    public function getIdTren() {
        return $this->id_tren;
    }

    public function getClase() {
        return $this->clase;
    }

    public function getEstado() {
        return $this->estado;
    }

    // Setters
    public function setNumeroAsiento($numero_asiento) {
        $this->numero_asiento = $numero_asiento;
    }

    public function setIdTren($id_tren) {
        $this->id_tren = $id_tren;
    }

    public function setClase($clase) {
        $this->clase = $clase;
    }

    public function setEstado($estado) {
        $this->estado = $estado;
    }

    // Convert to Array
    public function toArray() {
        return [
            'numero_asiento' => $this->numero_asiento,
            'id_tren' => $this->id_tren,
            'clase' => $this->clase,
            'estado' => $this->estado
        ];
    }
}

?>
