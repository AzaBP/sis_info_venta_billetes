<?php

class Incidencia {
    private $id_incidencia;
    private $id_viaje;
    private $id_mantenimiento;
    private $id_maquinista;
    private $tipo_incidencia;
    private $origen;
    private $descripcion;
    private $fecha_reporte;
    private $estado;
    private $afecta_pasajero;
    private $resolucion;
    private $fecha_resolucion;

    public function __construct($id_incidencia = null, $id_viaje = null, $id_mantenimiento = null, 
                                $id_maquinista = null, $tipo_incidencia = null, $origen = null,
                                $descripcion = null, $fecha_reporte = null, $estado = null,
                                $afecta_pasajero = null, $resolucion = null, $fecha_resolucion = null) {
        $this->id_incidencia = $id_incidencia;
        $this->id_viaje = $id_viaje;
        $this->id_mantenimiento = $id_mantenimiento;
        $this->id_maquinista = $id_maquinista;
        $this->tipo_incidencia = $tipo_incidencia;
        $this->origen = $origen;
        $this->descripcion = $descripcion;
        $this->fecha_reporte = $fecha_reporte;
        $this->estado = $estado;
        $this->afecta_pasajero = $afecta_pasajero;
        $this->resolucion = $resolucion;
        $this->fecha_resolucion = $fecha_resolucion;
    }

    public function getIdIncidencia() {
        return $this->id_incidencia;
    }

    public function setIdIncidencia($id_incidencia) {
        $this->id_incidencia = $id_incidencia;
    }

    public function getIdViaje() {
        return $this->id_viaje;
    }

    public function setIdViaje($id_viaje) {
        $this->id_viaje = $id_viaje;
    }

    public function getIdMantenimiento() {
        return $this->id_mantenimiento;
    }

    public function setIdMantenimiento($id_mantenimiento) {
        $this->id_mantenimiento = $id_mantenimiento;
    }

    public function getIdMaquinista() {
        return $this->id_maquinista;
    }

    public function setIdMaquinista($id_maquinista) {
        $this->id_maquinista = $id_maquinista;
    }

    public function getTipoIncidencia() {
        return $this->tipo_incidencia;
    }

    public function setTipoIncidencia($tipo_incidencia) {
        $this->tipo_incidencia = $tipo_incidencia;
    }

    public function getOrigen() {
        return $this->origen;
    }

    public function setOrigen($origen) {
        $this->origen = $origen;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion) {
        $this->descripcion = $descripcion;
    }

    public function getFechaReporte() {
        return $this->fecha_reporte;
    }

    public function setFechaReporte($fecha_reporte) {
        $this->fecha_reporte = $fecha_reporte;
    }

    public function getEstado() {
        return $this->estado;
    }

    public function setEstado($estado) {
        $this->estado = $estado;
    }

    public function getAfectaPasajero() {
        return $this->afecta_pasajero;
    }

    public function setAfectaPasajero($afecta_pasajero) {
        $this->afecta_pasajero = $afecta_pasajero;
    }

    public function getResolucion() {
        return $this->resolucion;
    }

    public function setResolucion($resolucion) {
        $this->resolucion = $resolucion;
    }

    public function getFechaResolucion() {
        return $this->fecha_resolucion;
    }

    public function setFechaResolucion($fecha_resolucion) {
        $this->fecha_resolucion = $fecha_resolucion;
    }

    public function toArray() {
        return array(
            'id_incidencia' => $this->id_incidencia,
            'id_viaje' => $this->id_viaje,
            'id_mantenimiento' => $this->id_mantenimiento,
            'id_maquinista' => $this->id_maquinista,
            'tipo_incidencia' => $this->tipo_incidencia,
            'origen' => $this->origen,
            'descripcion' => $this->descripcion,
            'fecha_reporte' => $this->fecha_reporte,
            'estado' => $this->estado,
            'afecta_pasajero' => $this->afecta_pasajero,
            'resolucion' => $this->resolucion,
            'fecha_resolucion' => $this->fecha_resolucion
        );
    }
}
?>