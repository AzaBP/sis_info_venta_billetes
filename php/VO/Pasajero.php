<?php

class Pasajero {
    private $id_pasajero;
    private $id_usuario;
    private $fecha_nacimiento;
    private $genero;
    private $tipo_documento;
    private $calle;
    private $ciudad;
    private $codigo_postal;
    private $pais;
    private $metodo_pago;
    private $acepta_terminos;
    private $acepta_privacidad;
    private $newsletter;

    public function __construct($id_pasajero = null, $id_usuario = null, $fecha_nacimiento = '', $genero = '', 
                                $tipo_documento = '', $calle = '', $ciudad = '', $codigo_postal = '', $pais = '', 
                                $metodo_pago = '', $acepta_terminos = false, $acepta_privacidad = false, $newsletter = false) {
        $this->id_pasajero = $id_pasajero;
        $this->id_usuario = $id_usuario;
        $this->fecha_nacimiento = $fecha_nacimiento;
        $this->genero = $genero;
        $this->tipo_documento = $tipo_documento;
        $this->calle = $calle;
        $this->ciudad = $ciudad;
        $this->codigo_postal = $codigo_postal;
        $this->pais = $pais;
        $this->metodo_pago = $metodo_pago;
        $this->acepta_terminos = $acepta_terminos;
        $this->acepta_privacidad = $acepta_privacidad;
        $this->newsletter = $newsletter;
    }

    // Getters
    public function getIdPasajero() {
        return $this->id_pasajero;
    }

    public function getIdUsuario() {
        return $this->id_usuario;
    }

    public function getFechaNacimiento() {
        return $this->fecha_nacimiento;
    }

    public function getGenero() {
        return $this->genero;
    }

    public function getTipoDocumento() {
        return $this->tipo_documento;
    }

    public function getCalle() {
        return $this->calle;
    }

    public function getCiudad() {
        return $this->ciudad;
    }

    public function getCodigoPostal() {
        return $this->codigo_postal;
    }

    public function getPais() {
        return $this->pais;
    }

    public function getMetodoPago() {
        return $this->metodo_pago;
    }

    public function getAceptaTerminos() {
        return $this->acepta_terminos;
    }

    public function getAceptaPrivacidad() {
        return $this->acepta_privacidad;
    }

    public function getNewsletter() {
        return $this->newsletter;
    }

    // Setters
    public function setIdPasajero($id_pasajero) {
        $this->id_pasajero = $id_pasajero;
    }

    public function setIdUsuario($id_usuario) {
        $this->id_usuario = $id_usuario;
    }

    public function setFechaNacimiento($fecha_nacimiento) {
        $this->fecha_nacimiento = $fecha_nacimiento;
    }

    public function setGenero($genero) {
        $this->genero = $genero;
    }

    public function setTipoDocumento($tipo_documento) {
        $this->tipo_documento = $tipo_documento;
    }

    public function setCalle($calle) {
        $this->calle = $calle;
    }

    public function setCiudad($ciudad) {
        $this->ciudad = $ciudad;
    }

    public function setCodigoPostal($codigo_postal) {
        $this->codigo_postal = $codigo_postal;
    }

    public function setPais($pais) {
        $this->pais = $pais;
    }

    public function setMetodoPago($metodo_pago) {
        $this->metodo_pago = $metodo_pago;
    }

    public function setAceptaTerminos($acepta_terminos) {
        $this->acepta_terminos = $acepta_terminos;
    }

    public function setAceptaPrivacidad($acepta_privacidad) {
        $this->acepta_privacidad = $acepta_privacidad;
    }

    public function setNewsletter($newsletter) {
        $this->newsletter = $newsletter;
    }

    // Convert to Array
    public function toArray() {
        return [
            'id_pasajero' => $this->id_pasajero,
            'id_usuario' => $this->id_usuario,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
            'tipo_documento' => $this->tipo_documento,
            'calle' => $this->calle,
            'ciudad' => $this->ciudad,
            'codigo_postal' => $this->codigo_postal,
            'pais' => $this->pais,
            'metodo_pago' => $this->metodo_pago,
            'acepta_terminos' => $this->acepta_terminos,
            'acepta_privacidad' => $this->acepta_privacidad,
            'newsletter' => $this->newsletter
        ];
    }
}

?>
