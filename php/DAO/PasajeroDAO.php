<?php

require_once(__DIR__ . '/../VO/Pasajero.php');
require_once(__DIR__ . '/../Conexion.php');

class PasajeroDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Pasajero $pasajero) {
        try {
            $sql = "INSERT INTO pasajero (id_usuario, fecha_nacimiento, genero, tipo_documento, 
                    calle, ciudad, codigo_postal, pais, metodo_pago, acepta_terminos, acepta_privacidad, newsletter) 
                    VALUES (:id_usuario, :fecha_nacimiento, :genero, :tipo_documento, 
                    :calle, :ciudad, :codigo_postal, :pais, :metodo_pago, :acepta_terminos, :acepta_privacidad, :newsletter)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':id_usuario', $pasajero->getIdUsuario());
            $stmt->bindValue(':fecha_nacimiento', $pasajero->getFechaNacimiento());
            $stmt->bindValue(':genero', $pasajero->getGenero());
            $stmt->bindValue(':tipo_documento', $pasajero->getTipoDocumento());
            $stmt->bindValue(':calle', $pasajero->getCalle());
            $stmt->bindValue(':ciudad', $pasajero->getCiudad());
            $stmt->bindValue(':codigo_postal', $pasajero->getCodigoPostal());
            $stmt->bindValue(':pais', $pasajero->getPais());
            $stmt->bindValue(':metodo_pago', $pasajero->getMetodoPago());
            $stmt->bindValue(':acepta_terminos', $pasajero->getAceptaTerminos(), PDO::PARAM_BOOL);
            $stmt->bindValue(':acepta_privacidad', $pasajero->getAceptaPrivacidad(), PDO::PARAM_BOOL);
            $stmt->bindValue(':newsletter', $pasajero->getNewsletter(), PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM pasajero WHERE id_pasajero = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Pasajero(
                    $resultado['id_pasajero'],
                    $resultado['id_usuario'],
                    $resultado['apellido'],
                    $resultado['fecha_nacimiento'],
                    $resultado['genero'],
                    $resultado['tipo_documento'],
                    $resultado['calle'],
                    $resultado['ciudad'],
                    $resultado['codigo_postal'],
                    $resultado['pais'],
                    $resultado['acepta_terminos'],
                    $resultado['acepta_privacidad'],
                    $resultado['newsletter']
                );
            }
            return null;
        } catch (PDOException $e) {
            echo "Error al obtener: " . $e->getMessage();
            return null;
        }
    }

    // OBTENER TODOS
    public function obtenerTodos() {
        try {
            $sql = "SELECT * FROM pasajero";
            $stmt = $this->pdo->query($sql);
            $pasajeros = [];
            
            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pasajeros[] = new Pasajero(
                    $resultado['id_pasajero'],
                    $resultado['id_usuario'],
                    $resultado['apellido'],
                    $resultado['fecha_nacimiento'],
                    $resultado['genero'],
                    $resultado['tipo_documento'],
                    $resultado['calle'],
                    $resultado['ciudad'],
                    $resultado['codigo_postal'],
                    $resultado['pais'],
                    $resultado['acepta_terminos'],
                    $resultado['acepta_privacidad'],
                    $resultado['newsletter']
                );
            }
            return $pasajeros;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Pasajero $pasajero) {
        try {
            $sql = "UPDATE pasajero SET id_usuario = :id_usuario, apellido = :apellido, 
                    fecha_nacimiento = :fecha_nacimiento, genero = :genero, tipo_documento = :tipo_documento,
                    calle = :calle, ciudad = :ciudad, codigo_postal = :codigo_postal, pais = :pais,
                    acepta_terminos = :acepta_terminos, acepta_privacidad = :acepta_privacidad, 
                    newsletter = :newsletter WHERE id_pasajero = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindParam(':id', $pasajero->getIdPasajero());
            $stmt->bindParam(':id_usuario', $pasajero->getIdUsuario());
            $stmt->bindParam(':apellido', $pasajero->getApellido());
            $stmt->bindParam(':fecha_nacimiento', $pasajero->getFechaNacimiento());
            $stmt->bindParam(':genero', $pasajero->getGenero());
            $stmt->bindParam(':tipo_documento', $pasajero->getTipoDocumento());
            $stmt->bindParam(':calle', $pasajero->getCalle());
            $stmt->bindParam(':ciudad', $pasajero->getCiudad());
            $stmt->bindParam(':codigo_postal', $pasajero->getCodigoPostal());
            $stmt->bindParam(':pais', $pasajero->getPais());
            $stmt->bindParam(':acepta_terminos', $pasajero->getAceptaTerminos(), PDO::PARAM_BOOL);
            $stmt->bindParam(':acepta_privacidad', $pasajero->getAceptaPrivacidad(), PDO::PARAM_BOOL);
            $stmt->bindParam(':newsletter', $pasajero->getNewsletter(), PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM pasajero WHERE id_pasajero = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al eliminar: " . $e->getMessage();
            return false;
        }
    }
}

?>
