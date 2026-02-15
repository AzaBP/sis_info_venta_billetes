<?php

class Usuario {
    private $id_usuario;
    private $nombre;
    private $apellido;
    private $email;
    private $password;
    private $telefono;
    private $tipo_usuario;

    public function __construct($id_usuario = null, $nombre = '', $apellido = '', $email = '', $password = '', $telefono = '', $tipo_usuario = null) {
        $this->id_usuario = $id_usuario;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->password = $password;
        $this->telefono = $telefono;
        $this->tipo_usuario = $tipo_usuario;
    }

    // Getters
    public function getIdUsuario() {
        return $this->id_usuario;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function getApellido() {
        return $this->apellido;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getTelefono() {
        return $this->telefono;
    }

    public function getTipoUsuario() {
        return $this->tipo_usuario;
    }

    // Setters
    public function setIdUsuario($id_usuario) {
        $this->id_usuario = $id_usuario;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function setApellido($apellido) {
        $this->apellido = $apellido;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function setTelefono($telefono) {
        $this->telefono = $telefono;
    }

    public function setTipoUsuario($tipo_usuario) {
        $this->tipo_usuario = $tipo_usuario;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_usuario' => $this->id_usuario,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'email' => $this->email,
            'password' => $this->password,
            'telefono' => $this->telefono,
            'tipo_usuario' => $this->tipo_usuario
        ];
    }
}

?>
