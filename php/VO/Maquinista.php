<?php

class Maquinista {
    private $id_empleado;
    private $licencia;
    private $experiencia_años;
    private $horario_preferido;

    public function __construct($id_empleado = null, $licencia = '', $experiencia_años = 0, $horario_preferido = '') {
        $this->id_empleado = $id_empleado;
        $this->licencia = $licencia;
        $this->experiencia_años = $experiencia_años;
        $this->horario_preferido = $horario_preferido;
    }

    // Getters
    public function getIdEmpleado() {
        return $this->id_empleado;
    }

    public function getLicencia() {
        return $this->licencia;
    }

    public function getExperienciaAños() {
        return $this->experiencia_años;
    }

    public function getHorarioPreferido() {
        return $this->horario_preferido;
    }

    // Setters
    public function setIdEmpleado($id_empleado) {
        $this->id_empleado = $id_empleado;
    }

    public function setLicencia($licencia) {
        $this->licencia = $licencia;
    }

    public function setExperienciaAños($experiencia_años) {
        $this->experiencia_años = $experiencia_años;
    }

    public function setHorarioPreferido($horario_preferido) {
        $this->horario_preferido = $horario_preferido;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_empleado' => $this->id_empleado,
            'licencia' => $this->licencia,
            'experiencia_años' => $this->experiencia_años,
            'horario_preferido' => $this->horario_preferido
        ];
    }
}

?>
