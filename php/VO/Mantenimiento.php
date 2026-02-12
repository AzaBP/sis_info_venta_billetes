<?php

class Mantenimiento {
    private $id_empleado;
    private $especialidad;
    private $turno;
    private $certificaciones;

    public function __construct($id_empleado = null, $especialidad = '', $turno = '', $certificaciones = '') {
        $this->id_empleado = $id_empleado;
        $this->especialidad = $especialidad;
        $this->turno = $turno;
        $this->certificaciones = $certificaciones;
    }

    // Getters
    public function getIdEmpleado() {
        return $this->id_empleado;
    }

    public function getEspecialidad() {
        return $this->especialidad;
    }

    public function getTurno() {
        return $this->turno;
    }

    public function getCertificaciones() {
        return $this->certificaciones;
    }

    // Setters
    public function setIdEmpleado($id_empleado) {
        $this->id_empleado = $id_empleado;
    }

    public function setEspecialidad($especialidad) {
        $this->especialidad = $especialidad;
    }

    public function setTurno($turno) {
        $this->turno = $turno;
    }

    public function setCertificaciones($certificaciones) {
        $this->certificaciones = $certificaciones;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_empleado' => $this->id_empleado,
            'especialidad' => $this->especialidad,
            'turno' => $this->turno,
            'certificaciones' => $this->certificaciones
        ];
    }
}

?>
