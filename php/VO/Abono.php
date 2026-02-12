<?php

class Abono {
    private $id_abono;
    private $id_pasajero;
    private $tipo;
    private $fecha_inicio;
    private $fecha_fin;
    private $viajes_totales;
    private $viajes_restantes;

    public function __construct($id_abono = null, $id_pasajero = null, $tipo = '', $fecha_inicio = '', $fecha_fin = '', $viajes_totales = 0, $viajes_restantes = 0) {
        $this->id_abono = $id_abono;
        $this->id_pasajero = $id_pasajero;
        $this->tipo = $tipo;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
        $this->viajes_totales = $viajes_totales;
        $this->viajes_restantes = $viajes_restantes;
    }

    // Getters
    public function getIdAbono() {
        return $this->id_abono;
    }

    public function getIdPasajero() {
        return $this->id_pasajero;
    }

    public function getTipo() {
        return $this->tipo;
    }

    public function getFechaInicio() {
        return $this->fecha_inicio;
    }

    public function getFechaFin() {
        return $this->fecha_fin;
    }

    public function getViajesTotales() {
        return $this->viajes_totales;
    }

    public function getViajesRestantes() {
        return $this->viajes_restantes;
    }

    // Setters
    public function setIdAbono($id_abono) {
        $this->id_abono = $id_abono;
    }

    public function setIdPasajero($id_pasajero) {
        $this->id_pasajero = $id_pasajero;
    }

    public function setTipo($tipo) {
        $this->tipo = $tipo;
    }

    public function setFechaInicio($fecha_inicio) {
        $this->fecha_inicio = $fecha_inicio;
    }

    public function setFechaFin($fecha_fin) {
        $this->fecha_fin = $fecha_fin;
    }

    public function setViajesTotales($viajes_totales) {
        $this->viajes_totales = $viajes_totales;
    }

    public function setViajesRestantes($viajes_restantes) {
        $this->viajes_restantes = $viajes_restantes;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_abono' => $this->id_abono,
            'id_pasajero' => $this->id_pasajero,
            'tipo' => $this->tipo,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'viajes_totales' => $this->viajes_totales,
            'viajes_restantes' => $this->viajes_restantes
        ];
    }
}

?>
