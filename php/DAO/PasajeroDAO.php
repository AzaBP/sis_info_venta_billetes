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

            // Bind values explicitly. Cast booleans to proper PDO::PARAM_BOOL to avoid empty-string errors.
            $stmt->bindValue(':id_usuario', $pasajero->getIdUsuario(), PDO::PARAM_INT);
            $stmt->bindValue(':fecha_nacimiento', $pasajero->getFechaNacimiento(), PDO::PARAM_STR);
            $stmt->bindValue(':genero', $pasajero->getGenero(), PDO::PARAM_STR);
            $stmt->bindValue(':tipo_documento', $pasajero->getTipoDocumento(), PDO::PARAM_STR);
            $stmt->bindValue(':numero_documento', $pasajero->getNumeroDocumento(), PDO::PARAM_STR);
            $stmt->bindValue(':calle', $pasajero->getCalle(), PDO::PARAM_STR);
            $stmt->bindValue(':ciudad', $pasajero->getCiudad(), PDO::PARAM_STR);
            $stmt->bindValue(':codigo_postal', $pasajero->getCodigoPostal(), PDO::PARAM_STR);
            $stmt->bindValue(':pais', $pasajero->getPais(), PDO::PARAM_STR);

            // Ensure boolean values are explicitly bound as booleans
            $stmt->bindValue(':acepta_terminos', (bool)$pasajero->getAceptaTerminos(), PDO::PARAM_BOOL);
            $stmt->bindValue(':acepta_privacidad', (bool)$pasajero->getAceptaPrivacidad(), PDO::PARAM_BOOL);
            $stmt->bindValue(':newsletter', (bool)$pasajero->getNewsletter(), PDO::PARAM_BOOL);

            $stmt->execute();

            return true;

        } catch (PDOException $e) {
            error_log("Error al insertar pasajero: " . $e->getMessage());
            return false;
        }
    }

}
?>