<?php

require_once(__DIR__ . '/../VO/Billete.php');

class BilleteMongoDB {
    private $collection;
    private $mongo;

    public function __construct($uri = "mongodb://localhost:27017", $database = "trenesDB", $collection = "billetes") {
        try {
            $this->mongo = new MongoDB\Client($uri);
            $db = $this->mongo->selectDatabase($database);
            $this->collection = $db->selectCollection($collection);
        } catch (Exception $e) {
            echo "Error de conexión a MongoDB: " . $e->getMessage();
        }
    }

    // INSERTAR
    public function insertar(Billete $billete) {
        try {
            $resultado = $this->collection->insertOne($billete->toArray());
            return $resultado->getInsertedId();
        } catch (Exception $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $documento = $this->collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
            if ($documento) {
                return $this->documentoABillete($documento);
            }
            return null;
        } catch (Exception $e) {
            echo "Error al obtener: " . $e->getMessage();
            return null;
        }
    }

    // OBTENER TODOS
    public function obtenerTodos() {
        try {
            $documentos = $this->collection->find();
            $billetes = [];
            
            foreach ($documentos as $doc) {
                $billetes[] = $this->documentoABillete($doc);
            }
            return $billetes;
        } catch (Exception $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // OBTENER POR PASAJERO
    public function obtenerPorPasajero($id_pasajero) {
        try {
            $documentos = $this->collection->find(['id_pasajero' => $id_pasajero]);
            $billetes = [];
            
            foreach ($documentos as $doc) {
                $billetes[] = $this->documentoABillete($doc);
            }
            return $billetes;
        } catch (Exception $e) {
            echo "Error al obtener por pasajero: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar($id, Billete $billete) {
        try {
            $resultado = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                ['$set' => $billete->toArray()]
            );
            return $resultado->getModifiedCount() > 0;
        } catch (Exception $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $resultado = $this->collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
            return $resultado->getDeletedCount() > 0;
        } catch (Exception $e) {
            echo "Error al eliminar: " . $e->getMessage();
            return false;
        }
    }

    // CONVERTIR DOCUMENTO A OBJETO BILLETE
    private function documentoABillete($documento) {
        return new Billete(
            (string)$documento['_id'],
            $documento['id_pasajero'] ?? null,
            $documento['id_ruta'] ?? null,
            $documento['id_tren'] ?? null,
            $documento['id_asiento'] ?? null,
            $documento['fecha_viaje'] ?? '',
            $documento['precio_pagado'] ?? 0.0,
            $documento['metodo_pago'] ?? '',
            $documento['codigo_billete'] ?? '',
            $documento['estado'] ?? '',
            $documento['fecha_compra'] ?? ''
        );
    }
}

?>
