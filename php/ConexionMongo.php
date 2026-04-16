<?php
require_once __DIR__ . '/../vendor/autoload.php';

class ConexionMongo {
    //private $uri = "mongodb://admin:admin@localhost:27017";
    //conexion en docker
    private $uri = "mongodb://admin:admin@mongo:27017";
    private $database = "trenesDB";
    private $client;
    private $db;

    public function conectar() {
        try {
            $this->client = new MongoDB\Client($this->uri);
            $this->db = $this->client->selectDatabase($this->database);
            return $this->db;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getDb() {
        return $this->db;
    }

    public function getClient() {
        return $this->client;
    }

    public function getCollection($nombreColeccion) {
        if ($this->db) {
            return $this->db->selectCollection($nombreColeccion);
        }
        return null;
    }
}

// Mensaje de verificación solo si se accede directamente desde el navegador
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $conexion = new ConexionMongo();
    $db = $conexion->conectar();
    if ($db) {
        echo "<p>Conexión a MongoDB exitosa.</p>";
    } else {
        echo "<p>Error al conectar a MongoDB.</p>";
    }
}

?>
