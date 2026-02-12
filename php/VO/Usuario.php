<?php

class Usuario {
    private $id_usuario;
    private $nombre;
    private $email;
    private $password;
    private $telefono;
    private $metodo_pago;

    public function __construct($id_usuario = null, $nombre = '', $email = '', $password = '', $telefono = '', $metodo_pago = '') {
        $this->id_usuario = $id_usuario;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password = $password;
        $this->telefono = $telefono;
        $this->metodo_pago = $metodo_pago;
    }

    // Getters
    public function getIdUsuario() {
        return $this->id_usuario;
    }

    public function getNombre() {
        return $this->nombre;
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

    public function getMetodoPago() {
        return $this->metodo_pago;
    }

    // Setters
    public function setIdUsuario($id_usuario) {
        $this->id_usuario = $id_usuario;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
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

    public function setMetodoPago($metodo_pago) {
        $this->metodo_pago = $metodo_pago;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_usuario' => $this->id_usuario,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'password' => $this->password,
            'telefono' => $this->telefono,
            'metodo_pago' => $this->metodo_pago
        ];
    }
}

?>
