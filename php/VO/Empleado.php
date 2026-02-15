<?php

class Empleado {
    private $id_empleado;
    private $id_usuario;
    private $email;
    private $tipo_empleado;

    public function __construct($id_empleado = null, $id_usuario = null, $email = '', $tipo_empleado = null) {
        $this->id_empleado = $id_empleado;
        $this->id_usuario = $id_usuario;
        $this->email = $email;
        $this->tipo_empleado = $tipo_empleado;
    }

    // Getters
    public function getIdEmpleado() {
        return $this->id_empleado;
    }

    public function getIdUsuario() {
        return $this->id_usuario;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getTipoEmpleado() {
        return $this->tipo_empleado;
    }

    // Setters
    public function setIdEmpleado($id_empleado) {
        $this->id_empleado = $id_empleado;
    }

    public function setIdUsuario($id_usuario) {
        $this->id_usuario = $id_usuario;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setTipoEmpleado($tipo_empleado) {
        $this->tipo_empleado = $tipo_empleado;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_empleado' => $this->id_empleado,
            'id_usuario' => $this->id_usuario,
            'email' => $this->email,
            'tipo_empleado' => $this->tipo_empleado
        ];
    }
}

?>
