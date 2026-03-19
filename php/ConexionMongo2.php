<?php
// C:\...\php\ConexionMongo.php

require_once __DIR__ . '/../vendor/autoload.php'; 

class ConexionMongo {
    private $uri = "mongodb://admin:password123@trenes_mongo:27017";
    private $database = "trenes_mongo";
    private $client;
    private $db;

    public function conectar() {
    try {
        $this->client = new MongoDB\Client($this->uri);
        // Esto fuerza a que PHP intente hablar con el servidor REALMENTE
        $this->client->selectDatabase($this->database)->command(['ping' => 1]); 
        
        $this->db = $this->client->selectDatabase($this->database);
        return $this->db;
    } catch (Exception $e) {
        // IMPORTANTE: Esto te dirá el error real (ej: "Authentication failed")
        echo "⚠️ Error Técnico: " . $e->getMessage() . "<br>";
        return null;
    }
}

    public function getDb() { return $this->db; }
    public function getClient() { return $this->client; }

    public function getCollection($nombreColeccion) {
        if ($this->db) {
            return $this->db->selectCollection($nombreColeccion);
        }
        return null;
    }
}

// --- BLOQUE DE VERIFICACIÓN ---
// Este código solo se ejecuta si abres el archivo directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $test = new ConexionMongo();
    $db = $test->conectar();
    
    if ($db) {
        echo "✅ Conexión exitosa a MongoDB.<br>";
        echo "Base de datos seleccionada: <strong>" . $test->getDb()->getDatabaseName() . "</strong>";
    } else {
        echo "❌ Error: No se pudo conectar a MongoDB. Revisa si el servicio está activo y las credenciales son correctas.";
    }
}
?>
