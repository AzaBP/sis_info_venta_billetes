<?php

class Incidencia {
    private $id_incidencia;
    private $id_viaje;
    private $id_mantenimiento;
    private $id_maquinista;
    private $descripcion;
    private $fecha_reporte;
    private $estado;

    public function __construct($id_incidencia = null, $id_viaje = null, $id_mantenimiento = null, 
                                $id_maquinista = null, $descripcion = null, $fecha_reporte = null, 
                                $estado = null) {
        $this->id_incidencia = $id_incidencia;
        $this->id_viaje = $id_viaje;
        $this->id_mantenimiento = $id_mantenimiento;
        $this->id_maquinista = $id_maquinista;
        $this->descripcion = $descripcion;
        $this->fecha_reporte = $fecha_reporte;
        $this->estado = $estado;
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

    public function toArray() {
        return array(
            'id_incidencia' => $this->id_incidencia,
            'id_viaje' => $this->id_viaje,
            'id_mantenimiento' => $this->id_mantenimiento,
            'id_maquinista' => $this->id_maquinista,
            'descripcion' => $this->descripcion,
            'fecha_reporte' => $this->fecha_reporte,
            'estado' => $this->estado
        );
    }
}
?>
