<?php

class Vendedor {
    private $id_empleado;
    private $comision_porcentaje;
    private $region;

    public function __construct($id_empleado = null, $comision_porcentaje = 0.0, $region = '') {
        $this->id_empleado = $id_empleado;
        $this->comision_porcentaje = $comision_porcentaje;
        $this->region = $region;
    }

    // Getters
    public function getIdEmpleado() {
        return $this->id_empleado;
    }

    public function getComisionPorcentaje() {
        return $this->comision_porcentaje;
    }

    public function getRegion() {
        return $this->region;
    }

    // Setters
    public function setIdEmpleado($id_empleado) {
        $this->id_empleado = $id_empleado;
    }

    public function setComisionPorcentaje($comision_porcentaje) {
        $this->comision_porcentaje = $comision_porcentaje;
    }

    public function setRegion($region) {
        $this->region = $region;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_empleado' => $this->id_empleado,
            'comision_porcentaje' => $this->comision_porcentaje,
            'region' => $this->region
        ];
    }
}

?>
