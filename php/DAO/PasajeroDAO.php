<?php

require_once(__DIR__ . '/../VO/Pasajero.php');
require_once(__DIR__ . '/../Conexion.php');

class PasajeroDAO {

    private $pdo;

    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->conectar();
    }

    // INSERTAR
    public function insertar(Pasajero $pasajero) {
        try {
            $sql = "INSERT INTO pasajero 
            (id_usuario, fecha_nacimiento, genero, tipo_documento, numero_documento,
             calle, ciudad, codigo_postal, pais, acepta_terminos, acepta_privacidad, newsletter)

            VALUES 
            (:id_usuario, :fecha_nacimiento, :genero, :tipo_documento, :numero_documento,
             :calle, :ciudad, :codigo_postal, :pais, :acepta_terminos, :acepta_privacidad, :newsletter)";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':id_usuario' => $pasajero->getIdUsuario(),
                ':fecha_nacimiento' => $pasajero->getFechaNacimiento(),
                ':genero' => $pasajero->getGenero(),
                ':tipo_documento' => $pasajero->getTipoDocumento(),
                ':numero_documento' => $pasajero->getNumeroDocumento(),
                ':calle' => $pasajero->getCalle(),
                ':ciudad' => $pasajero->getCiudad(),
                ':codigo_postal' => $pasajero->getCodigoPostal(),
                ':pais' => $pasajero->getPais(),
                ':acepta_terminos' => $pasajero->getAceptaTerminos(),
                ':acepta_privacidad' => $pasajero->getAceptaPrivacidad(),
                ':newsletter' => $pasajero->getNewsletter()
            ]);

            return true;

        } catch (PDOException $e) {
            echo "Error al insertar pasajero: " . $e->getMessage();
            return false;
        }
    }

}
?>