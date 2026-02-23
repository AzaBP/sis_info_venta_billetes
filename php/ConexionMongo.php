<?php

class ConexionMongo {
    private $uri = "mongodb://admin:admin@localhost:27017";
    private $database = "trenesDB";
    private $client;
    private $db;

    public function conectar() {
        try {
            $this->client = new MongoDB\Client($this->uri);
            $this->db = $this->client->selectDatabase($this->database);
            return $this->db;
        } catch (Exception $e) {
            echo "Error de conexión a MongoDB: " . $e->getMessage();
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

?>
